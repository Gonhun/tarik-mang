<?php

namespace App\Livewire\Modal\Asset;

use App\Models\Asset;
use App\Models\AssetTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class Pengembalian extends Component
{
    /** Status aset yang boleh dikembalikan. */
    public const RETURNABLE_STATUSES = ['Used', 'Pre-Used'];

    public $isModalOpen = false;
    public ?Asset $assetToReturn = null;
    public $returnCondition = '';
    public $returnNotes = '';

    protected $listeners = ['openReturnModal' => 'loadAssetToReturn'];

    /**
     * Pengelola aset (IT / Master Asset) boleh mengembalikan aset siapa pun,
     * user biasa hanya aset yang dipegangnya sendiri.
     */
    protected function canReturn(Asset $asset): bool
    {
        $user = Auth::user();

        return $user->isItStaff()
            || $user->can('Master Asset')
            || (int) $asset->holder_user_id === $user->id;
    }

    public function loadAssetToReturn($assetId)
    {
        $asset = Asset::with(['holder:id,name', 'assetType:id,name', 'assetBrand:id,name', 'assetSeries:id,name'])->find($assetId);

        if (!$asset || !$this->canReturn($asset)) {
            $this->dispatch('swal:error', ['title' => 'Gagal', 'text' => 'Aset tidak ditemukan atau Anda tidak berhak mengembalikannya.']);
            return;
        }
        if (!in_array($asset->status, self::RETURNABLE_STATUSES, true) || !$asset->holder_user_id) {
            $this->dispatch('swal:error', ['title' => 'Gagal', 'text' => "Aset {$asset->asset_number} berstatus {$asset->status} dan tidak sedang dipegang user, jadi tidak bisa dikembalikan."]);
            return;
        }

        $this->assetToReturn = $asset;
        $this->reset(['returnCondition', 'returnNotes']);
        $this->resetValidation();
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->assetToReturn = null;
    }

    public function submitReturn()
    {
        if (!$this->assetToReturn) {
            return;
        }

        $this->validate([
            'returnCondition' => 'required|string|max:255',
            'returnNotes' => 'nullable|string|max:1000',
        ], [
            'returnCondition.required' => 'Kondisi aset saat pengembalian wajib diisi.',
        ]);

        // Ambil ulang dari database: status bisa berubah sejak modal dibuka.
        $asset = Asset::with('holder:id,name')->find($this->assetToReturn->id);
        if (!$asset || !$this->canReturn($asset) || !in_array($asset->status, self::RETURNABLE_STATUSES, true) || !$asset->holder) {
            $this->dispatch('swal:error', ['title' => 'Gagal', 'text' => 'Aset sudah tidak bisa dikembalikan (status atau pemegang sudah berubah).']);
            $this->closeModal();
            $this->dispatch('refreshAssetList');
            return;
        }

        try {
            $transaction = DB::transaction(function () use ($asset) {
                $returnTransaction = AssetTransaction::create([
                    'transaction_number' => \App\Support\TransactionNumber::next('RTN'),
                    'transaction_type' => 'RETURN',
                    'transaction_date' => now(),
                    'from_user_id' => $asset->holder_user_id,
                    'from_jobsite_id' => $asset->jobsite_id,
                    'to_jobsite_id' => $asset->jobsite_id,
                    'notes' => "Pengembalian aset {$asset->asset_number} oleh {$asset->holder->name}",
                    'status' => 'Pending Inspection',
                    'created_by_user_id' => Auth::id(),
                ]);

                $returnTransaction->items()->create([
                    'asset_id' => $asset->id,
                    'condition_notes' => "Kondisi: {$this->returnCondition}. Catatan: {$this->returnNotes}",
                ]);

                // Simpan tautan ke transaksi pengembalian (dipakai cetak BA pengembalian).
                $asset->update([
                    'status' => 'Pending Inspection',
                    'asset_transaction_return_id' => $returnTransaction->id,
                ]);

                return $returnTransaction;
            });
        } catch (\Throwable $e) {
            Log::error("Pengembalian aset #{$asset->id} gagal: " . $e->getMessage());
            $this->dispatch('swal:error', ['title' => 'Gagal', 'text' => 'Pengembalian gagal disimpan. Coba lagi atau hubungi tim IT.']);
            return;
        }

        $this->closeModal();
        $this->dispatch('refreshMyAssets');
        $this->dispatch('refreshAssetList');
        $this->dispatch('swal:success', [
            'title' => 'Berhasil!',
            'text' => "Aset {$asset->asset_number} diajukan untuk pengembalian ({$transaction->transaction_number}) dan menunggu inspeksi IT.",
        ]);
    }

    public function render()
    {
        return view('livewire.modal.asset.pengembalian');
    }
}
