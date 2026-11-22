<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable; // Import Contract
use OwenIt\Auditing\Auditable as AuditableTrait; // Import Trait
class ApprovalWorkflowAssignment extends Model implements Auditable

{
    use AuditableTrait;
    protected $fillable = [
        'transaction_type', 'position_id', 'jobsite_id', 
        'approval_workflow_id', 'priority'
    ];
    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function jobsite()
    {
        return $this->belongsTo(Jobsite::class);
    }

    public function approvalWorkflow()
    {
        return $this->belongsTo(ApprovalWorkflow::class);
    }
}
