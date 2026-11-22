<?php

namespace App\Livewire\AssetHolders;

use App\Models\AssetTransaction;
use App\Models\User;
use App\Support\AssetHolding;
use App\Support\SqlLike;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Detail aset yang dipegang satu user + riwayat serah terima / pengembalian.
 */
class Show extends Component
{
    #[Locked] public int $userId;

    #[Url(as: 'tab')] public string $tab = 'aset';
    #[Url(as: 'q')] public string $search = '';
    #[Url(as: 'tipe')] public string $filterType = '';

    public function mount(User $user): void
    {
        $this->userId = $user->id;
        if (!in_array($this->tab, ['aset', 'riwayat'], true)) {
            $this->tab = 'aset';
        }
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['aset', 'riwayat'], true) ? $tab : 'aset';
    }

    public function render()
    {
        $user = User::with(['department:id,name', 'position:id,name', 'positionLevel:id,name', 'jobsite:id,code,name', 'manager:id,name'])
            ->findOrFail($this->userId);

        $base = AssetHolding::query()->where('holder_user_id', $user->id);

        $assets = (clone $base)
            ->with(['assetType:id,name', 'assetBrand:id,name', 'assetSeries:id,name', 'jobsite:id,code',
                'transaction:id,transaction_number,transaction_date,created_at,notes'])
            ->when($this->filterType !== '', fn ($q) => $q->where('asset_type_id', $this->filterType))
            ->when(trim($this->search) !== '', function ($q) {
                $term = '%' . SqlLike::escape(trim($this->search)) . '%';
                $q->where(fn ($w) => $w->where('asset_number', 'like', $term)->orWhere('serial_number', 'like', $term)->orWhere('old_asset_number', 'like', $term));
            })
            ->orderBy('asset_type_id')->orderBy('asset_number')
            ->get();

        $all = (clone $base)->with('assetType:id,name')->get(['id', 'asset_type_id', 'status', 'price']);

        $history = $this->tab === 'riwayat'
            ? AssetTransaction::query()
                ->whereIn('transaction_type', ['HANDOVER', 'RETURN', 'MUTATION'])
                ->where(fn ($q) => $q->where('from_user_id', $user->id)->orWhere('to_user_id', $user->id))
                ->with(['items.asset:id,asset_number,asset_type_id', 'items.asset.assetType:id,name', 'fromUser:id,name', 'toUser:id,name'])
                ->orderByDesc('id')->limit(100)->get()
            : collect();

        return view('livewire.asset-holders.show', [
            'user' => $user,
            'assets' => $assets,
            'byType' => $all->groupBy(fn ($a) => $a->assetType->name ?? 'Lainnya')->map->count()->sortDesc(),
            'typeIds' => $all->pluck('assetType.name', 'asset_type_id')->filter(),
            'totalCount' => $all->count(),
            'totalValue' => (float) $all->sum('price'),
            'pendingCount' => $all->where('status', 'Pre-Used')->count(),
            'history' => $history,
            'statusLabels' => Index::STATUS_LABELS,
        ])->title($user->name . ' · Pemegang Aset');
    }
}
