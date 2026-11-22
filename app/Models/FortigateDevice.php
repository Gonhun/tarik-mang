<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FortigateDevice extends Model
{
    protected $fillable = [
        'name', 'jobsite_id', 'base_url', 'api_token', 'vdom', 'verify_ssl', 'is_active', 'notes',
        'last_status', 'last_error', 'last_info', 'last_checked_at', 'created_by',
    ];

    /** Token tidak pernah ikut di-serialize ke browser / Livewire. */
    protected $hidden = ['api_token'];

    protected $casts = [
        'api_token' => 'encrypted',
        'verify_ssl' => 'boolean',
        'is_active' => 'boolean',
        'last_info' => 'array',
        'last_checked_at' => 'datetime',
    ];

    public function jobsite(): BelongsTo
    {
        return $this->belongsTo(Jobsite::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    /** URL dasar API (…/api/v2). */
    public function apiUrl(): string
    {
        return rtrim(preg_replace('#/api/v2/?$#', '', rtrim($this->base_url, '/')), '/') . '/api/v2';
    }
}
