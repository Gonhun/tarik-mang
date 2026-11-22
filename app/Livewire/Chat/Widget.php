<?php

namespace App\Livewire\Chat;

use Livewire\Component;
use App\Models\ChatMessage;
use App\Models\User; // Jika perlu
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads; // Untuk upload file
use Illuminate\Support\Str;    // Untuk session ID
use GeminiAPI\Laravel\Facades\Gemini;

class Widget extends Component
{use WithFileUploads;

    public $messages = [];
    public $newMessage = '';
    public $newFile; // Untuk file yang diupload
    public $sessionId;
    public $isOpen = false; // Untuk mengontrol visibilitas widget
    public $isLoadingResponse = false;

    public function mount()
    {
        // Buat atau dapatkan session ID unik untuk chat ini
        if (session()->has('chat_session_id')) {
            $this->sessionId = session('chat_session_id');
        } else {
            $this->sessionId = (string) Str::uuid();
            session(['chat_session_id' => $this->sessionId]);
        }
        $this->loadMessages();
    }

    public function toggleChat()
    {
        $this->isOpen = !$this->isOpen;
        if ($this->isOpen) {
            $this->loadMessages(); // Muat pesan saat dibuka
            $this->dispatch('chat-opened'); // Event untuk scroll
        }
    }

    public function loadMessages()
    {
        $this->messages = ChatMessage::where('session_id', $this->sessionId)
                            ->orderBy('created_at', 'asc')
                            ->get()
                            ->toArray(); // Ubah ke array agar mudah dimanipulasi Livewire
    }

    protected $rules = [
        'newMessage' => 'nullable|string|max:1000',
        'newFile' => 'nullable|image|max:2048', // Max 2MB, hanya gambar
    ];

    protected $messagesValidation = [
        'newFile.image' => 'File yang diupload harus berupa gambar.',
        'newFile.max' => 'Ukuran gambar maksimal 2MB.',
    ];

    public function updatedNewFile()
    {
        $this->validateOnly('newFile');
    }

    protected function getGeminiResponse(string $userInputText, ?string $userUploadedFilePath = null, ?string $userUploadedFileMimeType = null): string
    {
        $promptParts = [];

        // Selalu tambahkan teks pengguna jika ada
        if (!empty($userInputText)) {
            $promptParts[] = prepareGeminiTextPart($userInputText);
        }

        $modelToUse = 'gemini-1.5-flash-latest'; // Atau 'gemini-pro' jika tidak ada gambar

        if ($userUploadedFilePath && $userUploadedFileMimeType) {
            if (Storage::disk('public')->exists($userUploadedFilePath)) {
                $imageData = Storage::disk('public')->get($userUploadedFilePath);
                $promptParts[] = prepareGeminiImagePart($userUploadedFileMimeType, base64_encode($imageData));
                // Pastikan model yang digunakan mendukung vision jika ada gambar
                // model 'gemini-1.5-flash-latest' sudah mendukung multimodal.
                // Jika Anda ingin menggunakan model spesifik vision yang lebih lama seperti 'gemini-pro-vision', set di sini.
                // $modelToUse = 'gemini-pro-vision';
            } else {
                Log::error("Helper: File gambar tidak ditemukan di storage: " . $userUploadedFilePath);
                // Tambahkan informasi ke prompt bahwa gambar gagal diproses
                if(empty($promptParts)) $promptParts[] = prepareGeminiTextPart("Pengguna mencoba mengirim gambar tetapi gagal diproses.");
                else $promptParts[0]['text'] .= " (Catatan: Pengguna juga mencoba mengirim gambar tetapi gagal diproses.)";
            }
        }

        if (empty($promptParts)) {
            return "Mohon berikan input teks atau gambar.";
        }

        // Panggil helper global Anda
        $response = callGeminiApi($promptParts, $modelToUse);

        return $response ?? "Maaf, saya tidak dapat menghasilkan respons saat ini.";
    }

