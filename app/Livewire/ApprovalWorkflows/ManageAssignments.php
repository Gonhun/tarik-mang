<?php

namespace App\Livewire\ApprovalWorkflows;

use Livewire\Component;
use App\Models\ApprovalWorkflowAssignment;
use App\Models\ApprovalWorkflow;
use App\Models\Position;
use App\Models\Jobsite;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;

class ManageAssignments extends Component
{
    use WithPagination;

    // Properti untuk form modal
    public $assignment_id;
    public $transaction_type, $position_id, $jobsite_id, $approval_workflow_id;
    public $priority = 0;

    // Data untuk mengisi dropdown di form
    public $allWorkflows = [];
    public $allPositions = [];
    public $allJobsites = [];
    /** Kode jenis transaksi dari master (config/transaction_types.php). */
    public $transactionTypes = [];

    /** Hasil perbaikan massal terakhir: [['number' => .., 'ok' => bool, 'message' => ..], ...] */
    public $repairReport = [];

    // Uji coba workflow
    public $testType = 'REQUEST_ASSET';
    public $testJobsiteId = '';
    public $testPositionId = '';

    public $isModalOpen = false;
    public $search = '';
    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $this->transactionTypes = array_keys(\App\Support\Workflow::types());
        // Muat data master sekali untuk dropdown
        $this->allWorkflows = ApprovalWorkflow::where('is_active', true)->orderBy('name')->get();
        $this->allPositions = Position::orderBy('name')->get();
        $this->allJobsites = Jobsite::orderBy('name')->get();
    }

    protected function rules()
    {
        return [
            // Aturan baru hanya untuk jenis aktif; saat edit, jenis lama tetap boleh dipertahankan.
            'transaction_type' => ['required', Rule::in(array_unique(array_merge(
                array_keys(\App\Support\Workflow::types(true)),
                $this->assignment_id ? [ApprovalWorkflowAssignment::find($this->assignment_id)?->transaction_type] : []
            )))],
            'priority' => 'required|integer|min:0|max:100',
            'approval_workflow_id' => 'required|exists:approval_workflows,id',
            'position_id' => 'nullable|exists:positions,id',
            'jobsite_id' => 'nullable|exists:jobsites,id',
        ];
    }

    // ------------------------------------------------------------------
    // Perbaikan transaksi yang tertahan tanpa approver
    // ------------------------------------------------------------------

    protected function repairTransaction(\App\Models\AssetTransaction $trx): array
    {
        try {
            $plan = \App\Support\TransactionApprovalRepair::applyWorkflow($trx, \Illuminate\Support\Facades\Auth::user());
            return ['number' => $trx->transaction_number, 'ok' => true, 'message' => count($plan) . ' tahap · menunggu ' . $plan[0]['user']->name];
        } catch (\RuntimeException $e) {
            return ['number' => $trx->transaction_number, 'ok' => false, 'message' => $e->getMessage()];
        }
    }

    public function repairOne($transactionId)
    {
        abort_unless(\App\Support\TransactionApprovalRepair::canManage(\Illuminate\Support\Facades\Auth::user()), 403);
        $trx = \App\Models\AssetTransaction::find($transactionId);
        if ($trx) {
            $this->repairReport = [$this->repairTransaction($trx)];
        }
    }

    /** Perbaiki maks. 25 transaksi per klik (aman dari timeout). */
    public function repairBatch()
    {
        abort_unless(\App\Support\TransactionApprovalRepair::canManage(\Illuminate\Support\Facades\Auth::user()), 403);
        $this->repairReport = [];
        $candidates = \App\Support\TransactionApprovalRepair::stuckQuery()
            ->whereDoesntHave('approvals', fn ($q) => $q->whereIn('status', ['Approved', 'Rejected']))
            ->orderBy('id')->limit(25)->get();
        foreach ($candidates as $trx) {
            $this->repairReport[] = $this->repairTransaction($trx);
        }
        if (!$candidates->count()) {
            $this->repairReport = [['number' => '-', 'ok' => true, 'message' => 'Tidak ada transaksi yang bisa diperbaiki otomatis.']];
        }
    }

    /**
     * Hasil uji coba: workflow & approver yang akan dipakai, atau pesan kenapa pengajuan akan ditolak.
     */
    protected function testResult(): array
    {
        $jobsiteId = $this->testJobsiteId !== '' ? (int) $this->testJobsiteId : null;
        $positionId = $this->testPositionId !== '' ? (int) $this->testPositionId : null;
        $fallbacks = \App\Support\Workflow::fallbacks($this->testType);

        try {
            $workflow = \App\Support\Workflow::resolve($this->testType, $positionId, $jobsiteId, $fallbacks);
            $steps = \App\Support\Workflow::approvers($workflow, $jobsiteId, $this->testType);
            return ['ok' => true, 'workflow' => $workflow, 'steps' => $steps];
        } catch (\App\Support\WorkflowNotConfigured $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    public function render()
    {
        $query = ApprovalWorkflowAssignment::with(['position', 'jobsite', 'approvalWorkflow']);

        if (!empty($this->search)) {
            $query->where('transaction_type', 'like', '%' . $this->search . '%');
        }

        $assignments = $query->orderBy('priority', 'desc')->orderBy('transaction_type')->paginate(10);

        // Kesiapan tiap jenis: jumlah aturan & apakah ada aturan default (semua jabatan + semua site).
        $readiness = collect($this->transactionTypes)->mapWithKeys(function ($type) {
            $rules = ApprovalWorkflowAssignment::where('transaction_type', $type)
                ->whereIn('approval_workflow_id', \App\Models\ApprovalWorkflowStep::select('approval_workflow_id'))
                ->get(['position_id', 'jobsite_id']);
            return [$type => [
                'rules' => $rules->count(),
                'hasDefault' => $rules->contains(fn ($r) => !$r->position_id && !$r->jobsite_id),
            ]];
        });

        // Transaksi tertahan (tanpa tahapan / tahap tanpa approver)
        $stuckBase = \App\Support\TransactionApprovalRepair::stuckQuery();
        $stuckTotal = (clone $stuckBase)->count();
        $stuckAuto = (clone $stuckBase)->whereDoesntHave('approvals', fn ($q) => $q->whereIn('status', ['Approved', 'Rejected']))->count();
        $stuckList = (clone $stuckBase)
            ->with(['fromJobsite:id,code', 'creator:id,name', 'items.asset:id,asset_number'])
            ->withCount(['approvals', 'approvals as decided_count' => fn ($q) => $q->whereIn('status', ['Approved', 'Rejected'])])
            ->orderByDesc('id')->limit(10)->get();

        return view('livewire.approval-workflows.manage-assignments', [
            'assignments' => $assignments,
            'stuckTotal' => $stuckTotal,
            'stuckAuto' => $stuckAuto,
            'stuckList' => $stuckList,
            'canRepair' => \App\Support\TransactionApprovalRepair::canManage(\Illuminate\Support\Facades\Auth::user()),
            'readiness' => $readiness,
            'test' => $this->testResult(),
            'typeLabels' => \App\Support\Workflow::labels(),
        ]);
    }

    public function create()
    {
        $this->resetInputFields();
        $this->openModal();
    }

    public function edit($id)
    {
        $assignment = ApprovalWorkflowAssignment::findOrFail($id);
        $this->assignment_id = $id;
        $this->transaction_type = $assignment->transaction_type;
        $this->position_id = $assignment->position_id;
        $this->jobsite_id = $assignment->jobsite_id;
        $this->approval_workflow_id = $assignment->approval_workflow_id;
        $this->priority = $assignment->priority;
        $this->openModal();
    }

    public function store()
    {
        $this->validate();

        // Workflow harus punya tahapan; aturan tidak boleh duplikat (jenis + jabatan + site sama).
        if (!\App\Models\ApprovalWorkflowStep::where('approval_workflow_id', $this->approval_workflow_id)->exists()) {
            $this->addError('approval_workflow_id', 'Workflow ini belum punya tahapan approval. Tambahkan tahapan dulu di menu Create Step.');
            return;
        }
        $duplicate = ApprovalWorkflowAssignment::where('transaction_type', $this->transaction_type)
            ->where('position_id', $this->position_id ?: null)
            ->where('jobsite_id', $this->jobsite_id ?: null)
            ->when($this->assignment_id, fn ($q) => $q->where('id', '!=', $this->assignment_id))
            ->exists();
        if ($duplicate) {
            $this->addError('transaction_type', 'Sudah ada aturan untuk jenis, jabatan, dan site yang sama. Edit aturan tersebut.');
            return;
        }

        ApprovalWorkflowAssignment::updateOrCreate(['id' => $this->assignment_id], [
            'transaction_type' => $this->transaction_type,
            'position_id' => $this->position_id ?: null,
            'jobsite_id' => $this->jobsite_id ?: null,
            'approval_workflow_id' => $this->approval_workflow_id,
            'priority' => $this->priority,
        ]);

        session()->flash('message', $this->assignment_id ? 'Aturan validasi berhasil diperbarui.' : 'Aturan validasi baru berhasil ditambahkan.');
        $this->closeModal();
    }

    public function delete($id)
    {
        ApprovalWorkflowAssignment::find($id)->delete();
        session()->flash('message', 'Aturan validasi berhasil dihapus.');
    }

    // --- Method Helper ---
    public function openModal() { $this->isModalOpen = true; }
    public function closeModal() { $this->isModalOpen = false; }
    private function resetInputFields()
    {
        $this->reset();
        $this->mount(); // Panggil mount lagi untuk memastikan data dropdown ter-load
    }
}
