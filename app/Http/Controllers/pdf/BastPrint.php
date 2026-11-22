<?php

namespace App\Http\Controllers\pdf;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Asset;

class BastPrint extends Controller
{
    public function view(int $id)
    {
        $bast = Asset::with(['transaction.items','transaction.fromUser','transaction.toUser','transaction.fromJobsite','transaction.toJobsite','transaction.approvals'])->findOrFail($id);
        abort_if(!$bast->transaction, 404, 'Aset ini belum memiliki transaksi serah terima.');
        $pdf = Pdf::loadView('pdf.bast_asset', compact('bast'))->setPaper('A4', 'portrait');
        return $pdf->stream("BAST-{$bast->nomor}.pdf");
    }

    // public function download($id)
    // {
    //     $bast = Bast::with('items')->findOrFail($id);

    //     $pdf = Pdf::loadView('pdf.bast', compact('bast'))->setPaper('A4', 'portrait');

    //     return $pdf->download("BAST-{$bast->nomor}.pdf");
    // }
}
