<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LicenseRequestAttachment extends Model
{
    //
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'license_request_id',
        'doc_file_name',
        'doc_file_path'
    ];

    public function request() {
        return $this->belongsTo(LicenseRequest::class, 'license_request_id');
    }
}
