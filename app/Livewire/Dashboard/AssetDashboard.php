<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use App\Models\Asset;
use App\Models\AssetRequest;
use App\Models\Jobsite;
use Illuminate\Support\Facades\DB;

class AssetDashboard extends Component
{
    public $totalAssets = 0;
    public $freeAssets = 0;
    public $usedAssets = 0;
    public $repairAssets = 0;

    /**
     * Dijalankan saat komponen pertama kali dimuat.
     */
    public function mount()
    {
        $this->loadSummaryData();
    }

    /**
     * Memuat data untuk kartu statistik ringkasan.
     */
    public function loadSummaryData()
    {
        $this->totalAssets = Asset::count();
        $this->freeAssets = Asset::where('status', 'Free')->count();
        $this->usedAssets = Asset::where('status', 'Used')->count();
        $this->repairAssets = Asset::where('status', 'Repair')->count();
    }

    /**
     * Menyiapkan semua data untuk chart dan mengirimkannya ke frontend.
     */
    public function prepareChartData()
    {
        $this->dispatch('updateAssetCharts', [
            'assetsByJobsite' => $this->prepareAssetsByJobsiteChart(),
            'assetRequestTrend' => $this->prepareAssetRequestTrendChart(),
        ]);
    }

    protected function prepareAssetsByJobsiteChart()
    {
        $data = Jobsite::query()
            ->withCount('assets')
            ->has('assets')
            ->orderBy('assets_count', 'desc')
            ->pluck('assets_count', 'name');

        return [
            'series' => $data->values()->toArray(),
            'categories' => $data->keys()->toArray(),
        ];
    }

    protected function prepareAssetRequestTrendChart()
    {
        $data = AssetRequest::query()
            ->select(
                DB::raw('YEAR(request_date) as year'),
                DB::raw('MONTH(request_date) as month'),
                DB::raw('count(*) as count')
            )
            ->where('request_date', '>=', now()->subYear())
            ->groupBy(DB::raw('YEAR(request_date)'), DB::raw('MONTH(request_date)'))
            ->orderBy(DB::raw('YEAR(request_date)'), 'asc')
            ->orderBy(DB::raw('MONTH(request_date)'), 'asc')
            ->get();

        return [
            'series' => $data->pluck('count')->toArray(),
            'labels' => $data->map(fn($item) => \Carbon\Carbon::create($item->year, $item->month)->isoFormat('MMM YY'))->toArray(),
        ];
    }

    public function render()
    {
        // Panggil ini di render agar chart dimuat saat pertama kali
        $this->prepareChartData();

        // Daftar status yang dianggap "sedang berjalan"
        $activeStatuses = [
            'Pending IT Item Validation', 'Pending Approval', 'Processing',
            'Pending PR & Accounting Number', 'Pending PO Number'
        ];

        $activeRequests = AssetRequest::whereIn('status', $activeStatuses)
            ->with(['requester:id,name', 'jobsite:id,name'])
            ->orderBy('request_date', 'desc')
            ->limit(5) // Tampilkan 5 pengajuan terbaru yang aktif
            ->get();

        return view('livewire.dashboard.asset-dashboard', [
            'activeRequests' => $activeRequests,
        ]);
    }
}
