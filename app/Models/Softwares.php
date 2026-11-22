<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Softwares extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'category_id',
        'manufacture_id',
        'name',
        'version',
        'description',
        'is_active',
    ];
    
    protected $table = 'softwares';

    public function manufacturer()
    {
        return $this->belongsTo(SoftwareManufacturer::class, 'manufacture_id', 'id');
    }

    public function category()
    {
        return $this->belongsTo(SoftwareCategory::class, 'category_id', 'id');
    }

    public function licenses()
    {
        return $this->hasMany(SoftwareLicenses::class, 'software_id', 'id');
    }
}
