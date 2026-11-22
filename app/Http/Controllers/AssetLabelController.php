<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Asset;
class AssetLabelController extends Controller
{
    public function printSingle(Asset $asset) // Menggunakan Route Model Binding
    {
        // Tampilkan view khusus untuk cetak satu label
        return view('assets.print_single_label', ['asset' => $asset]);
    }
}
