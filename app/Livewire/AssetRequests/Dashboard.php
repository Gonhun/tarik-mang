<?php

namespace App\Livewire\AssetRequests;

use App\Livewire\Dashboard\Concerns\PendingApprovals;
use App\Models\AssetRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Dashboard pengajuan aset: satu tempat untuk
 *  - "Perlu tindakan saya" (approval giliran saya + validasi IT di site saya),
 *  - "Pengajuan saya",
 *  - "Semua pengajuan" (IT / admin / pengelola pengajuan).
 */
class Dashboard extends Component
{
    use WithPagination, PendingApprovals;

    /** Urutan tahap pengajuan: status => [label singkat, nomor tahap (1-5)]. */
    public const STAGES = [
        'Pending IT Item Validation' => ['Validasi IT', 1],
        'Pending Approval' => ['Persetujuan', 2],
        'Pending PR & Accounting Number' => ['Pembelian (PR)', 3],
        'Pending PO' => ['Pembelian (PO)', 3],
        'Pending QC' => ['Cek barang (QC)', 4],
        'Pending Asset Mutation' => ['Pengiriman aset', 4],
        'Completed' => ['Selesai', 5],
        'Close' => ['Ditutup', 5],
        'Rejected' => ['Ditolak', 5],
    ];
    public const CLOSED = ['Completed', 'Close', 'Rejected'];

    protected $paginationTheme = 'bootstrap';

    #[Url(as: 'tab')]
    public $tab = '';
    #[Url(as: 'q')]
    public $search = '';
    #[Url(as: 'status')]
    public $statusFilter = 'active'; // active | closed | <status>

    protected $listeners = ['refreshDashboard' => '$refresh'];

    public function mount()
    {
        if (!in_array($this->tab, ['action', 'mine', 'all'], true)) {
            $this->tab = $this->actionCount() > 0 ? 'action' : 'mine';
        }
    }

    public function setTab($tab)
    {
        $this->tab = in_array($tab, ['action', 'mine', 'all'], true) ? $tab : 'mine';
        $this->resetPage();
    }

    public function updatingSearch() { $this->resetPage(); }
    public function updatingStatusFilter() { $this->resetPage(); }

    protected function canSeeAll(): bool
    {
        $user = Auth::user();

        return $user->isItStaff() || $user->can('Manajement Request') || $user->hasRole(['Administrator IT', 'Administrator']);
    }

    /** Pengajuan yang menunggu validasi IT di site user (khusus tim IT). */
    protected function itValidationQuery()
    {
        $user = Auth::user();
        if (!$user->isItStaff() || !$user->jobsite_id) {
            return AssetRequest::whereRaw('1 = 0');
        }

        return AssetRequest::where('status', 'Pending IT Item Validation')->where('jobsite_id', $user->jobsite_id);
    }

    protected function actionCount(): int
    {
        return $this->myAssetRequestApprovals(Auth::id())->count() + $this->itValidationQuery()->count();
    }

    protected function applyFilters($query)
    {
        return $query
            ->when($this->statusFilter === 'active', fn ($q) => $q->whereNotIn('status', self::CLOSED))
            ->when($this->statusFilter === 'closed', fn ($q) => $q->whereIn('status', self::CLOSED))
            ->when($this->statusFilter && !in_array($this->statusFilter, ['active', 'closed'], true), fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->search, function ($q) {
                $term = '%' . $this->search . '%';
                // Dikelompokkan agar filter status tetap berlaku.
                $q->where(fn ($w) => $w->where('request_number', 'like', $term)
                    ->orWhere('purpose', 'like', $term)
                    ->orWhereHas('requester', fn ($r) => $r->where('name', 'like', $term)->orWhere('nik', 'like', $term)));
            });
    }

    public function render()
    {
        $user = Auth::user();
        $canSeeAll = $this->canSeeAll();
        if ($this->tab === 'all' && !$canSeeAll) {
            $this->tab = 'mine';
        }

        $with = [
            'requester:id,name,nik', 'jobsite:id,code',
            'approvals' => fn ($q) => $q->with('approver:id,name'),
        ];

        // Tab "Perlu tindakan": approval giliran saya + validasi IT (tanpa paginasi, biasanya sedikit)
        $myApprovalRequestIds = $this->myAssetRequestApprovals($user->id)->pluck('asset_request_id');
        $actionRequests = AssetRequest::query()
            ->where(fn ($q) => $q->whereIn('id', $myApprovalRequestIds)
                ->orWhereIn('id', $this->itValidationQuery()->select('id')))
            ->with($with)->withCount('items')
            ->orderBy('request_date')
            ->get();

        $list = null;
        if ($this->tab !== 'action') {
            $base = $this->tab === 'all' ? AssetRequest::query() : AssetRequest::where(fn ($q) => $q->where('requester_user_id', $user->id)->orWhere('submitted_by_user_id', $user->id));
            $list = $this->applyFilters($base)
                ->with($with)->withCount('items')
                ->orderByDesc('request_date')->orderByDesc('id')
                ->paginate(10);
        }

        $statusSource = $this->tab === 'all' ? AssetRequest::query() : AssetRequest::where(fn ($q) => $q->where('requester_user_id', $user->id)->orWhere('submitted_by_user_id', $user->id));
        $statusCounts = $statusSource->select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status');

        return view('livewire.asset-requests.dashboard', [
            'actionRequests' => $actionRequests,
            'myApprovalRequestIds' => $myApprovalRequestIds->all(),
            'list' => $list,
            'canSeeAll' => $canSeeAll,
            'statusCounts' => $statusCounts,
            'activeCount' => $statusCounts->except(self::CLOSED)->sum(),
            'closedCount' => $statusCounts->only(self::CLOSED)->sum(),
            'stages' => self::STAGES,
        ]);
    }
}
