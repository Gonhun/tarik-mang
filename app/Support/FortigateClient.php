<?php

namespace App\Support;

use App\Models\FortigateDevice;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Client REST API FortiOS (…/api/v2) untuk banyak perangkat.
 */
class FortigateClient
{
    public const CONNECT_TIMEOUT = 4;
    public const TIMEOUT = 15;

    public function __construct(protected FortigateDevice $device)
    {
    }

    public static function for(FortigateDevice $device): self
    {
        return new self($device);
    }

    protected static function prepare(PendingRequest $http, FortigateDevice $device, int $timeout = self::TIMEOUT): PendingRequest
    {
        return $http->withToken($device->api_token)
            ->withOptions(['verify' => $device->verify_ssl])
            ->acceptJson()
            ->connectTimeout(self::CONNECT_TIMEOUT)
            ->timeout($timeout);
    }

    protected function query(array $query = []): array
    {
        return array_filter($query + ['vdom' => $this->device->vdom], fn ($v) => $v !== null && $v !== '');
    }

    /** GET ke path relatif (mis. "monitor/system/interface"). */
    public function get(string $path, array $query = [], int $timeout = self::TIMEOUT): array
    {
        $path = ltrim($path, '/');
        if (!preg_match('#^[A-Za-z0-9_\-/.]+$#', $path) || str_contains($path, '..')) {
            throw new RuntimeException('Path API tidak valid.');
        }

        try {
            $response = self::prepare(Http::baseUrl($this->device->apiUrl()), $this->device, $timeout)
                ->get('/' . $path, $this->query($query));
        } catch (Throwable $e) {
            throw new RuntimeException('Tidak dapat terhubung ke ' . $this->device->name . ': ' . self::shortError($e));
        }

        return self::decode($response, $this->device);
    }

    protected static function decode(Response $response, FortigateDevice $device): array
    {
        if (in_array($response->status(), [401, 403], true)) {
            throw new RuntimeException("API token {$device->name} ditolak ({$response->status()}). Periksa token dan trusted host di Fortigate.");
        }
        if (!$response->successful()) {
            throw new RuntimeException("{$device->name} membalas status {$response->status()}.");
        }

        return $response->json() ?? [];
    }

    public static function shortError(Throwable $e): string
    {
        $m = $e->getMessage();
        return match (true) {
            str_contains($m, 'timed out'), str_contains($m, 'Timeout') => 'waktu koneksi habis',
            str_contains($m, 'Could not resolve') => 'host tidak ditemukan',
            str_contains($m, 'Connection refused') => 'koneksi ditolak',
            str_contains($m, 'SSL') => 'masalah sertifikat SSL (matikan "verifikasi SSL" untuk sertifikat self-signed)',
            default => mb_strimwidth($m, 0, 160, '…'),
        };
    }

    /** Daftar interface fisik + trafik. */
    public function interfaces(): array
    {
        $data = $this->get('monitor/system/interface');

        return collect($data['results'] ?? [])
            ->map(fn ($p, $key) => [
                'name' => $p['name'] ?? $key,
                'alias' => $p['alias'] ?? '',
                'ip' => isset($p['ip']) && $p['ip'] !== '0.0.0.0' ? $p['ip'] . (isset($p['mask']) ? '/' . $p['mask'] : '') : null,
                'link' => (bool) ($p['link'] ?? false),
                'speed' => $p['speed'] ?? null,
                'duplex' => $p['duplex'] ?? null,
                'rx' => (int) ($p['rx_bytes'] ?? 0),
                'tx' => (int) ($p['tx_bytes'] ?? 0),
                'rx_errors' => (int) ($p['rx_errors'] ?? 0),
                'tx_errors' => (int) ($p['tx_errors'] ?? 0),
            ])
            ->sortBy('name', SORT_NATURAL)
            ->values()
            ->all();
    }

