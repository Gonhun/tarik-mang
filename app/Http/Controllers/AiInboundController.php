<?php
namespace App\Http\Controllers;

use App\Models\AiChatMessage;

use Illuminate\Http\Request;


class AiInboundController extends Controller
{
    public function receive(Request $req)
    {
        // optional: verify token
        $secret = config('services.n8n.secret_token');
        if (empty($secret) || !hash_equals((string) $secret, (string) $req->header('x-n8n-token'))) {
            return response()->json(['error'=>'unauthorized'], 401);
        }

        $payload = $req->all();
        $chatId = $payload['chat_id'] ?? null;
        if (! $chatId) return response()->json(['error'=>'no chat_id'], 400);

        // Save messages array if provided
        if (!empty($payload['messages']) && is_array($payload['messages'])) {
            foreach ($payload['messages'] as $m) {
                AiChatMessage::create([
                    'chat_id' => $chatId,
                    'role' => $m['role'] ?? 'assistant',
                    'content' => $m['content'] ?? '',
                    'meta' => $m['meta'] ?? null
                ]);
            }
        }

        // Optionally handle attachments in payload (urls)
        // return status
        return response()->json(['status'=>'ok']);
    }
}
