<?php

namespace App\Livewire\AssetMutations;

use Livewire\Component;
use App\Models\AssetTransaction;
use App\Services\TransactionApprovalService;
use App\Models\AssetTransactionApproval;
use App\Models\Asset;
use App\Models\User;
use App\Models\CostCenter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\WithFileUploads;

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

    // Perbaikan approval (admin): ganti approver satu tahap
    public $reassignApprovalId = null;
    public $reassignSearch = '';
    public $reassignUserId = null;

    


    /**
     * Mount komponen dan muat semua data relasi yang diperlukan.
     */
    public function mount(AssetTransaction $transaction)
    {
        $this->transaction = $transaction->load([
            'fromUser:id,name,nik', 'toUser:id,name,nik', 'creator:id,name,department_id,whatsapp',
            'fromJobsite:id,name', 'toJobsite:id,name',
            'items.asset.assetType', 'items.asset.assetBrand', 'items.asset.assetSeries',
            'items.asset.jobsite:id,code,name', 'items.asset.holder:id,name,nik',
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
        // Approval harus milik transaksi yang sedang dibuka; aturan giliran & approver ada di service (sama dengan API).
        return (int) $approval->asset_transaction_id === (int) $this->transaction->id
            && app(TransactionApprovalService::class)->canDecide($approval, Auth::user());
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
        if (!in_array($action, ['approve', 'reject'], true)) {
            return;
        }
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

        // Pemenuhan IT dari stok (tahap "Kasi IT") tidak lagi dipakai di workflow transaksi.
        if ($approval->approver_role_name === 'Kasi IT' && $this->it_fulfillment_type === 'stock') {
            session()->flash('error', 'Pemenuhan dari stok tidak tersedia di tahap ini.');
            return;
        }

        app(TransactionApprovalService::class)->decide($approval, Auth::user(), $this->approval_action, $this->approval_notes);

        session()->flash('message', 'Status pengajuan berhasil diperbarui.');
        $this->closeApprovalModal();
        $this->transaction->refresh(); // Muat ulang data
    }
    
    // ------------------------------------------------------------------
    // Perbaikan approval transaksi yang sudah berjalan (khusus admin)
    // ------------------------------------------------------------------

    public function applyCurrentWorkflow()
    {
        try {
            $plan = \App\Support\TransactionApprovalRepair::applyWorkflow($this->transaction, Auth::user());
        } catch (\RuntimeException $e) {
            $this->dispatch('swal:error', ['title' => 'Tidak bisa diterapkan', 'text' => $e->getMessage()]);
            return;
        }
        $this->transaction->refresh();
        $this->dispatch('swal:success', [
            'title' => 'Approval diperbarui',
            'text' => count($plan) . ' tahap dibuat. Menunggu ' . $plan[0]['user']->name . ' (notifikasi WhatsApp dikirim bila nomor tersedia).',
        ]);
    }

    public function openReassign($approvalId)
    {
        abort_unless(\App\Support\TransactionApprovalRepair::canManage(Auth::user()), 403);
        $approval = AssetTransactionApproval::where('asset_transaction_id', $this->transaction->id)->find($approvalId);
        if (!$approval || $approval->status !== 'Pending') {
            return;
        }
        $this->reassignApprovalId = $approval->id;
        $this->reassignUserId = null;
        $this->reassignSearch = '';
        $this->resetValidation();
    }

    public function closeReassign()
    {
        $this->reset(['reassignApprovalId', 'reassignUserId', 'reassignSearch']);
    }

    public function chooseReassignUser($userId)
    {
        $this->reassignUserId = (int) $userId;
        $this->reassignSearch = '';
    }

    public function submitReassign()
    {
        $this->validate(['reassignUserId' => 'required|exists:users,id'], ['reassignUserId.required' => 'Pilih approver pengganti.']);
        $approval = AssetTransactionApproval::where('asset_transaction_id', $this->transaction->id)->find($this->reassignApprovalId);
        if (!$approval) {
            return;
        }

        try {
            \App\Support\TransactionApprovalRepair::reassign($approval, User::findOrFail($this->reassignUserId), Auth::user());
        } catch (\RuntimeException $e) {
            $this->dispatch('swal:error', ['title' => 'Tidak bisa diganti', 'text' => $e->getMessage()]);
            return;
        }

        $name = User::find($this->reassignUserId)->name;
        $this->closeReassign();
        $this->transaction->refresh();
        $this->dispatch('swal:success', ['title' => 'Approver diganti', 'text' => "Tahap {$approval->step_order} sekarang ditangani {$name}."]);
    }

    /**
     * Pesan bila transaksi yang masih berjalan tidak punya approver lengkap (data lama / workflow belum diatur).
     */
    protected function approvalProblem(): ?string
    {
        if (in_array($this->transaction->status, ['Completed', 'Rejected', 'Cancelled', 'Pending Release', 'Delivery', 'Pending Inspection'], true)) {
            return null;
        }
        $approvals = $this->transaction->approvals()->get(['id', 'status', 'approver_user_id', 'step_order']);
        if ($approvals->isEmpty()) {
            return 'Transaksi ini tidak memiliki tahapan approval (workflow belum diatur saat dibuat). ' . \App\Support\Workflow::ADMIN_HINT;
        }
        $orphan = $approvals->where('status', 'Pending')->whereNull('approver_user_id')->sortBy('step_order')->first();
        if ($orphan) {
            return "Tahap {$orphan->step_order} belum punya approver, sehingga transaksi tidak bisa disetujui. " . \App\Support\Workflow::ADMIN_HINT;
        }
        return null;
    }

    public function render()
    {
        $this->transaction->load(['approvals.approver:id,name', 'approvals.actionBy:id,name']);
        $approvals = $this->transaction->approvals->sortBy('step_order')->values();
        $current = $approvals->firstWhere('status', 'Pending');

        // Panel perbaikan approval untuk admin.
        $canRepair = \App\Support\TransactionApprovalRepair::canManage(Auth::user())
            && in_array($this->transaction->transaction_type, ['DISPOSED', 'MUTATION'], true)
            && !in_array($this->transaction->status, \App\Support\TransactionApprovalRepair::CLOSED_STATUSES, true);
        $repairPlan = null;
        $repairPlanError = null;
        if ($canRepair && \App\Support\TransactionApprovalRepair::canReapply($this->transaction)) {
            try {
                $repairPlan = \App\Support\TransactionApprovalRepair::plan($this->transaction);
            } catch (\App\Support\WorkflowNotConfigured $e) {
                $repairPlanError = $e->getMessage();
            }
        }
        $reassignOptions = collect();
        if ($this->reassignApprovalId && strlen(trim($this->reassignSearch)) >= 2) {
            $term = '%' . trim($this->reassignSearch) . '%';
            $reassignOptions = User::with('jobsite:id,code')
                ->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('nik', 'like', $term))
                ->orderBy('name')->limit(8)->get(['id', 'name', 'nik', 'jobsite_id']);
        }

        return view('livewire.asset-mutations.show-asset-transaction', [
            'approvalProblem' => $this->approvalProblem(),
            'canRepair' => $canRepair,
            'repairPlan' => $repairPlan,
            'repairPlanError' => $repairPlanError,
            'reassignOptions' => $reassignOptions,
            'reassignUser' => $this->reassignUserId ? User::find($this->reassignUserId, ['id', 'name', 'nik']) : null,
            'approvals' => $approvals,
            'currentApproval' => in_array($this->transaction->status, \App\Support\TransactionApprovalRepair::CLOSED_STATUSES, true) ? null : $current,
            'approvalsNotNeeded' => in_array($this->transaction->status, \App\Support\TransactionApprovalRepair::CLOSED_STATUSES, true),
            // Hanya approver yang sedang giliran yang mendapat tombol (dicek ulang di server saat submit).
            'myTurnApprovalId' => $current && $this->canApprove($current) ? $current->id : null,
            'modalApproval' => $this->isApprovalModalOpen && $this->currentApprovalId ? $approvals->firstWhere('id', $this->currentApprovalId) : null,
        ]);
    }
}
