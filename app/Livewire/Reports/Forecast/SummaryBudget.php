<?php

namespace App\Livewire\Reports\Forecast;

use App\Models\Department;
use App\Models\Jobsite;
use App\Models\MasterForecast;
use App\Models\MasterPivot;
use App\Models\Coa;
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

class SummaryBudget extends Component
{
    use WithPagination;

    public $departements = [], $jobsites = [];
    public $dept_filter, $site_filter, $year_filter;
    public $search = '';
    public $data_show = 10;
    public $isModalOpen;


    #[Lazy]
    public function mount()
    {
        $this->year_filter = date('Y');
        $this->departements = Department::orderBy('code')->get(['id', 'name', 'code']);
        $this->jobsites = Jobsite::orderBy('code')->get(['id', 'name', 'code']);
    }

    public function render()
    {
        return view('livewire.reports.forecast.summary-budget');
    }

    public function queryPivot()
    {
        return MasterPivot::query();
    }

    public function datas()
    {
        $query = $this->queryPivot();
        $paginatedData = $query->paginate($this->data_show);

        return $paginatedData;
    }
}
