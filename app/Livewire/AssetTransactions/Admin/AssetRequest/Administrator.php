<?php

namespace App\Livewire\Admin\AssetRequest;

use Livewire\Component;
use App\Models\AssetRequest;
use App\Models\AssetRequestItem;
use Illuminate\Support\Facades\Gate;
use Livewire\WithPagination;

class Administrator extends Component
{
    use WithPagination;

    public $procurementData = [];
    protected $paginationTheme = 'bootstrap';
    
    protected function initializePoData($requests)
    {
        foreach ($requests as $request) {
            foreach ($request->items as $item) {
                if (!isset($this->procurementData[$item->id])) {
                    $this->procurementData[$item->id] = [
                        'pr_number' => $item->pr_number,
                        'accounting_number' => $item->accounting_number,
                    ];
                }
            }
        }
    }
    
    public function saveProcurementDetailsForItem($itemId)
    {
        // ... (logika otorisasi Gate::allows tetap sama) ...
       
        $this->validate([
            "procurementData.{$itemId}.pr_number" => 'required|digits:10',
            "procurementData.{$itemId}.accounting_number" => 'required|digits:12',
        ], [
            "procurementData.{$itemId}.pr_number.required" => 'Nomor PR harus diisi',
            "procurementData.{$itemId}.pr_number.numeric" => 'Nomor PR harus berupa angka',
            "procurementData.{$itemId}.pr_number.min" => 'Nomor PR harus terdiri dari 10 digit',
            "procurementData.{$itemId}.pr_number.max" => 'Nomor PR harus terdiri dari 10 digit',
            "procurementData.{$itemId}.accounting_number.required" => 'Nomor Akunting harus diisi',
            "procurementData.{$itemId}.accounting_number.numeric" => 'Nomor Akunting harus berupa angka',
            "procurementData.{$itemId}.accounting_number.min" => 'Nomor Akunting harus terdiri dari 12 digit',
            "procurementData.{$itemId}.accounting_number.max" => 'Nomor Akunting harus terdiri dari 12 digit',
        ]);
      
        $item = AssetRequestItem::find($itemId);
       
        // Hanya proses item yang merupakan pembelian baru
        if ($item && $item->fulfillment_type === 'NEW_PURCHASE') {
            $item->update([
                'pr_number' => $this->procurementData[$itemId]['pr_number'],
                'accounting_number' => $this->procurementData[$itemId]['accounting_number'],
            ]);
           
            session()->flash('message', "Data pengadaan untuk item '{$item->assetSeries->name}' berhasil disimpan.");
            
            // Periksa apakah seluruh pengajuan sudah selesai
            $this->checkIfRequestIsCompleted($item->asset_request_id);
           
        } else {
            session()->flash('error', 'Item ini tidak memerlukan data pengadaan (bukan pembelian baru).');
        }
    }

    /**
     * Mengecek & menyelesaikan tahap input PO.
     * LOGIKA INI DIPERBARUI untuk hanya mengecek item NEW_PURCHASE.
     */
    public function checkIfRequestIsCompleted($requestId)
    {
        $request = AssetRequest::find($requestId);

        $itemsNeedingPo = $request->items()
                                ->where('fulfillment_type', 'NEW_PURCHASE')
                                ->where(fn($q) => $q->whereNull('pr_number')->orWhere('pr_number', ''))
                                ->count();

        if ($itemsNeedingPo === 0) {
            $request->update(['status' => 'Pending PO']);
            session()->flash('global-message', "Semua Nomor PR dan Accoutning untuk pengajuan #{$request->request_number} telah lengkap. Proses pengajuan ini selesai.");
            // $this->emit('refreshDashboard'); // Refresh dashboard jika perlu
        }
    }
    public function render()
    {
        $requests = AssetRequest::where('status', 'Pending PR & Accounting Number')
        ->orWhere('status','Pending PR Number')
        ->orWhere('status','Pending Accounting Number')
        // ---- TAMBAHAN 1: Filter Pengajuan Utama ----
        // Hanya ambil pengajuan yang memiliki ('Has') relasi 'items'
        // yang memenuhi kondisi di dalam closure.
        ->whereHas('items', function ($query) {
            $query->where('fulfillment_type', 'NEW_PURCHASE');
        })
    
        ->with([
            'requester:id,name',
            
            // ---- TAMBAHAN 2: Filter Item yang Ditampilkan (Opsional, tapi direkomendasikan) ----
            // Saat me-load relasi 'items', kita juga bisa memfilternya
            // agar hanya item 'NEW_PURCHASE' yang ikut ditampilkan.
            'items' => function ($query) {
                $query->where('fulfillment_type', 'NEW_PURCHASE')
                      ->with(['assetType:id,name', 'assetBrand:id,name', 'assetSeries:id,name']);
            },
        ])
        ->orderBy('request_date', 'asc')
        ->paginate(10);

        $this->initializePoData($requests);
        return view('livewire.admin.asset-request.administrator',[
            'requests' => $requests
        ]);
    }
}
