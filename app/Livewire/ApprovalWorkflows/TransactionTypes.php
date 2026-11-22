<?php

namespace App\Livewire\ApprovalWorkflows;

use App\Models\ApprovalWorkflowAssignment;
use App\Models\ApprovalWorkflowStep;
use App\Support\Workflow;
use Livewire\Component;

/**
 * Master Jenis Transaksi (baca saja). Sumber data: config/transaction_types.php.
 */
class TransactionTypes extends Component
{
    public function render()
    {
        $types = Workflow::types();

        $rules = ApprovalWorkflowAssignment::with(['approvalWorkflow:id,name', 'position:id,name', 'jobsite:id,code,name'])
            ->get()
            ->groupBy('transaction_type');
        $workflowsWithSteps = ApprovalWorkflowStep::distinct()->pluck('approval_workflow_id')->all();

        $rows = collect($types)->map(function ($t, $code) use ($rules, $workflowsWithSteps) {
            $r = $rules[$code] ?? collect();
            $valid = $r->filter(fn ($a) => in_array($a->approval_workflow_id, $workflowsWithSteps));

            return $t + [
                'code' => $code,
                'rules' => $r,
                'valid_rules' => $valid->count(),
                'has_default' => $valid->contains(fn ($a) => !$a->position_id && !$a->jobsite_id),
            ];
        });

        // Aturan dengan kode yang tidak ada di master (mis. data lama).
        $unknown = $rules->keys()->diff(array_keys($types))->values();

        return view('livewire.approval-workflows.transaction-types', [
            'rows' => $rows,
            'unknown' => $unknown,
            'rulesByType' => $rules,
            'types' => $types,
        ]);
    }
}
