<?php

namespace App\Livewire\Master\Asset;

use Livewire\Component;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\AssetBrand;
use App\Models\AssetRequestItem;
use App\Models\AssetSeries;
use App\Models\AssetTransaction;
use App\Models\AssetTransactionItem;
use App\Models\CostCenter;
use App\Models\Department;
use App\Models\Jobsite;
use App\Models\Entity;
use App\Models\User;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel; // <-- Import Excel Facade
use App\Exports\AssetsExport;      // <-- Import class Export yang baru dibuat

class ManageAsset extends Component
{
    use WithPagination;

    // Properti untuk form
    public $asset_id;
    public $asset_number,$cost_center, $cost_center_id, $old_asset_number, $pr_number, $po_number, $sap_number;
    public $asset_type_id, $asset_brand_id, $asset_series_id,$asset_request_id;
    public $jobsite_id, $departement_id, $serial_number, $status, $holder_user_id, $current_location_detail;
    public $entity_id;
    public $spec,$notes;
    // Properti untuk populate dropdown
   
    public $assetTypes = [];
    public $assetBrands = [];
    public $assetSeriesCollection = []; // Nama diubah untuk menghindari konflik dengan model AssetSeries
    public $jobsites = [];
    public $departements = [];
    public $entities = [];

    public $selectedJobsite = ''; // Asumsi Anda punya filter ini
    public $selectedStatus = '';  // Asumsi Anda punya filter ini

    // Properti UI
    public $isModalOpen = false;
    public $isEdit = false;
    public $isGR = false;
    public $search = '';
    public $tab = 1;
    protected $paginationTheme = 'bootstrap';
    protected $listeners = ['refreshParentComponent' => '$refresh','refreshAssetList' => '$refresh'];
    public $perPage = 10;
       

    // Daftar status (bisa juga dari config atau model)
    public $statuses;
    public $Rulestatus;

    public function exportExcel()
    {
        $fileName = 'daftar_aset_' . date('Y-m-d_H-i') . '.xlsx';
        
        // Panggil class export dengan membawa nilai filter saat ini
        return Excel::download(new AssetsExport(
            $this->search, 
            $this->selectedJobsite, 
            $this->selectedStatus
        ), $fileName);
    }
    public function openReturnModal($assetId)
    {
        $this->dispatch('openReturnModal', $assetId);
    }
     // Method untuk memicu modal inspeksi
     public function inspectAsset($assetId)
    {
        // Cari transaksi pengembalian terakhir untuk aset ini yang statusnya 'Pending Inspection'
        $transaction = AssetTransaction::where('transaction_type', 'RETURN')
            ->where('status', 'Pending Inspection')
            ->whereHas('items', function ($query) use ($assetId) {
                $query->where('asset_id', $assetId);
            })
            ->latest() // Ambil yang paling baru
            ->first();

        if ($transaction) {
           
            $this->dispatch('openInspectionModal', $transaction->id);
        } else {
            // Jika tidak, tampilkan pesan error
            $this->dispatch('swal:error', [
                'title' => 'Gagal',
                'text' => 'Tidak ada transaksi pengembalian yang menunggu inspeksi untuk aset ini.'
            ]);
        }
    }
    public function openDisposal($assetId, $mode = 'LOST')
    {
        $this->dispatch('openDisposalModal', ['asset_id' => $assetId, 'mode' => $mode === 'DISPOSE' ? 'DISPOSE' : 'LOST']);
    }

