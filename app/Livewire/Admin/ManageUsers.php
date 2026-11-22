<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\User;
use App\Models\Department;
use App\Models\Jobsite;
use App\Models\Position;
use App\Models\PositionLevel;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Attributes\Validate;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\UsersImport;

class ManageUsers extends Component
{
    use WithPagination;
    use WithFileUploads;
    #[Validate('file|mimes:xlsx,xls|max:2048')]
    public $excelFile;
    // Properti untuk form
    public $user_id, $name, $nik,  $whatsapp_number,$manager_id;
    public $department_id, $jabatan_id, $jobsite_id, $position_level_id;

    // Data untuk dropdown
    public $departments = [];
    public $jobsites = [];
    public $jabatans = [], $positions = [];
    public $usersForManagerSelection = [];
    public $user_roles = []; 
    public $allRoles = [];
    public $allLevels = [];

    // Properti UI
    public $isModalOpen = false;
    public string $search = '';
    protected $paginationTheme = 'bootstrap';
    public $mode = 'create'; // 'create' atau 'edit'


    
    public function mount()
    {
        $this->departments = Department::orderBy('name')->get(['id', 'name']);
        $this->jobsites = Jobsite::orderBy('name')->get(['id', 'name', 'code']);
        $this->positions = Position::orderBy('name')->get(['id', 'name']);
        $this->allRoles = Role::orderBy('name')->get();
        $this->allLevels = PositionLevel::OrderBy('name')->get();
    }
    public function uploadExcel()
    {   
        try {
            $this->validate([
                'excelFile' => 'required|file|mimes:xlsx,xls|max:2048'
            ]);
            Excel::Import(new UsersImport, $this->excelFile);
            session()->flash('global-message', 'File Excel berhasil diupload dan diproses.');
            $this->reset('excelFile');
        } catch (\Exception $e) {
            session()->flash('error', 'Terjadi kesalahan saat memproses file: ' . $e->getMessage());
            $this->reset('excelFile');
        }
        $this->resetValidation();
        $this->resetErrorBag();
    }
    public function getPosisions($value)
    {
       
        if($value){
            $this->jabatans = Position::where('department_id', $value)->orderBy('name')->get(['id', 'name']);
        }else{
            $this->jabatans = [];
        }
    }
    public function toggleApproved($id)
    {
        $req = User::find($id);
        $req->is_active = !$req->is_active;
        $req->save();
    }
    protected function rules()
    {
         $userIdToIgnore = $this->user_id ?? null;
        
        return [
            'name' => 'required|string|max:255',
            'nik' => [
                'required',
                'string',
                'max:25', // Sesuaikan
                Rule::unique('users', 'nik')->ignore($userIdToIgnore),
            ],
            'whatsapp_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('users', 'whatsapp')->ignore($userIdToIgnore),
            ],
            'jabatan_id' => 'required|exists:positions,id',
            'department_id' => 'required|exists:departments,id',
            'jobsite_id' => 'required|exists:jobsites,id',
            'manager_id' => ['nullable', 'exists:users,id', Rule::notIn([$this->user_id])],
            // 'password' => $passwordRules,
            'user_roles' => 'required|array|min:1', // <-- 5. Validasi untuk role
            'user_roles.*' => 'exists:roles,id', // Pastikan setiap id role valid
            'position_level_id' => 'required|exists:position_levels,id'
        ];
    }

    protected $messages = [
        'name.required' => 'Nama wajib diisi.',
        'nik.required' => 'NIK wajib diisi.',
        'nik.unique' => 'NIK sudah terdaftar.',
        'nik.max' => 'NIK maksimal 25 karakter.',
        'whatsapp_number.required' => 'Nomor WhatsApp wajib diisi.',
        'whatsapp_number.max' => 'Nomor WhatsApp maksimal 20 karakter.',
        'whatsapp_number.unique' => 'Nomor WhatsApp sudah terdaftar.',
        'email.required' => 'Email wajib diisi.',
        'email.email' => 'Format email tidak valid.',
        'email.unique' => 'Email sudah terdaftar.',
        'jabatan_id.required' => 'Jabatan wajib dipilih.',
        'jabatan_id.exists' => 'Jabatan yang dipilih tidak valid.',
        'department_id.required' => 'Departemen wajib dipilih.',
        'department_id.exists' => 'Departemen yang dipilih tidak valid.',
        'jobsite_id.required' => 'Jobsite wajib dipilih.',
        'jobsite_id.exists' => 'Jobsite yang dipilih tidak valid.',
        'manager_id.exists' => 'Manager yang dipilih tidak valid.',
        'manager_id.not_in' => 'User tidak dapat memilih dirinya sendiri sebagai manager.',
        'position_id.required' => 'Jabatan wajib dipilih.',
        'user_roles.required' => 'Minimal satu peran (role) harus dipilih.',
        'position_level_id.required' => 'Level Jabatan wajib dipilih.',
        'position_level_id.exists' => 'Level Jabatan yang dipilih tidak valid.',
    ];


    public function render()
    {
        $query = User::with(['position', 'department', 'jobsite', 'manager:id,name','roles','positionLevel']); // Eager load relasi
      

        if (!empty($this->search)) {
            $searchTerm = '%' . $this->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                  ->orWhere('nik', 'like', $searchTerm)
                  ->orWhereHas('department', function($q2) use ($searchTerm) {
                    $q2->where('name', 'like', $searchTerm)
                    ->orWhere('code', 'like', $searchTerm);
                })
                  ->orWhereHas('positionLevel', function($q3) use ($searchTerm) {
                      $q3->where('name', 'like', $searchTerm);
                  });
            });
        }
        $users = $query->orderBy('name', 'asc')->paginate(10);

        return view('livewire.admin.manage-users', [
            'usersCollection' => $users, // Ganti nama variabel agar tidak konflik dengan properti $users
        ]);
    }
    public function RessetPassowrd($userid)
    {
        if($userid)
        {
            $user = User::find($this->user_id);
            if ($user) {
                $user->update(['password' => null]);
                $this->dispatch('swal:success', [
                    'title' => 'Berhasil!',
                    'text' => $this->department_id ? 'Departemen berhasil diperbarui.' : 'Departemen berhasil ditambahkan.',
                ]);
                $this->closeModal();
                $this->resetInputFields();
            }
        }else{
            $this->dispatch('swal:error', [
                'title' => 'Error!',
                'text' => 'NIK/User ID tidak ditemukan!',
            ]);
            $this->closeModal();
            $this->resetInputFields();
            return;
        }
    }
    public function create()
    {
        $this->mode = 'create';
        $this->resetInputFields();
        $this->openModal();
    }
    private function loadUsersForManagerSelection()
    {
        $query = User::orderBy('name');
        // Saat mengedit, kecualikan diri sendiri dari daftar calon atasan
        // if ($this->user_id) {
            $query->where('id', '!=', $this->user_id)
                  ->whereHas('positionLevel', function($q) {
                      $q->where(function($sub) {
                          $sub->where('name', 'like', 'Kepala %');
                      });
                  });
        // }
        $this->usersForManagerSelection = $query
            ->with(['department', 'position', 'positionLevel', 'jobsite'])
            ->get(['id', 'name', 'nik', 'department_id', 'position_id', 'position_level_id', 'jobsite_id']);
           
    //     $this->usersForManagerSelection = $query->get(['id', 'name', 'nik','department_id']);
    }
    public function edit($id)
    {
        $user = User::findOrFail($id);
        if ($user->nik === 'admin' && Auth::user()->nik !== 'admin') {
            $this->dispatch('swal:error', [
                'title' => 'Error!',
                'text' => 'Tidak bisa mengedit Administrator!',
            ]);
            $this->closeModal();
            $this->resetInputFields();
            return;
        }
        $this->mode = 'edit';
        $this->user_id = $id;
        $this->name = $user->name;
        $this->nik = $user->nik;
        $this->whatsapp_number = $user->whatsapp;
        $this->department_id = $user->department_id;
        $this->jabatan_id = $user->position_id;
        $this->jobsite_id = $user->jobsite_id;
        $this->manager_id = $user->manager_id;
        $this->user_roles = $user->roles->pluck('id')->toArray();
        $this->position_level_id = $user->position_level_id;
        if($user->department_id){
            $this->jabatans = Position::where('department_id', $user->department_id)->orderBy('name')->get(['id', 'name']);
        }else{
            $this->jabatans = [];
        }
        $this->loadUsersForManagerSelection();
        $this->openModal();
    }

    public function store()
    {
       
        $validatedData = $this->validate($this->rules());
       
        $dataToStore = [
            'name' => ucwords($validatedData['name']),
            'nik' => $validatedData['nik'],
            'whatsapp' => PhoneFormat($validatedData['whatsapp_number']),
            'department_id' => $validatedData['department_id'],
            'position_id' => $validatedData['jabatan_id'],
            'jobsite_id' => $validatedData['jobsite_id'],
            'manager_id' => $validatedData['manager_id'],
            'position_level_id' => $validatedData['position_level_id'],
        ];
       
        $user = User::updateOrCreate(['id' => $this->user_id], $dataToStore);
     
        if (is_array($this->user_roles)) {
            $rolesToSync = Role::whereIn('id', $this->user_roles)->get();
            $user->syncRoles($rolesToSync);
        }
       
        session()->flash('message',
            $this->user_id ? 'Data Pengguna berhasil diperbarui.' : 'Pengguna baru berhasil ditambahkan.');

        $this->closeModal();
        $this->resetInputFields();
    }

    public function delete($id)
    {
        try {
            User::find($id)->delete(); // Soft delete
            session()->flash('message', 'Pengguna berhasil dihapus.');
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal menghapus pengguna: ' . $e->getMessage());
        }
    }


    public function openModal() { 
        $this->loadUsersForManagerSelection();
        $this->isModalOpen = true; 
    }
    public function closeModal() { $this->isModalOpen = false; $this->resetValidation();
        $this->resetErrorBag(); }

    private function resetInputFields()
    {
        $this->user_roles = [];
        $this->user_id = null;
        $this->name = '';
        $this->nik = '';
        $this->whatsapp_number = '';
        $this->department_id = null;
        $this->jabatan_id = null;
        $this->jobsite_id = null;
        $this->manager_id = null; 
        $this->position_level_id = null;
        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function updatedSearch(string $value)
    {
        $this->resetPage();
    }
}
