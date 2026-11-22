<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable; // Import Contract
use OwenIt\Auditing\Auditable as AuditableTrait; // Import Trait

class MasterForecast extends Model Implements Auditable
{
    use HasFactory, SoftDeletes, AuditableTrait;

    
    protected $fillable = [
        'material_code',
        'material_name',
        'max_lt',
        'avg_lt',
        'period',
    ];

    protected $table = 'master_forecasts';
    
}
