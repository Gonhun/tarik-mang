<?php

namespace App\Livewire\Dashboard;

use App\Livewire\Dashboard\Concerns\PendingApprovals;
use App\Models\Asset;
use App\Models\AssetRequest;
use App\Models\AssetTransaction;
use App\Models\Jobsite;
use App\Models\SoftwareLicenses;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Dashboard tim IT: apa yang perlu dikerjakan hari ini + kondisi aset per site.
 */
class ItDashboard extends Component
{
    use PendingApprovals;

    /** Status aset yang dikelompokkan untuk kartu ringkasan & chart. */
    public const STATUS_GROUPS = [
        'Siap pakai' => ['Free'],
        'Dipakai' => ['Used', 'Pre-Used'],
        'Perlu perhatian' => ['Repair', 'Demage', 'Pending Inspection', 'Missing'],
        'Dalam proses' => ['Delivery', 'Pending Mutation'],
        'Akan / sudah dibuang' => ['Pre Disposed', 'Disposed', 'Scrap'],
    ];

    public const ACTIVE_REQUEST_STATUSES = [
        'Pending IT Item Validation', 'Pending Approval', 'Pending PR & Accounting Number',
        'Pending PO', 'Pending QC', 'Pending Asset Mutation',
    ];

    public const PENDING_TRANSACTION_STATUSES = ['Pending Release', 'Pending Inspection', 'Delivery'];

    /** '' = semua site. Default: site user sendiri. */
    #[Url(as: 'site')]
    public $jobsiteId = null;

    public function mount()
    {
        if ($this->jobsiteId === null) {
            $this->jobsiteId = (string) (Auth::user()->jobsite_id ?? '');
        }
    }

    public function updatedJobsiteId()
    {
        $this->dispatch('it-dashboard-charts', charts: $this->chartData());
    }

    protected function siteScope(Builder $query, string $column = 'jobsite_id'): Builder
    {
        return $this->jobsiteId !== '' && $this->jobsiteId !== null
            ? $query->where($column, (int) $this->jobsiteId)
            : $query;
    }

    protected function assetStatusCounts(): array
    {
        $raw = $this->siteScope(Asset::query())
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $groups = [];
        foreach (self::STATUS_GROUPS as $label => $statuses) {
            $groups[$label] = (int) collect($statuses)->sum(fn ($s) => $raw[$s] ?? 0);
        }

        return ['groups' => $groups, 'raw' => $raw, 'total' => (int) $raw->sum()];
    }

    protected function requestTrend(): array
    {
        $start = now()->startOfMonth()->subMonths(11);

        $rows = $this->siteScope(AssetRequest::query())
            ->where('request_date', '>=', $start)
            ->select(
                DB::raw('YEAR(request_date) as y'),
                DB::raw('MONTH(request_date) as m'),
                DB::raw('count(*) as total')
            )
            ->groupBy(DB::raw('YEAR(request_date)'), DB::raw('MONTH(request_date)'))
            ->get()
            ->keyBy(fn ($r) => sprintf('%04d-%02d', $r->y, $r->m));

        $labels = [];
        $series = [];
        for ($i = 0; $i < 12; $i++) {
            $month = $start->copy()->addMonths($i);
            $labels[] = $month->locale('id')->isoFormat('MMM YY');
            $series[] = (int) ($rows[$month->format('Y-m')]->total ?? 0);
        }

        return ['labels' => $labels, 'series' => $series];
    }

    public function chartData(?array $status = null): array
    {
        $status ??= $this->assetStatusCounts();

        return [
            'status' => [
                'labels' => array_keys($status['groups']),
                'series' => array_values($status['groups']),
            ],
            'trend' => $this->requestTrend(),
        ];
    }

    public function render()
    {
        $user = Auth::user();
        $assets = $this->assetStatusCounts();

        // --- Butuh tindakan ---
        $itValidationQuery = $this->siteScope(AssetRequest::query())->where('status', 'Pending IT Item Validation');
        $itValidationCount = (clone $itValidationQuery)->count();
        $itValidationList = $itValidationQuery
            ->with(['requester:id,name', 'jobsite:id,code'])
            ->withCount(['items as pending_items_count' => fn ($q) => $q->where('it_approval_status', 'Pending')])
            ->orderBy('request_date')
            ->limit(6)
            ->get();

        $approvals = $this->pendingApprovalCounts($user->id);

        $pendingTransactions = $this->siteScope(AssetTransaction::query(), 'to_jobsite_id')
            ->whereIn('status', self::PENDING_TRANSACTION_STATUSES)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        // --- Pipeline pengajuan aset ---
        $pipeline = $this->siteScope(AssetRequest::query())
            ->whereIn('status', self::ACTIVE_REQUEST_STATUSES)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');
        $pipeline = collect(self::ACTIVE_REQUEST_STATUSES)->mapWithKeys(fn ($s) => [$s => (int) ($pipeline[$s] ?? 0)]);

        // --- Lisensi akan habis (global, 60 hari) ---
        $expiringLicenses = SoftwareLicenses::query()
            ->whereNotNull('expiration_date')
            ->whereBetween('expiration_date', [now()->startOfDay(), now()->addDays(60)->endOfDay()])
            ->orderBy('expiration_date')
            ->limit(5)
            ->get(['id', 'name', 'expiration_date', 'seats']);

        // Peringatan konfigurasi workflow, hanya untuk admin yang bisa memperbaikinya.
        $workflowAlert = null;
        if ($user->can('Add Workflow') || $user->hasRole(['Administrator IT', 'Administrator'])) {
            $openStatuses = ['Completed', 'Rejected', 'Cancelled', 'Pending Release', 'Delivery', 'Pending Inspection'];
            $workflowAlert = [
                'unconfigured' => \App\Support\Workflow::unconfiguredTypes(),
                'noApprovals' => AssetTransaction::whereIn('transaction_type', ['DISPOSED', 'MUTATION'])
                    ->whereNotIn('status', $openStatuses)
                    ->doesntHave('approvals')
                    ->count(),
                'orphanSteps' => \App\Models\AssetTransactionApproval::where('status', 'Pending')
                    ->whereNull('approver_user_id')
                    ->whereHas('transaction', fn ($q) => $q->whereNotIn('status', $openStatuses))
                    ->count()
                    + \App\Models\AssetRequestApproval::where('status', 'Pending')->whereNull('approver_user_id')
                    ->whereHas('assetRequest', fn ($q) => $q->where('status', 'Pending Approval'))
                    ->count(),
            ];
            if (!$workflowAlert['unconfigured'] && !$workflowAlert['noApprovals'] && !$workflowAlert['orphanSteps']) {
                $workflowAlert = null;
            }
        }

        return view('livewire.dashboard.it-dashboard', [
            'workflowAlert' => $workflowAlert,
            'jobsites' => Jobsite::orderBy('code')->get(['id', 'code', 'name']),
            'assets' => $assets,
            'itValidationCount' => $itValidationCount,
            'itValidationList' => $itValidationList,
            'approvals' => $approvals,
            'pendingTransactions' => $pendingTransactions,
            'pipeline' => $pipeline,
            'expiringLicenses' => $expiringLicenses,
            'chartData' => $this->chartData($assets),
            'today' => Carbon::now(),
        ]);
    }
}