    /** Ringkasan: identitas perangkat + pemakaian CPU/memori/sesi. */
    public function summary(): array
    {
        $s = $this->get('monitor/system/status', [], 10);
        try {
            $r = $this->get('monitor/system/resource/usage', ['interval' => '1-min'], 10)['results'] ?? [];
        } catch (Throwable) {
            $r = [];
        }

        return [
            'hostname' => data_get($s, 'results.hostname'),
            'model' => trim(data_get($s, 'results.model_name') . ' ' . data_get($s, 'results.model_number')),
            'serial' => $s['serial'] ?? null,
            'version' => $s['version'] ?? null,
            'build' => $s['build'] ?? null,
            'vdom' => $s['vdom'] ?? null,
            'cpu' => data_get($r, 'cpu.0.current'),
            'mem' => data_get($r, 'mem.0.current'),
            'disk' => data_get($r, 'disk.0.current'),
            'sessions' => data_get($r, 'session.0.current'),
            'setup_rate' => data_get($r, 'setuprate.0.current'),
        ];
    }

    /** Batasi jumlah baris agar halaman tetap ringan. */
    public const MAX_ROWS = 1000;

    protected static function limit(array $rows): array
    {
        return array_slice($rows, 0, self::MAX_ROWS);
    }

    /** Klien DHCP yang mendapat IP. */
    public function dhcpLeases(): array
    {
        $rows = collect($this->get('monitor/system/dhcp')['results'] ?? [])
            ->map(fn ($l) => [
                'ip' => $l['ip'] ?? '-',
                'mac' => $l['mac'] ?? '-',
                'hostname' => $l['hostname'] ?? '',
                'interface' => $l['interface'] ?? '',
                'status' => $l['status'] ?? '',
                'expire' => isset($l['expire_time']) && is_numeric($l['expire_time']) ? date('d M Y H:i', (int) $l['expire_time']) : '',
            ])
            ->sortBy('ip', SORT_NATURAL)->values()->all();

        return self::limit($rows);
    }

    /** Tabel ARP (perangkat yang terlihat di jaringan). */
    public function arp(): array
    {
        $rows = collect($this->get('monitor/network/arp')['results'] ?? [])
            ->map(fn ($a) => [
                'ip' => $a['ip'] ?? '-',
                'mac' => $a['mac'] ?? '-',
                'interface' => $a['interface'] ?? '',
                'age' => isset($a['age']) ? $a['age'] . ' dtk' : '',
            ])
            ->sortBy('ip', SORT_NATURAL)->values()->all();

        return self::limit($rows);
    }

    /** Tabel routing IPv4 aktif. */
    public function routes(): array
    {
        $rows = collect($this->get('monitor/router/ipv4')['results'] ?? [])
            ->map(fn ($r) => [
                'destination' => $r['ip_mask'] ?? (($r['ip'] ?? '') . '/' . ($r['mask'] ?? '')),
                'gateway' => $r['gateway'] ?? '-',
                'interface' => $r['interface'] ?? '',
                'type' => $r['type'] ?? '',
                'distance' => $r['distance'] ?? '',
                'metric' => $r['metric'] ?? '',
            ])
            ->values()->all();

        return self::limit($rows);
    }

    /** Tunnel VPN IPsec beserta status phase 2. */
    public function ipsec(): array
    {
        return collect($this->get('monitor/vpn/ipsec')['results'] ?? [])
            ->map(function ($t) {
                $proxies = collect($t['proxyid'] ?? []);
                $up = $proxies->where('status', 'up')->count();

                return [
                    'name' => $t['name'] ?? '-',
                    'remote' => $t['rgwy'] ?? '-',
                    'status' => $proxies->isEmpty() ? 'down' : ($up === $proxies->count() ? 'up' : ($up > 0 ? 'sebagian' : 'down')),
                    'phase2' => $up . '/' . $proxies->count(),
                    'rx' => (int) $proxies->sum('incoming_bytes'),
                    'tx' => (int) $proxies->sum('outgoing_bytes'),
                ];
            })
            ->sortBy('name', SORT_NATURAL)->values()->all();
    }

