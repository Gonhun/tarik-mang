<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_no',
        'user_id',
        'jobsite_id',
        'ticket_sub_category_id',
        'assigned_to',
        'assigned_by',
        'assigned_at',
        'closed_by',
        'closed_at',
        'due_date',
        'title',
        'description',
        'attachment',
        'priority',
        'status',
        'resolution_note',
        'resolved_by',
        'resolved_at',
        'rating',
        'rating_note',
    ];

    /** User yang membuat ticket */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Kategori */
    public function category()
    {
        return $this->belongsTo(TicketCategory::class, 'ticket_category_id');
    }
    public function subCategory()
    {
        return $this->belongsTo(TicketSubCategory::class, 'ticket_sub_category_id');
    }
    
    /** User yang dikerjakan */
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** User yang menugaskan */
    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /** User yang menutup ticket */
    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function jobsite()
    {
        return $this->belongsTo(Jobsite::class, 'jobsite_id');
    }
}
