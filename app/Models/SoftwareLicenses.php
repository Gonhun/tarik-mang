<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SoftwareLicenses extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category_id',
        'seats',
        'product_key',
        'manufacture_id',
        'order_number',
        'purchase_cost',
        'purchase_date',
        'expiration_date',
        'po_number',
        'note',
        'create_by',
    ];

    /** RELASI KE CATEGORY (software_category) */
    public function softwareCategory()
    {
        return $this->belongsTo(SoftwareCategory::class, 'category_id');
    }

    /** RELASI KE MANUFACTURER (software_manufacturer) */
    public function softwareManufacturer()
    {
        return $this->belongsTo(SoftwareManufacturer::class, 'manufacture_id');
    }
    public function softwareTransactions()
    {
        return $this->hasMany(SoftwareTransaction::class, 'software_id');
    }
    /** RELASI KE USER (create_by) */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'create_by');
    }
}
