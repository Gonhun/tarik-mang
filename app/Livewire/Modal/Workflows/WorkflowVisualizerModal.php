<?php

namespace App\Livewire\Modal\Workflows;

use Livewire\Component;
use App\Models\ApprovalWorkflow;
use App\Models\Position;
use App\Models\PositionLevel;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class WorkflowVisualizerModal extends Component
{
    public $isModalOpen = false;
    public ?ApprovalWorkflow $workflow = null;
    public $resolvedApprovers = [];
    
    protected $listeners = ['showWorkflowVisualizer' => 'showModal'];

    public function showModal($workflowId)
    {
        
        
        $contextUserId = Auth::id() ?? null; // ID user pemohon (opsional)

        if (!$workflowId) return;

        $this->workflow = ApprovalWorkflow::with('steps.approverPositionLevel')->find($workflowId);
        
        if ($this->workflow) {
            // Jika ada konteks user, coba cari nama approver spesifik
            if ($contextUserId) {
                $requester = User::with('positionLevel', 'manager.position')->find($contextUserId);
                if ($requester) {
                    $this->resolveApproverNames($requester);
                }
            }
            $this->isModalOpen = true;
        } else {
            $this->dispatch('show-toast', ['type' => 'error', 'message' => 'Alur kerja tidak ditemukan.']);
        }
    }
    /**
     * Method BARU untuk mencari nama approver untuk setiap langkah.
     */
    protected function resolveApproverNames(User $requester)
    {
        $this->resolvedApprovers = [];
       
        foreach ($this->workflow->steps as $step) {
            // $session= $session+1;
            $approverPosition = $step->approverPositionLevel;
           
            $approverDepartment = $step->department_id;
            $roleName = $approverPosition->name;

            // Skip Project Manager if jobsite is SMD or JKT
            if ($roleName === 'Kepala Proyek' && in_array($requester->jobsite->code, ['SMD', 'JKT'])) {
                continue;
            }
            
            // Base query
            $userQuery = User::query()->with('positionLevel');
            if($step->user_id)
            {
                $userQuery->where('id',$step->user_id);
                $approverUser = $userQuery->first();
                $this->resolvedApprovers[$step->id] = $approverUser ? $approverUser->name : '<span class="text-danger">Tidak Ditemukan</span>';
                continue;
            }
            if(!empty($approverDepartment))
            {
              
                $userQuery->where('department_id',$approverDepartment)
                ->whereHas('positionLevel', fn($q) => $q->where('name', $roleName))
                ->where('jobsite_id', $requester->jobsite_id);
            }else{
                $userQuery
                ->whereHas('positionLevel', fn($q) => $q->where('name', $roleName))
                ->where('jobsite_id', $requester->jobsite_id);
            }
            $approverUser = $userQuery->first();

            
            if (!$approverUser) {
                $fallbackQuery = User::query();
                if(!empty($approverDepartment))
                {
                    $fallbackQuery->where('department_id', $approverDepartment)
                ->whereHas('positionLevel', fn($q) => $q->where('name', $roleName))
                ->whereHas('jobsite', fn($q) => $q->where('code', 'JKT'));
                }else{
                    $fallbackQuery
                ->whereHas('positionLevel', fn($q) => $q->where('name', $roleName))
                ->whereHas('jobsite', fn($q) => $q->where('code', 'JKT'));
                }
                
                
                $approverUser = $fallbackQuery->first();
               
            }
           
            $this->resolvedApprovers[$step->id] = $approverUser ? $approverUser->name : '<span class="text-danger">Tidak Ditemukan</span>';
           
        }
       
        // foreach ($this->workflow->steps as $step) {
        //    dd($step);
        //     if($step->approverPositionLevel->name === 'Project Manager'){
        //         if(!in_array($requester->jobsite->code, ['JKT', 'SMD'])){
                   
        //             $approverUser = $this->findApproverForPosition($step->approverPositionLevel, $requester);
        //             $this->resolvedApprovers[$step->id] = $approverUser ? $approverUser->name : '<span class="text-danger">Tidak Ditemukan</span>';
        //         }else{
        //             continue;
        //         }
        //     }else{
        //         $approverUser = $this->findApproverForPosition($step->approverPositionLevel, $requester);
        //         $this->resolvedApprovers[$step->id] = $approverUser ? $approverUser->name : '<span class="text-danger">Tidak Ditemukan</span>';
        //     }
           
        // }
    }

    /**
     * Helper untuk menemukan user approver berdasarkan Jabatan dan konteks pemohon.
     * Logika ini harus sama dengan yang ada di form pengajuan Anda.
     */
    protected function findApproverForPosition(?PositionLevel $approverPositionLevel, User $requester): ?User
    {
       
        if (is_null($approverPositionLevel)) {
            return null;
        }
        if ($approverPositionLevel->name === 'Kepala Departemen' || $approverPositionLevel->name === 'Kepala Bagian') {
            $user = User::where('department_id', $requester->department_id)
                ->whereHas('positionLevel', fn($q) => $q->where('name', $approverPositionLevel->name))
                ->where('jobsite_id', $requester->jobsite_id)
                ->first()
                ?? User::where('department_id', $requester->department_id)
                    ->whereHas('positionLevel', fn($q) => $q->where('name', $approverPositionLevel->name))
                    ->whereHas('jobsite', fn($q) => $q->where('code', 'JKT'))
                    ->first();
        } else {
            $user = User::where('position_level_id', $approverPositionLevel->id)
                ->where('jobsite_id', $requester->jobsite_id)
                ->first()
                ?? User::where('position_level_id', $approverPositionLevel->id)
                    ->whereHas('jobsite', fn($q) => $q->where('code', 'JKT'))
                    ->first();
        }
        
        return $user;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->workflow = null;
        $this->resolvedApprovers = []; // Reset nama approver
    }

    public function render()
    {
        return view('livewire.modal.workflows.workflow-visualizer-modal');
    }
}
