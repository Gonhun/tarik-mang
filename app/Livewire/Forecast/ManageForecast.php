<?php

namespace App\Livewire\Forecast;

use App\Models\Department;
use App\Models\Jobsite;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Lazy;
use App\Models\ManageForecast as ManageForecastsModel;

class ManageForecast extends Component
{
    use WithPagination;
    
    public $jobsites = [], $months=[
        1 => 'Jan',
        2 => 'Feb',
        3 => 'Mar',
        4 => 'Apr',
        5 => 'May',
        6 => 'Jun',
        7 => 'Jul',
        8 => 'Aug',
        9 => 'Sep',
        10 => 'Oct',
        11 => 'Nov',
        12 => 'Dec'
    ];
    public $search, $site_filter, $year_filter, $month_filter;
    public $data_show = 10;

    #[Lazy]
    public function Mount()
    {
        $this->jobsites = Jobsite::orderBy('code')->get(['id', 'name', 'code']);
        $this->year_filter = 2025;
        $this->month_filter = date('M');
    }

    public function render()
    {
        return view('livewire.forecast.manage-forecast');
    }

    public function queryForecast(): Builder
    {
        return ManageForecastsModel::query();
    }

    #[Computed]
    public function datas(){
        $paginatedDatas = $this->queryForecast()
        ->where(function (Builder $query) {
            $query->where('jobsite_id', 'like', '%' . $this->site_filter . '%')
                  ->whereYear('Tahun', 'like', '%' . $this->year_filter . '%');
        })
        ->paginate($this->data_show,['*'], 'forecast');

        return $paginatedDatas;
    }

}
