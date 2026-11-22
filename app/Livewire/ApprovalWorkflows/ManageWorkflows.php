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

    /** Site untuk pratinjau approver tiap tahap. */
    public $previewJobsiteId = '';

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
        $this->selectedWorkflow = ApprovalWorkflow::with(['steps.approverPositionLevel', 'steps.user', 'steps.department'])->find($workflowId);
        if ($this->previewJobsiteId === '' ) {
            $this->previewJobsiteId = (string) (\Illuminate\Support\Facades\Auth::user()->jobsite_id ?? '');
        }
    }

    /**
     * Rapikan nomor tahap menjadi 1..n sesuai urutan sekarang (tidak memengaruhi approval yang sudah dibuat).
     */
    protected function renumberSteps(): void
    {
        if (!$this->selectedWorkflow) return;
        $order = 1;
        foreach (ApprovalWorkflowStep::where('approval_workflow_id', $this->selectedWorkflow->id)->orderBy('step_order')->orderBy('id')->get() as $step) {
            if ((int) $step->step_order !== $order) {
                $step->update(['step_order' => $order]);
            }
            $order++;
        }
    }

    public function moveStep($stepId, $direction)
    {
        if (!$this->selectedWorkflow) return;
        $this->renumberSteps();
        $step = ApprovalWorkflowStep::where('approval_workflow_id', $this->selectedWorkflow->id)->find($stepId);
        if (!$step) return;
        $target = $direction === 'up' ? $step->step_order - 1 : $step->step_order + 1;
        $other = ApprovalWorkflowStep::where('approval_workflow_id', $this->selectedWorkflow->id)->where('step_order', $target)->first();
        if ($other) {
            \Illuminate\Support\Facades\DB::transaction(function () use ($step, $other) {
                // Ada unique index (workflow, step_order): pakai nomor sementara agar tidak bentrok saat menukar.
                [$a, $b] = [$step->step_order, $other->step_order];
                $step->update(['step_order' => 1000 + $step->id]);
                $other->update(['step_order' => $a]);
                $step->update(['step_order' => $b]);
            });
        }
        $this->selectWorkflow($this->selectedWorkflow->id);
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
        // Tahap harus menunjuk user tertentu ATAU level jabatan (agar approver bisa dicari).
        $this->validate([
            'new_step_position_id' => 'required_without:new_step_user_id|nullable|exists:position_levels,id',
            'new_step_Department_id' => 'nullable|exists:departments,id',
            'new_step_user_id' => 'nullable|exists:users,id',
        ], [
            'new_step_position_id.required_without' => 'Pilih jabatan approver atau user approver.',
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
        if (!$this->selectedWorkflow) return;
        ApprovalWorkflowStep::where('approval_workflow_id', $this->selectedWorkflow->id)->find($stepId)?->delete();
        $this->renumberSteps();
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
        // Pratinjau: approver yang akan dipakai tiap tahap untuk site terpilih.
        $preview = [];
        $usedBy = collect();
        if ($this->selectedWorkflow) {
            $jobsiteId = $this->previewJobsiteId !== '' ? (int) $this->previewJobsiteId : null;
            foreach ($this->selectedWorkflow->steps as $step) {
                $preview[$step->id] = \App\Support\Workflow::findApprover($step, $jobsiteId);
            }
            $usedBy = ApprovalWorkflowAssignment::with(['position:id,name', 'jobsite:id,code,name'])
                ->where('approval_workflow_id', $this->selectedWorkflow->id)->get();
        }

        return view('livewire.approval-workflows.manage-workflows', [
            'preview' => $preview,
            'usedBy' => $usedBy,
            'jobsites' => \App\Models\Jobsite::orderBy('code')->get(['id', 'code', 'name']),
            'typeLabels' => \App\Support\Workflow::labels(),
        ]);
    }
}
