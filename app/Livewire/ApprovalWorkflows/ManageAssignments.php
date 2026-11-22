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
    public $transactionTypes = [
        'REQUEST_ASSET',
        'DISPOSED',
        'MUTATION',
        'REPAIR',
        'REQUEST_LICENSE'
    ];

    public $isModalOpen = false;
    public $search = '';
    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        // Muat data master sekali untuk dropdown
        $this->allWorkflows = ApprovalWorkflow::where('is_active', true)->orderBy('name')->get();
        $this->allPositions = Position::orderBy('name')->get();
        $this->allJobsites = Jobsite::orderBy('name')->get();
    }

    protected function rules()
    {
        return [
            'transaction_type' => 'required|string',
            'approval_workflow_id' => 'required|exists:approval_workflows,id',
            'position_id' => 'nullable|exists:positions,id',
            'jobsite_id' => 'nullable|exists:jobsites,id',
            'priority' => 'required|integer',
        ];
    }

    public function render()
    {
        $query = ApprovalWorkflowAssignment::with(['position', 'jobsite', 'approvalWorkflow']);

        if (!empty($this->search)) {
            $query->where('transaction_type', 'like', '%' . $this->search . '%');
        }

        $assignments = $query->orderBy('priority', 'desc')->orderBy('transaction_type')->paginate(10);

        return view('livewire.approval-workflows.manage-assignments', [
            'assignments' => $assignments,
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
