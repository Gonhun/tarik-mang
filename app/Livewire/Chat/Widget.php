<?php

namespace App\Livewire\Chat;

use App\Models\ChatMessage;
use App\Support\Assistant\Assistant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use RuntimeException;

/**
 * Widget "Asisten AMS" (9Router). Jawaban sesuai role user; bisa mengirim file PDF/Excel.
 */
class Widget extends Component
{
    use WithFileUploads;

    public $messages = [];
    public $newMessage = '';
    public $newFile;
    #[Locked] public $sessionId;
    public $isOpen = false;
    public $isLoadingResponse = false;

    /** Pesan per menit per user. */
    private const RATE_LIMIT = 15;

    public function mount()
    {
        // ID sesi chat per user: session browser tetap terbawa setelah login ulang,
        // jadi tanpa ini user berikutnya di browser yang sama bisa melihat riwayat chat user sebelumnya.
        $key = 'chat_session_id_' . Auth::id();
        $this->sessionId = session($key) ?: tap((string) Str::uuid(), fn ($id) => session([$key => $id]));
    }

    public function toggleChat()
    {
        $this->isOpen = !$this->isOpen;
        if ($this->isOpen) {
            $this->loadMessages();
            $this->dispatch('chat-opened');
        }
    }

    protected function myMessages()
    {
        return ChatMessage::where('session_id', $this->sessionId)
            ->where(fn ($q) => $q->where('user_id', Auth::id())->orWhereNull('user_id'));
    }

    public function loadMessages()
    {
        $this->messages = $this->myMessages()->orderBy('created_at')->orderBy('id')->limit(200)->get()->toArray();
    }

    /** Mulai percakapan baru (riwayat lama tetap tersimpan di database). */
    public function newConversation()
    {
        $this->sessionId = (string) Str::uuid();
        session(['chat_session_id_' . Auth::id() => $this->sessionId]);
        $this->messages = [];
    }

    protected $rules = [
        'newMessage' => 'nullable|string|max:1000',
        'newFile' => 'nullable|image|max:2048',
    ];

    private const VALIDATION_MESSAGES = [
        'newFile.image' => 'File yang diupload harus berupa gambar.',
        'newFile.max' => 'Ukuran gambar maksimal 2MB.',
        'newMessage.max' => 'Pesan maksimal 1000 karakter.',
    ];

    public function updatedNewFile()
    {
        $this->validateOnly('newFile', null, self::VALIDATION_MESSAGES);
    }

    /** Kirim contoh pertanyaan dari tombol saran. */
    public function ask(string $text)
    {
        $this->newMessage = Str::limit($text, 200, '');
        $this->sendMessage();
    }

    public function sendMessage()
    {
        $this->validate(null, self::VALIDATION_MESSAGES);
        $userId = Auth::id();
        $text = trim((string) $this->newMessage);
        if ($text === '' && !$this->newFile) {
            return;
        }

        $limitKey = 'assistant:' . $userId;
        if (RateLimiter::tooManyAttempts($limitKey, self::RATE_LIMIT)) {
            $this->addError('newMessage', 'Terlalu banyak pesan. Coba lagi dalam ' . RateLimiter::availableIn($limitKey) . ' detik.');
            return;
        }
        RateLimiter::hit($limitKey, 60);

        // Riwayat untuk konteks (sebelum pesan baru disimpan): 10 pesan teks terakhir.
        $history = $this->myMessages()->where('message_type', 'text')->whereNotNull('content')
            ->orderByDesc('id')->limit(10)->get(['sender_type', 'content'])->reverse()
            ->map(fn ($m) => ['role' => $m->sender_type === 'user' ? 'user' : 'assistant', 'content' => (string) $m->content])
            ->values()->all();

        // 1. Simpan pesan user
        $image = null;
        if ($this->newFile) {
            $path = $this->newFile->store('chat_attachments', 'public');
            $mime = $this->newFile->getMimeType();
            $userMessage = ChatMessage::create([
                'session_id' => $this->sessionId, 'user_id' => $userId, 'sender_type' => 'user', 'message_type' => 'image',
                'file_path' => $path, 'file_name' => $this->newFile->getClientOriginalName(), 'mime_type' => $mime, 'content' => $text ?: null,
            ]);
            $image = ['data_url' => 'data:' . $mime . ';base64,' . base64_encode(Storage::disk('public')->get($path))];
            $this->newFile = null;
            $this->dispatch('fileInputReset');
        } else {
            $userMessage = ChatMessage::create([
                'session_id' => $this->sessionId, 'user_id' => $userId, 'sender_type' => 'user', 'message_type' => 'text', 'content' => $text,
            ]);
        }
        $this->messages[] = $userMessage->toArray();
        $this->newMessage = '';
        $this->dispatch('message-sent');

        // 2. Minta jawaban asisten
        $files = [];
        try {
            $result = (new Assistant(Auth::user(), $this->sessionId))->reply($text, $history, $image);
            $reply = $result['text'];
            $files = $result['files'];
        } catch (RuntimeException $e) {
            $reply = 'Maaf, ' . lcfirst($e->getMessage()) . ' Silakan coba beberapa saat lagi.';
        } catch (\Throwable $e) {
            Log::error('Asisten chat gagal: ' . $e->getMessage());
            $reply = 'Maaf, terjadi kesalahan pada asisten. Silakan coba lagi.';
        }

        // 3. Simpan jawaban + file
        $bot = ChatMessage::create([
            'session_id' => $this->sessionId, 'user_id' => $userId, 'sender_type' => 'bot', 'message_type' => 'text',
            'content' => $reply, 'metadata' => ['engine' => '9router', 'model' => config('services.ai.model')],
        ]);
        $this->messages[] = $bot->toArray();
        foreach ($files as $file) {
            $this->messages[] = ChatMessage::create([
                // message_type dibatasi CHECK constraint (text|image), jadi file ditandai lewat metadata.kind.
                'session_id' => $this->sessionId, 'user_id' => $userId, 'sender_type' => 'bot', 'message_type' => 'text',
                'file_path' => $file['path'], 'file_name' => $file['name'], 'mime_type' => $file['mime'],
                'metadata' => ['kind' => 'file', 'size' => $file['size']],
            ])->toArray();
        }

        $this->isLoadingResponse = false;
        $this->dispatch('message-sent');
    }

    public function render()
    {
        return view('livewire.chat.widget', [
            'suggestions' => $this->suggestions(),
        ]);
    }

    /** Contoh pertanyaan sesuai akses user. */
    protected function suggestions(): array
    {
        $u = Auth::user();
        $s = ['Aset apa saja yang saya pegang?', 'Ada yang menunggu persetujuan saya?'];
        if (!$u) {
            return $s;
        }
        if ($u->canAny(['Master Asset', 'Pemegang Aset'])) $s[] = 'Total laptop berapa, apa saja mereknya?';
        if ($u->canAny(['Mutasi', 'Master Asset'])) $s[] = 'Pengajuan disposal yang belum divalidasi, siapa yang ditunggu?';
        if ($u->can('Add Workflow') || $u->hasRole(['Administrator', 'Administrator IT'])) $s[] = 'Siapa approver yang paling banyak menahan persetujuan?';
        if ($u->can('Pemegang Aset')) $s[] = '10 pemegang aset terbanyak, jadikan Excel';
        if ($u->can('Kerusakan Aset')) $s[] = 'Laporan kerusakan yang masih terbuka per jobsite';
        if ($u->can('Peminjaman')) $s[] = 'Peminjaman yang terlambat';

        $s = array_slice($s, 0, 5);
        return $s;
    }
}
