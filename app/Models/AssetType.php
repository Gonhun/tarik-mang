<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable; // Import Contract
use OwenIt\Auditing\Auditable as AuditableTrait; // Import Trait

class AssetType extends Model implements Auditable
{
    use HasFactory;
    use AuditableTrait;
    protected $fillable = [
        'name',
        'requires_bast',
        'code',
        'photo_path',
    ];

    // protected $appends = ['photo_url'];

    // public function getPhotoUrlAttribute(): ?string
    // {
    //     if ($this->photo_path) {
    //         // Pastikan disk 'public' dikonfigurasi dengan benar dan storage:link sudah dijalankan
    //         return Storage::disk('public')->url($this->photo_path);
    //     }
    //     // Kembalikan URL gambar default jika tidak ada foto atau null
    //     return null; // Atau misalnya: asset('images/default_asset_type.png');
    // }

    public function assetBrands(): HasMany
    {
        return $this->hasMany(AssetBrand::class);
    }
    public function assets(): HasMany // <-- 2. TAMBAHKAN METHOD INI
    {
        return $this->hasMany(Asset::class, 'asset_type_id');
    }
    /**
     * Get all of the series for the AssetType.
     */
    public function assetSeries(): HasMany
    {
        return $this->hasMany(AssetSeries::class);
    }
}
