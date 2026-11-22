<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable; // Import Contract
use OwenIt\Auditing\Auditable as AuditableTrait; // Import Trait
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoginwaSession extends Model implements Auditable
{
    use HasFactory;
    use AuditableTrait;
    
    protected $fillable = [
        'phone_number',
        'token',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime', // Otomatis cast ke objek Carbon
    ];
}
