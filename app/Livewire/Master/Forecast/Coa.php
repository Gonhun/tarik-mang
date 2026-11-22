<?php
namespace App\Livewire\Master\Forecast;

use App\Models\Department;
use App\Models\Jobsite;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Coa extends Component
{
    use WithPagination;

    public $id_coa, $cost_element_number, $description, $department_id;
    public $departements = [], $jobsites = [];
    public $search = '';
    public $data_show = 10;
    public $isModalOpen;

    public function mount()
    {
        $this->departements = Department::orderBy('code')->get(['id', 'name', 'code']);
        $this->jobsites = Jobsite::orderBy('code')->get(['id', 'name', 'code']);
    }

    protected function rules()
    {
        $costElementToIgnore = $this->cost_element_number;
        return [
            'cost_element_number' => [
                'required',
                'max:255',
            ],
            'description' => [
                'required',
                'max:255'
            ],
            'department_id' => 'required|exists:departments,id',
        ];
    }

    protected $messages = [
        'cost_element_number.required' => 'Nomor Cost Element wajib diisi.',
        'cost_element_number.max' => 'Nomor Cost Element maksimal 255 karakter.',
        'description.required' => 'Deskripsi harus diisi.',
        'description.max' => 'Deskripsi maksimal 255 karakter.',
        'department_id.required' => 'Departemen harus diisi.',
        'department_id.exists' => 'Departemen tidak valid untuk cost element.',
    ];


    public function render()
    {
        return view('livewire.master.forecast.coa');
    }

    public function openModal()
    {
        $this->isModalOpen = true;
         $this->dispatch('open-modal', true);
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->resetField();
    }

    public function resetField()
    {
        $this->cost_element_number = '';
        $this->description = '';
        $this->department_id = '';
    }

    public function queryCoa(): Builder
    {
        $query = \App\Models\Coa::query();
        return $query;
    }

    #[Computed]
    public function datas()
    {
        $query = $this->queryCoa()
            ->with('department');

        $paginatedDatas = $query->paginate($this->data_show);

        return $paginatedDatas;
    }

    public function store()
    {
        try {
            $validatedData = $this->validate($this->rules(), $this->messages);
        
            $dataToStore = $validatedData;

            if ($this->id_coa) {
                // Update existing record
                \App\Models\Coa::where('id', $this->id_coa)->update($dataToStore);
                $this->dispatch('swal:success', ['title' => 'Sukses', 'text' => 'Cost element berhasil diperbarui.']);
            } else {
                // Create new record
                \App\Models\Coa::create($dataToStore);
                $this->dispatch('swal:success', ['title' => 'Sukses', 'text' => 'Cost element berhasil ditambahkan.']);
            }

            $this->closeModal();
            $this->resetField();
            $this->id_coa = null;

            $this->closeModal();
            $this->resetField();

        }  catch (\Exception $e) {
            $this->closeModal();
            // untuk error lain seperti DB, logic, dll
            $this->dispatch('swal:error', ['title' => 'Error', 'text' => $e->getMessage()]);
        }
    }

    public function edit($id){
        try{
            $coa = \App\Models\Coa::findOrFail($id);
            $this->id_coa = $id;
            $this->cost_element_number = $coa->cost_element_number;
            $this->description = $coa->description;
            $this->department_id = $coa->department_id;

            $this->openModal();
        }catch (\Exception $e) {
            // untuk error lain seperti DB, logic, dll
            session()->flash('error', $e->getMessage());
        }
    }

}