<?php

namespace App\Livewire\AssetDisposals;

use App\Models\AssetTransaction;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Daftar pengajuan penghapusan aset (DISPOSED): rusak berat, kehilangan, dan lainnya.
 * Pengajuan baru dibuat dari halaman Aset (menu aksi) atau dari inspeksi / laporan kerusakan.
 */
class ManageDisposed extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    #[Url(as: 'q')]
    public $search = '';
    #[Url(as: 'status')]
    public $statusFilter = '';
    #[Url(as: 'jenis')]
    public $reasonFilter = ''; // '' | LOST | OTHER_THAN_LOST

    public const OPEN_STATUSES = ['Pending Approval', 'Pending KADEP IT', 'Pending Cost Control', 'Kepala Departemen', 'Draft'];

    public function updatingSearch() { $this->resetPage(); }
    public function updatingStatusFilter() { $this->resetPage(); }
    public function updatingReasonFilter() { $this->resetPage(); }

    public function resetFilters()
    {
        $this->reset(['search', 'statusFilter', 'reasonFilter']);
        $this->resetPage();
    }

    public function render()
    {
        $base = AssetTransaction::query()->where('transaction_type', 'DISPOSED');

        $statusCounts = (clone $base)->select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status');
        $lostOpen = (clone $base)->whereIn('status', self::OPEN_STATUSES)->where('notes', 'like', '[Kehilangan]%')->count();

        $query = (clone $base)
            ->with([
                'items.asset:id,asset_number,serial_number,status,asset_type_id,asset_series_id,jobsite_id',
                'items.asset.assetType:id,name', 'items.asset.assetSeries:id,name', 'items.asset.jobsite:id,code',
                'creator:id,name', 'fromUser:id,name',
                'approvals' => fn ($q) => $q->with(['approver:id,name', 'actionBy:id,name']),
            ])
            ->when($this->statusFilter === 'open', fn ($q) => $q->whereIn('status', self::OPEN_STATUSES))
            ->when($this->statusFilter && $this->statusFilter !== 'open', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->reasonFilter === 'LOST', fn ($q) => $q->where('notes', 'like', '[Kehilangan]%'))
            ->when($this->reasonFilter === 'OTHER', fn ($q) => $q->where(fn ($w) => $w->whereNull('notes')->orWhere('notes', 'not like', '[Kehilangan]%')))
            ->when($this->search, function ($q) {
                $term = '%' . $this->search . '%';
                $q->where(fn ($w) => $w->where('transaction_number', 'like', $term)
                    ->orWhere('notes', 'like', $term)
                    ->orWhereHas('creator', fn ($c) => $c->where('name', 'like', $term))
                    ->orWhereHas('items.asset', fn ($a) => $a->where('asset_number', 'like', $term)->orWhere('serial_number', 'like', $term)));
            });

        return view('livewire.asset-disposals.manage-disposed', [
            'disposalRequests' => $query->orderByDesc('transaction_date')->orderByDesc('id')->paginate(10),
            'statusCounts' => $statusCounts,
            'openCount' => $statusCounts->only(self::OPEN_STATUSES)->sum(),
            'lostOpen' => $lostOpen,
        ]);
    }
}
