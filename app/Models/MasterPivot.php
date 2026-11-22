<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable; // Import Contract
use OwenIt\Auditing\Auditable as AuditableTrait; // Import Trait
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterPivot extends Model implements Auditable
{
    use HasFactory, SoftDeletes,AuditableTrait;

    protected $fillable = [
        'cost_element_number',
        'description',
        'department_id',
        'jobsite_id',
        'year',
        'Jan',
        'Feb',
        'Mar',
        'Apr',
        'May',
        'Jun',
        'Jul',
        'Aug',
        'Sep',
        'Okt',
        'Nov',
        'Dec',
    ];
    //
    public function jobsite(): BelongsTo
    {
        return $this->belongsTo(Jobsite::class);
    }
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

}
