<?php

namespace App\Support\Assistant;

use Illuminate\Support\Str;

/**
 * Penjaga hal sensitif untuk asisten chat:
 *  1. Pertanyaan tentang password, .env, API key, token, konfigurasi server, dll. ditolak
 *     SEBELUM dikirim ke AI.
 *  2. Jawaban AI disaring: nilai rahasia dari konfigurasi aplikasi tidak pernah ditampilkan.
 */
class SensitiveGuard
{
    public const REFUSAL = 'Maaf, saya tidak bisa membantu soal password, kredensial, API key/token, isi file .env, '
        . 'atau konfigurasi server. Jika lupa password AMS, hubungi tim IT untuk reset; setelah itu Anda bisa membuat password baru sendiri saat login dengan NIK.';

    /** Pola pertanyaan sensitif (huruf kecil). */
    private const PATTERNS = [
        '/\bpass(word|wd|kode)?\b/', '/kata\s*sandi/', '/\bpin\b/', '/\botp\b/',
        '/\.env\b/', '/\benv(ironment)?\b/', '/\bapp_key\b/', '/\bdb_[a-z_]+/', '/\bmail_[a-z_]+/',
        '/api[\s_-]*key/', '/\bapikey\b/', '/\btoken\b/', '/\bsecret\b/', '/\brahasia\b/', '/\bkredensial\b/', '/\bcredential/',
        '/private[\s_-]*key/', '/\bssh\b/', '/\bsmtp\b/', '/connection\s*string/', '/\bhash\b/',
        '/konfigurasi\s+(server|database|db|aplikasi|sistem|env)/', '/config(uration)?\s+(server|database|db|app|env)/',
        '/(username|user)\s+(dan|&|and)\s+pass/', '/login\s+(sebagai|as)\s+(admin|orang lain)/',
        '/(akses|masuk)\s+(ke\s+)?(database|server|db)\b/', '/\bip\s+(server|database|db)\b/',
        '/(ignore|abaikan)\s+(previous|semua|instruksi|aturan)/', '/system\s*prompt/', '/prompt\s+(sistem|awal)/',
    ];

    public static function isSensitiveQuestion(string $text): bool
    {
        $t = Str::lower($text);
        foreach (self::PATTERNS as $p) {
            if (preg_match($p, $t)) {
                return true;
            }
        }

        return false;
    }

    /** Nilai rahasia yang tidak boleh pernah muncul di jawaban. */
    protected static function secrets(): array
    {
        $values = [
            config('app.key'),
            config('database.connections.sqlsrv.password'),
            config('database.connections.sqlsrv.username'),
            config('database.connections.sqlsrv.host'),
            config('database.connections.sqlsrv_hrd.password'),
            config('database.connections.sqlsrv_hrd.host'),
            config('mail.mailers.smtp.password'),
            config('mail.mailers.smtp2.password'),
            config('services.whatsapp.key'),
            config('services.ai.key'),
            config('services.fortigate.token'),
            config('services.n8n.secret') ?? env('N8N_SECRET_TOKEN'),
            env('GEMINI_API_KEY'),
        ];

        return array_values(array_filter(array_map('strval', $values), fn ($v) => strlen($v) >= 6));
    }

    /** Sensor nilai rahasia dan pola kunci umum di teks keluaran. */
    public static function redact(string $text): string
    {
        foreach (self::secrets() as $secret) {
            $text = str_replace($secret, '[disensor]', $text);
        }
        // Pola kunci/token umum (base64:..., sk-..., Bearer ..., blok private key).
        $text = preg_replace('/base64:[A-Za-z0-9+\/=]{20,}/', '[disensor]', $text);
        $text = preg_replace('/\b(sk|pk|rk)-[A-Za-z0-9_-]{16,}/', '[disensor]', $text);
        $text = preg_replace('/Bearer\s+[A-Za-z0-9._-]{16,}/i', 'Bearer [disensor]', $text);
        $text = preg_replace('/-----BEGIN [A-Z ]*PRIVATE KEY-----.*?-----END [A-Z ]*PRIVATE KEY-----/s', '[disensor]', $text);

        return $text;
    }
}
