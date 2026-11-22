<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable; // Import Contract
use OwenIt\Auditing\Auditable as AuditableTrait; // Import Trait
use Illuminate\Database\Eloquent\Casts\Attribute;

class AssetSeries extends Model implements Auditable
{
    use HasFactory;
    use AuditableTrait;
    protected $table = 'asset_series'; // Eksplisit jika nama tabel berbeda dari konvensi (meski ini sudah sesuai)

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'asset_type_id',
        'asset_brand_id',
        'material_code',
        'name',
        'specifications',
    ];

    /**
     * Get the asset type that this series might loosely belong to.
     */
    public function assetType(): BelongsTo
    {
        return $this->belongsTo(AssetType::class);
    }

    /**
     * Get the asset brand that this series belongs to.
     */
    public function assetBrand(): BelongsTo
    {
        return $this->belongsTo(AssetBrand::class);
    }
    protected function materialCode(): Attribute
    {
        return Attribute::make(
            // 'set' akan dijalankan setiap kali Anda mencoba mengisi nilai material_code
            set: fn ($value) => empty(trim((string) $value)) ? null : strtoupper(trim($value)),
        );
    }
   
}