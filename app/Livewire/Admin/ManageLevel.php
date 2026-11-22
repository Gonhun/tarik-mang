<?php

namespace App\Livewire\Admin;

use App\Models\PositionLevel;
use App\Models\User;
use Livewire\Component;
use Illuminate\Validation\Rule;
use Livewire\WithPagination;

class ManageLevel extends Component
{
    use WithPagination;
    public $position_id, $name;
    public $department_id = null; 

    public $levels = []; 
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
        $this->levels = PositionLevel::orderBy('name')->get(['id', 'name']);
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
                Rule::unique('position_levels', 'name')->ignore($this->position_id),
            ],
        ];
    }
    protected $messages = [
        'name.required' => 'Nama Level jabatan wajib diisi.',
        'name.unique' => 'Nama Level jabatan sudah digunakan.',
    ];
    public function render()
    {
        $query = PositionLevel::query();

        if (!empty($this->search)) {
            $searchTerm = '%' . $this->search . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm);
            });
        }
        $positionlevels = $query->orderBy('name', 'asc')->paginate($this->perPage);

        return view('livewire.admin.manage-level',[
            'positionlevels' => $positionlevels
        ]);
    }
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
        $this->resetValidation();
    }
    public function openViewModal($positionId)
    {
        $position = PositionLevel::findOrFail($positionId);
        if ($position) {
            $this->viewingPositionName = $position->name;
            $this->isViewModalOpen = true;
        }
    }

    public function closeViewModal()
    {
        $this->isViewModalOpen = false;
        $this->viewingPositionName = '';
    }
    public function store()
    {
        $validatedData = $this->validate();
        $dataToStore = [
            'name' => ucfirst($validatedData['name']),
        ];
        PositionLevel::updateOrCreate(['id' => $this->position_id], $dataToStore);
        $this->dispatch('swal:success', [
            'title' => 'Berhasil!',
            'text' => $this->position_id ? 'Jabatan berhasil diperbarui.' : 'Jabatan berhasil ditambahkan.',
        ]);
        $this->closeModal();
        $this->resetInputFields();
    }

    public function edit($id)
    {
        $position = PositionLevel::findOrFail($id);
        $this->position_id = $id;
        $this->name = $position->name;
        $this->openModal();
    }

    public function delete($id)
    {
        $row = \App\Models\PositionLevel::find($id);
        if (!$row) return;

        // Cek pemakaian DULU (data master dihapus permanen / soft delete tidak ditolak database).
        if ($reasons = \App\Support\MasterUsage::reasons('position_level', (int) $row->id)) {
            $this->dispatch('swal:error', ['title' => 'Tidak bisa dihapus', 'text' => \App\Support\MasterUsage::message('Level jabatan ' . $row->name, $reasons)]);
            return;
        }

        try {
            $row->delete();
            $this->dispatch('swal:success', ['title' => 'Dihapus', 'text' => 'Level jabatan ' . $row->name . ' berhasil dihapus.']);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Hapus Level jabatan #' . $row->id . ' gagal: ' . $e->getMessage());
            $this->dispatch('swal:error', ['title' => 'Tidak bisa dihapus', 'text' => 'Level jabatan ' . $row->name . ' masih terhubung dengan data lain.']);
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }
}
