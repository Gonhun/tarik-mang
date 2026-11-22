<?php

namespace App\Support;

use App\Models\AssetTransaction;

/**
 * Nomor transaksi format lama: {PREFIX}/{YYYYMM}/{0001}, contoh RTN/202611/0293, BAST/202611/0148.
 */
class TransactionNumber
{
    public static function next(string $typePrefix): string
    {
        $prefix = "{$typePrefix}/" . date('Ym') . '/';

        $last = AssetTransaction::withTrashed()
            ->where('transaction_number', 'LIKE', $prefix . '%')
            ->orderByDesc('transaction_number')
            ->value('transaction_number');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
