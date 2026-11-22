<?php

namespace App\Livewire\Modal\Asset;

use App\Models\Asset;
use App\Models\AssetDamageReport;
use App\Models\AssetTransaction;
use App\Support\AssetDisposal;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class Inspection extends Component
{
    public $isModalOpen = false;
    public ?AssetTransaction $transactionToInspect = null;
    public ?Asset $assetToInspect = null;

    // Form inspeksi
    public $inspection_outcome = ''; // good | repair | disposed
    public $inspection_notes = '';

    /** Peringatan bila workflow disposal belum siap (dicek saat memilih "Rusak berat"). */
    public $disposalWarning = null;

    protected $listeners = ['openInspectionModal' => 'loadTransaction'];

    public function updatedInspectionOutcome($value)
    {
        $this->disposalWarning = $value === 'disposed'
            ? \App\Support\Workflow::problem('DISPOSED', Auth::user(), $this->assetToInspect?->jobsite_id)
            : null;
    }

    protected function canInspect(): bool
    {
        $user = Auth::user();

        return $user->isItStaff() || $user->can('Master Asset');
    }

    public function loadTransaction($transactionId)
    {
        if (!$this->canInspect()) {
            $this->dispatch('swal:error', ['title' => 'Gagal', 'text' => 'Anda tidak berhak melakukan inspeksi aset.']);
            return;
        }

        $transaction = AssetTransaction::with(['items.asset.assetType', 'items.asset.assetBrand', 'items.asset.assetSeries', 'fromUser:id,name'])
            ->where('transaction_type', 'RETURN')
            ->find($transactionId);

        $asset = $transaction?->items->first()?->asset;

        if (!$transaction || $transaction->status !== 'Pending Inspection' || !$asset) {
            $this->dispatch('swal:error', ['title' => 'Gagal', 'text' => 'Transaksi pengembalian tidak ditemukan atau sudah diinspeksi.']);
            return;
        }

        $this->transactionToInspect = $transaction;
        $this->assetToInspect = $asset;
        $this->reset(['inspection_outcome', 'inspection_notes', 'disposalWarning']);
        $this->resetValidation();
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->transactionToInspect = null;
        $this->assetToInspect = null;
    }

    public function submitInspection()
    {
        if (!$this->assetToInspect || !$this->transactionToInspect) return;
        abort_unless($this->canInspect(), 403);

        // Catatan wajib hanya bila ada masalah (perbaikan / disposal); kondisi baik boleh kosong.
        $this->validate([
            'inspection_outcome' => 'required|in:good,repair,disposed',
            'inspection_notes' => $this->inspection_outcome === 'good'
                ? 'nullable|string|max:2000'
                : 'required|string|min:10|max:2000',
        ], [
            'inspection_outcome.required' => 'Hasil inspeksi wajib dipilih.',
            'inspection_notes.required' => 'Catatan wajib diisi untuk aset yang perlu perbaikan atau rusak berat.',
            'inspection_notes.min' => 'Catatan inspeksi minimal 10 karakter.',
        ]);
        $this->inspection_notes = trim((string) $this->inspection_notes);

        // Ambil ulang: cegah inspeksi ganda bila dua orang membuka modal yang sama.
        $transaction = AssetTransaction::find($this->transactionToInspect->id);
        $asset = Asset::find($this->assetToInspect->id);
        if (!$transaction || $transaction->status !== 'Pending Inspection' || !$asset) {
            $this->dispatch('swal:error', ['title' => 'Gagal', 'text' => 'Transaksi ini sudah diinspeksi oleh orang lain.']);
            $this->closeModal();
            $this->dispatch('refreshAssetList');
            return;
        }

        $user = Auth::user();

        try {
            $resultText = DB::transaction(function () use ($transaction, $asset, $user) {
                $extra = '';

                switch ($this->inspection_outcome) {
                    case 'good':
                        $newStatus = 'Free';
                        break;

                    case 'repair':
                        $newStatus = 'Repair';
                        // Laporan kerusakan otomatis, dengan status yang dikenali halaman Laporan Kerusakan.
                        $report = AssetDamageReport::create([
                            'asset_id' => $asset->id,
                            'report_date' => now(),
                            'damage_description' => 'Kerusakan ditemukan saat inspeksi pengembalian ' . $transaction->transaction_number . ': ' . $this->inspection_notes,
                            'damage_status' => 'Under Repair',
                            'asset_status_before_report' => $asset->status,
                            'reported_by_user_id' => $user->id,
                            'created_by_user_id' => $user->id,
                        ]);
                        $extra = " Laporan kerusakan #{$report->id} dibuat.";
                        break;

                    case 'disposed':
                        $newStatus = 'Pre Disposed';
                        // Bila disposal ditolak, aset kembali ke Repair tanpa pemegang (bukan ke Pending Inspection).
                        $disposal = AssetDisposal::create(
                            $asset,
                            $user,
                            'Disposal dari inspeksi pengembalian ' . $transaction->transaction_number . ': ' . $this->inspection_notes,
                            'DAMAGED',
                            ['previous_status' => 'Repair', 'previous_holder_user_id' => null]
                        );
                        $extra = " Pengajuan disposal {$disposal->transaction_number} menunggu approval.";
                        break;
                }

                $asset->update([
                    'status' => $newStatus,
                    'holder_user_id' => null,
                ]);
                if (isset($disposal)) {
                    AssetDisposal::finishIfAutoApproved($disposal);
                }

                $transaction->update([
                    'status' => 'Completed',
                    'to_user_id' => $user->id,
                    'notes' => trim(($transaction->notes ?? '') . "\n\nHasil Inspeksi: "
                        . ['good' => 'Kondisi baik', 'repair' => 'Perlu perbaikan', 'disposed' => 'Rusak berat'][$this->inspection_outcome]
                        . ($this->inspection_notes !== '' ? '. Catatan: ' . $this->inspection_notes : '')),
                ]);

                Log::info("Inspeksi transaksi #{$transaction->id} selesai. Aset #{$asset->id} -> {$newStatus}.");

                return "Aset {$asset->asset_number} sekarang berstatus {$newStatus}." . $extra;
            });
        } catch (\RuntimeException $e) {
            // Mis. alur approval disposal belum dikonfigurasi: tidak ada data yang tersimpan.
            $this->addError('inspection_outcome', $e->getMessage());
            return;
        } catch (\Throwable $e) {
            Log::error("Inspeksi transaksi #{$transaction->id} gagal: " . $e->getMessage());
            $this->dispatch('swal:error', ['title' => 'Gagal', 'text' => 'Hasil inspeksi gagal disimpan. Coba lagi atau hubungi Administrator.']);
            return;
        }

        $this->closeModal();
        $this->dispatch('refreshAssetList');
        $this->dispatch('swal:success', ['title' => 'Inspeksi tersimpan', 'text' => $resultText]);
    }

    public function render()
    {
        return view('livewire.modal.asset.inspection');
    }
}
