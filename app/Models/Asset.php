<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // Import SoftDeletes
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable; // Import Contract
use OwenIt\Auditing\Auditable as AuditableTrait; // Import Trait

class Asset extends Model implements Auditable
{
    use HasFactory, SoftDeletes; // Gunakan SoftDeletes
    use AuditableTrait;
    protected $fillable = [
        'asset_number',
        'asset_request_item_id',
        'old_asset_number',
        'cost_center_id',
        'pr_number',
        'po_number',
        'sap_number',
        'asset_type_id',
        'asset_brand_id',
        'asset_series_id',
        'department_id',
        'entity_id', 
        'jobsite_id',
        'serial_number',
        'status',
        'created_by',
        'holder_user_id',           
        'current_location_detail',  
        'price',
        'asset_transaction_id',
        'asset_transaction_return_id'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        // Jika Anda ingin status di-cast ke string atau enum kustom di PHP
        'price' => 'decimal:2',
    ];
    protected function sapNumber(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => empty(trim((string) $value)) ? null : trim($value),
        );
    }
    public function costCenter(): BelongsTo // <-- TAMBAHKAN RELASI INI
    {
        return $this->belongsTo(CostCenter::class);
    }
    public function assetRequestItem(): BelongsTo // <-- TAMBAHKAN RELASI INI
    {
        return $this->belongsTo(AssetRequestItem::class);
    }
    // Lakukan hal yang sama untuk old_asset_number jika perlu
    protected function oldAssetNumber(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => empty(trim((string) $value)) ? null : trim($value),
        );
    }

    public function assetType(): BelongsTo
    {
        return $this->belongsTo(AssetType::class);
    }
    public function transactions()
    {
        // Asumsi tabel pivot Anda adalah 'asset_transaction_items'
        return $this->belongsToMany(\App\Models\AssetTransaction::class, 'asset_transaction_items', 'asset_id', 'asset_transaction_id')
                    ->withTimestamps()
                    ->orderBy('transaction_date', 'desc');
    }
    public function assetBrand(): BelongsTo
    {
        return $this->belongsTo(AssetBrand::class);
    }

    public function assetSeries(): BelongsTo
    {
        return $this->belongsTo(AssetSeries::class);
    }
    public function departement(): BelongsTo
    {
        // Kolom di tabel assets bernama department_id (tanpa ini Laravel mencari departement_id).
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function jobsite(): BelongsTo
    {
        return $this->belongsTo(Jobsite::class);
    }

    public function creator(): BelongsTo // Relasi untuk created_by
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function holder(): BelongsTo // <-- Tambahkan relasi ini
    {
        return $this->belongsTo(User::class, 'holder_user_id');
    }
    public function entity(): BelongsTo // <-- Tambahkan relasi ini
    {
        return $this->belongsTo(Entity::class);
    }
    public function transactionItems(): HasMany
    {
        return $this->hasMany(AssetTransactionItem::class);
    }
    public function damageReports(): HasMany
    {
        return $this->hasMany(AssetDamageReport::class);
    }
    public function requestItems(): HasMany
    {
        // Semua item pengajuan yang menunjuk ke aset ini (setelah dialokasikan)
        return $this->hasMany(AssetRequestItem::class, 'assigned_asset_id');
    }
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(AssetTransaction::class, 'asset_transaction_id');
    }
    public function returnTransaction(): BelongsTo
    {
        return $this->belongsTo(AssetTransaction::class, 'asset_transaction_return_id');
    }
}