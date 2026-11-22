<?php

namespace App\Livewire\Admin;

use App\Support\LoginSettings as Settings;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Pengaturan tampilan halaman login: gambar latar, logo, judul, poin, karakter animasi.
 */
#[Title('Tampilan Login')]
class LoginSettings extends Component
{
    use WithFileUploads;

    public $background;      // upload baru
    public $logo;            // upload baru
    public int $overlay = 55;
    public string $headline = '';
    public array $points = [];
    public bool $show_character = true;

    public function mount(): void
    {
        $s = Settings::all();
        $this->overlay = (int) $s['overlay'];
        $this->headline = (string) $s['headline'];
        $this->points = array_values(array_pad((array) $s['points'], 3, ''));
        $this->show_character = (bool) $s['show_character'];
    }

    protected function rules(): array
    {
        return [
            'background' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:min_width=800,min_height=600'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:1024'],
            'overlay' => ['required', 'integer', 'min:0', 'max:90'],
            'headline' => ['required', 'string', 'max:120'],
            'points' => ['array', 'max:5'],
            'points.*' => ['nullable', 'string', 'max:80'],
            'show_character' => ['boolean'],
        ];
    }

    protected $messages = [
        'background.image' => 'File latar harus berupa gambar.',
        'background.max' => 'Ukuran gambar latar maksimal 4 MB.',
        'background.dimensions' => 'Gambar latar minimal 800 × 600 piksel agar tidak pecah.',
        'logo.max' => 'Ukuran logo maksimal 1 MB.',
        'headline.required' => 'Judul wajib diisi.',
        'headline.max' => 'Judul maksimal 120 karakter.',
        'points.*.max' => 'Setiap poin maksimal 80 karakter.',
    ];

    public function updatedBackground(): void
    {
        $this->validateOnly('background');
    }

    public function updatedLogo(): void
    {
        $this->validateOnly('logo');
    }

    public function addPoint(): void
    {
        if (count($this->points) < 5) {
            $this->points[] = '';
        }
    }

    public function removePoint(int $i): void
    {
        unset($this->points[$i]);
        $this->points = array_values($this->points);
    }

    public function save(): void
    {
        $this->validate();
        $current = Settings::all();
        $values = [
            'overlay' => $this->overlay,
            'headline' => trim($this->headline),
            'points' => array_values(array_filter(array_map('trim', $this->points), fn ($p) => $p !== '')),
            'show_character' => $this->show_character,
            'updated_by' => Auth::user()->name,
            'updated_at' => now()->toDateTimeString(),
        ];

        if ($this->background) {
            $values['background'] = Settings::storeImage($this->background, 'bg');
            Settings::deleteImage($current['background']);
        }
        if ($this->logo) {
            $values['logo'] = Settings::storeImage($this->logo, 'logo');
            Settings::deleteImage($current['logo']);
        }

        Settings::save($values);
        $this->reset(['background', 'logo']);
        $this->dispatch('swal:success', ['title' => 'Tersimpan', 'text' => 'Tampilan halaman login diperbarui.']);
    }

    public function removeBackground(): void
    {
        Settings::deleteImage(Settings::get('background'));
        Settings::save(['background' => null, 'updated_by' => Auth::user()->name, 'updated_at' => now()->toDateTimeString()]);
        $this->reset('background');
    }

    public function resetLogo(): void
    {
        Settings::deleteImage(Settings::get('logo'));
        Settings::save(['logo' => null, 'updated_by' => Auth::user()->name, 'updated_at' => now()->toDateTimeString()]);
        $this->reset('logo');
    }

    public function resetText(): void
    {
        $this->headline = Settings::DEFAULTS['headline'];
        $this->points = Settings::DEFAULTS['points'];
        $this->overlay = Settings::DEFAULTS['overlay'];
    }

    public function render()
    {
        $s = Settings::all();

        return view('livewire.admin.login-settings', [
            // Pratinjau memakai upload yang belum disimpan bila ada.
            'previewBg' => $this->background && !$this->getErrorBag()->has('background') ? $this->background->temporaryUrl() : Settings::backgroundUrl(),
            'previewLogo' => $this->logo && !$this->getErrorBag()->has('logo') ? $this->logo->temporaryUrl() : Settings::logoUrl(),
            'hasBackground' => (bool) Settings::backgroundUrl(),
            'hasCustomLogo' => (bool) $s['logo'],
            'updatedBy' => $s['updated_by'],
            'updatedAt' => $s['updated_at'],
        ]);
    }
}
