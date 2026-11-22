<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SoftwareCategory extends Model
{
    protected $fillable = [
        'name',
    ];
    protected $table = 'software_category';
    public function softwares()
    {
        return $this->hasMany(SoftwareLicenses::class);
    }
}
