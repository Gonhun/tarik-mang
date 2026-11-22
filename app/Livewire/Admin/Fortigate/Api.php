<?php

namespace App\Livewire\Admin\Fortigate;

use App\Livewire\Admin\Fortigate\Concerns\SelectsDevice;
use App\Support\FortigateClient;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

/**
 * Penjelajah REST API Fortigate (hanya baca / GET) untuk perangkat terpilih.
 */
#[Title('API Fortigate')]
class Api extends Component
{
    use SelectsDevice;

    public const CATEGORIES = ['monitor' => 'Monitoring', 'log' => 'Log'];

    #[Locked] public string $category = '';
    #[Locked] public array $endpoints = [];
    public string $search = '';
    public bool $onlySupported = true;
    public string $errorMessage = '';

    #[Locked] public ?string $resultPath = null;
    #[Locked] public ?string $resultJson = null;

    public function updatedDeviceId(): void
    {
        $this->reset(['category', 'endpoints', 'resultPath', 'resultJson', 'errorMessage']);
    }

    public function fetchGroupData(string $category): void
    {
        if (!array_key_exists($category, self::CATEGORIES)) {
            return;
        }
        $this->errorMessage = '';
        $this->category = $category;
        $this->endpoints = [];
        $device = $this->device();
        if (!$device) {
            return;
        }

        try {
            $data = FortigateClient::for($device)->get($category, [], 60);
            foreach ($data['directory'] ?? [] as $e) {
                if (!isset($e['path'], $e['name'])) continue;
                $this->endpoints[] = [
                    'path' => $e['path'],
                    'name' => $e['name'],
                    'action' => $e['action'] ?? '',
                    'supported' => (bool) ($e['supported'] ?? false),
                    'summary' => $e['summary'] ?? '',
                    'params' => collect($e['request']['parameters'] ?? [])->map(fn ($p) => [
                        'name' => $p['name'] ?? '', 'type' => $p['type'] ?? '', 'summary' => $p['summary'] ?? '',
                    ])->all(),
                ];
            }
        } catch (Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function back(): void
    {
        $this->reset(['category', 'endpoints', 'search', 'resultPath', 'resultJson', 'errorMessage']);
    }

    /** Jalankan GET ke endpoint "select" (hanya baca). */
    public function run(int $index): void
    {
        $e = $this->endpoints[$index] ?? null;
        $device = $this->device();
        if (!$e || !$device || $e['action'] !== 'select') {
            return;
        }
        $path = "{$this->category}/{$e['path']}/{$e['name']}";
        $this->resultPath = $path;
        try {
            $json = json_encode(FortigateClient::for($device)->get($path, [], 30), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $this->resultJson = mb_strlen($json) > 200000 ? mb_substr($json, 0, 200000) . "\n… (dipotong)" : $json;
        } catch (Throwable $ex) {
            $this->resultJson = 'Gagal: ' . $ex->getMessage();
        }
    }

    public function closeResult(): void
    {
        $this->reset(['resultPath', 'resultJson']);
    }

    public function render()
    {
        $term = mb_strtolower(trim($this->search));
        $rows = collect($this->endpoints)
            ->map(fn ($e, $i) => $e + ['index' => $i])
            ->filter(fn ($e) => (!$this->onlySupported || $e['supported'])
                && ($term === '' || str_contains(mb_strtolower("{$e['path']}/{$e['name']} {$e['summary']}"), $term)))
            ->values();

        return view('livewire.admin.fortigate.api', [
            'rows' => $rows,
            'device' => $this->device(),
            'devices' => $this->deviceOptions(),
            'categories' => self::CATEGORIES,
        ]);
    }
}
