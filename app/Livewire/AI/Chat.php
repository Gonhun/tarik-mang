<?php
namespace App\Livewire\AI;

use App\Models\AiChatFiles;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Chat as AIChat;
use App\Models\AiChatMessage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class Chat extends Component
{
    use WithFileUploads;

    public $chatId;
    public $message = '';
    public $messages = [];
    public $file;
    public $loading = false;
    public $chatHistory ;

    protected $listeners = ['refreshMessages' => 'loadMessages'];

    public function mount($chat = null)
    {
        if ($chat) {
            $this->chatId = $chat;
        } else {
            $c = AIChat::create(['user_id' => Auth::id(), 'title' => 'Chat '.now()->format('YmdHis')]);
            $this->chatId = $c->id;
        }
        $this->chatHistory = AIChat::get();
        $this->loadMessages();
    }

    public function loadMessages()
    {
        $this->messages = AiChatMessage::where('chat_id', $this->chatId)
            ->orderBy('id')->get()->toArray();

        $this->dispatch('scrollToBottom');
    }

    public function send()
    {
        if (trim($this->message) === '' && ! $this->file) return;

        $this->loading = true;

        // 1) Save user message
        AiChatMessage::create([
            'chat_id' => $this->chatId,
            'role' => 'user',
            'content' => $this->message,
        ]);

        // 2) Save file locally if uploaded
        $uploaded = null;
        if ($this->file) {
            $path = $this->file->store('chat_files');
            $uploaded = AiChatFiles::create([
                'chat_id' => $this->chatId,
                'file_name' => $this->file->getClientOriginalName(),
                'file_path' => $path,
                'mime_type' => $this->file->getClientMimeType(),
            ]);
        }

        // clear input
        $this->message = '';
        $this->file = null;
        $this->loadMessages();

        // 3) Build payload for n8n (send minimal history or last N)
        $history = AiChatMessage::where('chat_id', $this->chatId)->orderBy('id','desc')->limit(20)->get()->reverse()->map(function($m){
            return ['role'=>$m->role, 'content'=>$m->content];
        })->values()->toArray();

        $payload = [
            'chat_id' => $this->chatId,
            'user_id' => Auth::id(),
            'message' => end($history)['content'] ?? '',
            'history' => $history,
        ];
       
        $webhook = env('N8N_WEBHOOK_URL');

        try {
            if ($uploaded) {
                $response = Http::withoutVerifying()->withHeaders([
                    'AIBAGUS' => env('N8N_SECRET_TOKEN') ?? ''
                ])->attach(
                    'file',
                    file_get_contents(storage_path('app/'.$uploaded->file_path)),
                    $uploaded->file_name
                )->post($webhook, $payload);
            } else {
                $response = Http::withoutVerifying()->withHeaders([
                    'AIBAGUS' => env('N8N_SECRET_TOKEN') ?? ''
                ])->post($webhook, $payload);
            }
            $data = $response->json();
           

// --- 1. Pengecekan Kunci 'message' ---
            if (!isset($data['message'])) {
                // Tangani jika kunci 'message' tidak ada dalam respons JSON
                $errorMessage = 'Respons server tidak memiliki kunci "message".';
                // Catat pesan error ini ke chat atau log
                AiChatMessage::create([
                    'chat_id' => $this->chatId,
                    'role' => 'assistant',
                    'content' => $errorMessage,
                    'meta' => null
                ]);
                $this->loadMessages();
                return;
            }

            $allAssets = $data['message'];
            if($data['status']){
                if (!is_array($allAssets) && !($allAssets instanceof \Traversable)) {
                    // Tangani jika 'message' BUKAN array (misalnya, string)
                    $errorMessage = "Gagal memproses data aset. Server mengembalikan pesan error: '{$allAssets}'";
                    // Catat pesan error ini ke chat
                    AiChatMessage::create([
                        'chat_id' => $this->chatId,
                        'role' => 'assistant',
                        'content' => $errorMessage,
                        'meta' => null
                    ]);
                    $this->loadMessages();
                    return;
                }
                $responseMessage = "💾 Ditemukan <strong>" . count($allAssets) . "</strong> Data:\n\n";
                foreach ($allAssets as $assetData) {
                    // Pastikan $assetData adalah array (untuk menangani kasus 9 data)
                    if (!is_array($assetData)) {
                        continue; // Lewati jika data aset individual tidak berbentuk array
                    }

                    $responseMessage .= "---\n"; 
                    
                    // Looping untuk detail SATU aset
                    foreach ($assetData as $key => $value) {
                        $tempKey = preg_replace('/(?<!^)([A-Z])/', ' $1', $key);

                        // Langkah 2: Ubah semua menjadi huruf kecil untuk konsistensi (opsional, tapi disarankan)
                        $lowerKey = strtolower($tempKey);

                        // Langkah 3: Ganti semua underscore (_) dengan spasi
                        $spacedKey = str_replace('_', ' ', $lowerKey);

                        // Langkah 4: Terapkan Title Case (kapital di awal setiap kata)
                        $cleanKey = ucwords($spacedKey);
                        
                        if ($key === 'created_at' && $value) {
                            $value = \Carbon\Carbon::parse($value)->format('d M Y H:i:s');
                        }
                        
                        $responseMessage .= "{$cleanKey}: {$value}\n";
                    }
                    $responseMessage .= "\n";
                }

                // Simpan pesan yang berhasil dibuat
                AiChatMessage::create([
                    'chat_id' => $this->chatId,
                    'role' => 'assistant',
                    'content' => $responseMessage,
                    'meta' => null
                ]);
                $this->loadMessages();
            }else{
                $responseMessage = $allAssets;
                AiChatMessage::create([
                    'chat_id' => $this->chatId,
                    'role' => 'assistant',
                    'content' => $responseMessage,
                    'meta' => null
                ]);
                $this->loadMessages();
            }
            // --- 2. Pengecekan Tipe Data ---
            

            // Jika lolos kedua pengecekan, lanjutkan dengan looping
            
            // if sync response contains messages, save immediately
            

        } catch (\Exception $e) {
            AiChatMessage::create([
                'chat_id' => $this->chatId,
                'role' => 'assistant',
                'content' => 'Gagal menghubungi agent: '.$e->getMessage()
            ]);
            $this->loadMessages();
        }

        $this->loading = false;
    }

    public function render()
    {
        return view('livewire.a-i.chat');
    }
}
