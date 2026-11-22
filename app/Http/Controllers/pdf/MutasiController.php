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
        $bast = AssetTransaction::with(['approvals.actionBy', 'creator'])->findOrFail($id);
        $this->authorize('view-asset-transaction', $bast);

        // creator bisa null (user sudah dihapus): jangan sampai error 500.
        $departmentId = $bast->creator?->department_id;
        $destinationCostCenter = $departmentId
            ? CostCenter::where('jobsite_id', $bast->to_jobsite_id)->where('department_id', $departmentId)->first()
            : null;
        $fromCostCenter = $departmentId
            ? CostCenter::where('jobsite_id', $bast->from_jobsite_id)->where('department_id', $departmentId)->first()
            : null;
        $pdf = Pdf::loadView('pdf.mutasi_asset', compact('bast','destinationCostCenter','fromCostCenter'))->setPaper('A4', 'portrait');
        $filename = 'MUTASI-'.str_replace(['/', '\\'], '-', $bast->transaction_number) . '.pdf';
        
        return $pdf->stream($filename);
    }
}
