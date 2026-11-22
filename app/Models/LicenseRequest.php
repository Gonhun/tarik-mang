<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LicenseRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_no',
        'user_id',
        'purpose',
        'status',
        'remarks',
        'approved_by',
        'license_type',
        'submitted_at',
        'approved_at',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function items() {
        return $this->hasMany(LicenseRequestItem::class);
    }

    public function approvals() {
        return $this->hasMany(LicenseRequestApproval::class);
    }

    public function attachments(){
        return $this->hasMany(LicenseRequestAttachment::class);
    }

  
}
