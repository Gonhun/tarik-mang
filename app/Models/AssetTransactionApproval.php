<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable; // Import Contract
use OwenIt\Auditing\Auditable as AuditableTrait; // Import Trait

class AssetTransactionApproval extends Model implements Auditable
{
    use HasFactory,AuditableTrait;
    protected $fillable = [
        'asset_transaction_id', 'step_order', 'approver_role_name', 'approver_user_id',
        'status', 'action_by_user_id', 'action_at', 'notes'
    ];
    protected $casts = ['action_at' => 'datetime'];

    public function transaction(): BelongsTo { return $this->belongsTo(AssetTransaction::class, 'asset_transaction_id'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approver_user_id'); }
    public function actionBy(): BelongsTo { return $this->belongsTo(User::class, 'action_by_user_id'); }
}