    /** Health check SD-WAN per link. */
    public function sdwan(): array
    {
        $rows = [];
        foreach ($this->get('monitor/virtual-wan/health-check')['results'] ?? [] as $check => $members) {
            foreach ((array) $members as $iface => $m) {
                $rows[] = [
                    'check' => $check,
                    'interface' => $iface,
                    'status' => $m['status'] ?? '-',
                    'latency' => isset($m['latency']) ? round($m['latency'], 1) . ' ms' : '-',
                    'jitter' => isset($m['jitter']) ? round($m['jitter'], 1) . ' ms' : '-',
                    'loss' => isset($m['packet_loss']) ? round($m['packet_loss'], 1) . '%' : '-',
                ];
            }
        }

        return $rows;
    }

    /** Ambil nilai pertama yang terisi dari beberapa kemungkinan nama field (beda versi FortiOS). */
    protected static function pick(array $row, array $keys, $default = null)
    {
        foreach ($keys as $k) {
            $v = data_get($row, $k);
            if ($v !== null && $v !== '') {
                return $v;
            }
        }

        return $default;
    }

    /** Ambil daftar baris dari respons (results / results.details). */
    protected static function rowsFrom(array $json): array
    {
        $rows = data_get($json, 'results.details') ?? data_get($json, 'results') ?? [];
        if (!is_array($rows)) {
            return [];
        }
        if (!array_is_list($rows)) {
            $rows = array_values($rows);
        }

        return array_values(array_filter($rows, 'is_array'));
    }

    /**
     * FortiView: statistik teratas. $period: realtime | hour | day | week.
     * Data historis (hour/day/week) butuh log tersimpan di disk Fortigate.
     */
    public function fortiview(string $reportBy, string $period = 'realtime', int $count = 50): array
    {
        $q = ['report_by' => $reportBy, 'sort_by' => 'bytes', 'count' => $count];
        $q += $period === 'realtime' ? ['realtime' => 'true'] : ['time_period' => $period, 'device' => 'disk'];

        return self::rowsFrom($this->get('monitor/fortiview/statistics', $q, 30));
    }

    protected static function bytesOf(array $r): int
    {
        $sent = (int) self::pick($r, ['sentbyte', 'sent_bytes', 'tx_bytes'], 0);
        $rcvd = (int) self::pick($r, ['rcvdbyte', 'rcvd_bytes', 'rx_bytes'], 0);
        $total = (int) self::pick($r, ['bytes', 'traffic', 'total_bytes', 'bandwidth'], 0);

        return $total ?: $sent + $rcvd;
    }

    /** Pemakaian bandwidth per user / IP. */
    public function bandwidthPerUser(string $period = 'realtime'): array
    {
        return collect($this->fortiview('source', $period, 100))
            ->map(fn ($r) => [
                'user' => (string) self::pick($r, ['user', 'username', 'unauthuser', 'srcname'], ''),
                'ip' => (string) self::pick($r, ['srcip', 'source', 'ip', 'srcaddr'], '-'),
                'device' => (string) self::pick($r, ['hostname', 'srchost', 'devname', 'osname', 'srcmac', 'mac'], ''),
                'download' => (int) self::pick($r, ['rcvdbyte', 'rcvd_bytes', 'rx_bytes'], 0),
                'upload' => (int) self::pick($r, ['sentbyte', 'sent_bytes', 'tx_bytes'], 0),
                'bytes' => self::bytesOf($r),
                'sessions' => (int) self::pick($r, ['sessions', 'session_count', 'count'], 0),
            ])
            ->sortByDesc('bytes')->values()->all();
    }

    /** Website yang paling sering dibuka. */
    public function topWebsites(string $period = 'realtime'): array
    {
        try {
            $rows = $this->fortiview('website', $period, 100);
        } catch (RuntimeException) {
            $rows = $this->fortiview('domain', $period, 100);
        }

        return collect($rows)
            ->map(fn ($r) => [
                'domain' => (string) self::pick($r, ['website', 'domain', 'hostname', 'host', 'dstname'], '-'),
                'category' => (string) self::pick($r, ['catdesc', 'category_name', 'category'], ''),
                'sessions' => (int) self::pick($r, ['sessions', 'hits', 'count', 'session_count'], 0),
                'bytes' => self::bytesOf($r),
            ])
            ->sortByDesc('bytes')->values()->all();
    }

