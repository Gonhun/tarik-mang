<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketCategory extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'active'
    ];

    // Jika ingin relasi ke Ticket
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
