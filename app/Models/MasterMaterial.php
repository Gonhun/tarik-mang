<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable; // Import Contract
use OwenIt\Auditing\Auditable as AuditableTrait; // Import Trait

class MasterMaterial extends Model
{
    use HasFactory;
    protected $table = 'sap_master_material';

    public function cost_center()
    {
        return $this->belongsTo(CostCenter::class, 'cost_center', 'cost_center_number');
    }
}
