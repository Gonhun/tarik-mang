<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use OwenIt\Auditing\Contracts\Auditable; // Import Contract
use OwenIt\Auditing\Auditable as AuditableTrait; // Import Trait

class ItClearanceItem extends Model implements Auditable
{
    use AuditableTrait;
    protected $fillable = [
        'it_clearance_id',
        'item_description',
        'status',
        'notes',
    ];
}
