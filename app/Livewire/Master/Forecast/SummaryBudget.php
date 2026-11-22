<?php

namespace App\Livewire\Master\Forecast;

use App\Models\Department;
use App\Models\Jobsite;
use App\Models\MasterPivot;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Component;
use Livewire\WithPagination;


class SummaryBudget extends Component
{
    use WithPagination;
    public $year_filter;
    public $departements = [], $jobsites = [];
    public $search = '';
    public $data_show = 10;

    #[Lazy]
    public function mount()
    {
        $this->year_filter = date('Y');
        $this->departements = Department::orderBy('code')->get(['id', 'name', 'code']);
        $this->jobsites = Jobsite::orderBy('code')->get(['id', 'name', 'code']);
    }

    public function render()
    {
        return view('livewire.master.forecast.summary-budget');
    }

    public function querySummaryAllSite()
    {
        return DB::table('coas')
            ->select(
                DB::raw('CASE WHEN year is null THEN ' . $this->year_filter . ' ELSE pivots.year END as year'),
                'pivots.jobsite_id',
                'jobsites.code as site_code',
                DB::raw('SUM(pivots.plan_jan) as plan_jan'),
                DB::raw('SUM(pivots.act_jan) as act_jan'),
                DB::raw('SUM(pivots.plan_feb) as plan_feb'),
                DB::raw('SUM(pivots.act_feb) as act_feb'),
                DB::raw('SUM(pivots.plan_mar) as plan_mar'),
                DB::raw('SUM(pivots.act_mar) as act_mar'),
                DB::raw('SUM(pivots.plan_apr) as plan_apr'),
                DB::raw('SUM(pivots.act_apr) as act_apr'),
                DB::raw('SUM(pivots.plan_may) as plan_may'),
                DB::raw('SUM(pivots.act_may) as act_may'),
                DB::raw('SUM(pivots.plan_jun) as plan_jun'),
                DB::raw('SUM(pivots.act_jun) as act_jun'),
                DB::raw('SUM(pivots.plan_jul) as plan_jul'),
                DB::raw('SUM(pivots.act_jul) as act_jul'),
                DB::raw('SUM(pivots.plan_aug) as plan_aug'),
                DB::raw('SUM(pivots.act_aug) as act_aug'),
                DB::raw('SUM(pivots.plan_sep) as plan_sep'),
                DB::raw('SUM(pivots.act_sep) as act_sep'),
                DB::raw('SUM(pivots.plan_oct) as plan_oct'),
                DB::raw('SUM(pivots.act_oct) as act_oct'),
                DB::raw('SUM(pivots.plan_nov) as plan_nov'),
                DB::raw('SUM(pivots.act_nov) as act_nov'),
                DB::raw('SUM(pivots.plan_dec) as plan_dec'),
                DB::raw('SUM(pivots.act_dec) as act_dec')
            )
            ->leftJoin('master_pivots as pivots', function ($join) {
                $join->on('coas.cost_element_number', '=', 'pivots.cost_element_number')
                    ->where('pivots.year', '=', $this->year_filter);
            })
            ->rightJoin('jobsites', 'pivots.jobsite_id', '=', 'jobsites.id')
            ->groupBy(
                'year',
                'pivots.jobsite_id',
                'jobsites.code'
            );
    }

    public function querySummaryByJobsite($jobsite_id)
    {
        return DB::table('coas')
            ->leftJoin('master_pivots as pivots', function ($join) use ($jobsite_id) {
                $join->on('coas.cost_element_number', '=', 'pivots.cost_element_number')
                    ->where('pivots.year', '=', $this->year_filter)
                    ->where('pivots.jobsite_id', '=', $jobsite_id);
            })
            ->leftJoin('jobsites', 'pivots.jobsite_id', '=', 'jobsites.id')
            ->select(
                'pivots.id',
                DB::raw('CASE WHEN year is null THEN ' . $this->year_filter . ' ELSE pivots.year END as year'),
                'coas.cost_element_number',
                'coas.description',
                'pivots.jobsite_id',
                'jobsites.code as site_code',
                DB::raw('pivots.plan_jan + pivots.plan_feb + pivots.plan_mar + pivots.plan_apr + pivots.plan_may + pivots.plan_jun + pivots.plan_jul + pivots.plan_aug + pivots.plan_sep + pivots.plan_oct + pivots.plan_nov + pivots.plan_dec as total_plan'),
                DB::raw('pivots.act_jan + pivots.act_feb + pivots.act_mar + pivots.act_apr + pivots.act_may + pivots.act_jun + pivots.act_jul + pivots.act_aug + pivots.act_sep + pivots.act_oct + pivots.act_nov + pivots.act_dec as total_act'),
            )
            ->groupBy(
                'pivots.id',
                'year',
                'coas.cost_element_number',
                'coas.description',
                'pivots.jobsite_id',
                'jobsites.code'
            );
    }

    public function dataSumarryAlls()
    {
        // dd($this->querySummaryAllSite()->get());
        return $this->querySummaryAllSite()
            ->paginate($this->data_show, ['*'], 'all_sites');
    }

    public function dataSumarryByJobsite($jobsite_id)
    {
        return $this->querySummaryByJobsite($jobsite_id)
            ->where(function ($query) {
                $query->where('coas.cost_element_number', 'like', '%' . $this->search . '%')
                    ->orWhere('coas.description', 'like', '%' . $this->search . '%');
            })
            ->paginate($this->data_show, ['*'], 'jobsite_' . $jobsite_id);
    }

}
