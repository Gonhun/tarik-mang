<?php

namespace App\Livewire\ApprovalWorkflows;

use Livewire\Component;
use App\Models\ApprovalWorkflow;
use App\Models\ApprovalWorkflowStep;
use App\Models\ApprovalWorkflowAssignment;
use App\Models\Department;
use App\Models\Position;
use App\Models\PositionLevel;
use App\Models\User;

class ManageWorkflows extends Component
{
    public $workflows;
    public ?ApprovalWorkflow $selectedWorkflow = null;
    public $allPositions = [];
    public $allDepartments = [];
    public $userDepartments = [];

    // Properti untuk form
    public $workflow_id, $workflow_name, $workflow_description,$new_step_user_id;
    public $new_step_position_id,$new_step_Department_id;

    public function mount()
    {
        $this->workflows = ApprovalWorkflow::orderBy('name')->get();
        // $this->allPositions = Position::all()->unique('name')->values();
        // $this->allPositions = Position::orderBy('name')->get();
        $this->allDepartments = Department::orderBy('name')->get();
        $this->allPositions = PositionLevel::orderBy('name')->get();
    }
    public function updatedNewStepUserId($value)
    {
        $this->new_step_user_id = $value;
        if(!$value)
        {
            $this->new_step_position_id =null;
        }
        $this->new_step_position_id = User::where('id',$value)->orderby('name')->first()?->position_level_id;
    }
    public function updatedNewStepDepartmentId($value)
    {
        $this->new_step_Department_id = $value;
        if(!$value){
            $this->userDepartments =[];
            $this->new_step_user_id=null;
        }
        $this->userDepartments = User::where('department_id',$value)->orderby('name')->get();
        
    }
    public function editWorkflow($workflowId)
    {
        $workflow = ApprovalWorkflow::find($workflowId);
        if ($workflow) {
            $this->workflow_id = $workflow->id;
            $this->workflow_name = $workflow->name;
            $this->workflow_description = $workflow->description;
            $this->selectWorkflow($workflowId); // Juga pilih workflow untuk menampilkan langkah-langkahnya
        }
    }
    public function selectWorkflow($workflowId)
    {
        $this->selectedWorkflow = ApprovalWorkflow::with('steps.approverPositionLevel')->find($workflowId);
    }

    public function saveWorkflow()
    {
        $this->validate(['workflow_name' => 'required|string|unique:approval_workflows,name,' . $this->workflow_id]);
        ApprovalWorkflow::updateOrCreate(
            ['id' => $this->workflow_id],
            ['name' => $this->workflow_name, 'description' => $this->workflow_description]
        );
        $this->mount(); // Refresh list
        $this->reset(['workflow_id', 'workflow_name', 'workflow_description']);
        session()->flash('message', 'Workflow berhasil disimpan.');
    }

    public function addStep()
    {
        $this->validate([
            'new_step_position_id' => 'required|exists:position_levels,id',
            'new_step_Department_id' => 'nullable|exists:departments,id',
            'new_step_user_id' => 'nullable|exists:users,id',
        ]);
        if ($this->selectedWorkflow) {
            $lastStepOrder = $this->selectedWorkflow->steps()->max('step_order') ?? 0;
            $this->selectedWorkflow->steps()->create([
                'step_order' => $lastStepOrder + 1,
                'user_id' => $this->new_step_user_id,
                'department_id' => $this->new_step_Department_id,
                'approver_position_level_id' => $this->new_step_position_id,
            ]);
            $this->selectWorkflow($this->selectedWorkflow->id); // Refresh
            $this->new_step_position_id = null;
            $this->new_step_Department_id = null;
            $this->new_step_user_id = null;
        }
    }

    public function removeStep($stepId)
    {
        ApprovalWorkflowStep::find($stepId)->delete();
        $this->selectWorkflow($this->selectedWorkflow->id); // Refresh
    }
    public function deleteWorkflow($workflowId)
    {
        // Cek apakah workflow ini masih digunakan oleh jabatan manapun
        $isUsed = ApprovalWorkflowAssignment::where('approval_workflow_id', $workflowId)->exists();

        if ($isUsed) {
            session()->flash('error', 'Gagal menghapus. Workflow ini masih digunakan dalam satu atau lebih Aturan Validasi.');
            return;
        }

        $workflow = ApprovalWorkflow::find($workflowId);
        if ($workflow) {
            $workflow->delete(); // Ini akan menghapus workflow dan steps-nya (karena cascade)

            // Jika workflow yang dihapus adalah yang sedang dipilih, reset pilihan
            if ($this->selectedWorkflow && $this->selectedWorkflow->id == $workflowId) {
                $this->selectedWorkflow = null;
            }

            $this->mount(); // Muat ulang daftar workflow
            session()->flash('message', 'Workflow berhasil dihapus.');
        }
    }
    public function render()
    {
        return view('livewire.approval-workflows.manage-workflows');
    }
}
