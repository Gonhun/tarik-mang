<?php

namespace App\Http\Controllers\pdf;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Asset;
use App\Http\Controllers\Controller;
use App\Models\AssetTransaction;

class PengembalianController extends Controller
{
    public function view(int $id)
    {
        $bast = Asset::with(['returnTransaction.items','returnTransaction.fromUser','returnTransaction.toUser','returnTransaction.fromJobsite','returnTransaction.toJobsite','returnTransaction.approvals'])->findOrFail($id);
        abort_if(!$bast->returnTransaction, 404, 'Aset ini belum memiliki transaksi pengembalian.');
        $pdf = Pdf::loadView('pdf.pengembalian_asset', compact('bast'))->setPaper('A4', 'portrait');
        $filename = 'PENGEMBALIAN-'.str_replace(['/', '\\'], '-', $bast->returnTransaction->transaction_number) . '.pdf';
        return $pdf->stream($filename);
    }
}
