<?php

namespace App\Livewire\Licenses\Modal;

use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Department;
use App\Models\SoftwareLicenses;
use App\Models\SoftwareTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Checkout extends Component
{
    public $isModalOpen = false;
    public $touser = true;
    public $softwaredata,$softwareUsed,$note;
    protected $listeners = ['openCheckoutModal' => 'checkOutFunction'];
    public $licenseDepartements = [];
    public $licenseUsers = [];
    public $listTypeAsset = [];
    public $listAsset = [];
    public $departement_id,$user_id,$asset_id,$type_id;
   
    public function mount()
    {
        $this->licenseDepartements = Department::orderBy('name')->get(['id','code', 'name']);
       
        
    }
    protected function rules()
    {
        return [
            'touser' => 'required|boolean',
            'departement_id' => $this->touser ? 'required|exists:departments,id' : 'nullable',
            'user_id' => $this->touser ? 'required|exists:users,id' : 'nullable',
            'type_id' => !$this->touser ? 'required|exists:asset_types,id' : 'nullable',
            'asset_id' => !$this->touser ? 'required|exists:assets,id' : 'nullable',
            'note' => 'nullable|string|max:255',
        ];
    }

    protected function prepareForValidation($attributes)
    {
        // No-op, but can be used for pre-validation adjustments in the future
        return $attributes;
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    public function validateCheckout()
    {
        $data = $this->validate();

        // Khusus aturan mutual exclusive antara asset_id dan user_id
        if (empty($this->asset_id) && empty($this->user_id)) {
            $this->addError('assign', 'User atau Asset harus diisi salah satu.');
            return false;
        }
        if (!empty($this->asset_id) && !empty($this->user_id)) {
            $this->addError('assign', 'Hanya boleh memilih User atau Asset, tidak keduanya.');
            return false;
        }

        // Validasi seats availability
        if ($this->softwaredata && $this->softwareUsed >= $this->softwaredata->seats) {
            $this->addError('softwaredata', 'Tidak ada seat license yang tersedia.');
            return false;
        }

        return true;
    }
    public function store()
    {
        if (!$this->validateCheckout()) {
            return;
        }
        try {
            $data = [
                'software_id'   => $this->softwaredata->id,
                'created_by'    => Auth::id(),
                'note'          => $this->note,
                'assigned_at'   => now(),
            ];

            if ($this->touser) {
                $data['user_id']        = $this->user_id;
            } else {
                $data['asset_id']   = $this->asset_id;
            }
            $transaction = SoftwareTransaction::create($data);
            // Buat transaksi peminjaman license baru
           

            $this->dispatch('refreshParentComponent');
            $this->closeModal();
            $this->emit('licenseCheckedOut');
        } catch (\Exception $e) {
          
            session()->flash('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }
    public function changeDataId()
    {
        if($this->departement_id > 0)
        {
            $this->licenseUsers = User::where('department_id',$this->departement_id)->orderBy('name')->get(['id','nik','name']);
        }else{
            $this->licenseUsers = [];
        }
        if($this->type_id > 0)
        {
            $this->listAsset = Asset::where('asset_type_id',$this->type_id)->orderBy('asset_number')->get(['id','asset_number']);
        }else{
            $this->listAsset = [];
        }
    }
    public function updatedSelectedUser($value)
    {
        // otomatis ke-trigger saat select berubah
        // contoh:
        $this->dispatch('user-selected', id: $value);
    }
    public function checkOutFunction($softwareId)
    {
        $this->softwaredata = SoftwareLicenses::where('id',$softwareId)->first();
        $this->softwareUsed = SoftwareTransaction::where('software_id', $softwareId)
        ->whereNull('returned_at')
        ->count();
        $this->isModalOpen = true;
       
    }
    public function changelocationCheckout($data)
    {
        $this->touser=$data;
        $this->licenseUsers = [];
        $this->departement_id=0;
        $this->user_id=null;
        $this->asset_id=null;
        $this->type_id=0;
        if(!$data)
        {
            $this->listTypeAsset=AssetType::orderBy('name')->get(['id','code','name']);
        }
    }
    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->softwaredata = 0;
        $this->softwareUsed = 0;
        $this->resetData();
    }
    public function resetData()
    {
        $this->departement_id = 0;
        $this->user_id = null;
        $this->asset_id = 0;
        $this->type_id = null;
        $this->touser = true;
        $this->note = null;
        $this->licenseUsers = [];
        $this->listTypeAsset = [];
        $this->softwaredata = null;
        $this->softwareUsed = 0;
        $this->isModalOpen = false;
    }
    public function render()
    {
        return view('livewire.licenses.modal.checkout');
    }
}
