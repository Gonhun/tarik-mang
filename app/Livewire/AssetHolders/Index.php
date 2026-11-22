<?php

namespace App\Livewire\AssetHolders;

use App\Models\AssetType;
use App\Models\Department;
use App\Models\Jobsite;
use App\Models\User;
use App\Support\AssetHolding;
use App\Support\SqlLike;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Rekap aset yang sedang dipegang per user.
 */
#[Title('Pemegang Aset')]
class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    #[Url(as: 'q')] public string $search = '';
    #[Url(as: 'site')] public $filterJobsite = '';      // jobsite aset
    #[Url(as: 'dept')] public $filterDepartment = '';   // departemen user
    #[Url(as: 'tipe')] public $filterType = '';
    #[Url(as: 'status')] public string $filterStatus = '';
    #[Url(as: 'min')] public $minAssets = '';
    #[Url(as: 'urut')] public string $sort = 'total';
    #[Url(as: 'per')] public int $perPage = 20;

    public const SORTS = ['total' => 'Aset terbanyak', 'value' => 'Nilai terbesar', 'name' => 'Nama A–Z', 'latest' => 'Serah terima terbaru'];
    public const STATUS_LABELS = [
        'Used' => 'Dipakai', 'Pre-Used' => 'Menunggu konfirmasi', 'Borrowed' => 'Dipinjam',
        'Pending Mutation' => 'Proses mutasi', 'Pending Inspection' => 'Menunggu inspeksi',
        'Repair' => 'Perbaikan', 'Demage' => 'Rusak', 'Pre Disposed' => 'Proses disposal', 'Delivery' => 'Pengiriman', 'Missing' => 'Hilang',
    ];

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFilterJobsite(): void { $this->resetPage(); }
    public function updatingFilterDepartment(): void { $this->resetPage(); }
    public function updatingFilterType(): void { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }
    public function updatingMinAssets(): void { $this->resetPage(); }
    public function updatingSort(): void { $this->resetPage(); }
    public function updatingPerPage(): void { $this->resetPage(); }

    public function resetFilters(): void
    {
        $this->reset(['search', 'filterJobsite', 'filterDepartment', 'filterType', 'filterStatus', 'minAssets', 'sort']);
        $this->resetPage();
    }

    /** Aset dipegang sesuai filter aset (jobsite, tipe, status). */
    protected function assetScope()
    {
        return AssetHolding::query()
            ->when($this->filterJobsite, fn ($q) => $q->where('jobsite_id', $this->filterJobsite))
            ->when($this->filterType, fn ($q) => $q->where('asset_type_id', $this->filterType))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus));
    }

    /** User + agregat asetnya. */
    protected function holdersQuery()
    {
        $agg = $this->assetScope()
            ->selectRaw("holder_user_id,
                count(*) as total,
                sum(case when status = 'Used' then 1 else 0 end) as used,
                sum(case when status = 'Pre-Used' then 1 else 0 end) as pending,
                sum(case when status = 'Borrowed' then 1 else 0 end) as borrowed,
                sum(case when status not in ('Used','Pre-Used','Borrowed') then 1 else 0 end) as other,
                sum(coalesce(price, 0)) as value,
                max(updated_at) as last_change")
            ->groupBy('holder_user_id');

        $q = User::query()
            ->joinSub($agg, 'h', 'h.holder_user_id', '=', 'users.id')
            ->select('users.id', 'users.name', 'users.nik', 'users.pp_path', 'users.department_id', 'users.position_id', 'users.jobsite_id', 'users.is_active',
                'h.total', 'h.used', 'h.pending', 'h.borrowed', 'h.other', 'h.value', 'h.last_change')
            ->when($this->filterDepartment, fn ($w) => $w->where('users.department_id', $this->filterDepartment))
            ->when(is_numeric($this->minAssets) && (int) $this->minAssets > 1, fn ($w) => $w->where('h.total', '>=', (int) $this->minAssets));

        if (trim($this->search) !== '') {
            $term = '%' . SqlLike::escape(trim($this->search)) . '%';
            $q->where(fn ($w) => $w->where('users.name', 'like', $term)->orWhere('users.nik', 'like', $term));
        }

        return $q;
    }

    public function export()
    {
        $q = $this->holdersQuery()->with(['department:id,name', 'position:id,name', 'jobsite:id,code'])->orderByDesc('h.total')->orderBy('users.name');

        return response()->streamDownload(function () use ($q) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['NIK', 'Nama', 'Departemen', 'Jabatan', 'Jobsite user', 'Total aset', 'Dipakai', 'Menunggu konfirmasi', 'Dipinjam', 'Status lain', 'Nilai (Rp)']);
            $q->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $u) {
                    fputcsv($out, [$u->nik, $u->name, $u->department->name ?? '', $u->position->name ?? '', $u->jobsite->code ?? '',
                        $u->total, $u->used, $u->pending, $u->borrowed, $u->other, (int) $u->value]);
                }
            });
            fclose($out);
        }, 'pemegang-aset-' . now()->format('Ymd-His') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render()
    {
        $holders = $this->holdersQuery()->with(['department:id,name', 'position:id,name', 'jobsite:id,code']);
        match ($this->sort) {
            'value' => $holders->orderByDesc('h.value'),
            'name' => $holders->orderBy('users.name'),
            'latest' => $holders->orderByDesc('h.last_change'),
            default => $holders->orderByDesc('h.total')->orderBy('users.name'),
        };

        // Ringkasan mengikuti filter.
        $summary = DB::query()->fromSub($this->holdersQuery(), 's')
            ->selectRaw('count(*) as users, coalesce(sum(total),0) as assets, coalesce(sum(value),0) as value, coalesce(sum(pending),0) as pending, coalesce(max(total),0) as max_total')
            ->first();

        $byType = $this->assetScope()
            ->when($this->filterDepartment, fn ($q) => $q->whereIn('holder_user_id', User::select('id')->where('department_id', $this->filterDepartment)))
            ->join('asset_types', 'asset_types.id', '=', 'assets.asset_type_id')
            ->selectRaw('asset_types.name as type, count(*) as total')
            ->groupBy('asset_types.name')->orderByDesc('total')->limit(8)->pluck('total', 'type');

        return view('livewire.asset-holders.index', [
            'holders' => $holders->paginate($this->perPage),
            'summary' => $summary,
            'byType' => $byType,
            'anomalies' => AssetHolding::anomalies()->count(),
            'jobsites' => Jobsite::orderBy('code')->get(['id', 'code']),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'types' => AssetType::orderBy('name')->get(['id', 'name']),
            'sorts' => self::SORTS,
            'statusLabels' => self::STATUS_LABELS,
            'filtersActive' => collect([$this->search, $this->filterJobsite, $this->filterDepartment, $this->filterType, $this->filterStatus, $this->minAssets])->filter(fn ($v) => $v !== '' && $v !== null)->count(),
        ]);
    }
}
