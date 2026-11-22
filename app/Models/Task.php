<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'status',
        'progress',
        'user_id',
        'due_date',
        'priority',
    ];

    protected $casts = [
        'progress' => 'integer',
        'due_date' => 'date',
    ];

    public function logs()
    {
        return $this->hasMany(TaskProgressLog::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }
}
