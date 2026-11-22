<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/** Unduh file PDF/Excel yang dibuat asisten chat. Hanya pemilik percakapan yang bisa mengunduh. */
class ChatFileController extends Controller
{
    public function __invoke(ChatMessage $message)
    {
        $isFile = ($message->metadata['kind'] ?? null) === 'file' && $message->sender_type === 'bot';
        abort_unless($isFile && (int) $message->user_id === (int) Auth::id(), 404);
        abort_unless($message->file_path && Storage::disk('local')->exists($message->file_path), 410, 'File sudah tidak tersedia.');

        return Storage::disk('local')->download($message->file_path, $message->file_name, ['Content-Type' => $message->mime_type]);
    }
}
