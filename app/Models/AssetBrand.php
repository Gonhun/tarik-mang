<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Contracts\Auditable; // Import Contract
use OwenIt\Auditing\Auditable as AuditableTrait; // Import Trait

class AssetBrand extends Model implements Auditable
{
    use HasFactory;
    use AuditableTrait;
    protected $fillable = [
        'asset_type_id',
        'name',
        'photo_path',
    ];

    /**
     * Get the asset type that owns the brand.
     */
    public function assetType(): BelongsTo
    {
        return $this->belongsTo(AssetType::class);
    }
}
