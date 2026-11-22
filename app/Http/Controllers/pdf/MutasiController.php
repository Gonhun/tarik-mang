<?php

namespace App\Http\Controllers\pdf;

use App\Http\Controllers\Controller;
use App\Models\AssetTransaction;
use App\Models\CostCenter;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class MutasiController extends Controller
{
    
    public function view(int $id)
    {
        $bast = AssetTransaction::with(['approvals.actionBy'])->findOrFail($id);
        $destinationCostCenter = CostCenter::where('jobsite_id', $bast->to_jobsite_id)
                                                       ->where('department_id', $bast->creator->department_id)
                                                       ->first();
        $fromCostCenter = CostCenter::where('jobsite_id', $bast->from_jobsite_id)->where('department_id', $bast->creator->department_id)->first();
        $pdf = Pdf::loadView('pdf.mutasi_asset', compact('bast','destinationCostCenter','fromCostCenter'))->setPaper('A4', 'portrait');
        $filename = 'MUTASI-'.str_replace(['/', '\\'], '-', $bast->transaction_number) . '.pdf';
        
        return $pdf->stream($filename);
    }
}
