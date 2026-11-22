<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SoftwareManufacturer extends Model
{
    protected $fillable = [
        'name',
    ];
    protected $table = 'software_manufacturer';
    public function softwares()
    {
        return $this->hasMany(SoftwareLicenses::class);
    }
}