    /** Aplikasi dengan trafik terbesar. */
    public function topApplications(string $period = 'realtime'): array
    {
        return collect($this->fortiview('application', $period, 100))
            ->map(fn ($r) => [
                'app' => (string) self::pick($r, ['app_name', 'appname', 'application', 'app'], '-'),
                'category' => (string) self::pick($r, ['appcat', 'app_cat', 'category'], ''),
                'risk' => (string) self::pick($r, ['apprisk', 'risk'], ''),
                'sessions' => (int) self::pick($r, ['sessions', 'session_count', 'count'], 0),
                'bytes' => self::bytesOf($r),
            ])
            ->sortByDesc('bytes')->values()->all();
    }

    /**
     * Log terbaru dari Fortigate. Dicoba dari disk, lalu memory.
     * $type: virus | webfilter | ips | app-ctrl | ...
     */
    public function logs(string $type, int $rows = 200): array
    {
        $lastError = null;
        foreach (['disk', 'memory'] as $device) {
            try {
                $json = $this->get("log/{$device}/{$type}", ['rows' => $rows], 30);
                if (isset($json['results']) && is_array($json['results'])) {
                    return array_values(array_filter($json['results'], 'is_array'));
                }
            } catch (RuntimeException $e) {
                $lastError = $e;
                if (str_contains($e->getMessage(), 'token') || str_contains($e->getMessage(), 'terhubung')) {
                    throw $e; // masalah koneksi / token: tidak perlu coba lokasi log lain
                }
            }
        }
        if ($lastError) {
            throw new RuntimeException('Log tidak tersedia. Pastikan logging ' . $type . ' aktif (disk/memory) dan profil keamanan dipasang di policy. ' . $lastError->getMessage());
        }

        return [];
    }

    protected static function logTime(array $r): string
    {
        $date = self::pick($r, ['date'], '');
        $time = self::pick($r, ['time'], '');
        if ($date || $time) {
            return trim("{$date} {$time}");
        }
        $ts = self::pick($r, ['eventtime', '_metadata.timestamp'], null);
        if (is_numeric($ts)) {
            $ts = (int) $ts;
            $ts = $ts > 1e15 ? intdiv($ts, 1000000000) : ($ts > 1e12 ? intdiv($ts, 1000) : $ts);
            return date('Y-m-d H:i:s', $ts);
        }

        return '';
    }

    protected static function logUser(array $r): string
    {
        return (string) self::pick($r, ['user', 'unauthuser', 'srcname', 'srcip'], '-');
    }

    /** Virus / malware yang terdeteksi. */
    public function virusLogs(): array
    {
        return collect($this->logs('virus'))
            ->map(fn ($r) => [
                'time' => self::logTime($r),
                'user' => self::logUser($r),
                'ip' => (string) self::pick($r, ['srcip'], ''),
                'virus' => (string) self::pick($r, ['virus', 'attack', 'ref'], '-'),
                'file' => (string) self::pick($r, ['filename', 'url', 'hostname'], ''),
                'action' => (string) self::pick($r, ['action'], ''),
            ])
            ->sortByDesc('time')->values()->all();
    }

    /** Akses web yang diblokir / diperingatkan web filter. */
    public function webFilterLogs(): array
    {
        return collect($this->logs('webfilter'))
            ->map(fn ($r) => [
                'time' => self::logTime($r),
                'user' => self::logUser($r),
                'ip' => (string) self::pick($r, ['srcip'], ''),
                'url' => trim(self::pick($r, ['hostname'], '') . self::pick($r, ['url'], '')) ?: '-',
                'category' => (string) self::pick($r, ['catdesc', 'cat'], ''),
                'action' => (string) self::pick($r, ['action'], ''),
            ])
            ->sortByDesc('time')->values()->all();
    }

