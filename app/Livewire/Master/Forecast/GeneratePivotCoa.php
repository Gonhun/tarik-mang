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

class GeneratePivotCoa extends Component
{
    use WithPagination;

    public $departements = [], $jobsites = [];
    public $dept_filter, $site_filter, $year_filter;
    public $pivot_draft;
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
        return view('livewire.master.forecast.pivot');
    }

    public function openModal()
    {
        $this->isModalOpen = true;
        $this->dispatch('open-modal', true);
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
    }



}