    public function sendMessage()
    {
        $this->validate();
        $userId = Auth::id();
        $userMessageData = null;
        $userMessageEloquent = null;

        $userTextPrompt = trim($this->newMessage); // Teks asli dari pengguna

        // 1. Simpan pesan pengguna (teks atau gambar) ke database
        if ($this->newFile) {
            // ... (logika penyimpanan file dan data pesan gambar) ...
            // Seperti yang sudah ada di contoh sebelumnya:
            $originalFileName = $this->newFile->getClientOriginalName();
            $mimeType = $this->newFile->getMimeType();
            $filePath = $this->newFile->store('chat_attachments', 'public');

            $userMessageData = [
                'session_id' => $this->sessionId,
                'user_id' => $userId,
                'sender_type' => 'user',
                'message_type' => 'image',
                'file_path' => $filePath,
                'file_name' => $originalFileName,
                'mime_type' => $mimeType,
                'content' => $userTextPrompt ?: null, // Caption untuk gambar
            ];
            $this->newFile = null;
            $this->dispatch('fileInputReset');

        } elseif (!empty($userTextPrompt)) {
            $userMessageData = [
                'session_id' => $this->sessionId,
                'user_id' => $userId,
                'sender_type' => 'user',
                'message_type' => 'text',
                'content' => $userTextPrompt,
            ];
        } else {
            return; // Tidak ada pesan teks atau file
        }

        if ($userMessageData) {
            $userMessageEloquent = ChatMessage::create($userMessageData);
            $this->messages[] = $userMessageEloquent->toArray();
        }

        // Reset input teks setelah digunakan (baik untuk caption atau pesan teks murni)
        $this->newMessage = '';
        $this->dispatch('message-sent');
        $this->isLoadingResponse = true;

        // 2. Dapatkan respons dari Gemini
        $botResponseText = "Maaf, saya tidak bisa merespons saat ini."; // Default
        $uploadedFilePath = null;
        $uploadedFileMimeType = null;

        try {
            $promptParts = []; // Array untuk menampung bagian-bagian prompt ke Gemini

            // Tambahkan teks dari pengguna ke promptParts jika ada
            if (!empty($userTextPrompt)) {
                $promptParts[] = $userTextPrompt;
            }

            // Cek apakah pesan yang BARU SAJA DIKIRIM adalah gambar
            if ($userMessageEloquent && $userMessageEloquent->message_type === 'image' && $userMessageEloquent->file_path) {
                $uploadedFilePath = $userMessageEloquent->file_path;
                $uploadedFileMimeType = $userMessageEloquent->mime_type;
            }
        
            // Dapatkan respons dari Gemini menggunakan helper
            $botResponseText = $this->getGeminiResponse($userTextPrompt, $uploadedFilePath, $uploadedFileMimeType);

        } catch (\Exception $e) {
            Log::error("Gemini Exception in ChatWidget sendMessage: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            $botResponseText = "Maaf, terjadi kesalahan pada layanan AI kami. Silakan coba beberapa saat lagi.";
            if (str_contains($e->getMessage(), 'API key not valid')) {
                $botResponseText = "Konfigurasi API Key layanan AI sepertinya bermasalah.";
            } elseif (str_contains(strtolower($e->getMessage()), 'quota') || (method_exists($e, 'getCode') && $e->getCode() == 429) ) {
                 $botResponseText = "Layanan AI sedang sibuk. Mohon coba lagi nanti.";
            }
        }

        // 3. Simpan respons bot dan update UI
        $botMessage = ChatMessage::create([
            'session_id' => $this->sessionId,
            'sender_type' => 'bot',
            'message_type' => 'text',
            'content' => $botResponseText,
            'metadata' => ['engine' => ($userMessageEloquent && $userMessageEloquent->message_type === 'image' ? 'gemini-vision' : 'gemini-pro')],
        ]);
        $this->messages[] = $botMessage->toArray();
        $this->isLoadingResponse = false;
        $this->dispatch('message-sent');
    }

    // Fungsi helper untuk memetakan MIME type string ke konstanta SDK (dari sebelumnya)
    private function mapMimeTypeToSdkConstant(string $mimeTypeString)
    {
        // ... (implementasi dari contoh sebelumnya, SESUAIKAN DENGAN PAKET ANDA) ...
        // Contoh:
        if (class_exists('\Gemini\Enums\MimeType')) { // Ganti dengan namespace yang benar
            switch (strtolower($mimeTypeString)) {
                case 'image/jpeg': return \Gemini\Enums\MimeType::IMAGE_JPEG;
                case 'image/png':  return \Gemini\Enums\MimeType::IMAGE_PNG;
                // ...
                default: return \Gemini\Enums\MimeType::IMAGE_JPEG; // Fallback
            }
        }
        Log::warning("Gemini MimeType Enum class not found as expected. Returning raw MIME type: " . $mimeTypeString);
        return $mimeTypeString; // Mungkin tidak ideal, tergantung SDK
    }

    // Fungsi helper untuk mengekstrak teks dari hasil Gemini (SESUAIKAN DENGAN PAKET ANDA)
    private function extractTextFromResult($result): string
    {
        if (method_exists($result, 'text')) {
            return $result->text();
        } elseif (isset($result->candidates[0]->content->parts[0]->text)) {
            return $result->candidates[0]->content->parts[0]->text;
        }
        Log::warning("Tidak dapat mengekstrak teks dari hasil Gemini: " . print_r($result, true));
        return "Tidak dapat memproses respons dari AI.";
    }
    
    public function render()
    {
        return view('livewire.chat.widget');
    }
}
