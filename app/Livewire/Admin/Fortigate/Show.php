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
    /** key => [label, ikon, method client, kolom tabel (key => judul)] */
    public const TABS = [
        'ringkasan' => ['Ringkasan', 'bx-tachometer', 'summary', []],
        'interface' => ['Interface', 'bx-network-chart', 'interfaces', []],
        'dhcp' => ['DHCP', 'bx-devices', 'dhcpLeases', ['ip' => 'IP', 'mac' => 'MAC', 'hostname' => 'Hostname', 'interface' => 'Interface', 'expire' => 'Berakhir']],
        'arp' => ['ARP', 'bx-sitemap', 'arp', ['ip' => 'IP', 'mac' => 'MAC', 'interface' => 'Interface', 'age' => 'Umur']],
        'routing' => ['Routing', 'bx-git-merge', 'routes', ['destination' => 'Tujuan', 'gateway' => 'Gateway', 'interface' => 'Interface', 'type' => 'Tipe', 'distance' => 'Distance']],
        'vpn' => ['VPN IPsec', 'bx-lock-alt', 'ipsec', []],
        'sdwan' => ['SD-WAN', 'bx-pulse', 'sdwan', ['check' => 'Health check', 'interface' => 'Link', 'status' => 'Status', 'latency' => 'Latency', 'jitter' => 'Jitter', 'loss' => 'Packet loss']],
        'api' => ['API', 'bx-code-alt', null, []],
    ];

    #[Locked] public int $deviceId;

    #[Url(as: 'tab')]
    public string $tab = 'ringkasan';

    /** Data per tab yang sudah dimuat (tab => rows/array). */
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
    }

    public function setTab(string $tab): void
    {
        if (array_key_exists($tab, self::TABS)) {
            $this->tab = $tab;
            $this->search = '';
        }
    }

    protected function device(): FortigateDevice
    {
        return FortigateDevice::with('jobsite:id,code,name')->findOrFail($this->deviceId);
    }

    /** Dipanggil wire:init milik tab yang belum dimuat, atau tombol muat ulang. */
    public function load(?string $tab = null): void
    {
        $tab ??= $this->tab;
        $method = self::TABS[$tab][2] ?? null;
        if (!$method) {
            return;
        }
        $device = $this->device();
        unset($this->errors_[$tab]);

        try {
            $this->data[$tab] = FortigateClient::for($device)->{$method}();
            $this->loadedAt[$tab] = now()->format('H:i:s');
            if ($tab === 'ringkasan') {
                $device->forceFill([
                    'last_status' => 'online', 'last_error' => null, 'last_checked_at' => now(),
                    'last_info' => $this->data[$tab],
                ])->save();
            }
        } catch (Throwable $e) {
            $this->data[$tab] = [];
            $this->errors_[$tab] = $e->getMessage();
            $this->loadedAt[$tab] = now()->format('H:i:s');
            if ($tab === 'ringkasan') {
                $device->forceFill([
                    'last_status' => str_contains($e->getMessage(), 'token') ? 'auth_error' : 'offline',
                    'last_error' => $e->getMessage(), 'last_checked_at' => now(),
                ])->save();
            }
        }
    }

    public function render()
    {
        $device = $this->device();
        $rows = $this->data[$this->tab] ?? null;

        if (is_array($rows) && $this->tab !== 'ringkasan' && trim($this->search) !== '') {
            $term = mb_strtolower(trim($this->search));
            $rows = array_values(array_filter($rows, fn ($r) => str_contains(mb_strtolower(implode(' ', array_map('strval', $r))), $term)));
        }

        return view('livewire.admin.fortigate.show', [
            'device' => $device,
            'tabs' => self::TABS,
            'loaded' => array_key_exists($this->tab, $this->data),
            'rows' => $rows,
            'error' => $this->errors_[$this->tab] ?? null,
        ])->title($device->name . ' · Fortigate');
    }
}
