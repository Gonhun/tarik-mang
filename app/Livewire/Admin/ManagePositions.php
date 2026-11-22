<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Position;
use App\Models\Department; 
use App\Models\User;
use Livewire\WithPagination;
use App\Models\ApprovalWorkflow;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ManagePositions extends Component
{
    use WithPagination, WithFileUploads;

    public $position_id, $name;
    public $department_id = null; 

    public $departments = []; 
    public $perPage = 10;
    public $jobdesk_file; 
    public $existing_jobdesk_path;
    public $isModalOpen = false;
    public $search = '';
    protected $paginationTheme = 'bootstrap';

    public $isViewModalOpen = false;
    public $viewingPositionName = '';
    public $viewingJobdeskUrl = '';

    public function mount()
    {
        $this->perPage = 10;
        $this->departments = Department::orderBy('name')->get(['id', 'name']);
    }
    public function updatedPerPage($value)
    {
        $this->resetPage();
    }
    protected function rules()
    {
        return [
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('positions', 'name')->ignore($this->position_id)->whereNull('deleted_at'),
            ],
            'department_id' => 'required|exists:departments,id',
            'jobdesk_file' => 'nullable|file|mimes:pdf,doc,docx,png,jpg|max:2048'
        ];
    }

    protected $messages = [
        'name.required' => 'Nama jabatan wajib diisi.',
        'name.unique' => 'Nama jabatan sudah digunakan.',
        'department_id.required' => 'Departemen wajib dipilih.',
        'department_id.exists' => 'Departemen yang dipilih tidak valid.',
        'jobdesk_file.mimes' => 'File Jobdesk harus berformat PDF, DOC, atau DOCX.',
        'jobdesk_file.max' => 'Ukuran file Jobdesk maksimal 2MB.',
    ];

    public function render()
    {
        $query = Position::with('department:id,name'); // Eager load department

        if (!empty($this->search)) {
            $searchTerm = '%' . $this->search . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                  ->orWhereHas('department', fn($sq) => $sq->where('name', 'like', $searchTerm));
            });
        }
        $positions = $query->orderBy('name', 'asc')->paginate($this->perPage);

        return view('livewire.admin.manage-positions', [
            'positions' => $positions,
        ]);
    }

    // Create, Edit, Store, Delete, Modal methods mirip dengan CRUD lain (misal ManageDepartments)
    // Sesuaikan nama variabel dan modelnya.

    public function create()
    {
        $this->resetInputFields();
        $this->openModal();
    }

    public function openModal() { $this->isModalOpen = true; }
    public function closeModal() { $this->isModalOpen = false; $this->resetValidation(); }

    private function resetInputFields()
    {
        $this->position_id = null;
        $this->name = '';
        $this->department_id = null;
        $this->resetValidation();
    }
    public function openViewModal($positionId)
    {
        $position = Position::findOrFail($positionId);
        if ($position && $position->job_description_path) {
            $this->viewingPositionName = $position->name;
            $this->viewingJobdeskUrl =  Storage::url($position->job_description_path);
            $this->isViewModalOpen = true;
        }
    }

    public function closeViewModal()
    {
        $this->isViewModalOpen = false;
        $this->viewingPositionName = '';
        $this->viewingJobdeskUrl = '';
    }
    public function store()
    {
        $validatedData = $this->validate();
        $dataToStore = [
            'name' => ucfirst($validatedData['name']),
            'department_id' => $this->department_id,
        ];

        
        if ($this->jobdesk_file) {
            // Hapus file lama jika ada saat proses update
            if ($this->position_id && $this->existing_jobdesk_path) {
                Storage::disk('public')->delete($this->existing_jobdesk_path);
            }
            // Simpan file baru
            $dataToStore['job_description_path'] = $this->jobdesk_file->store('job_descriptions', 'public');
            $dataToStore['job_description_name'] = $this->jobdesk_file->getClientOriginalName();
        }
        Position::updateOrCreate(['id' => $this->position_id], $dataToStore);
        $this->dispatch('swal:success', [
            'title' => 'Berhasil!',
            'text' => $this->position_id ? 'Jabatan berhasil diperbarui.' : 'Jabatan berhasil ditambahkan.',
        ]);
        $this->closeModal();
        $this->resetInputFields();
    }

    public function edit($id)
    {
        $position = Position::findOrFail($id);
        $this->position_id = $id;
        $this->name = $position->name;
        $this->department_id = $position->department_id;
        $this->existing_jobdesk_path = $position->job_description_path;
        $this->openModal();
    }

    public function delete($id)
    {
        try {
            // Cek apakah jabatan ini masih digunakan oleh user
            $position=Position::find($id)->delete(); // Soft delete
            if ($position) {
                if (User::where('position_id', $id)->exists()) {
                    session()->flash('error', 'Gagal menghapus jabatan. Jabatan ini masih digunakan oleh pengguna.');
                    return;
                }
                // Hapus file jobdesk dari storage jika ada
                if ($position->job_description_path) {
                    Storage::disk('public')->delete($position->job_description_path);
                }
                $position->delete();
                session()->flash('message', 'Jabatan berhasil dihapus.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal menghapus jabatan. ' . $e->getMessage());
        }
    }
     public function updatedSearch(){ $this->resetPage(); }
}
