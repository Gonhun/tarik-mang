<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LicenseRequestApproval extends Model
{
    use HasFactory;
    protected $fillable = [
        'license_request_item_id',
        'user_id',
        'status',
        'remarks',
        'approved_at',
        'step_order'
    ];

    public function request() {
        return $this->belongsTo(LicenseRequestItem::class, 'license_request_item_id')->with(['request','softwareCategory']);
    }

    public function approver() {
        return $this->belongsTo(User::class, 'user_id');
    }
}
