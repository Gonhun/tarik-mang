<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable; // Import Contract
use OwenIt\Auditing\Auditable as AuditableTrait; // Import Trait

class Jobsite extends Model implements Auditable
{
    use HasFactory;
    use AuditableTrait;
    protected $table='jobsites';
    protected $fillable = [
        'name',
        'code',
        'location',
    ];

    /**
     * Mendapatkan semua user yang ada di jobsite ini.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
    public function assets(): HasMany // <-- 2. TAMBAHKAN METHOD INI
    {
        return $this->hasMany(Asset::class, 'jobsite_id');
    }


}
