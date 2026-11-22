<?php

namespace App\Support;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Client WhatsApp gateway (REST API v2).
 * Konfigurasi: config/services.php → whatsapp (WHATSAPP_API_URL, WHATSAPP_API_KEY, WHATSAPP_INSTANCE).
 */
class WhatsAppGateway
{
    /** state dari /instance/connectionState: open | connecting | close */
    public const STATE_LABELS = [
        'open' => 'Terhubung',
        'connecting' => 'Menunggu scan QR',
        'close' => 'Terputus',
        'missing' => 'Instance belum dibuat',
        'error' => 'Server tidak dapat dihubungi',
        'unconfigured' => 'Belum dikonfigurasi',
    ];

    public static function configured(): bool
    {
        return filled(config('services.whatsapp.url')) && filled(config('services.whatsapp.key'));
    }

    public static function instance(): string
    {
        return (string) config('services.whatsapp.instance', 'ams');
    }

    protected static function http(int $timeout = 20): PendingRequest
    {
        if (!self::configured()) {
            throw new RuntimeException('WhatsApp gateway belum dikonfigurasi (WHATSAPP_API_URL / WHATSAPP_API_KEY).');
        }

        return Http::baseUrl(config('services.whatsapp.url'))
            ->withHeaders(['apikey' => config('services.whatsapp.key')])
            ->withOptions(['verify' => config('services.whatsapp.verify_ssl', true)])
            ->acceptJson()
            ->timeout($timeout);
    }

    protected static function fail(string $action, Response $response): RuntimeException
    {
        $message = data_get($response->json(), 'response.message.0')
            ?? data_get($response->json(), 'response.message')
            ?? data_get($response->json(), 'message')
            ?? $response->body();
        if (is_array($message)) {
            $message = json_encode($message);
        }
        Log::warning("WhatsApp gateway {$action} gagal", ['status' => $response->status(), 'body' => $response->body()]);

        return new RuntimeException("{$action} gagal ({$response->status()}): " . mb_strimwidth((string) $message, 0, 200, '…'));
    }

    /** Info server (versi). */
    public static function server(): array
    {
        return self::http(10)->get('/')->json() ?? [];
    }

    /** Status koneksi: open | connecting | close | missing | error | unconfigured. */
    public static function state(): string
    {
        if (!self::configured()) {
            return 'unconfigured';
        }
        try {
            $response = self::http(10)->get('/instance/connectionState/' . self::instance());
            if ($response->status() === 404) {
                return 'missing';
            }

            return $response->successful() ? (string) data_get($response->json(), 'instance.state', 'close') : 'error';
        } catch (Throwable $e) {
            Log::warning('WhatsApp gateway state: ' . $e->getMessage());

            return 'error';
        }
    }

    /** Detail instance: nomor, nama profil, foto, dll. Null jika belum ada. */
    public static function info(): ?array
    {
        $response = self::http(10)->get('/instance/fetchInstances', ['instanceName' => self::instance()]);
        if (!$response->successful()) {
            return null;
        }
        $data = $response->json();

        return is_array($data) && isset($data[0]) ? $data[0] : null;
    }

    /** Buat instance baru (Baileys) bila belum ada. */
    public static function create(): void
    {
        $response = self::http(30)->post('/instance/create', [
            'instanceName' => self::instance(),
            'integration' => 'WHATSAPP-BAILEYS',
            'qrcode' => true,
        ]);
        if (!$response->successful()) {
            throw self::fail('Membuat instance', $response);
        }
    }

    /**
     * Minta QR untuk dihubungkan. Dengan $phone (62...), API juga memberi kode pairing
     * yang bisa dimasukkan di WhatsApp → Perangkat tertaut → Tautkan dengan nomor telepon.
     *
     * @return array{base64: ?string, code: ?string, pairingCode: ?string}
     */
    public static function connect(?string $phone = null): array
    {
        $response = self::http(30)->get('/instance/connect/' . self::instance(), array_filter(['number' => $phone]));
        if (!$response->successful()) {
            throw self::fail('Meminta QR', $response);
        }
        $data = $response->json() ?? [];

        return [
            'base64' => $data['base64'] ?? data_get($data, 'qrcode.base64'),
            'code' => $data['code'] ?? data_get($data, 'qrcode.code'),
            'pairingCode' => $data['pairingCode'] ?? data_get($data, 'qrcode.pairingCode'),
        ];
    }

    public static function logout(): void
    {
        $response = self::http()->delete('/instance/logout/' . self::instance());
        if (!$response->successful()) {
            throw self::fail('Logout', $response);
        }
    }

    public static function restart(): void
    {
        $response = self::http()->post('/instance/restart/' . self::instance());
        if (!$response->successful()) {
            throw self::fail('Restart', $response);
        }
    }

    /** Daftar grup WhatsApp: [['id' => ...@g.us, 'subject' => ..., 'size' => n], ...] */
    public static function groups(): array
    {
        $response = self::http(60)->get('/group/fetchAllGroups/' . self::instance(), ['getParticipants' => 'false']);
        if (!$response->successful()) {
            throw self::fail('Mengambil grup', $response);
        }

        return collect($response->json() ?? [])
            ->map(fn ($g) => [
                'id' => $g['id'] ?? '',
                'subject' => $g['subject'] ?? '(tanpa nama)',
                'size' => $g['size'] ?? null,
                'desc' => $g['desc'] ?? null,
            ])
            ->filter(fn ($g) => $g['id'] !== '')
            ->sortBy('subject', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /** Normalisasi tujuan: nomor → 62xxxx, JID grup (@g.us) dibiarkan. */
    public static function normalizeTarget(?string $target): string
    {
        $target = trim((string) $target);
        if ($target === '' || str_contains($target, '@')) {
            return $target;
        }

        return PhoneFormat($target);
    }

    /** Kirim pesan teks. Melempar exception jika gagal. */
    public static function sendText(string $target, string $text): array
    {
        $number = self::normalizeTarget($target);
        if ($number === '' || (!str_contains($number, '@') && strlen($number) < 9)) {
            throw new RuntimeException("Nomor WhatsApp tidak valid: '{$target}'.");
        }

        $response = self::http(30)->post('/message/sendText/' . self::instance(), [
            'number' => $number,
            'text' => $text,
        ]);
        if (!$response->successful()) {
            throw self::fail('Kirim pesan', $response);
        }

        return $response->json() ?? [];
    }

    /**
     * Status koneksi dengan cache singkat, supaya pengiriman massal tidak cek status berulang.
     */
    public static function isConnected(): bool
    {
        return Cache::remember('whatsapp:state', 30, fn () => self::state()) === 'open';
    }

    public static function forgetState(): void
    {
        Cache::forget('whatsapp:state');
    }
}
