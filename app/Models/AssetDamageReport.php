<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable; // Import Contract
use OwenIt\Auditing\Auditable as AuditableTrait; // Import Trait
use Illuminate\Support\Facades\Storage;

class AssetDamageReport extends Model implements Auditable
{
    use HasFactory, SoftDeletes,AuditableTrait;

    protected $fillable = [
        'asset_id',
        'reported_by_user_id',
        'report_date',
        'damage_description',
        'damage_status',
        'asset_status_before_report',
        'assessment_notes',
        'repair_notes',
        'repair_cost',
        'repaired_date',
        'photo1_path',
        'photo2_path',
        'other_document_path',
        'other_document_name',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected $casts = [
        'report_date' => 'date',
        'repaired_date' => 'date',
        'repair_cost' => 'decimal:2',
    ];

    protected $appends = ['photo1_url', 'photo2_url', 'other_document_url'];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function reporter(): BelongsTo // User yang melaporkan
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }

    public function creator(): BelongsTo // User yang membuat record
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
    
    public function updater(): BelongsTo // User yang mengupdate record
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function getPhoto1UrlAttribute(): ?string
    {
        return $this->photo1_path ? asset('storage/' . $this->photo1_path) : null;
    }

    public function getPhoto2UrlAttribute(): ?string
    {
        return $this->photo2_path ? asset('storage/' . $this->photo2_path) : null;
    }

    public function getOtherDocumentUrlAttribute(): ?string
    {
        return $this->other_document_path ? asset('storage/' . $this->other_document_path) : null;
    }
}