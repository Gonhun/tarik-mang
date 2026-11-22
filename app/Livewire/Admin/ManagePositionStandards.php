<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Position;
use App\Models\PositionAssetStandard;
use App\Models\AssetType;
use App\Models\AssetBrand;
use App\Models\AssetSeries;
use App\Models\Department;
use Livewire\WithPagination;

class ManagePositionStandards extends Component
{
    use WithPagination; // Opsional

    public $selectedPositionId = null; // ID jabatan yang dipilih dari dropdown
    public $selectedDepartementId = null;
    public ?Position $position = null; 

    // Properti untuk form tambah/edit standar item
    public $standard_id, $asset_type_id, $asset_brand_id, $asset_series_id;
    public $quantity = 1, $specifications_notes;

    // Data untuk dropdown di form
    public $allDepartements = [];
    public $allPositions = []; // Untuk dropdown pilih jabatan
    public $assetTypes = [];
    public $assetBrands = [];
    public $assetSeries = [];

    public $isModalOpen = false;
    protected $paginationTheme = 'bootstrap';

    // Listener untuk menerima ID jabatan dari luar jika komponen ini reusable
    // protected $listeners = ['loadPositionStandards'];

    public function mount() // Bisa menerima ID jabatan saat mount
    {
        $this->allDepartements = Department::orderBy('name')->get(['id', 'name','code']);
        $this->allPositions = collect();
        // $this->allPositions = Position::orderBy('name')->get(['id', 'name', 'code']);
        $this->assetTypes = AssetType::orderBy('name')->get(['id', 'name']);
        $this->assetBrands = collect();
        $this->assetSeries = collect();
    }
    public function SelectedDepartementId($departementId)
    {
        if (!empty($departementId)) {
            $this->allPositions = Position::where('department_id', $departementId)->orderBy('name')->get(['id', 'name']);
        } else {
            $this->allPositions = null; // Reset jika tidak ada jabatan dipilih
        }
        $this->position=null;
        $this->resetInputFields(); // Reset form standar aset
        $this->closeModal(); // Tutup modal jika terbuka
    }
    public function SelectedPositionId($positionId)
    {
        if (!empty($positionId)) {
            $this->position = Position::with([
                'assetStandards.assetType', 
                'assetStandards.assetBrand', 
                'assetStandards.assetSeries'
            ])->find($positionId);
        } else {
            $this->position = null; // Reset jika tidak ada jabatan dipilih
        }
        $this->resetInputFields(); // Reset form standar aset
        $this->closeModal(); // Tutup modal jika terbuka
    }

    // --- Dropdown Dinamis untuk Form Standar Item ---
    public function changeAssetTypeId($typeId)
    {
        if (!empty($typeId)) {
            $this->asset_type_id = $typeId;
            $this->assetBrands = AssetBrand::where('asset_type_id', $typeId)->orderBy('name')->get(['id','name']);
        } else {
            $this->assetBrands = collect();
        }
        $this->asset_brand_id = null;
        $this->asset_series_id = null;
        $this->assetSeries = collect();
    }
    public function changeAssetSeriesId($seriesId)
    {
        if (!empty($seriesId)) {
            $this->asset_series_id = $seriesId;
            $this->specifications_notes = AssetSeries::find($seriesId)->specifications;
            // $this->assetSeries = AssetSeries::where('asset_brand_id', $this->asset_brand_id)->orderBy('name')->get(['id', 'name','specifications']);
        } else {
            $this->asset_series_id = null;
        }
    }
    public function changeAssetBrandId($brandId)
    {
        if (!empty($brandId)) {
            $this->asset_brand_id = $brandId;
            $this->assetSeries = AssetSeries::where('asset_brand_id', $brandId)->orderBy('name')->get();
        } else {
            $this->assetSeries = collect();
        }
        $this->asset_series_id = null;
    }
    // --- Akhir Dropdown Dinamis ---

    protected function rules()
    {
        return [
            'asset_type_id' => 'required|exists:asset_types,id',
            'asset_brand_id' => 'required|exists:asset_brands,id',
            'asset_series_id' => 'required|exists:asset_series,id',
            'quantity' => 'required|integer|min:1',
            'specifications_notes' => 'nullable|string',
        ];
    }

