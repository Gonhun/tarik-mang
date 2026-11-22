<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable; // Import Contract
use OwenIt\Auditing\Auditable as AuditableTrait; // Import Trait

class AssetTransactionItem extends Model implements Auditable
{
    use HasFactory, AuditableTrait;
    protected $fillable = [
        'asset_transaction_id', 'asset_id', 'condition_notes', 'metadata','asset_request_item_id',
    ];
    protected $casts = ['metadata' => 'array'];
    public function assetTransaction(): BelongsTo { return $this->belongsTo(AssetTransaction::class); }
    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
    public function originatingRequestItem(): BelongsTo 
    {
        return $this->belongsTo(AssetRequestItem::class, 'asset_request_item_id');
    }
}
