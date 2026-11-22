<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable; // Import Contract
use OwenIt\Auditing\Auditable as AuditableTrait; // Import Trait
use Illuminate\Database\Eloquent\Relations\HasOne;

class AssetRequestItem extends Model implements Auditable
{
    use HasFactory,AuditableTrait;
    protected $fillable = [
        'asset_request_id',
        'asset_type_id',
        'asset_brand_id',
        'asset_series_id',
        'quantity',
        'item_description',
        'fulfillment_type',
        'assigned_asset_id',
        'it_approval_status',
        'it_approver_id',
        'it_approval_at',
        'it_approval_notes',
        'pr_number',           
        'accounting_number',   
        'procurement_notes',   
        'po_number',
        'qc_status',       
        'gr_number',
        'price',
        'is_standard'    
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'it_approval_at' => 'datetime',
        'price' => 'decimal:2',
    ];

    public function assetRequest(): BelongsTo
    {
        return $this->belongsTo(AssetRequest::class);
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

    /**
     * Relasi ke aset spesifik dari stok yang dialokasikan.
     */
    public function assignedAsset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'assigned_asset_id');
    }
    public function procuredAsset(): HasOne // <-- TAMBAHKAN RELASI INI
    {
        return $this->hasOne(Asset::class, 'asset_request_item_id');
    }
    /**
     * Relasi ke user IT yang melakukan approval untuk item ini.
     */
    public function itApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'it_approver_id');
    }
}
