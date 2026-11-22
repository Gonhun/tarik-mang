<?php

namespace App\Http\Controllers\pdf;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\LicenseRequest;

class requestLicensi extends Controller
{
    public function view(int $id)
    {
        $licens = LicenseRequest::with(['user','items','items.approvals','items.approvals.approver.position'])->findOrFail($id);
       
        $pdf = Pdf::loadView('pdf.request_licensi', compact('licens'))->setPaper('A4', 'portrait');
        return $pdf->stream("Request-{$licens->nomor}.pdf");
        
    }
    
    // $bast = Asset::with(['transaction.items','transaction.fromUser','transaction.toUser','transaction.fromJobsite','transaction.toJobsite','transaction.approvals'])->findOrFail($id);
    // $pdf = Pdf::loadView('pdf.bast_asset', compact('bast'))->setPaper('A4', 'portrait');
    // return $pdf->stream("BAST-{$bast->nomor}.pdf");
}
