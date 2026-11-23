<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable; // Import Contract
use OwenIt\Auditing\Auditable as AuditableTrait; // Import Trait

class AssetRequest extends Model implements Auditable
{
    use HasFactory, SoftDeletes,AuditableTrait;
    protected $fillable = [
        'request_number',
        'requester_user_id',
        'submitted_by_user_id', // pengisi form bila mengajukan atas nama orang lain (null = pemohon sendiri)
        'jobsite_id',
        'request_date',
        'needed_date',
        'purpose',
        'remarks',
        'status',
        'current_approver_id', // Jika Anda menggunakan field ini untuk penunjuk approver aktif
        'final_rejection_notes',
        'ba_damage_path',
        'ba_budget_outside_path',
        'ba_damage_name',
        'ba_budget_outside_name',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'request_date' => 'date',
        'needed_date' => 'date',
    ];

    /**
     * Relasi ke user yang membuat pengajuan.
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_user_id');
    }

    /**
     * User yang mengisi form bila pengajuan dibuat atas nama orang lain (null = diajukan sendiri).
     */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    /** Diajukan oleh orang lain atas nama pemohon? */
    public function isSubmittedOnBehalf(): bool
    {
        return $this->submitted_by_user_id && (int) $this->submitted_by_user_id !== (int) $this->requester_user_id;
    }

    /**
     * Relasi ke jobsite tempat pengajuan dibuat.
     */
    public function jobsite(): BelongsTo
    {
        return $this->belongsTo(Jobsite::class);
    }

    /**
     * Relasi ke user yang saat ini harus melakukan approval.
     */
    
    public function currentApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_approver_id');
    }
    
    /**
     * Relasi ke semua item yang ada dalam pengajuan ini.
     */
    public function items(): HasMany
    {
        return $this->hasMany(AssetRequestItem::class);
    }

    /**
     * Relasi ke semua tahapan approval untuk pengajuan ini.
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(AssetRequestApproval::class)->orderBy('step_order', 'asc');
    }
}
