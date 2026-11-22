<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartTransaction extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'transaction_date' => 'datetime',
    ];

    /**
     * Relasi ke MasterPart (part mana yang dimutasi).
     */
    public function masterPart(): BelongsTo
    {
        return $this->belongsTo(MasterPart::class);
    }

    /**
     * Relasi ke StockLocation (jobsite mana yang terlibat).
     */
    public function jobsite(): BelongsTo
    {
        // Asumsi foreign key adalah 'jobsite_id'
        return $this->belongsTo(Jobsite::class, 'jobsite_id'); 
    }

    /**
     * Relasi ke User (siapa yang mencatat transaksi).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}