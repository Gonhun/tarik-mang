<?php

namespace App\Livewire\Admin\Fortigate;

use App\Models\FortigateDevice;
use App\Support\FortigateClient;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Throwable;

/**
 * Detail satu Fortigate. Data diambil dari perangkat HANYA ketika tab dibuka,
 * sehingga halaman tampil instan walau perangkat lambat / tidak terjangkau.
 */
class Show extends Component
{
    /**
     * Definisi tab.
     *  method : method FortigateClient (null = tab khusus)
     *  period : tab memakai pilihan periode FortiView
     *  cols   : kolom tabel => [judul, tipe] (tipe: text|code|bytes|bar|num|badge|severity)
     */
    public const TABS = [
        'ringkasan' => ['label' => 'Ringkasan', 'icon' => 'bx-tachometer', 'group' => null, 'method' => 'summary'],

        'bandwidth' => ['label' => 'Bandwidth per user', 'icon' => 'bx-bar-chart-alt-2', 'group' => 'Pemakaian', 'method' => 'bandwidthPerUser', 'period' => true,
            'cols' => ['user' => ['User', 'text'], 'ip' => ['IP', 'code'], 'device' => ['Perangkat', 'text'], 'download' => ['Download', 'bytes'], 'upload' => ['Upload', 'bytes'], 'bytes' => ['Total', 'bar'], 'sessions' => ['Sesi', 'num']]],
        'websites' => ['label' => 'Website', 'icon' => 'bx-globe', 'group' => 'Pemakaian', 'method' => 'topWebsites', 'period' => true,
            'cols' => ['domain' => ['Website', 'text'], 'category' => ['Kategori', 'text'], 'sessions' => ['Sesi', 'num'], 'bytes' => ['Trafik', 'bar']]],
        'apps' => ['label' => 'Aplikasi', 'icon' => 'bx-grid-alt', 'group' => 'Pemakaian', 'method' => 'topApplications', 'period' => true,
            'cols' => ['app' => ['Aplikasi', 'text'], 'category' => ['Kategori', 'text'], 'risk' => ['Risiko', 'severity'], 'sessions' => ['Sesi', 'num'], 'bytes' => ['Trafik', 'bar']]],
        'users' => ['label' => 'User login', 'icon' => 'bx-user-check', 'group' => 'Pemakaian', 'method' => 'firewallUsers',
            'cols' => ['user' => ['User', 'text'], 'ip' => ['IP', 'code'], 'method' => ['Metode', 'text'], 'duration' => ['Durasi', 'text'], 'bytes' => ['Trafik', 'bar']]],

        'virus' => ['label' => 'Virus terdeteksi', 'icon' => 'bx-bug', 'group' => 'Keamanan', 'method' => 'virusLogs',
            'cols' => ['time' => ['Waktu', 'text'], 'user' => ['User / IP', 'text'], 'virus' => ['Virus', 'text'], 'file' => ['File / URL', 'code'], 'action' => ['Aksi', 'badge']]],
        'webfilter' => ['label' => 'Web diblokir', 'icon' => 'bx-block', 'group' => 'Keamanan', 'method' => 'webFilterLogs',
            'cols' => ['time' => ['Waktu', 'text'], 'user' => ['User / IP', 'text'], 'url' => ['URL', 'code'], 'category' => ['Kategori', 'text'], 'action' => ['Aksi', 'badge']]],
        'ips' => ['label' => 'Serangan (IPS)', 'icon' => 'bx-shield-x', 'group' => 'Keamanan', 'method' => 'ipsLogs',
            'cols' => ['time' => ['Waktu', 'text'], 'source' => ['Sumber', 'code'], 'target' => ['Tujuan', 'code'], 'attack' => ['Serangan', 'text'], 'severity' => ['Tingkat', 'severity'], 'action' => ['Aksi', 'badge']]],

        'interface' => ['label' => 'Interface', 'icon' => 'bx-network-chart', 'group' => 'Jaringan', 'method' => 'interfaces'],
        'dhcp' => ['label' => 'DHCP', 'icon' => 'bx-devices', 'group' => 'Jaringan', 'method' => 'dhcpLeases',
            'cols' => ['ip' => ['IP', 'code'], 'mac' => ['MAC', 'code'], 'hostname' => ['Hostname', 'text'], 'interface' => ['Interface', 'text'], 'expire' => ['Berakhir', 'text']]],
        'arp' => ['label' => 'ARP', 'icon' => 'bx-sitemap', 'group' => 'Jaringan', 'method' => 'arp',
            'cols' => ['ip' => ['IP', 'code'], 'mac' => ['MAC', 'code'], 'interface' => ['Interface', 'text'], 'age' => ['Umur', 'text']]],
        'routing' => ['label' => 'Routing', 'icon' => 'bx-git-merge', 'group' => 'Jaringan', 'method' => 'routes',
            'cols' => ['destination' => ['Tujuan', 'code'], 'gateway' => ['Gateway', 'code'], 'interface' => ['Interface', 'text'], 'type' => ['Tipe', 'text'], 'distance' => ['Distance', 'num']]],
        'vpn' => ['label' => 'VPN IPsec', 'icon' => 'bx-lock-alt', 'group' => 'Jaringan', 'method' => 'ipsec',
            'cols' => ['name' => ['Tunnel', 'text'], 'remote' => ['Remote gateway', 'code'], 'status' => ['Status', 'badge'], 'phase2' => ['Phase 2 up', 'text'], 'rx' => ['RX', 'bytes'], 'tx' => ['TX', 'bytes']]],
        'sdwan' => ['label' => 'SD-WAN', 'icon' => 'bx-pulse', 'group' => 'Jaringan', 'method' => 'sdwan',
            'cols' => ['check' => ['Health check', 'text'], 'interface' => ['Link', 'text'], 'status' => ['Status', 'badge'], 'latency' => ['Latency', 'text'], 'jitter' => ['Jitter', 'text'], 'loss' => ['Packet loss', 'text']]],
        'api' => ['label' => 'API', 'icon' => 'bx-code-alt', 'group' => 'Jaringan', 'method' => null],
    ];

