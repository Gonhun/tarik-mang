<?php

namespace App\Livewire\Forecast;

use App\Models\ManageForecast as ManageForecastsModel;
use App\Models\MasterMaterial;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Lazy;

class DetailManageForecast extends Component
{
    use WithPagination;

    public $jobsites = [], $months = [
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
    public $sap_id, $detail_forecast;
    public $data_show = 10;

    #[Lazy]
    public function mount($id = NULL)
    {
        $this->sap_id = explode(',', base64_decode($id));
        // dd(date('m', strtotime($this->sap_id[2])));
    }

    public function render()
    {
        return view('livewire.forecast.detail-manage-forecast');
    }

    public function queryMaterial(): Builder
    {
        return MasterMaterial::query();
    }

    public function datas()
    {
        $this->detail_forecast = $this->queryMaterial()
            ->where('material', $this->sap_id[0])
            ->whereRaw('SUBSTRING(created_on, 4, 2) = ?', [
                date('m', strtotime($this->sap_id[2]))
            ])
            ->get();
            // ->paginate($this->data_show, ['*'], 'detail_page');
        return $this->detail_forecast;
    }
}
