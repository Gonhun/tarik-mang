<?php

namespace App\Livewire\AssetMutations;

use App\Mail\MailDisposeRequest;
use Livewire\Component;
use App\Models\AssetTransaction;
use App\Models\AssetTransactionApproval;
use App\Models\Asset;
use App\Models\User;
use App\Models\CostCenter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\WithFileUploads;
use Mail;

class ShowAssetTransaction extends Component
{
    use WithFileUploads;

    public AssetTransaction $transaction; // Route model binding

    // Properti untuk Modal Approval
    public $isApprovalModalOpen = false;
    public $currentApprovalId = null;
    public $approval_action = ''; // 'approve' atau 'reject'
    public $approval_notes = '';

    // Properti khusus untuk modal approval IT
    public $it_fulfillment_type = 'new'; // 'new' atau 'stock'
    public $available_stock_assets = [];
    public $it_selected_stock_asset_id = null;
    public $destinationCostCenter,$fromCostCenter;

    


    /**
     * Mount komponen dan muat semua data relasi yang diperlukan.
     */
    public function mount(AssetTransaction $transaction)
    {
        $this->transaction = $transaction->load([
            'fromUser:id,name,nik', 'toUser:id,name,nik', 'creator:id,name,department_id',
            'fromJobsite:id,name', 'toJobsite:id,name',
            'items.asset.assetType', 'items.asset.assetBrand', 'items.asset.assetSeries',
            'approvals.approver:id,name', 'approvals.actionBy:id,name'
        ]);
       
        if ($this->transaction->transaction_type === 'MUTATION' && $this->transaction->to_jobsite_id && $this->transaction->from_jobsite_id && $this->transaction->creator->department_id) {
            // Asumsi sederhana: mengambil Cost Center pertama yang terkait dengan jobsite tujuan.
            // Jika satu jobsite bisa punya banyak cost center, Anda mungkin perlu logika yang lebih spesifik.
            $this->destinationCostCenter = CostCenter::where('jobsite_id', $this->transaction->to_jobsite_id)
                                                       ->where('department_id', $this->transaction->creator->department_id)
                                                       ->first();
            $this->fromCostCenter = CostCenter::where('jobsite_id', $this->transaction->from_jobsite_id)->where('department_id', $this->transaction->creator->department_id)->first();
        }
        
    //   
    }
    
    /**
     * Memeriksa apakah user yang login saat ini berhak melakukan aksi approval
     * pada tahapan tertentu.
     */
    public function canApprove(AssetTransactionApproval $approval): bool
    {
        // Kondisi 1: Tahap ini harus 'Pending'.
        // Kondisi 2: User yang login harus sama dengan user approver yang ditunjuk.
        return $approval->status === 'Pending' && Auth::id() == $approval->approver_user_id;
    }

    /**
     * Lifecycle hook untuk mereset pilihan saat modal ditutup.
     */
    public function updatedIsApprovalModalOpen($value)
    {
        if (!$value) {
            $this->resetApprovalForm();
        }
    }

    /**
     * Lifecycle hook untuk memuat aset stok saat IT memilih "Ambil dari Stok".
     */
    // public function updatedItFulfillmentType($value)
    // {
    //     $this->it_selected_stock_asset_id = null;

    //     if ($value === 'stock') {
    //         $firstItem = $this->transaction->items->first();
    //         if ($firstItem && $firstItem->material_code_id) {
    //             $material = \App\Models\MaterialCode::find($firstItem->material_code_id);
    //             if ($material) {
    //                 $this->available_stock_assets = Asset::where('asset_type_id', $material->asset_type_id)
    //                                                   ->where('status', 'Free')
    //                                                   ->with('jobsite:id,name')
    //                                                   ->get();
    //             }
    //         } else {
    //             $this->available_stock_assets = [];
    //         }
    //     } else {
    //         $this->available_stock_assets = [];
    //     }
    // }
    
    public function openApprovalModal(int $approvalId, string $action)
    {
        $approval = AssetTransactionApproval::find($approvalId);
        if (!$approval || !$this->canApprove($approval)) {
            session()->flash('error', 'Anda tidak berhak melakukan aksi ini atau tahapan tidak valid.');
            return;
        }

        $this->currentApprovalId = $approvalId;
        $this->approval_action = $action;
        $this->isApprovalModalOpen = true;
    }
    
    public function closeApprovalModal() { $this->isApprovalModalOpen = false; }
    
    private function resetApprovalForm()
    {
        $this->reset(['approval_notes', 'currentApprovalId', 'approval_action', 'it_fulfillment_type', 'it_selected_stock_asset_id']);
        $this->available_stock_assets = [];
        $this->resetValidation();
    }
    
