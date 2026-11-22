<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Asset;
use App\Models\AssetRequest;
use App\Models\AssetTransaction;
use App\Models\Jobsite;
use App\Models\AssetType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;

class Dashboard extends Component
{
    public $assetsByStatusData;
    public $assetsByTypeData;
    public $assetsByJobsiteData;
    public $assetRequestTrendData;
    public $selectedJobsite;
    public $jobsites = [];

    use WithPagination;
    // Properti untuk filter (bisa ditambahkan nanti)
    // public $filterYear = '2025';

    /**
     * Dijalankan saat komponen dimuat.
     */
    public function mount()
    {
       
        $this->jobsites = Jobsite::orderBy('code','asc')->get(['id','name','code']);
       
    }
    public function updatedselectedJobsite($value)
    {
        $this->prepareChartData();
       
       
    }
    /**
     * Method utama untuk mengambil dan memformat semua data chart.
     */
    public function prepareChartData()
    {
        // $this->assetsByStatusData = $this->prepareAssetsByStatusChart();
        // $this->assetsByTypeData = $this->prepareAssetsByTypeChart();
        // $this->assetsByJobsiteData = $this->prepareAssetsByJobsiteChart();
        // $this->assetRequestTrendData = $this->prepareAssetRequestTrendChart();
       
        // // Kirim data ke frontend menggunakan event browser
        // // Ini cara yang baik agar JavaScript bisa me-render chart setelah data siap.
        // $this->dispatch('updateCharts', [
        //     'assetsByStatus' => $this->assetsByStatusData,
        //     'assetsByType' => $this->assetsByTypeData,
        //     'assetsByJobsite' => $this->assetsByJobsiteData,
        //     'assetRequestTrend' => $this->assetRequestTrendData,
        // ]);
      
        $this->dispatch('updateCharts', [
            'assetsByStatus' => $this->prepareAssetsByStatusChart(),
            'assetsByType' => $this->prepareAssetsByTypeChart(),
            'assetsByJobsite' => $this->prepareAssetsByJobsiteChart(),
            'assetRequestTrend' => $this->prepareAssetRequestTrendChart(),
        ]);
    }

    protected function prepareAssetsByStatusChart()
    {
        $query = Asset::query();
        if (!empty($this->selectedJobsite)) {
            $query->where('jobsite_id', $this->selectedJobsite);
        }
        $data = $query->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');
       
        // Ensure at least one value for JS chart (avoid undefined['0'] error)
        $series = $data->values()->toArray();
        $labels = $data->keys()->toArray();
        // if (empty($series)) {
        //     $series = ['0', '1'];
        //     $labels = ['-', '-'];
        // }
        
        return [
            'series' => $series,
            'labels' => $labels,
        ];
    }

    public function resetFilters()
    {
        $this->selectedJobsite=null;
    }

    protected function prepareAssetsByTypeChart()
    {
        $query = AssetType::query();

        if (!empty($this->selectedJobsite)) {
            $query->whereHas('assets', function ($q) {
                $q->where('jobsite_id', $this->selectedJobsite);
            });
        }
        $data = $query->withCount('assets')
            ->has('assets')
            ->orderBy('assets_count', 'desc')
            ->limit(10)
            ->pluck('assets_count', 'name');

        $series = $data->values()->toArray();
        $categories = $data->keys()->toArray();
        // if (empty($series)) {
        //     $series = ['0'];
        //     $categories = ['-'];
        // }
       
        return [
            'series' => $series,
            'categories' => $categories,
        ];
    }

    protected function prepareAssetsByJobsiteChart()
    {
        $query = Jobsite::query();
        if (!empty($this->selectedJobsite)) {
            $query->whereHas('assets', function ($q) {
                $q->where('jobsite_id', $this->selectedJobsite);
            });
        }
        $data = $query->withCount('assets')
            ->has('assets', '>', 0)
            ->orderBy('assets_count', 'desc')
            ->pluck('assets_count', 'name');

        $series = $data->values()->toArray();
        $categories = $data->keys()->toArray();
        // if (empty($series)) {
        //     $series = ['0'];
        //     $categories = ['-'];
        // }

        return [
            'series' => $series,
            'categories' => $categories,
        ];
    }

    protected function prepareAssetRequestTrendChart()
    {
        $query = AssetRequest::query();

        if (!empty($this->selectedJobsite)) {
            $query->where('jobsite_id', $this->selectedJobsite);
        }

        $data = $query->select(
                DB::raw('YEAR(request_date) as year'),
                DB::raw('MONTH(request_date) as month'),
                DB::raw('count(*) as count')
            )
            ->where('request_date', '>=', now()->subYear())
            ->groupBy(DB::raw('YEAR(request_date)'), DB::raw('MONTH(request_date)'))
            ->orderBy(DB::raw('YEAR(request_date)'), 'asc')
            ->orderBy(DB::raw('MONTH(request_date)'), 'asc')
            ->get();

        $labels = $data->map(fn($item) => \Carbon\Carbon::create($item->year, $item->month)->isoFormat('MMM YY'))->toArray();
        $series = $data->pluck('count')->toArray();

        // if (empty($series)) {
        //     $series = ['0'];
        //     $labels = ['-'];
        // }

        return [
            'series' => $series,
            'labels' => $labels,
        ];
    }
    public function render()
    {
        $this->prepareChartData();
        return view('livewire.dashboard');
    }
    
}
