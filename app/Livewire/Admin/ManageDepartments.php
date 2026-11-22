<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Department; // Pastikan model Department Anda sudah ada
use Livewire\WithPagination;
use Illuminate\Validation\Rule;

class ManageDepartments extends Component
{
    use WithPagination;

    // Properti untuk form
    public $department_id; // Untuk mode edit, null saat create
    public $name;
    public $code;
    public $isEdit=false;
    public $isModalOpen = false;
    protected $paginationTheme = 'bootstrap';

    protected function rules()
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'name')->ignore($this->department_id),
            ],
            'code' => [
                'required',
                'string',
                'max:50', // Sesuaikan panjang maksimal
                Rule::unique('departments', 'code')->ignore($this->department_id), // Kode harus unik di antara yang aktif
            ],
           
        ];
    }

    protected $messages = [
        'name.required' => 'Nama departemen wajib diisi.',
        'code.required' => 'Kode departemen wajib diisi.',
        'code.unique' => 'Kode departemen sudah digunakan.',
    ];
    // SEARCH FUNCTIONALITY

    public $search = '';

    // Update pagination when search changes
    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        // Bagian ini untuk menampilkan daftar departemen (opsional jika hanya fokus pada form tambah)
        $departments = Department::query()
            ->when($this->search, function($query) {
                $query->where(function($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                      ->orWhere('code', 'like', '%'.$this->search.'%');
                });
            })
            ->orderBy('name', 'asc')
            ->paginate(10);

        return view('livewire.admin.manage-departments', [
            'departments' => $departments,
        ]); // Sesuaikan dengan layout utama Anda
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
        $this->department_id = null;
        $this->name = '';
        $this->code = '';
        $this->isEdit = false;
        $this->resetValidation();
    }

    public function store()
    {
        $validatedData = $this->validate();
        $existing = Department::where('code', strtoupper($validatedData['code']))
                    ->first();
        
        if($existing && $this->isEdit == false){
            $this->dispatch('swal:error', [
                'title' => 'Error!',
                'text' => 'Kode Departement Sudah ada!',
            ]);
            return;
        }
        Department::updateOrCreate(['id' => $this->department_id], [
            'code' => strtoupper($validatedData['code']),
            'name' => strtoupper($validatedData['name']),
            
        ]);
        $this->dispatch('swal:success', [
            'title' => 'Berhasil!',
            'text' => $this->department_id ? 'Departemen berhasil diperbarui.' : 'Departemen berhasil ditambahkan.',
        ]);
        $this->closeModal();
        $this->resetInputFields();
    }

    public function edit($id)
    {
        $department = Department::findOrFail($id);
        $this->department_id = $id;
        $this->name = $department->name;
        $this->code = $department->code;
        $this->isEdit = true;
        $this->openModal();
    }

    public function delete($id)
    {
        try {
            Department::find($id)->delete(); // Asumsi menggunakan SoftDeletes jika ada
            session()->flash('message', 'Departemen berhasil dihapus.');
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal menghapus departemen. Kemungkinan masih terhubung dengan data pengguna atau data lain.');
        }
    }
}
