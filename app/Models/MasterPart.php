<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterPart extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * Relasi ke PartTransaction (mencatat semua mutasi stok untuk part ini).
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(PartTransaction::class);
    }

    /**
     * Accessor untuk menghitung Total Stok Global (IN - OUT)
     */
    public function getTotalStockAttribute(): int
    {
        $in = $this->transactions()->where('type', 'IN')->sum('quantity');
        $out = $this->transactions()->where('type', 'OUT')->sum('quantity');
        return $in - $out;
    }

    /**
     * Metode untuk menghitung Stok Per Lokasi/Jobsite secara dinamis.
     */
    public function getStockByLocation(int $locationId): int
    {
        $in = $this->transactions()
                   ->where('jobsite_id', $locationId)
                   ->where('type', 'IN')
                   ->sum('quantity');

        $out = $this->transactions()
                    ->where('jobsite_id', $locationId)
                    ->where('type', 'OUT')
                    ->sum('quantity');

        return $in - $out;
    }
}