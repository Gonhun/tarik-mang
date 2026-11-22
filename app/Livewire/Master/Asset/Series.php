<?php

namespace App\Livewire\Master\Asset;

use Livewire\Component;
use App\Models\AssetSeries;
use App\Models\AssetType;
use App\Models\AssetBrand;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;

class Series extends Component
{
    use WithPagination;

    public $asset_series_id, $asset_type_id, $asset_brand_id, $name,$material_code, $specifications;
    public $assetTypes = [];
    public $assetBrands = []; 

    public $isModalOpen = false;
    public $search = '';
    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $this->assetTypes = AssetType::orderBy('name')->get(['id', 'name']);
        $this->assetBrands = collect(); // Inisialisasi sebagai koleksi kosong
    }
    public function assetTypeChanged()
    {
        // dd('handleAssetTypeChange dipanggil. Nilai asset_type_id: ' . $this->asset_type_id);
        $this->updatedAssetTypeId($this->asset_type_id);
        // Anda bisa memanggil logika dari updatedAssetTypeId di sini jika mau
        // if (!empty($this->asset_type_id)) {
        //     $this->assetBrands = AssetBrand::where('asset_type_id', $this->asset_type_id)->orderBy('name')->get(['id', 'name']);
        // } else {
        //     $this->assetBrands = collect();
        // }
        // $this->asset_brand_id = null;
        // $this->name = '';
    }
   
    protected function rules()
    {
        $nameRules = [];
        $nameRules[] = 'string';
        $nameRules[] = 'max:255';
        $nameRules[] = Rule::unique('asset_series', 'name')
            ->where('asset_brand_id', $this->asset_brand_id)
            ->ignore($this->asset_series_id);
        return [
            'asset_type_id' => 'required|exists:asset_types,id',
            'asset_brand_id' => [
                'required',
                Rule::exists('asset_brands', 'id')->where(function ($query) {
                    return $query->where('asset_type_id', $this->asset_type_id);
                }),
            ],
            'material_code' => [
                'nullable', // Boleh kosong
                'string',
                'max:255', // Sesuaikan
                // Periksa keunikan hanya di antara record yang aktif (belum di-soft delete)
                Rule::unique('asset_series', 'material_code')
                    ->ignore($this->asset_series_id) // Abaikan record saat ini jika update
                    ->whereNull('deleted_at'),
            ],
            'name' => $nameRules,
            'specifications' => 'required|string',
        ];
    }

    protected $messages = [
        'asset_type_id.required' => 'Tipe aset wajib dipilih.',
        'asset_brand_id.required' => 'Merek aset wajib dipilih.',
        'asset_brand_id.exists' => 'Merek aset tidak valid untuk tipe aset yang dipilih.',
        'name.required' => 'Nama/Model Seri wajib diisi.',
        'name.unique' => 'Kombinasi Nama/Model Seri dan Merek Aset ini sudah ada.',
        'name.string' => 'Nama/Model Seri harus berupa teks.',
        'name.max' => 'Nama/Model Seri maksimal 255 karakter.',
        'material_code.string' => 'Kode material harus berupa teks.',
        'material_code.max' => 'Kode material maksimal 255 karakter.',
        'material_code.unique' => 'Kode material ini sudah digunakan.',
        'specifications.string' => 'Spesifikasi harus berupa teks.',
    ];

    // Lifecycle hook saat $asset_type_id diupdate
    public function updatedAssetTypeId($value)
    {
        if (!empty($value)) {
            $this->assetBrands = AssetBrand::where('asset_type_id', $value)->orderBy('name')->get(['id', 'name']);
        } else {
            $this->assetBrands = collect();
        }
        $this->asset_brand_id = null; // Reset pilihan merek jika tipe berubah
        $this->name = ''; // Reset nama seri jika tipe berubah
    }

    public function render()
    {
        $query = AssetSeries::with(['assetType', 'assetBrand']);

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('specifications', 'like', '%' . $this->search . '%')
                  ->orWhereHas('assetType', function ($subQuery) {
                      $subQuery->where('name', 'like', '%' . $this->search . '%');
                  })
                  ->orWhereHas('assetBrand', function ($subQuery) {
                      $subQuery->where('name', 'like', '%' . $this->search . '%');
                  });
            });
        }

        $assetSeriesCollection = $query->orderBy('id', 'DESC')->paginate(10);

        return view('livewire.master.series', [
            'assetSeriesCollection' => $assetSeriesCollection,
        ]);
    }

    public function create()
    {
        $this->resetInputFields();
        $this->openModal();
    }

    public function openModal()
    {
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->resetValidation();
    }

    private function resetInputFields()
    {
        $this->asset_series_id = null;
        $this->asset_type_id = null;
        $this->asset_brand_id = null;
        $this->name = '';
        $this->material_code='';
        $this->specifications = '';
        $this->assetBrands = collect(); // Kosongkan pilihan merek
        $this->resetValidation();
    }

    public function store()
    {
        $this->validate();
        // if(empty($this->name))
        // {
        //     $this->name=null;
        // }
        
        AssetSeries::updateOrCreate(['id' => $this->asset_series_id], [
            'asset_type_id' => $this->asset_type_id,
            'asset_brand_id' => $this->asset_brand_id,
            'material_code' => $this->material_code,
            'name' => $this->name,
            'specifications' => $this->specifications,
        ]);

        session()->flash('message',
            $this->asset_series_id ? 'Seri Aset berhasil diperbarui.' : 'Seri Aset berhasil ditambahkan.');
        
        $this->closeModal();
        $this->resetInputFields();
        
    }

    public function edit($id)
    {
        $series = AssetSeries::findOrFail($id);
        $this->asset_series_id = $id;
        $this->asset_type_id = $series->asset_type_id;

        // Penting: Load merek berdasarkan tipe aset yang dipilih
        if (!empty($this->asset_type_id)) {
            $this->assetBrands = AssetBrand::where('asset_type_id', $this->asset_type_id)->orderBy('name')->get(['id', 'name']);
        } else {
            $this->assetBrands = collect();
        }
        $this->material_code= $series->material_code ;
        $this->asset_brand_id = $series->asset_brand_id;
        $this->name = $series->name;
        $this->specifications = $series->specifications;

        $this->openModal();
    }

    public function delete($id)
    {
        try {
            AssetSeries::find($id)->delete();
            session()->flash('message', 'Seri Aset berhasil dihapus.');
        } catch (\Illuminate\Database\QueryException $e) {
             session()->flash('error', 'Gagal menghapus seri aset. Kemungkinan seri ini masih digunakan oleh data lain.');
        } catch (\Exception $e) {
            session()->flash('error', 'Terjadi kesalahan saat menghapus seri aset.');
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }
   
}