    protected $messages = [
        'asset_type_id.required' => 'Tipe aset wajib dipilih',
        'asset_type_id.exists' => 'Tipe aset tidak valid',
        'asset_brand_id.required' => 'Merek aset wajib dipilih',
        'asset_brand_id.exists' => 'Merek aset tidak valid', 
        'asset_series_id.required' => 'Seri aset wajib dipilih',
        'asset_series_id.exists' => 'Seri aset tidak valid',
        'quantity.required' => 'Jumlah wajib diisi',
        'quantity.integer' => 'Jumlah harus berupa angka',
        'quantity.min' => 'Jumlah minimal 1',
        'specifications_notes.max' => 'Catatan spesifikasi maksimal 500 karakter',
    ];

    public function create()
    {
        if (!$this->position) {
            session()->flash('error', 'Silakan pilih jabatan terlebih dahulu.');
            return;
        }
        $this->resetInputFields();
        $this->openModal();
    }
    
    private function resetInputFields()
    {
        $this->standard_id = null;
        $this->asset_type_id = null;
        $this->asset_brand_id = null;
        $this->asset_series_id = null;
        $this->quantity = 1;
        $this->specifications_notes = '';
        // $this->assetBrands = collect(); // Jangan reset ini jika asset_type_id masih ada
        // $this->assetSeries = collect(); // Jangan reset ini jika asset_brand_id masih ada
        if(empty($this->asset_type_id)) $this->assetBrands = collect();
        if(empty($this->asset_brand_id)) $this->assetSeries = collect();
        $this->resetValidation();
    }
    
    public function store()
    {
        if (!$this->position || !$this->position->id) {
            $this->dispatch('swal:error', [
                'title' => 'Error!',
                'text' => 'Jabatan belum dipilih untuk menyimpan standar aset.',
            ]);
            return;
        }

        $validatedData = $this->validate();
        $validatedData['quantity'] =1;
        $this->position->assetStandards()->updateOrCreate(
            ['id' => $this->standard_id],
            [
                'asset_type_id' => $validatedData['asset_type_id'],
                'asset_brand_id' => $this->asset_brand_id,
                'asset_series_id' => $this->asset_series_id,
                'quantity' => $validatedData['quantity'],
                'specifications_notes' => $this->specifications_notes,
            ]
        );
        $this->dispatch('swal:success', [
            'title' => 'Berhasil!',
            'text' => $this->standard_id ? 'Standar Aset berhasil diperbarui.' : 'Standar Aset berhasil ditambahkan.',
        ]);
        $this->closeModal();
        $this->resetInputFields();
        // Muat ulang data jabatan (termasuk standar asetnya) untuk refresh tampilan
        $this->position = Position::with('assetStandards.assetType', 'assetStandards.assetBrand', 'assetStandards.assetSeries')->find($this->position->id);
    }

    public function edit($standardId)
    {
        $standard = PositionAssetStandard::find($standardId);
        if ($standard && $this->position && $standard->position_id == $this->position->id) {
            $this->standard_id = $standard->id;
            $this->asset_type_id = $standard->asset_type_id;
            
            if($this->asset_type_id){
                $this->assetBrands = AssetBrand::where('asset_type_id', $this->asset_type_id)->orderBy('name')->get(['id', 'name']);
            } else { $this->assetBrands = collect(); }
            $this->asset_brand_id = $standard->asset_brand_id;

            if($this->asset_brand_id){
                 $this->assetSeries = AssetSeries::where('asset_brand_id', $this->asset_brand_id)->orderBy('name')->get(['id', 'name']);
            } else { $this->assetSeries = collect(); }
            $this->asset_series_id = $standard->asset_series_id;
            
            $this->quantity = $standard->quantity;
            $this->specifications_notes = $standard->specifications_notes;
            $this->openModal();
        }
    }

    public function delete($standardId)
    {
        if (!$this->position) return;
        $standard = $this->position->assetStandards()->find($standardId);
        if ($standard) {
            $standard->delete();
            session()->flash('message', 'Standar Aset berhasil dihapus.');
            $this->position = Position::with('assetStandards.assetType', 'assetStandards.assetBrand', 'assetStandards.assetSeries')->find($this->position->id); // Muat ulang
        }
    }

    public function openModal() { $this->isModalOpen = true; }
    public function closeModal() { $this->isModalOpen = false; }

    public function render()
    {
        return view('livewire.admin.manage-position-standards');
    }
}
