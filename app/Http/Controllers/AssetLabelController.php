<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Asset;
class AssetLabelController extends Controller
{
    /**
     * Cetak banyak label sekaligus. ?ids=1,2,3&size=small|medium|large&layout=roll|sheet
     */
    public function printMany(Request $request)
    {
        $ids = collect(explode(',', (string) $request->query('ids')))
            ->map(fn ($v) => (int) trim($v))->filter()->unique()->take(500)->values();
        abort_if($ids->isEmpty(), 404, 'Tidak ada aset yang dipilih.');

        $assets = Asset::with(['assetType:id,name', 'assetBrand:id,name', 'assetSeries:id,name', 'entity:id,name'])
            ->whereIn('id', $ids)
            ->orderBy('asset_number')
            ->get();

        return view('assets.print-many', [
            'assets' => $assets,
            'size' => in_array($request->query('size'), ['small', 'medium', 'large'], true) ? $request->query('size') : 'small',
            'layout' => $request->query('layout') === 'sheet' ? 'sheet' : 'roll',
        ]);
    }

    public function printSingle(Asset $asset) // Menggunakan Route Model Binding
    {
        // Tampilkan view khusus untuk cetak satu label
        return view('assets.print_single_label', ['asset' => $asset]);
    }
}
