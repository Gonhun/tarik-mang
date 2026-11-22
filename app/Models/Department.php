<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable; // Import Contract
use OwenIt\Auditing\Auditable as AuditableTrait; // Import Trait

class Department extends Model implements Auditable
{
    use HasFactory;
    use AuditableTrait;
    protected $fillable = [
        'name',
        'code',
    ];

    /**
     * Mendapatkan semua user yang ada di departemen ini.
     */
    public function users(): HasMany // <-- Tambahkan relasi ini
    {
        return $this->hasMany(User::class);
    }
    public function positionlevels(): HasMany // <-- Tambahkan relasi ini
    {
        return $this->hasMany(PositionLevel::class);
    }
    public function positions(): HasMany // <-- TAMBAHKAN INI
    {
        return $this->hasMany(Position::class);
    }
}
