<?php

namespace App\Livewire\Master\Asset;

use Livewire\Component;
use App\Models\Asset;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

class MyAssets extends Component
{
    use WithPagination;

    public $search = '';
    #[\Livewire\Attributes\Url(as: 'status')]
    public string $filter = '';   // '' | pending | used | borrowed | other
    protected $paginationTheme = 'bootstrap';
    protected $listeners = ['AcceptAssetId', 'deletePermissionConfirmed','refreshMyAssets' => '$refresh'];

    /**
     * Reset pagination saat melakukan pencarian.
     */
    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function setFilter(string $filter): void
    {
        $this->filter = in_array($filter, ['', 'pending', 'used', 'borrowed', 'other'], true) ? $filter : '';
        $this->resetPage();
    }
    public function reportLost($assetId)
    {
        $this->dispatch('openDisposalModal', ['asset_id' => $assetId, 'mode' => 'LOST']);
    }

    public function openReturnModal($assetId)
    {
        $this->dispatch('openReturnModal', $assetId);
    }
    /**
     * Aset milik user yang login dan masih menunggu konfirmasi terima (Pre-Used).
     */
    protected function pendingAssetForMe($id): ?Asset
    {
        return Asset::with('assetSeries:id,name')
            ->whereKey($id)
            ->where('holder_user_id', Auth::id())
            ->where('status', 'Pre-Used')
            ->first();
    }

    public function confirmAccept($id)
    {
        $asset = $this->pendingAssetForMe($id);
        if (!$asset) {
            $this->dispatch('swal:error', ['title' => 'Gagal', 'text' => 'Aset tidak ditemukan atau tidak menunggu konfirmasi Anda.']);
            return;
        }

        $this->dispatch('swal:question', [
            'id' => $id,
            'title' => 'Terima aset?',
            'text' => 'Apakah Anda yakin aset ' . $asset->asset_number . ' (' . ($asset->assetSeries->name ?? '-') . ') sudah Anda terima?',
            'method' => 'AcceptAssetId'
        ]);
    }

    public function AcceptAssetId($id)
    {
        // Hanya pemegang aset yang boleh mengonfirmasi, dan hanya dari status Pre-Used.
        $asset = $this->pendingAssetForMe($id);
        if (!$asset) {
            $this->dispatch('swal:error', ['title' => 'Gagal', 'text' => 'Aset tidak ditemukan atau tidak menunggu konfirmasi Anda.']);
            return;
        }

        $asset->update(['status' => 'Used']);
        $this->dispatch('swal:success', [
            'id' => $id,
            'title' => 'Berhasil!',
            'text' => 'Aset ' . $asset->asset_number . ' telah diterima.',
        ]);
    }

    public function render()
    {
        $base = \App\Support\AssetHolding::query()->where('holder_user_id', Auth::id());

        $counts = (clone $base)->selectRaw("count(*) as total,
                sum(case when status = 'Pre-Used' then 1 else 0 end) as pending,
                sum(case when status = 'Used' then 1 else 0 end) as used,
                sum(case when status = 'Borrowed' then 1 else 0 end) as borrowed")->first();

        $query = (clone $base)
            ->with(['assetType:id,name', 'assetBrand:id,name', 'assetSeries:id,name', 'jobsite:id,code,name',
                'transaction:id,transaction_number,transaction_date,created_at'])
            ->when($this->filter === 'pending', fn ($q) => $q->where('status', 'Pre-Used'))
            ->when($this->filter === 'used', fn ($q) => $q->where('status', 'Used'))
            ->when($this->filter === 'borrowed', fn ($q) => $q->where('status', 'Borrowed'))
            ->when($this->filter === 'other', fn ($q) => $q->whereNotIn('status', ['Pre-Used', 'Used', 'Borrowed']));

        if (trim((string) $this->search) !== '') {
            $term = '%' . \App\Support\SqlLike::escape(trim($this->search)) . '%';
            $query->where(fn ($q) => $q->where('asset_number', 'like', $term)
                ->orWhere('serial_number', 'like', $term)
                ->orWhereHas('assetType', fn ($sq) => $sq->where('name', 'like', $term))
                ->orWhereHas('assetBrand', fn ($sq) => $sq->where('name', 'like', $term))
                ->orWhereHas('assetSeries', fn ($sq) => $sq->where('name', 'like', $term)));
        }

        return view('livewire.master.asset.my-assets', [
            // Yang perlu dikonfirmasi selalu di atas.
            'myAssets' => $query->orderByRaw("case when status = 'Pre-Used' then 0 else 1 end")->orderByDesc('updated_at')->paginate(12),
            'counts' => $counts,
        ]);
    }
}