    public function createBast($assetId = null)
    {
        $this->dispatch('openBastModal', ['asset_id' => $assetId]);
    }    
    public function mount()
    {
        $this->Rulestatus = Asset::select('status')->distinct()->pluck('status')->toArray();
        $user=Auth::user();
        $this->jobsite_id = $user->jobsite_id;
        $this->assetTypes = AssetType::orderBy('name')->get(['id', 'name']);
        $this->jobsites = Jobsite::orderBy('name')->get(['id', 'name', 'code']);
        $this->departement_id =  $user->department_id;
        $this->departements =  Department::orderBy('code')->get(['id','name','code']);
        $this->entities = Entity::orderBy('name')->get(['id','code','name']);
        $this->status='Free';
        $this->assetBrands = collect();
        $this->assetSeriesCollection = collect();
        if (!$user || !$user->department_id || !$user->jobsite_id) {
            abort(403, 'Akun Anda belum memiliki Department atau Jobsite. Hubungi Administrator.');
        }
        
       
    }
    public function SendFUWhatApp($userID){
        $user = User::find($userID);
        $wa = (string) ($user->whatsapp ?? '');
        if (!$user || $wa === '') {
            $this->dispatch('swal:error', [
                'title' => 'Gagal',
                'text' => 'User ini belum punya nomor WhatsApp, mohon kabari langsung.',
            ]);
            return;
        }

        $pesan = "Halo {$user->name}, terdapat aset yang memerlukan proses BAST. Mohon segera lakukan pengecekan dan tindak lanjut Konfirmasi pada sistem AMS.\n";
        $pesan .= "Yang ada pada menu *Asset Saya*";

        try {
            $sent = sendMessageWithWhatsApp($wa, $pesan);
        } catch (\Throwable $e) {
            Log::warning("Follow up WA ke user #{$user->id} gagal: " . $e->getMessage());
            $sent = false;
        }

        if (!$sent) {
            $this->dispatch('swal:error', ['title' => 'Gagal', 'text' => 'Pesan WhatsApp gagal dikirim. Coba lagi nanti.']);
            return;
        }

        $masked = str_repeat('•', max(0, strlen($wa) - 4)) . substr($wa, -4);
        $this->dispatch('swal:success', [
            'title' => 'Berhasil!',
            'text' => 'Pesan dikirim ke ' . $user->name . ' No WhatsApp ' . $masked . ' 🙏',
        ]);
    }
    protected function rules()
    {
        $assetIdToIgnore = $this->asset_id ?? null;

        return [
            'asset_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('assets', 'asset_number')->ignore($assetIdToIgnore),
            ],
            'old_asset_number' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('assets', 'old_asset_number')->ignore($assetIdToIgnore)->whereNotNull('old_asset_number')->whereNull('deleted_at'),
            ],
            'pr_number' => 'nullable|string|max:255',
            'po_number' => 'nullable|string|max:255',
            'sap_number' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('assets', 'sap_number')->ignore($assetIdToIgnore)->whereNotNull('sap_number')->whereNull('deleted_at'),
            ],
            'asset_type_id' => 'required|exists:asset_types,id',
            'asset_brand_id' => [
                'required',
                Rule::exists('asset_brands', 'id')->where('asset_type_id', $this->asset_type_id),
            ],
            'asset_series_id' => [
                'required',
                Rule::exists('asset_series', 'id')->where('asset_brand_id', $this->asset_brand_id),
            ],
            'entity_id' => 'required|exists:entities,id',
            'jobsite_id' => 'required|exists:jobsites,id',
            'serial_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('assets', 'serial_number')->ignore($assetIdToIgnore)->whereNull('deleted_at'),
            ],
            'status' => ['required', Rule::in($this->Rulestatus)],
            'holder_user_id' => 'nullable|exists:users,id',
            'current_location_detail' => 'nullable|string|max:500',
            'cost_center_id' => 'required|exists:cost_centers,id',
            
        ];
    }

    protected $messages = [
        'asset_number.required' => 'Nomor aset wajib diisi.',
        'asset_number.unique' => 'Nomor aset sudah terdaftar.',
        'asset_number.max' => 'Nomor aset maksimal 255 karakter.',
        'old_asset_number.unique' => 'Nomor aset lama sudah terdaftar.',
        'old_asset_number.max' => 'Nomor aset lama maksimal 255 karakter.',
        'pr_number.max' => 'PR Number maksimal 255 karakter.',
        'po_number.max' => 'PO Number maksimal 255 karakter.',
        'sap_number.unique' => 'Nomor SAP sudah terdaftar.',
        'sap_number.max' => 'Nomor SAP maksimal 255 karakter.',
        'asset_type_id.required' => 'Tipe aset wajib dipilih.',
        'asset_type_id.exists' => 'Tipe aset yang dipilih tidak valid.',
        'asset_brand_id.required' => 'Merek aset wajib dipilih.',
        'asset_brand_id.exists' => 'Merek tidak valid untuk tipe aset yang dipilih.',
        'asset_series_id.required' => 'Seri aset wajib dipilih.',
        'asset_series_id.exists' => 'Seri tidak valid untuk merek aset yang dipilih.',
        'entity_id.required' => 'Entitas wajib dipilih.',
        'entity_id.exists' => 'Entitas yang dipilih tidak valid.',
        'jobsite_id.required' => 'Jobsite wajib dipilih.',
        'jobsite_id.exists' => 'Jobsite yang dipilih tidak valid.',
        'serial_number.required' => 'Nomor seri wajib diisi.',
        'serial_number.unique' => 'Nomor seri sudah terdaftar.',
        'serial_number.max' => 'Nomor seri maksimal 255 karakter.',
        'status.required' => 'Status wajib dipilih.',
        'status.in' => 'Status yang dipilih tidak valid.',
        'holder_user_id.exists' => 'User pemegang tidak valid.',
        'current_location_detail.max' => 'Detail lokasi maksimal 500 karakter.',
        'cost_center_id.required' => 'Cost Center wajib dipilih.',
        'cost_center_id.exists' => 'Cost Center yang dipilih tidak valid.',
    ];

    // Lifecycle hook untuk dropdown dinamis
    public function changeAssetTypeId()
    {
        
        if (!empty($this->asset_type_id)) {
            $this->assetBrands = AssetBrand::where('asset_type_id', $this->asset_type_id)->orderBy('name')->get(['id', 'name']);
            
        } else {
            $this->assetBrands = collect();
        }
        $this->asset_brand_id = null;
        $this->asset_series_id = null;
        $this->assetSeriesCollection = collect();
    }

    public function changeAssetBrandId()
    {
        
        if (!empty($this->asset_brand_id)) {
            $this->assetSeriesCollection = AssetSeries::where('asset_brand_id', $this->asset_brand_id)->orderBy('name')->get();
           
            
        } else {
            $this->assetSeriesCollection = collect();
        }
        $this->asset_series_id = null;
    }
    public function numberAssetTemporarry()
    {
        if (!$this->isEdit) {
            $this->asset_number=generateAssetNumber($this->entity_id, $this->asset_type_id);
        }
        if (str_starts_with( $this->asset_number, 'ERROR_')) {
            // Tampilkan pesan error ke pengguna
            session()->flash('error', 'Gagal menghasilkan nomor aset unik. Silakan coba lagi atau hubungi admin.');
            return;
        }
    }
    
    public function render()
    {
        // Jumlah aset per status (mengikuti filter site) untuk chip & dropdown filter.
        $statusCounts = Asset::query()
            ->when($this->selectedJobsite, fn ($q) => $q->where('jobsite_id', $this->selectedJobsite))
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->orderBy('status')
            ->pluck('total', 'status');
        // Daftar status sebagai teks biasa (dipakai dropdown status di form aset).
        $this->statuses = $statusCounts->keys()->values()->all();

        $query = Asset::with([
            'assetType:id,name', 'assetBrand:id,name', 'assetSeries:id,name,material_code',
            'jobsite:id,code,name', 'holder:id,name,nik', 'entity:id,code,name',
            'departement:id,code,name', 'costCenter:id,cost_center_number',
        ]);
        if (!empty($this->selectedJobsite)) {
            $query->where('jobsite_id', $this->selectedJobsite);
        }
        if (!empty($this->selectedStatus)) {
            $query->where('status', $this->selectedStatus);
        }
        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('asset_number', 'like', '%' . $this->search . '%')
                  ->orWhere('old_asset_number', 'like', '%' . $this->search . '%')
                  ->orWhere('serial_number', 'like', '%' . $this->search . '%') // Jika ada kolom 'name' di tabel assets
                  ->orWhereHas('assetType', fn($sq) => $sq->where('name', 'like', '%' . $this->search . '%'))
                  ->orWhereHas('assetBrand', fn($sq) => $sq->where('name', 'like', '%' . $this->search . '%'))
                  ->orWhereHas('assetSeries', fn($sq) => $sq->where('name', 'like', '%' . $this->search . '%'))
                  ->orWhereHas('assetSeries', fn($sq) => $sq->where('material_code', 'like', '%' . $this->search . '%'))
                  ->orWhereHas('jobsite', fn($sq) => $sq->where('name', 'like', '%' . $this->search . '%'))
                  ->orWhereHas('holder', fn($sq) => $sq->where('name', 'like', '%' . $this->search . '%'));
                  $q->orWhereHas('entity', fn($sq) => $sq->where('name', 'like', '%' . $this->search . '%')); // <-- Tambah pencarian berdasarkan nama entitas
            });
        }

        $assetsCollection = $query->orderBy('id', 'DESC')->paginate($this->perPage);
        
        $waittingGRCount = AssetRequestItem::whereNotIn('id', function($query) {
            $query->select('asset_request_item_id')
                  ->from('assets')
                  ->whereNotNull('asset_request_item_id');
        })
        ->whereNotNull('po_number')
        ->whereHas('assetRequest', function ($query) {
            // Kondisinya: 'jobsite_id' di tabel 'asset_requests' harus sama dengan jobsite_id user
            $query->where('jobsite_id', Auth::user()->jobsite_id);
        })
        ->count();
       
        return view('livewire.master.asset.manage-asset', [
            'assetsCollection' => $assetsCollection,
            'statusCounts' => $statusCounts,
            'waittingGRCount' => $waittingGRCount,
            // Daftar holder (6000+ user) hanya dimuat saat form terbuka, tidak disimpan di state komponen.
            'users' => $this->isModalOpen ? User::orderBy('name')->get(['id', 'name', 'nik']) : collect(),
        ]);
    }
    public function create()
    {
        $this->resetInputFields();
        $this->openModal();
    }

    public function openModal()
    {
        $cc = CostCenter::where('department_id',$this->departement_id)
                ->where('jobsite_id',$this->jobsite_id)
                ->first(['id', 'cost_center_number']);
                if($cc)
                {
                    $this->cost_center = $cc->cost_center_number;
                    $this->cost_center_id = $cc->id;
                }else{
                    $this->cost_center = '';
                    $this->cost_center_id = '';
                }
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        
        $this->isModalOpen = false;
        $this->resetValidation();
    }

    private function resetInputFields()
    {
        $this->asset_id = null;
        $this->asset_request_id=null;
        $this->asset_number = '';
        $this->old_asset_number = '';
        $this->pr_number = '';
        $this->po_number = '';
        $this->sap_number = '';
        $this->cost_center ='';
        $this->asset_type_id = null;
        $this->asset_brand_id = null;
        $this->asset_series_id = null;
        $this->entity_id = null;
        $this->jobsite_id = Auth::user()->jobsite_id;
        $this->departement_id =  Auth::user()->department_id;
        $this->serial_number = '';
        $this->status = 'Free'; // Default status
        $this->holder_user_id = null;
        $this->current_location_detail = '';
        $this->isEdit = false;
        $this->isGR = false;
        $this->spec = null;
        $this->notes=null;
        $this->assetBrands = collect();
        $this->assetSeriesCollection = collect();
        $this->resetValidation();
    }
    
    public function updatedDepartementId($value)
    {
       if($value)
       {
            $this->departement_id= $value;
            if($this->jobsite_id)
            {
                $cc = CostCenter::where('department_id',$value)
                ->where('jobsite_id',$this->jobsite_id)
                ->first(['id', 'cost_center_number']);
                if($cc)
                {
                    $this->cost_center = $cc->cost_center_number;
                    $this->cost_center_id = $cc->id;
                }else{
                    $this->cost_center = '';
                    $this->cost_center_id = '';
                }
            }
       }
    }
    public function updatedJobsiteId($value)
    {
        
        if($value)
       {
            $this->jobsite_id= $value;
            if($this->departement_id)
            {
                $cc = CostCenter::where('department_id',$this->departement_id)
                ->where('jobsite_id',$this->jobsite_id)
                ->first(['id', 'cost_center_number']);
                if($cc)
                {
                    $this->cost_center = $cc->cost_center_number;
                    $this->cost_center_id = $cc->id;
                }else{
                    $this->cost_center = '';
                    $this->cost_center_id = '';
                }
            }
       } 
    }
   
    public function store()
    {
        try {
           
            // Validasi (kalau gagal akan otomatis tampil di Blade)
            $validatedData = $this->validate($this->rules());
            $dataToStore = $validatedData;
            $dataToStore['department_id'] = Auth::user()->department_id; 
            $dataToStore['entity_id'] = $this->entity_id;
            
            if (!$this->asset_id) {
                $dataToStore['created_by'] = Auth::id();
    
                if ($this->entity_id && $this->asset_type_id) {
                    $this->numberAssetTemporarry();
                }
            }
           
            Asset::updateOrCreate(['id' => $this->asset_id], $dataToStore);
    
            session()->flash('message',
                $this->asset_id ? 'Aset berhasil diperbarui.' : 'Aset berhasil ditambahkan.'
            );
    
            $this->closeModal();
            $this->resetInputFields();
    
        } catch (\Illuminate\Validation\ValidationException $e) {
    
            // biarkan Livewire menampilkan error normal di Blade
            throw $e;
    
        } catch (\Exception $e) {
    
            // untuk error lain seperti DB, logic, dll
            session()->flash('error', $e->getMessage());
        }
    }
    public function storeGR($id)
    {
        $validatedData = $this->validate($this->rules());
        $dataToStore = $validatedData;
        $dataToStore['department_id'] = Auth::user()->department_id; 
        $dataToStore['asset_request_item_id'] = $id; 
        $dataToStore['entity_id'] = $this->entity_id;
        if (!$this->asset_id) { // Saat create
            $dataToStore['created_by'] = Auth::id();
        }
        if($this->entity_id && $this->asset_type_id)
        {
            $this->numberAssetTemporarry();
        }
        Asset::updateOrCreate(['id' => $this->asset_id], $dataToStore);

        session()->flash('message',
            $this->asset_id ? 'Aset berhasil diperbarui.' : 'Aset berhasil ditambahkan.');

        $this->closeModal();
        $this->resetInputFields();
    }
    public function edit($id)
    {
        
        $asset = Asset::findOrFail($id);
        $this->asset_id = $id;
        $this->asset_number = $asset->asset_number;
        $this->old_asset_number = $asset->old_asset_number;
        $this->pr_number = $asset->pr_number;
        $this->po_number = $asset->po_number;
        $this->sap_number = $asset->sap_number;
        $this->entity_id = $asset->entity_id;
        $this->serial_number = $asset->serial_number;
        $this->status = $asset->status;
        $this->current_location_detail = $asset->current_location_detail;
        $this->holder_user_id = $asset->holder_user_id;
        $this->jobsite_id = $asset->jobsite_id;
        $this->cost_center_id = $asset->cost_center_id;
        $this->cost_center = $asset->costCenter->cost_center_number;

        // Load dependent dropdowns
        $this->asset_type_id = $asset->asset_type_id;
        if (!empty($this->asset_type_id)) {
            $this->assetBrands = AssetBrand::where('asset_type_id', $this->asset_type_id)->orderBy('name')->get();
        } else {
             $this->assetBrands = collect();
        }

        $this->asset_brand_id = $asset->asset_brand_id;
        if (!empty($this->asset_brand_id)) {
            $this->assetSeriesCollection = AssetSeries::where('asset_brand_id', $this->asset_brand_id)->orderBy('name')->get();
        } else {
            $this->assetSeriesCollection = collect();
        }
        $this->asset_series_id = $asset->asset_series_id;
        $this->isEdit = true;
        $this->openModal();
    }

    public function delete($id)
    {
        try {
            Asset::find($id)->delete(); // Menggunakan soft delete jika model menggunakan trait SoftDeletes
            session()->flash('message', 'Aset berhasil dihapus.');
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal menghapus aset. ' . $e->getMessage());
        }
    }

    public function updatedSelectedJobsite()
    {
        $this->resetPage();
    }

    public function updatedSelectedStatus()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->reset(['search', 'selectedJobsite', 'selectedStatus']);
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
        
    }
    
}
