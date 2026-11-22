<?php

namespace App\Http\Controllers;

use App\Models\AssetRequest;

class PrinRequestAssetController extends Controller
{
    public function print(AssetRequest $AssetRequest)
    {
        // Eager load semua relasi yang dibutuhkan untuk dokumen cetak
        $AssetRequest->load([
            'items.asset.assetType',
            'items.asset.assetBrand',
            'items.asset.assetSeries',
            'fromJobsite',
            'toJobsite',
            'creator'
        ]);

        // Tampilkan view khusus untuk cetak, dan kirim data transaksi
        return view('assets.printing_request_asset', ['AssetRequest' => $AssetRequest]);
    }
}
