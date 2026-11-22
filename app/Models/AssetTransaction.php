<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable; // Import Contract
use OwenIt\Auditing\Auditable as AuditableTrait; // Import Trait
use Illuminate\Support\Facades\Storage;


class AssetTransaction extends Model implements Auditable
{
    use HasFactory, SoftDeletes,AuditableTrait;
    protected $fillable = [
        'transaction_number', 'transaction_date', 'transaction_type',
        'from_user_id', 'to_user_id', 'from_jobsite_id', 'to_jobsite_id',
        'notes', 'status','document_path', 'document_name', 'created_by_user_id',
    ];
    protected $casts = ['transaction_date' => 'date'];

    public function fromUser(): BelongsTo { return $this->belongsTo(User::class, 'from_user_id'); }
    public function toUser(): BelongsTo { return $this->belongsTo(User::class, 'to_user_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by_user_id'); }
    public function items(): HasMany { return $this->hasMany(AssetTransactionItem::class, 'asset_transaction_id'); }
    public function fromJobsite(): BelongsTo { return $this->belongsTo(Jobsite::class, 'from_jobsite_id'); }
    public function toJobsite(): BelongsTo { return $this->belongsTo(Jobsite::class, 'to_jobsite_id'); }
    public function approvals(): HasMany
    {
        return $this->hasMany(AssetTransactionApproval::class)->orderBy('step_order', 'asc');
    }
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'asset_transaction_id');
    }
    // public function getDocumentUrlAttribute(): ?string {
    //     return $this->document_path ? Storage::disk('public')->url($this->document_path) : null;
    // }
}