    public function submitApproval()
    {
        $approval = AssetTransactionApproval::with('transaction.items')->find($this->currentApprovalId);
        if (!$approval || !$this->canApprove($approval)) {
            session()->flash('error', 'Aksi tidak diizinkan.');
            $this->closeApprovalModal();
            return;
        }

        $this->validate([
            'approval_notes' => ($this->approval_action === 'reject' ? 'required|string|min:10' : 'nullable|string'),
            // Validasi khusus untuk pemenuhan IT
            'it_fulfillment_type' => ($approval->approver_role_name === 'Kasi IT' && $this->approval_action === 'approve') ? 'required|in:new,stock' : 'nullable',
            'it_selected_stock_asset_id' => ($this->it_fulfillment_type === 'stock' ? 'required|exists:assets,id' : 'nullable'),
        ]);

        DB::transaction(function () use ($approval) {
            // 1. Update record approval saat ini
            $approval->update([
                'status' => ($this->approval_action === 'approve' ? 'Approved' : 'Rejected'),
                'action_at' => now(),
                'action_by_user_id' => Auth::id(),
                'notes' => $this->approval_notes,
            ]);

            $transaction = $approval->transaction;
            $finalAction = 'continue'; // Default action is to continue the workflow

            // 2. Tentukan status transaksi utama selanjutnya
            if ($this->approval_action === 'reject') {
                $transaction->status = 'Rejected';
                $finalAction = 'notify_requester_rejected';
            } else { // Jika 'approve'
                
                // Cek apakah ini tahap IT dengan logika khusus
                if ($approval->approver_role_name === 'Kasi IT' && $this->it_fulfillment_type === 'stock') {
                    // Logika untuk pemenuhan dari stok, ini mungkin akan mengakhiri alur
                    // Anda perlu memindahkan logika 'fulfillFromLocalStock' dan 'initiateAssetMutation' ke sini
                    // dan mengaturnya untuk mengembalikan status final.
                    // Untuk contoh ini, kita asumsikan 'stock' langsung 'Completed'.
                    $transaction->status = 'Completed'; 
                    // Kaitkan aset stok ke item permintaan
                    $firstItem = $transaction->items->first();
                    $firstItem->update(['assigned_asset_id' => $this->it_selected_stock_asset_id]);
                    // Update status aset stok
                    Asset::find($this->it_selected_stock_asset_id)->update(['status' => 'Used', 'holder_user_id' => $transaction->requester_user_id]);
                    $finalAction = 'notify_requester_completed_from_stock';
                } else {
                    // Logika approval standar: cari tahap berikutnya
                    $nextApproval = AssetTransactionApproval::where('asset_transaction_id', $transaction->id)
                        ->where('status', 'Pending')->orderBy('step_order', 'asc')->first();

                    if ($nextApproval) {
                        $transaction->status = 'Pending Approval' ;
                        $finalAction = 'notify_next_approver';
                    } else {
                       if($transaction->transaction_type === 'DISPOSED'){
                        $transaction->status = 'Completed';
                        $finalAction = 'process_and_notify_requester_approved';
                       }else{
                        $transaction->status = 'Pending Release';
                        $finalAction = 'process_and_notify_requester_approved';
                       }
                    }
                }
            }

            $transaction->save();
            
            // 3. Jalankan aksi setelahnya (misal: notifikasi) di luar transaksi DB jika memungkinkan
            // Di sini kita jalankan di dalam untuk kesederhanaan, tapi pertimbangkan Queue untuk notifikasi
            switch($finalAction){
                case 'notify_next_approver':
                    $this->sendNotificationToApprover($nextApproval);
                    break;
                case 'process_and_notify_requester_approved':
                    $this->processFullyApprovedRequest($transaction);
                    $this->sendNotificationToRequester($transaction, 'Pengajuan Anda Telah Disetujui Sepenuhnya');
                    break;
                case 'notify_requester_rejected':
                    $this->processFullyApprovedRequest($transaction);
                     $this->sendNotificationToRequester($transaction, 'Pengajuan Anda Ditolak');

                    break;
                case 'notify_requester_completed_from_stock':
                     $this->sendNotificationToRequester($transaction, 'Pengajuan Anda Dipenuhi dari Stok');
                    break;
            }
        });

        session()->flash('message', 'Status pengajuan berhasil diperbarui.');
        $this->closeApprovalModal();
        $this->transaction->refresh(); // Muat ulang data
    }
    
