<?php

namespace App\Livewire\Modal\Asset;

use Livewire\Component;
use App\Models\Asset;
use App\Models\AssetTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Pengembalian extends Component
{
    public $isModalOpen = false;
    public ?Asset $assetToReturn = null;
    public $returnCondition = '';
    public $returnNotes = '';

    // Listener untuk "mendengar" event dari komponen lain
    protected $listeners = ['openReturnModal' => 'loadAssetToReturn'];

    /**
     * Method ini dipanggil oleh event 'openReturnModal'.
     * Ia akan memuat data aset dan membuka modal.
     */
    public function loadAssetToReturn($assetId)
    {
        if(Auth::user()->department->code === 'IT'){
            $this->assetToReturn = Asset::where('id', $assetId)
                                    ->first();
        }else{
            $this->assetToReturn = Asset::where('id', $assetId)
                                    ->where('holder_user_id', Auth::id())
                                    ->first();
        }
        
        
        if ($this->assetToReturn) {
            $this->reset(['returnCondition', 'returnNotes']);
            $this->resetValidation();
            $this->isModalOpen = true; // Buka modal
        } else {
            // Jika aset tidak ditemukan, kirim notifikasi toast
            $this->dispatch('show-toast', [
                'type' => 'error', 
                'message' => 'Aset tidak ditemukan atau Anda tidak berhak mengembalikannya.'
            ]);
        }
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->assetToReturn = null;
    }

    /**
     * Memproses pengembalian aset.
     */
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

        DB::transaction(function () {
            $user = $this->assetToReturn->holder()->first();

            // 1. Buat record transaksi pengembalian
            $returnTransaction = AssetTransaction::create([
                'transaction_number' => $this->generateTransactionNumber('RTN'),
                'transaction_type' => 'RETURN',
                'transaction_date' => now(),
                'from_user_id' => $user->id,
                'to_jobsite_id' => $this->assetToReturn->jobsite_id,
                'notes' => "Pengembalian aset {$this->assetToReturn->asset_number} oleh {$user->name}",
                'status' => 'Pending Inspection',
                'created_by_user_id' => Auth::id(),
            ]);

            // 2. Tambahkan item ke transaksi
            $returnTransaction->items()->create([
                'asset_id' => $this->assetToReturn->id,
                'condition_notes' => "Kondisi: {$this->returnCondition}. Catatan: {$this->returnNotes}",
            ]);

            // 3. Update status aset utama
            $this->assetToReturn->update([
                'status' => 'Pending Inspection',
            ]);
        });

        // Kirim event ke parent component untuk me-refresh daftar asetnya
        $this->dispatch('refreshMyAssets');
        
        session()->flash('message', "Aset {$this->assetToReturn->asset_number} berhasil diajukan untuk pengembalian.");
        $this->closeModal();
    }
    
    protected function generateTransactionNumber($typePrefix = 'TRX')
    {
        $prefix = "{$typePrefix}/" . date('Ym') . "/";
        $lastTransaction = AssetTransaction::where('transaction_number', 'LIKE', $prefix . '%')->orderBy('id', 'desc')->first();
        $nextSequence = 1;
        if ($lastTransaction) {
            $lastSequence = (int) substr($lastTransaction->transaction_number, -4);
            $nextSequence = $lastSequence + 1;
        }
        return $prefix . str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
    }

    public function render()
    {
        return view('livewire.modal.asset.pengembalian');
    }
}
