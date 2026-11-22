<?php

namespace App\Livewire\Admin;

use App\Models\FortigateDevice;
use App\Models\Jobsite;
use App\Support\FortigateClient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Throwable;

/**
 * Daftar perangkat Fortigate per jobsite: status, tambah/ubah/hapus, tes koneksi.
 */
#[Title('Fortigate')]
class Fortigate extends Component
{
    #[Url(as: 'site')]
    public string $jobsiteFilter = '';
    #[Url(as: 'q')]
    public string $search = '';

    public bool $checked = false;

    // Form
    public bool $showForm = false;
    #[Locked] public ?int $editingId = null;
    public string $name = '';
    public $jobsite_id = '';
    public string $base_url = '';
    public string $api_token = '';
    public string $vdom = '';
    public bool $verify_ssl = false;
    public bool $is_active = true;
    public string $notes = '';
    public ?string $testResult = null;
    public bool $testOk = false;

    /** wire:init: cek status semua perangkat aktif (paralel). */
    public function checkAll(): void
    {
        FortigateClient::checkMany($this->filteredQuery()->active()->get());
        $this->checked = true;
    }

    public function checkOne(int $id): void
    {
        FortigateClient::checkMany(FortigateDevice::whereKey($id)->get());
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'jobsite_id' => ['nullable', Rule::exists('jobsites', 'id')],
            'base_url' => ['required', 'url:http,https', 'max:255'],
            'api_token' => [$this->editingId ? 'nullable' : 'required', 'string', 'max:500'],
            'vdom' => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9_\-]*$/'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected $messages = [
        'name.required' => 'Nama perangkat wajib diisi.',
        'base_url.required' => 'Alamat Fortigate wajib diisi.',
        'base_url.url' => 'Alamat harus berupa URL, mis. https://192.168.4.1:8443',
        'api_token.required' => 'API token wajib diisi.',
        'vdom.regex' => 'VDOM hanya huruf, angka, - dan _.',
    ];

    public function create(): void
    {
        $this->resetForm();
        $this->jobsite_id = (string) (Auth::user()->jobsite_id ?? '');
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $d = FortigateDevice::findOrFail($id);
        $this->resetForm();
        $this->editingId = $d->id;
        $this->name = $d->name;
        $this->jobsite_id = (string) ($d->jobsite_id ?? '');
        $this->base_url = $d->base_url;
        $this->vdom = (string) $d->vdom;
        $this->verify_ssl = $d->verify_ssl;
        $this->is_active = $d->is_active;
        $this->notes = (string) $d->notes;
        $this->showForm = true;
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'jobsite_id', 'base_url', 'api_token', 'vdom', 'verify_ssl', 'notes', 'testResult', 'testOk']);
        $this->is_active = true;
        $this->resetValidation();
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    /** Perangkat sementara dari isi form (token lama dipakai bila kolom token dikosongkan). */
    protected function formDevice(): FortigateDevice
    {
        $device = $this->editingId ? FortigateDevice::findOrFail($this->editingId) : new FortigateDevice();
        $device->fill([
            'name' => trim($this->name),
            'jobsite_id' => $this->jobsite_id ?: null,
            'base_url' => rtrim(trim($this->base_url), '/'),
            'vdom' => trim($this->vdom) ?: null,
            'verify_ssl' => $this->verify_ssl,
            'is_active' => $this->is_active,
            'notes' => trim($this->notes) ?: null,
        ]);
        if (trim($this->api_token) !== '') {
            $device->api_token = trim($this->api_token);
        }

        return $device;
    }

    public function testConnection(): void
    {
        $this->validate();
        $device = $this->formDevice();
        try {
            $s = FortigateClient::for($device)->get('monitor/system/status', [], 10);
            $this->testOk = true;
            $this->testResult = 'Terhubung: ' . (data_get($s, 'results.hostname') ?? $device->name)
                . ' · ' . trim(data_get($s, 'results.model_name') . ' ' . data_get($s, 'results.model_number'))
                . ' · ' . ($s['version'] ?? '');
        } catch (Throwable $e) {
            $this->testOk = false;
            $this->testResult = $e->getMessage();
        }
    }

    public function save(): void
    {
        $this->validate();
        $device = $this->formDevice();
        if (!$device->exists) {
            $device->created_by = Auth::id();
        }
        $device->save();

        $this->closeForm();
        FortigateClient::checkMany(collect([$device]));
        $this->dispatch('swal:success', ['title' => 'Tersimpan', 'text' => "Perangkat {$device->name} disimpan."]);
    }

    public function delete(int $id): void
    {
        $d = FortigateDevice::findOrFail($id);
        $name = $d->name;
        $d->delete();
        $this->dispatch('swal:success', ['title' => 'Dihapus', 'text' => "Perangkat {$name} dihapus."]);
    }

    protected function filteredQuery()
    {
        return FortigateDevice::query()
            ->when($this->jobsiteFilter === 'none', fn ($q) => $q->whereNull('jobsite_id'))
            ->when($this->jobsiteFilter !== '' && $this->jobsiteFilter !== 'none', fn ($q) => $q->where('jobsite_id', $this->jobsiteFilter))
            ->when(trim($this->search) !== '', function ($q) {
                $term = '%' . \App\Support\SqlLike::escape(trim($this->search)) . '%';
                $q->where(fn ($w) => $w->where('name', 'like', $term)->orWhere('base_url', 'like', $term));
            });
    }

    public function render()
    {
        $devices = $this->filteredQuery()->with('jobsite:id,code,name')->orderBy('name')->get();
        $all = FortigateDevice::query()->get(['id', 'jobsite_id', 'is_active', 'last_status']);

        return view('livewire.admin.fortigate', [
            'groups' => $devices->groupBy(fn ($d) => $d->jobsite ? $d->jobsite->code . ' — ' . $d->jobsite->name : 'Tanpa jobsite')->sortKeys(),
            'summary' => [
                'total' => $all->count(),
                'online' => $all->where('is_active', true)->where('last_status', 'online')->count(),
                'problem' => $all->where('is_active', true)->whereIn('last_status', ['offline', 'auth_error'])->count(),
                'sites' => $all->pluck('jobsite_id')->filter()->unique()->count(),
            ],
            'jobsites' => Jobsite::orderBy('code')->get(['id', 'code', 'name']),
            'siteOptions' => Jobsite::whereIn('id', $all->pluck('jobsite_id')->filter()->unique())->orderBy('code')->get(['id', 'code']),
        ]);
    }
}
