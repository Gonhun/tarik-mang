<?php

namespace App\Livewire\Admin\AssetRequest;

use App\Models\AssetRequest;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\PoImport;
use Livewire\Attributes\Validate;

class Importpo extends Component
{
    use WithFileUploads;
    #[Validate('file|mimes:xlsx,xls|max:2048')]
    public $excelFile;
    public $isModalOpen = false;
    protected $paginationTheme = 'bootstrap';

    protected function rules()
    {
        return [
            'excelFile' => 'required|file|mimes:xlsx,xls'
        ];
    }

    protected $messages = [
        'excelFile.required' => 'File Excel wajib diupload.',
        'excelFile.file' => 'File yang diupload harus berupa file.',
        'excelFile.mimes' => 'File harus berformat Excel (.xlsx atau .xls).'
    ];
    public function create()
    {
        
        $this->excelFile = null;
        $this->resetValidation();
        $this->openModal();
    }

    public function openModal()
    {
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->excelFile = null;
        $this->resetValidation();
    }

    
    public function uploadExcel()
    {   
        try {
            $this->validate();
            Excel::Import(new PoImport, $this->excelFile);
            session()->flash('global-message', 'File Excel berhasil diupload dan diproses.');
            $this->closeModal();
            // $this->reset('excelFile');
        } catch (\Exception $e) {
            session()->flash('error', 'Terjadi kesalahan saat memproses file: ' . $e->getMessage());
            $this->reset('excelFile');
        }
        
        // try {
        //     Excel::import(new PoImport, $this->excelFile);
        //     session()->flash('global-message', 'File Excel berhasil diupload dan diproses.');
        //     $this->reset('excelFile');
            
        // } catch (\Exception $e) {
        //     session()->flash('error', 'Terjadi kesalahan saat memproses file: ' . $e->getMessage());
        //     $this->reset('excelFile');
        // }
    }

    public function render()
    {
        $requests = AssetRequest::where(function ($query) {
            $query->where('status', 'Pending PO')
                  ->orWhere('status', 'Completed');
        })
        ->whereHas('items', function ($query) {
            $query->where('fulfillment_type', 'NEW_PURCHASE')
                  ->where(function ($q) {
                      $q->whereNull('po_number')
                        ->orWhereNull('gr_number');
                  });
        })
        ->with([
            'requester:id,name',
            'items' => function ($query) {
                $query->where('fulfillment_type', 'NEW_PURCHASE')
                      ->where(function ($q) {
                          $q->whereNull('po_number')
                            ->orWhereNull('gr_number');
                      })
                      ->with([
                          'assetType:id,name',
                          'assetBrand:id,name',
                          'assetSeries:id,name'
                      ]);
            },
        ])
        ->orderBy('request_date', 'asc')
        ->paginate(10);
                    
        return view('livewire.admin.asset-request.importpo',[
            'myRequests' => $requests
        ]);
    }
}
