<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable; // Import Contract
use OwenIt\Auditing\Auditable as AuditableTrait; // Import Trait

class KaryawanErp extends Model
{

    //
    protected $connection = 'sqlsrv_hrd';
    protected $table = 'Vw_Karyawan';
    protected $primaryKey = 'NIK';
    protected $keyType = 'string';
}
