<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TicketSubCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_category_id',
        'name',
        'active'
    ];

    public function category()
    {
        return $this->belongsTo(TicketCategory::class, 'ticket_category_id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
