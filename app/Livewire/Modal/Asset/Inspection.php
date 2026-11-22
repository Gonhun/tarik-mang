<?php

namespace App\Livewire\Modal\Asset;

use Livewire\Component;
use App\Models\Asset;
use App\Models\AssetTransaction;
use App\Models\AssetDamageReport; // Import jika Anda ingin membuat laporan kerusakan
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Inspection extends Component
{
    public $isModalOpen = false;
    public ?AssetTransaction $transactionToInspect = null;
    public ?Asset $assetToInspect = null;

    // Properti untuk form inspeksi
    public $inspection_outcome = ''; // 'good', 'repair', 'disposed'
    public $inspection_notes = '';

    protected $listeners = ['openInspectionModal' => 'loadTransaction'];

    public function loadTransaction($transactionId)
    {
        $this->transactionToInspect = AssetTransaction::with('items.asset')->find($transactionId);
        
        if ($this->transactionToInspect && $this->transactionToInspect->status === 'Pending Inspection') {
            // Asumsi satu transaksi pengembalian hanya untuk satu aset
            $this->assetToInspect = $this->transactionToInspect->items->first()->asset;
            $this->reset(['inspection_outcome', 'inspection_notes']);
            $this->resetValidation();
            $this->isModalOpen = true;
        } else {
            $this->dispatch('show-toast', ['type' => 'error', 'message' => 'Transaksi tidak ditemukan atau statusnya tidak valid.']);
        }
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->transactionToInspect = null;
        $this->assetToInspect = null;
    }

    public function submitInspection()
    {
        if (!$this->assetToInspect) return;

        $this->validate([
            'inspection_outcome' => 'required|in:good,repair,disposed',
            'inspection_notes' => 'required|string|min:10',
        ], [
            'inspection_outcome.required' => 'Hasil inspeksi wajib dipilih.',
            'inspection_notes.required' => 'Catatan inspeksi wajib diisi.',
        ]);

        DB::transaction(function () {
            $newAssetStatus = '';
            $logNotes = '';

            // Tentukan status aset baru berdasarkan hasil inspeksi
            switch ($this->inspection_outcome) {
                case 'good':
                    $newAssetStatus = 'Free'; // Kembali ke stok dan siap digunakan
                    $logNotes = "Aset dikembalikan dalam kondisi baik.";
                    break;
                case 'repair':
                    $newAssetStatus = 'Repair'; // Masuk ke status perbaikan
                    $logNotes = "Aset masuk perbaikan setelah inspeksi pengembalian.";
                    // Opsional: Otomatis buat laporan kerusakan
                    AssetDamageReport::create([
                        'asset_id' => $this->assetToInspect->id,
                        'report_date' => now(),
                        'damage_description' => "Kerusakan ditemukan saat inspeksi pengembalian: " . $this->inspection_notes,
                        'damage_status' => 'PendingAssessment', // atau 'UnderRepair'
                        'reported_by_user_id' => Auth::id(),
                    ]);
                    break;
                case 'disposed':
                    $newAssetStatus = 'Pre Disposed'; // Aset akan dihapusbukukan
                    $logNotes = "Aset dihapusbukukan setelah inspeksi pengembalian.";
                    $this->generateTransaction($this->assetToInspect);                
                    break;
            }

            // Update status aset
            $this->assetToInspect->update(['status' => $newAssetStatus,
            'holder_user_id' => null]);
            
            // Update status transaksi menjadi 'Completed'
            $this->transactionToInspect->update([
                'status' => 'Completed',
                'to_user_id' => Auth::id(),
                'notes' => ($this->transactionToInspect->notes ?? '') . "\n\nCatatan Inspeksi: " . $this->inspection_notes,
            ]);

            Log::info("Inspeksi untuk transaksi #{$this->transactionToInspect->id} selesai. Aset #{$this->assetToInspect->id} statusnya menjadi {$newAssetStatus}.");
        });

        session()->flash('message', "Hasil inspeksi untuk aset {$this->assetToInspect->asset_number} berhasil disimpan.");
        
        $this->closeModal();
        $this->dispatch('refreshParentComponent'); // Kirim event untuk refresh halaman induk
    }
    protected function generateTransaction(Asset $asset)
    {
       
        $user =Auth::user();
        $transactionNumber=generateMutationNumber('DPS');
        $workflow = findWorkflowForContext('DISPOSED', $user->position_id, $user->jobsite_id);
        if (!$workflow || $workflow->steps->isEmpty()) {
            throw new \Exception('Alur persetujuan belum didefinisikan. Hubungi Administrator.');
        }
        $transaction = AssetTransaction::create([
            'transaction_number' => $transactionNumber,
            'transaction_type' => 'DISPOSED',
            'transaction_date' => now(),
            'from_user_id' => $asset->holder_user_id,
            'notes' => $this->inspection_notes,
            'status' => 'Pending Approval', // Status awal disposal
            'created_by_user_id' => $user ? $user->id : null,
        ]);

        // Tambahkan item ke transaksi disposal
        $transaction->items()->create([
            'asset_id' => $asset->id,
            'condition_notes' => $this->inspection_notes,
        ]);
        // foreach ($workflow->steps as $step) {
        //     $approverUser = $this->findApproverForStep($step, $transaction);
        //     if ($approverUser) {
        //         $transaction->approvals()->create([
        //             'step_order' => $step->step_order,
        //             'approver_role_name' => $step->approverPosition->name ?? ($step->approverDepartment->name . ' - ' . $step->approverPositionLevel->name),
        //             'approver_user_id' => $approverUser->id,
        //             'status' => 'Pending',
        //         ]);
        //     }
        // }
        // $this->generateMutationApprovalSteps($transaction);        
    }
    protected function generateMutationApprovalSteps(AssetTransaction $transaction)
    {
      
        $kasiItUser = User::whereHas('position', fn($q) => $q->where('name', 'KADEP IT'))->first();
        $costControlUser = User::whereHas('position', fn($q) => $q->where('name', 'KASI CC'))->first();
        
        // Buat record untuk setiap tahap
        $transaction->approvals()->create([
            'step_order' => 1,
            'approver_role_name' => 'KADEP IT',
            'approver_user_id' => $kasiItUser ? $kasiItUser->id : null, // Fallback jika tidak ditemukan
            'status' => 'Pending',
        ]);
        
        $transaction->approvals()->create([
            'step_order' => 2,
            'approver_role_name' => 'Cost Control',
            'approver_user_id' => $costControlUser ? $costControlUser->id : null,
            'status' => 'Pending',
        ]);

      
        $transaction->status = 'Pending KADEP IT'; 
        $transaction->save();
        
        if ($kasiItUser) {
            $number = $kasiItUser->whatsapp;
            $msg = "Terdapat permintaan persetujuan untuk penghapusan aset. Silakan cek dan lakukan persetujuan pada sistem.";
            $success = sendMessageWithWhatsApp($number,$msg);
            if($success)
            {
                $this->dispatch('swal:success', [
                    'title' => 'Berhasil!',
                    'text' => 'Pesan di kirim ke '. $kasiItUser->name .' 🙏',
                ]);
            }
        }
    }
    public function render()
    {
        return view('livewire.modal.asset.inspection');
    }
}
