<?php

namespace App\Livewire\CostCenters;

use Livewire\Component;
use App\Models\CostCenter;
use App\Models\Jobsite;
use App\Models\Department;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;

class ManageCostCenters extends Component
{
    use WithPagination;

    // Properti untuk form
    public $cost_center_id;
    public $cost_center_number, $name, $description;
    public $jobsite_id = null, $department_id = null;

    // Data untuk dropdown
    public $jobsites = [];
    public $departments = [];

    public $isModalOpen = false;
    public $search = '';
    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $this->jobsites = Jobsite::orderBy('name')->get(['id', 'name']);
        $this->departments = Department::orderBy('name')->get(['id', 'name']);
    }

    protected function rules()
    {
        return [
            'cost_center_number' => ['required', 'string', 'max:50', Rule::unique('cost_centers')->ignore($this->cost_center_id)->whereNull('deleted_at')],
            'description' => 'nullable|string',
            'jobsite_id' => 'required|exists:jobsites,id',
            'department_id' => 'required|exists:departments,id',
        ];
    }

    public function render()
    {
        $query = CostCenter::with(['jobsite:id,name', 'department:id,name']);

        if (!empty($this->search)) {
            $searchTerm = '%' . $this->search . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('cost_center_number', 'like', $searchTerm)
                  ->orWhereHas('jobsite', fn($sq) => $sq->where('name', 'like', $searchTerm))
                  ->orWhereHas('jobsite', fn($sq) => $sq->where('code', 'like', $searchTerm))
                  ->orWhereHas('department', fn($sq) => $sq->where('name', 'like', $searchTerm))
                  ->orWhereHas('department', fn($sq) => $sq->where('code', 'like', $searchTerm));
            });
        }

        $costCenters = $query->orderBy('cost_center_number', 'asc')->paginate(10);

        return view('livewire.cost-centers.manage-cost-centers', [
            'costCenters' => $costCenters,
        ]);
    }

    public function create()
    {
        $this->resetInputFields();
        $this->openModal();
    }

    public function store()
    {
        $validatedData = $this->validate();

        CostCenter::updateOrCreate(['id' => $this->cost_center_id], [
            'cost_center_number' => $validatedData['cost_center_number'],
            'description' => $this->description,
            'jobsite_id' => $this->jobsite_id ?: null, // Simpan null jika kosong
            'department_id' => $this->department_id ?: null,
        ]);

        session()->flash('message',
            $this->cost_center_id ? 'Cost Center berhasil diperbarui.' : 'Cost Center berhasil ditambahkan.');

        $this->closeModal();
        $this->resetInputFields();
    }

    public function edit($id)
    {
        $costCenter = CostCenter::findOrFail($id);
        $this->cost_center_id = $id;
        $this->cost_center_number = $costCenter->cost_center_number;
        $this->description = $costCenter->description;
        $this->jobsite_id = $costCenter->jobsite_id;
        $this->department_id = $costCenter->department_id;
        $this->openModal();
    }

    public function delete($id)
    {
        try {
            CostCenter::find($id)->delete();
            session()->flash('message', 'Cost Center berhasil dihapus.');
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal menghapus Cost Center. Kemungkinan masih terhubung dengan data lain.');
        }
    }

    // --- Method Helper ---
    public function openModal() { $this->isModalOpen = true; }
    public function closeModal() { $this->isModalOpen = false; $this->resetValidation(); }
    public function updatedSearch() { $this->resetPage(); }
    private function resetInputFields()
    {
        $this->cost_center_id = null;
        $this->cost_center_number = '';
        $this->description = '';
        $this->jobsite_id = null;
        $this->department_id = null;
        $this->resetValidation();
    }
}
