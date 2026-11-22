<?php

namespace App\Http\Controllers\pdf;

use App\Http\Controllers\Controller;
use App\Models\AssetRequest;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class AssetRequestController extends Controller
{
    public function view(int $id)
    {
       
        $bast = AssetRequest::with(['requester', 'jobsite', 'currentApprover', 'items', 'approvals'])->findOrFail($id);
        $lastPending = $bast->approvals
        ->where('status', $bast->status)
        ->sortBy('step')
        ->first(); 
        
        $pdf = Pdf::loadView('pdf.request_asset', compact('bast','lastPending'))->setPaper('A4', 'portrait');
        $filename = 'REQUEST-'.str_replace(['/', '\\'], '-', $bast->request_number) . '.pdf';
        
        return $pdf->stream($filename);
    }
}
