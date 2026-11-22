<?php

namespace App\Http\Controllers;

use App\Models\AssetHandover;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf; // Import Facade PDF

class PdfController extends Controller
{
    public function generateBastPdf($handoverId)
    {
        $handover = AssetHandover::with([
            'giver.department', // Asumsi ada relasi department di User model
            'receiver.department',
            'items.asset.assetType',
            'items.asset.assetBrand',
            'items.asset.assetSeries'
        ])->findOrFail($handoverId);

        // Data yang akan dikirim ke view PDF
        $data = [
            'handover' => $handover,
            'title' => 'Berita Acara Serah Terima Aset - ' . $handover->handover_number,
        ];

        $pdf = Pdf::loadView('pdf.bast_asset', $data);

        // Opsi:
        // 1. Download PDF:
        return $pdf->download('BAST-' . str_replace('/', '_', $handover->handover_number) . '.pdf');

        // 2. Tampilkan PDF di browser (stream):
        // return $pdf->stream('BAST-' . str_replace('/', '_', $handover->handover_number) . '.pdf');
    }
}