<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // Import SoftDeletes
use OwenIt\Auditing\Contracts\Auditable; // Import Contract
use OwenIt\Auditing\Auditable as AuditableTrait; // Import Trait

class Entity extends Model implements Auditable
{
    use HasFactory, SoftDeletes; // Gunakan SoftDeletes

    use AuditableTrait;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'name',
    ];
}