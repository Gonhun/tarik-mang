<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable; // Import Contract
use OwenIt\Auditing\Auditable as AuditableTrait; // Import Trait

class Position extends Model implements Auditable
{

    use HasFactory;
    use AuditableTrait;
    protected $fillable = [
        'name',
        'code',
        'description',
        'job_description_name',
        'job_description_path',
        'department_id',
        'approval_workflow_id'
    ];

    /**
     * Mendapatkan departemen tempat jabatan ini berada.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Mendapatkan semua pengguna yang memiliki jabatan ini.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
    public function assetStandards(): HasMany // <-- TAMBAHKAN INI
    {
        return $this->hasMany(PositionAssetStandard::class);
    }
    public function approvalWorkflow(): HasMany
      {
          return $this->hasMany(ApprovalWorkflowAssignment::class);
      }
}
