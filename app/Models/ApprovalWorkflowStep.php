<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use OwenIt\Auditing\Contracts\Auditable; // Import Contract
use OwenIt\Auditing\Auditable as AuditableTrait; // Import Trait

class ApprovalWorkflowStep extends Model implements Auditable
{
    use HasFactory;
    use AuditableTrait;
    protected $fillable = [
        'approval_workflow_id',
        'step_order',
        'user_id',
        'department_id',
        'approver_position_level_id',
    ];

    public function approvalWorkflow(): BelongsTo
    {
        return $this->belongsTo(ApprovalWorkflow::class);
    }
    public function department(): BelongsTo 
    {
        return $this->belongsTo(Department::class);
    }
    public function user(): BelongsTo 
    {
        return $this->belongsTo(user::class,'user_id');
    }
    public function approverPositionLevel(): BelongsTo
    {
        return $this->belongsTo(PositionLevel::class, 'approver_position_level_id');
    }
}