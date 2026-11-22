<?php

namespace App\Livewire\Admin\Fortigate\Concerns;

use App\Models\FortigateDevice;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

/** Pemilih perangkat Fortigate (?device=ID) untuk halaman Interface & API. */
trait SelectsDevice
{
    #[Url(as: 'device')]
    public $deviceId = '';

    /** true = ditanam di halaman detail: perangkat tetap, tanpa pemilih. */
    public bool $embedded = false;

    public function mountSelectsDevice(): void
    {
        if ($this->embedded) {
            return;
        }
        if (!$this->deviceId || !FortigateDevice::active()->whereKey($this->deviceId)->exists()) {
            // Default: perangkat di jobsite user, kalau tidak ada ambil yang pertama.
            $this->deviceId = (string) (FortigateDevice::active()->where('jobsite_id', Auth::user()->jobsite_id)->orderBy('name')->value('id')
                ?? FortigateDevice::active()->orderBy('name')->value('id') ?? '');
        }
    }

    protected function device(): ?FortigateDevice
    {
        if (!$this->deviceId) {
            return null;
        }

        return $this->embedded ? FortigateDevice::find($this->deviceId) : FortigateDevice::active()->find($this->deviceId);
    }

    protected function deviceOptions()
    {
        return FortigateDevice::active()->with('jobsite:id,code')->orderBy('name')->get(['id', 'name', 'jobsite_id']);
    }
}
