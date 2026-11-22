<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use OwenIt\Auditing\Contracts\Auditable; // Import Contract
use OwenIt\Auditing\Auditable as AuditableTrait; // Import Trait

class PositionAssetStandard extends Model implements Auditable
{
    use HasFactory;
    use AuditableTrait;
    protected $table = 'position_asset_standards';

    protected $fillable = [
        'position_id',
        'asset_type_id',
        'asset_brand_id',
        'asset_series_id',
        'quantity',
        'specifications_notes',
    ];

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function assetType(): BelongsTo
    {
        return $this->belongsTo(AssetType::class);
    }

    public function assetBrand(): BelongsTo
    {
        return $this->belongsTo(AssetBrand::class);
    }

    public function assetSeries(): BelongsTo
    {
        return $this->belongsTo(AssetSeries::class);
    }
}