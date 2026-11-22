<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SoftwareTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'software_id',
        'user_id',
        'asset_id',
        'transaction_type',
        'assigned_at',
        'returned_at',
        'note',
        'created_by',
    ];

    protected $dates = [
        'assigned_at',
        'returned_at',
        'created_at',
        'updated_at',
    ];

    // Relasi ke software
    public function software()
    {
        return $this->belongsTo(SoftwareLicenses::class);
    }

    // Relasi ke user (yang memakai software)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke asset (misal laptop)
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    // Relasi ke user yang membuat record
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
