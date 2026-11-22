<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Sesi stock opname (pemeriksaan fisik aset) per site.
 */
class StockOpname extends Model implements Auditable
{
    use AuditableTrait;

    protected $fillable = [
        'code', 'jobsite_id', 'title', 'status', 'expected_count', 'notes',
        'started_by', 'started_at', 'closed_by', 'closed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    /** Status aset yang diharapkan ada secara fisik di site (yang sudah dihapus/hilang tidak dihitung). */
    public const EXPECTED_STATUSES_EXCLUDED = ['Disposed', 'Scrap', 'Missing', 'Delivery'];

    public function jobsite(): BelongsTo { return $this->belongsTo(Jobsite::class); }
    public function starter(): BelongsTo { return $this->belongsTo(User::class, 'started_by'); }
    public function closer(): BelongsTo { return $this->belongsTo(User::class, 'closed_by'); }
    public function items(): HasMany { return $this->hasMany(StockOpnameItem::class); }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    /** Aset yang seharusnya ada di site sesi ini. */
    public function expectedAssetsQuery()
    {
        return Asset::where('jobsite_id', $this->jobsite_id)
            ->whereNotIn('status', self::EXPECTED_STATUSES_EXCLUDED);
    }
}
