<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable; // Import Contract
use OwenIt\Auditing\Auditable as AuditableTrait; // Import Trait

class AssetRequestApproval extends Model implements Auditable
{
    use HasFactory,AuditableTrait;
    protected $fillable = [
        'asset_request_id',
        'step_order',
        'approver_role_name',
        'approver_user_id',
        'status',
        'action_by_user_id',
        'action_at',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'action_at' => 'datetime',
       
    ];

    /**
     * Relasi ke pengajuan aset utama.
     */
    public function assetRequest(): BelongsTo
    {
        return $this->belongsTo(AssetRequest::class);
    }
    
    /**
     * Relasi ke user yang ditunjuk sebagai approver.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_user_id')->with('karyawans');
    }
    

    /**
     * Relasi ke user yang melakukan aksi (menyetujui/menolak).
     */
    public function actionBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'action_by_user_id');
    }
}
