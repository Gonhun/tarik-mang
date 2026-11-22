<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IsrBilling extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'billing_number',
        'billing_date',
        'amount',
        'description',
    ];

    
    public function getPublishDateAttribute($value)
    {
        return $value ? \Carbon\Carbon::parse($value)->format('Y-m-d') : null;
    }
    //
}