    /**
     * Kirim notifikasi WhatsApp ke approver berikutnya.
     */
    protected function sendNotificationToApprover(AssetTransactionApproval $approval)
    {
        // Ambil data user approver
        $approver = $approval->approver;
        if (!$approver || !$approver->phone) {
            Log::warning('Gagal kirim WA: Approver tidak punya nomor telepon.', ['approval_id' => $approval->id]);
            return;
        }

        $transaction = $approval->transaction;
        $message = "Halo {$approver->name},\n\n"
            . "Ada pengajuan transaksi aset #{$transaction->transaction_number} yang membutuhkan persetujuan Anda.\n"
            . "Jenis: " . ucwords(strtolower(str_replace('_', ' ', $transaction->transaction_type))) . "\n"
            . "Tanggal: " . \Carbon\Carbon::parse($transaction->transaction_date)->format('d-m-Y') . "\n"
            . "Status: {$transaction->status}\n\n"
            . "Silakan login ke aplikasi AMS untuk melakukan approval."
            . "ams.binasaranasukses.co.id";

        $draftData = [
                'type' => 'OUTSTANDING_APPROVER',
                'approver' => $approval->approver->name,
                'draft' => $approval->request
            ];
            // dd($draftData['draft']);
            $isSuccess = Mail::Mailer('smtp2')->to($approval->approver->karyawans->Email)->send(new MailDisposeRequest($draftData));
            if ($isSuccess) {
                $this->dispatch('swal:success', [
                    'title' => 'Berhasil!',
                    'text' => "Pesan berhasil dikirim ke '{$approval->approver->name}'",
                ]);
            } else {
                $this->dispatch('swal:error', [
                    'title' => 'Error!',
                    'text' => "Gagal Mengirim Pesan Follow Up!",
                ]);
            }

            // sendMessageWithWhatsApp($approver->phone, $message);
    }

    /**
     * Kirim notifikasi WhatsApp ke pemohon/requester.
     */
    protected function sendNotificationToRequester(AssetTransaction $transaction, string $subject)
    {
        // Ambil data user pemohon/requester
        $requester = $transaction->creator;
        if (!$requester || !$requester->phone) {
            Log::warning('Gagal kirim WA: Requester tidak punya nomor telepon.', ['transaction_id' => $transaction->id]);
            return;
        }

        $message = "Halo {$requester->name},\n\n"
            . "{$subject}\n"
            . "Transaksi aset #{$transaction->transaction_number}\n"
            . "Jenis: " . ucwords(strtolower(str_replace('_', ' ', $transaction->transaction_type))) . "\n"
            . "Tanggal: " . \Carbon\Carbon::parse($transaction->transaction_date)->format('d-m-Y') . "\n"
            . "Status: {$transaction->status}\n\n"
            . "Cek detail di aplikasi AMS.";

            $draftData = [
                'type' => 'APPROVE',
                'author' => $transaction->fromUser->name,
                'approver' => $transaction->approvals->where('user_id', Auth::id()),
                'remarks' => $this->remarks,
                'draft' => $transaction->request
            ];
            // dd($draftData['draft']);
            $isSuccess = Mail::Mailer('smtp2')->to($transaction->request->user->karyawans->Email)->send(new MailDisposeRequest($draftData));

        // sendMessageWithWhatsApp($requester->phone, $message);
    }

    /**
     * Helper untuk mengirim WhatsApp (dummy, ganti dengan integrasi API WA sebenarnya).
     */
    
    protected function processFullyApprovedRequest(AssetTransaction $transaction) { 
        if ($transaction->status === 'Rejected') {
            // Jika transaksi di-reject, update status aset terkait (jika perlu)
            foreach ($transaction->items as $item) {
                if ($item->asset) {
                    // Misal: kembalikan status aset ke 'Free' jika sebelumnya 'Pending Mutation'
                    if ($item->asset->status === 'Pending Mutation' || $item->asset->status === 'Pre Disposed') {
                        $item->asset->status = 'Free';
                        $item->asset->save();
                    }
                }
            }
            // Kirim notifikasi ke pemohon bahwa transaksi ditolak
            // $this->sendNotificationToRequester($transaction, "Transaksi mutasi aset Anda telah DITOLAK.");
        } elseif ($transaction->status === 'Approved' || $transaction->status === 'Completed') {
            // Jika transaksi di-approve, update status aset terkait
            foreach ($transaction->items as $item) {
                if ($item->asset) {
                    if ($item->asset->status === 'Pending Mutation') {
                        $item->asset->jobsite_id = $transaction->to_jobsite_id;
                        $item->asset->status = 'Free';
                        $item->asset->save();
                    }elseif($item->asset->status === 'Pre Disposed'){
                        $item->asset->status = 'Disposed';
                        $item->asset->save();
                    }
                    // Misal: pindahkan aset ke jobsite tujuan dan update status
                   
                }
            }
            // Kirim notifikasi ke pemohon bahwa transaksi disetujui
            // $this->sendNotificationToRequester($transaction, "Transaksi mutasi aset Anda telah DISETUJUI dan diproses.");
        }
        $transaction->save();
        $transaction->refresh();
     }

    public function render()
    {
        return view('livewire.asset-mutations.show-asset-transaction');
    }
}
