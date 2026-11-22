<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LicenseRequestItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'license_request_id',
        'software_category_id',
        'software_manufacture_id',
        'software_name',
        'quantity',
        'no_pr',
        'no_po',
        'no_accounting',
        'price',
        'remarks',
        'status',
        'approved_by',
        'approved_at',
        'price',
        'no_sap'
    ];

    public function request() {
        return $this->belongsTo(LicenseRequest::class, 'license_request_id');
    }
    public function softwareCategory() {
        return $this->belongsTo(SoftwareCategory::class, 'software_category_id');
    }
    public function softwareManufacturer() {
        return $this->belongsTo(SoftwareManufacturer::class, 'software_manufacture_id');
    }
    public function approvals() {
        return $this->hasMany(LicenseRequestApproval::class);
    }
}
