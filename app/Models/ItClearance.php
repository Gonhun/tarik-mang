<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use OwenIt\Auditing\Contracts\Auditable; // Import Contract
use OwenIt\Auditing\Auditable as AuditableTrait; // Import Trait

class ItClearance extends Model implements Auditable
{
    use AuditableTrait;
    protected $fillable = [
        'user_id',
        'resign_date',
        'check_date',
        'additional_notes',
    ];
}
