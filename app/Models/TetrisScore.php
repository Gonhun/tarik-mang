<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TetrisScore extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'score', 'lines_cleared', 'level_reached'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}