<?php

namespace App\Livewire\Master\Forecast;

use App\Models\Department;
use App\Models\Jobsite;
use App\Models\MasterForecast;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Component;
use Livewire\WithPagination;

class RefForecast extends Component
{
    use WithPagination;

    public $departements = [], $jobsites = [];
    public $dept_filter, $site_filter, $year_filter;
    public $id, $material_code, $material_name, $max_lt, $avg_lt, $period;
    public $search = '';
    public $data_show = 10;
    public $isModalOpen;

    public function mount()
    {
        $this->year_filter = date('Y');
        $this->departements = Department::orderBy('code')->get(['id', 'name', 'code']);
        $this->jobsites = Jobsite::orderBy('code')->get(['id', 'name', 'code']);
    }

    public function render()
    {
        return view('livewire.master.forecast.ref-forecast');
    }

    function openModal()
    {
        $this->isModalOpen = true;
    }

    function closeModal()
    {
        $this->isModalOpen = false;
        $this->resetField();
    }
    
    public function resetField()
    {
        $this->material_name = '';
        $this->material_code = '';
        $this->max_lt = '';
        $this->avg_lt = '';
        $this->period = '';
    }

    public function queryForecast()
    {
        return MasterForecast::query();
    }

    #[Computed]
    public function datas()
    {
        $query = $this->queryForecast();

        return $query->paginate($this->data_show);
    }

    public function save()
    {
        try {
            $this->validate([
                'material_code' => 'required',
                'material_name' => 'required',
                'max_lt' => 'required|numeric',
                'avg_lt' => 'required|numeric',
                'period' => 'required|string',
            ]);

            MasterForecast::updateOrCreate(
                ['id' => $this->id],
                [
                    'material_code' => $this->material_code,
                    'material_name' => $this->material_name,
                    'max_lt' => $this->max_lt,
                    'avg_lt' => $this->avg_lt,
                    'period' => $this->period
                ]
            );

            $this->closeModal();
            $this->dispatch('swal:success', ['title' => 'Success', 'text' => 'Forecast data saved successfully.']);
        } catch (\Exception $e) {
            $this->closeModal();
            // untuk error lain seperti DB, logic, dll
            $this->dispatch('swal:error', ['title' => 'Error', 'text' => $e->getMessage()]);
        }
    }

    
    public function edit($ids){
        try{
            $forecast = MasterForecast::findOrFail($ids);
            $this->id = $ids;
            $this->material_code = $forecast->material_code;
            $this->material_name = $forecast->material_name;
            $this->max_lt = number_format($forecast->max_lt,2);
            $this->avg_lt = number_format($forecast->avg_lt,2);
            $this->period = $forecast->period;

            $this->openModal();
        }catch (\Exception $e) {
            // untuk error lain seperti DB, logic, dll
            session()->flash('error', $e->getMessage());
        }
    }
}
