<?php

namespace App\Http\Controllers;

use App\Models\AssetTransaction;
use Illuminate\Http\Request;

class AssetTransactionController extends Controller
{
    public function print(AssetTransaction $transaction)
    {
        $this->authorize('view-asset-transaction', $transaction);

        // Eager load semua relasi yang dibutuhkan untuk dokumen cetak
        $transaction->load([
            'items.asset.assetType',
            'items.asset.assetBrand',
            'items.asset.assetSeries',
            'fromJobsite',
            'toJobsite',
            'creator'
        ]);

        // Tampilkan view khusus untuk cetak, dan kirim data transaksi
        return view('assets.transaksi_print', ['transaction' => $transaction]);
    }
}
