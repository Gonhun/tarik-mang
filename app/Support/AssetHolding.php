<?php

namespace App\Support;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Builder;

/** Definisi "aset yang sedang dipegang user". */
class AssetHolding
{
    /** Status yang tidak dihitung sebagai dipegang walau holder_user_id terisi (sisa data lama). */
    public const EXCLUDED_STATUSES = ['Free', 'Disposed', 'Scrap'];

    public static function query(): Builder
    {
        return Asset::query()
            ->whereNotNull('holder_user_id')
            ->whereNotIn('status', self::EXCLUDED_STATUSES);
    }

    /** Aset dengan status tidak wajar tapi masih tercatat punya pemegang. */
    public static function anomalies(): Builder
    {
        return Asset::query()->whereNotNull('holder_user_id')->whereIn('status', self::EXCLUDED_STATUSES);
    }
}