    /** Serangan yang terdeteksi IPS. */
    public function ipsLogs(): array
    {
        return collect($this->logs('ips'))
            ->map(fn ($r) => [
                'time' => self::logTime($r),
                'source' => (string) self::pick($r, ['srcip'], '-'),
                'target' => (string) self::pick($r, ['dstip'], '-'),
                'attack' => (string) self::pick($r, ['attack', 'msg'], '-'),
                'severity' => (string) self::pick($r, ['severity', 'crlevel', 'level'], ''),
                'action' => (string) self::pick($r, ['action'], ''),
            ])
            ->sortByDesc('time')->values()->all();
    }

    /** User yang sedang login di firewall (FSSO / captive portal / dll.). */
    public function firewallUsers(): array
    {
        return collect(self::rowsFrom($this->get('monitor/user/firewall')))
            ->map(fn ($u) => [
                'user' => (string) self::pick($u, ['username', 'user_name', 'name'], '-'),
                'ip' => (string) self::pick($u, ['ipaddr', 'ip', 'src_ip'], '-'),
                'method' => (string) self::pick($u, ['method', 'type', 'auth_type'], ''),
                'duration' => ($d = self::pick($u, ['duration'], null)) !== null ? gmdate('H:i:s', (int) $d) : '',
                'bytes' => (int) self::pick($u, ['traffic_vol_bytes', 'traffic', 'bytes'], 0),
            ])
            ->sortByDesc('bytes')->values()->all();
    }

    /**
     * Cek banyak perangkat sekaligus (paralel) dan simpan hasilnya di kolom last_*.
     *
     * @param Collection<int, FortigateDevice> $devices
     */
    public static function checkMany(Collection $devices): void
    {
        $devices = $devices->values();
        if ($devices->isEmpty()) {
            return;
        }

        $responses = Http::pool(function (Pool $pool) use ($devices) {
            $requests = [];
            foreach ($devices as $i => $d) {
                $query = array_filter(['vdom' => $d->vdom]);
                $requests[] = self::prepare($pool->as("s{$i}"), $d, 10)->get($d->apiUrl() . '/monitor/system/status', $query);
                $requests[] = self::prepare($pool->as("r{$i}"), $d, 10)->get($d->apiUrl() . '/monitor/system/resource/usage', $query + ['interval' => '1-min']);
            }
            return $requests;
        });

        foreach ($devices as $i => $d) {
            $status = $responses["s{$i}"] ?? null;
            $usage = $responses["r{$i}"] ?? null;
            $update = ['last_checked_at' => now()];

            if ($status instanceof Throwable || $status === null) {
                $update += ['last_status' => 'offline', 'last_error' => $status ? self::shortError($status) : 'tidak ada respons'];
            } elseif (in_array($status->status(), [401, 403], true)) {
                $update += ['last_status' => 'auth_error', 'last_error' => "API token ditolak ({$status->status()})."];
            } elseif (!$status->successful()) {
                $update += ['last_status' => 'offline', 'last_error' => "Status HTTP {$status->status()}."];
            } else {
                $s = $status->json() ?? [];
                $r = $usage instanceof Response && $usage->successful() ? ($usage->json('results') ?? []) : [];
                $update += [
                    'last_status' => 'online',
                    'last_error' => null,
                    'last_info' => [
                        'hostname' => data_get($s, 'results.hostname'),
                        'model' => data_get($s, 'results.model_name') ? trim(data_get($s, 'results.model_name') . ' ' . data_get($s, 'results.model_number')) : null,
                        'serial' => $s['serial'] ?? null,
                        'version' => $s['version'] ?? null,
                        'build' => $s['build'] ?? null,
                        'cpu' => data_get($r, 'cpu.0.current'),
                        'mem' => data_get($r, 'mem.0.current'),
                        'sessions' => data_get($r, 'session.0.current'),
                    ],
                ];
            }
            $d->forceFill($update)->save();
        }
    }

    public static function formatBytes(int|float $bytes, int $precision = 1): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $bytes = max($bytes, 0);
        $pow = $bytes > 0 ? min((int) floor(log($bytes, 1024)), count($units) - 1) : 0;

        return round($bytes / (1024 ** $pow), $precision) . ' ' . $units[$pow];
    }
}
