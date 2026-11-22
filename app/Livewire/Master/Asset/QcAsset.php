<?php

namespace App\Livewire\Master\Asset;


use Livewire\Component;
use App\Models\AssetRequest;
use App\Models\AssetRequestItem;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\AssetBrand;
use App\Models\AssetSeries;
use App\Models\Entity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\WithPagination;

class QcAsset extends Component
{
    use WithPagination;

    // Properti untuk modal QC
    public $isModalOpen = false;
    public $isModalSn = false;
    public ?AssetRequestItem $itemToQc = null;

    //properti asset id
    public $assetId;

    // Properti untuk form di dalam modal
    public $qc_asset_type_id, $qc_asset_brand_id, $qc_asset_series_id,$qc_entity_id;
    public $qc_serial_number, $qc_change_reason;
    public $qc_failure_reason = ''; // Properti untuk alasan gagal QC

    // Properti untuk "Mastering Seri Baru"
    public $isAddingNewSeries = false;
    public $newSeriesName, $newSeriesSpecifications;

    // Data untuk dropdown
    public $allTypes = [], $allBrands = [], $allSeries = [], $allEntities = [];

    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        // Muat data master sekali untuk dropdown di modal
        $this->allTypes = AssetType::orderBy('name')->get();
        $this->allEntities = Entity::orderBy('name')->get();
        $this->allBrands = collect();
        $this->allSeries = collect();
    }

    // --- LOGIKA MODAL ---
    public function openQcModal($itemId)
    {
        $this->itemToQc = AssetRequestItem::with(['assetType', 'assetBrand', 'assetSeries'])->find($itemId);
        if ($this->itemToQc) {
            // Pra-isi form dengan data asli dari permintaan
            $this->qc_entity_id = $this->allEntities->first()?->id;
            $this->qc_asset_type_id = $this->itemToQc->asset_type_id;
            $this->qc_asset_brand_id = $this->itemToQc->asset_brand_id;
            $this->qc_asset_series_id = $this->itemToQc->asset_series_id;

            // Muat dropdown dinamis berdasarkan data pra-isi
            $this->updatedQcAssetTypeId($this->qc_asset_type_id);
            $this->updatedQcAssetBrandId($this->qc_asset_brand_id);

            $this->reset(['qc_serial_number', 'qc_change_reason', 'qc_failure_reason', 'isAddingNewSeries', 'newSeriesName', 'newSeriesSpecifications']);
            $this->resetValidation();
            $this->isModalOpen = true;
        }
    }

    public function closeModal() { $this->isModalOpen = false; }
    public function openModalSn($itemSn){
        $this->isModalOpen = false;
        $this->isModalSn = true;
        $this->assetId = Asset::where('serial_number', '=', $itemSn)->select('id')->first();
    }

    public function closeModalSn() { $this->isModalSn = false; }

    // --- LOGIKA DROPDOWN DINAMIS ---
    public function updatedQcAssetTypeId($typeId)
    {
        if (!empty($typeId)) {
            $this->allBrands = AssetBrand::where('asset_type_id', $typeId)->orderBy('name')->get();
        } else {
            $this->allBrands = collect();
        }
        $this->reset(['qc_asset_brand_id', 'qc_asset_series_id']);
        $this->allSeries = collect();
    }

    public function updatedQcAssetBrandId($brandId)
    {
        if (!empty($brandId)) {
            $this->allSeries = AssetSeries::where('asset_brand_id', $brandId)->orderBy('name')->get();
        } else {
            $this->allSeries = collect();
        }
        $this->reset('qc_asset_series_id');
    }

    // --- LOGIKA MASTERING SERI BARU ---
    public function toggleAddNewSeries() { $this->isAddingNewSeries = !$this->isAddingNewSeries; }

    public function saveNewSeries()
    {
        $this->validate([
            'qc_asset_type_id' => 'required|exists:asset_types,id',
            'qc_entity_id' => 'required|exists:entities,id',
            'qc_asset_brand_id' => 'required|exists:asset_brands,id',
            'newSeriesName' => ['required', 'string', 'max:255', Rule::unique('asset_series', 'name')->where('asset_brand_id', $this->qc_asset_brand_id)],
            'newSeriesSpecifications' => 'nullable|string',
        ]);

        $newSeries = AssetSeries::create([
            'asset_type_id' => $this->qc_asset_type_id,
            'entity_id' => $this->qc_entity_id,
            'asset_brand_id' => $this->qc_asset_brand_id,
            'name' => $this->newSeriesName,
            'specifications' => $this->newSeriesSpecifications,
        ]);

        $this->updatedQcAssetBrandId($this->qc_asset_brand_id);
        $this->qc_asset_series_id = $newSeries->id;
        $this->isAddingNewSeries = false;
        $this->dispatch('show-toast', ['type' => 'success', 'message' => 'Seri baru berhasil ditambahkan.']);
    }

    /**
     * Method untuk LULUS QC dan mendaftarkan aset.
     */
    public function passQcItem()
    {
        
        $cc = findCostCenter(Auth::user()->department_id, Auth::user()->jobsite_id);
        if(!$cc || !$this->itemToQc) {
            session()->flash('error', "Cost Center atau Asset Tidak ditemukan!.");
            return;
        }
        
       
        $this->validate([
            'qc_asset_series_id' => [
                'required',
                'exists:asset_series,id'
            ],
            'qc_entity_id' => [
                'required',
                'exists:entities,id'
            ],
            'qc_asset_type_id' => [
                'required',
                'exists:asset_types,id'
            ],
            'qc_asset_brand_id' => [
                'required',
                'exists:asset_brands,id'
            ],
            'qc_serial_number' => [
                'required',
                'string',
                'max:255',
                'unique:assets,serial_number'
            ],
            'qc_change_reason' => [
                Rule::requiredIf($this->itemToQc->asset_series_id != $this->qc_asset_series_id),
                'nullable',
                'string',
                'max:255'
            ],
        ]);

        DB::transaction(function () {
           
        
            Asset::create([
                'entity_id' => $this->qc_entity_id,
                'pr_number' => $this->itemToQc->pr_number,
                'po_number' => $this->itemToQc->po_number,
                'sap_number' => $this->itemToQc->accounting_number,
                'department_id' => Auth::user()->department_id,
                'asset_request_item_id' => $this->itemToQc->id,
                'asset_number' => generateAssetNumber($this->qc_entity_id, $this->itemToQc->asset_type_id),
                'serial_number' => $this->qc_serial_number,
                'asset_type_id' => $this->qc_asset_type_id,
                'asset_brand_id' => $this->qc_asset_brand_id,
                'asset_series_id' => $this->qc_asset_series_id,
                'category_id' => 'Ruangan IT',
                'cost_center_id' => findCostCenter(Auth::user()->department_id, Auth::user()->jobsite_id)->id,
                'status' => 'Free',
                'jobsite_id' => $this->itemToQc->assetRequest->jobsite_id,
                'created_by' => Auth::id(),
            ]);

            $this->itemToQc->update([
                'qc_status' => 'Passed',
                'procurement_notes' => $this->qc_change_reason,
            ]);

            $this->checkIfRequestIsCompleted($this->itemToQc->assetRequest);
        });

        session()->flash('message', "Aset baru berhasil didaftarkan.");
        $this->openModalSn($this->qc_serial_number);
    }

    /**
     * Method untuk GAGAL QC dan menandai untuk proses return.
     */
    public function failQcItem()
    {
        if (!$this->itemToQc) return;

        $this->validate(['qc_failure_reason' => 'required|string|min:10'], 
                        ['qc_failure_reason.required' => 'Alasan Gagal QC / Return wajib diisi.']);

        DB::transaction(function() {
            $this->itemToQc->update([
                'qc_status' => 'Failed',
                'procurement_notes' => $this->qc_failure_reason,
            ]);
            $this->itemToQc->assetRequest->update(['status' => 'Requires Return']);
        });

        session()->flash('message', "Item ditandai Gagal QC. Pengajuan ini sekarang menunggu tindakan di menu 'Manajemen Return'.");
        $this->closeModal();
    }

    protected function checkIfRequestIsCompleted(AssetRequest $request)
    {
        $pendingQcItems = $request->items()->where('fulfillment_type', 'NEW_PURCHASE')->where('qc_status', 'Pending')->count();
        if ($pendingQcItems === 0) {
            $failedItemsCount = $request->items()->where('qc_status', 'Failed')->count();
            if ($failedItemsCount > 0) {
                $request->update(['status' => 'Requires Return']);
            } else {
                $request->update(['status' => 'Completed']);
            }
        }
    }

    public function render()
    {
        $itemsWaitingForQc = AssetRequestItem::query()
            ->where('fulfillment_type', 'NEW_PURCHASE')
            ->where('qc_status', 'Pending')
            ->whereNotNull('gr_number')
            ->where('gr_number', '!=', '')
            ->where('gr_number', '!=', 'NULL')
            ->whereNotNull('po_number')
            ->where('po_number', '!=', '')
            ->where('po_number', '!=', 'NULL')
            ->with(['assetRequest:id,request_number', 'assetType', 'assetBrand', 'assetSeries'])
            ->paginate(10);

        return view('livewire.master.asset.qc-asset', [
            'itemsWaitingForQc' => $itemsWaitingForQc
        ]);
    }
}