    public const PERIODS = ['realtime' => 'Saat ini', 'hour' => '1 jam', 'day' => '24 jam', 'week' => '7 hari'];

    #[Locked] public int $deviceId;

    #[Url(as: 'tab')]
    public string $tab = 'ringkasan';

    #[Url(as: 'periode')]
    public string $period = 'realtime';

    /** Data per tab yang sudah dimuat (kunci tab|periode). */
    #[Locked] public array $data = [];
    #[Locked] public array $errors_ = [];
    #[Locked] public array $loadedAt = [];

    public string $search = '';

    public function mount(FortigateDevice $device): void
    {
        $this->deviceId = $device->id;
        if (!array_key_exists($this->tab, self::TABS)) {
            $this->tab = 'ringkasan';
        }
        if (!array_key_exists($this->period, self::PERIODS)) {
            $this->period = 'realtime';
        }
    }

    public function setTab(string $tab): void
    {
        if (array_key_exists($tab, self::TABS)) {
            $this->tab = $tab;
            $this->search = '';
        }
    }

    public function updatedPeriod(): void
    {
        if (!array_key_exists($this->period, self::PERIODS)) {
            $this->period = 'realtime';
        }
    }

    protected function cacheKey(string $tab): string
    {
        return !empty(self::TABS[$tab]['period']) ? "{$tab}|{$this->period}" : $tab;
    }

    protected function device(): FortigateDevice
    {
        return FortigateDevice::with('jobsite:id,code,name')->findOrFail($this->deviceId);
    }

    /** Dipanggil wire:init milik tab yang belum dimuat, atau tombol muat ulang. */
    public function load(?string $tab = null): void
    {
        $tab ??= $this->tab;
        $def = self::TABS[$tab] ?? null;
        if (!$def || !$def['method']) {
            return;
        }
        $key = $this->cacheKey($tab);
        $device = $this->device();
        $client = FortigateClient::for($device);
        unset($this->errors_[$key]);

        try {
            $this->data[$key] = !empty($def['period']) ? $client->{$def['method']}($this->period) : $client->{$def['method']}();
            if ($tab === 'ringkasan') {
                $device->forceFill(['last_status' => 'online', 'last_error' => null, 'last_checked_at' => now(), 'last_info' => $this->data[$key]])->save();
            }
        } catch (Throwable $e) {
            $this->data[$key] = [];
            $this->errors_[$key] = $e->getMessage();
            if ($tab === 'ringkasan') {
                $device->forceFill([
                    'last_status' => str_contains($e->getMessage(), 'token') ? 'auth_error' : 'offline',
                    'last_error' => $e->getMessage(), 'last_checked_at' => now(),
                ])->save();
            }
        }
        $this->loadedAt[$key] = now()->format('H:i:s');
    }

    public function render()
    {
        $device = $this->device();
        $key = $this->cacheKey($this->tab);
        $rows = $this->data[$key] ?? null;

        if (is_array($rows) && $this->tab !== 'ringkasan' && trim($this->search) !== '') {
            $term = mb_strtolower(trim($this->search));
            $rows = array_values(array_filter($rows, fn ($r) => str_contains(
                mb_strtolower(implode(' ', array_map(fn ($v) => is_scalar($v) ? (string) $v : '', $r))), $term)));
        }

        $def = self::TABS[$this->tab];

        return view('livewire.admin.fortigate.show', [
            'device' => $device,
            'tabs' => self::TABS,
            'def' => $def,
            'periods' => self::PERIODS,
            'loaded' => array_key_exists($key, $this->data),
            'rows' => $rows,
            'error' => $this->errors_[$key] ?? null,
            'loadedTime' => $this->loadedAt[$key] ?? null,
            'loadKey' => $key,
            // Nilai maksimum untuk kolom "bar" (perbandingan relatif).
            'barMax' => is_array($rows) ? collect($def['cols'] ?? [])->filter(fn ($c) => $c[1] === 'bar')
                ->map(fn ($c, $k) => max(1, (int) collect($rows)->max($k)))->all() : [],
            'totalBytes' => is_array($rows) && isset(($def['cols'] ?? [])['bytes']) ? (int) collect($rows)->sum('bytes') : null,
        ])->title($device->name . ' · Fortigate');
    }
}
