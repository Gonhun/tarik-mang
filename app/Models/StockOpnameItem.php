<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Hasil satu scan dalam sesi stock opname.
 * result: found (aset site ini) | other_site (aset tercatat di site lain) | not_registered (kode tidak dikenal)
 */
class StockOpnameItem extends Model
{
    protected $fillable = [
        'stock_opname_id', 'asset_id', 'scanned_code', 'result', 'condition', 'location_note',
        'asset_status_at_scan', 'asset_jobsite_id_at_scan', 'scanned_by', 'scanned_at',
    ];

    protected $casts = ['scanned_at' => 'datetime'];

    public function opname(): BelongsTo { return $this->belongsTo(StockOpname::class, 'stock_opname_id'); }
    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
    public function scanner(): BelongsTo { return $this->belongsTo(User::class, 'scanned_by'); }
    public function jobsiteAtScan(): BelongsTo { return $this->belongsTo(Jobsite::class, 'asset_jobsite_id_at_scan'); }
}
