<?php

namespace App\Livewire\Master\Asset;

use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Jobsite;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Pilih aset untuk dicetak label QR-nya (banyak sekaligus).
 */
class LabelPicker extends Component
{
    use WithPagination;

    public const MAX = 500;

    protected $paginationTheme = 'bootstrap';

    #[Url(as: 'site')]
    public $jobsiteId = '';
    #[Url(as: 'status')]
    public $status = '';
    #[Url(as: 'type')]
    public $typeId = '';
    #[Url(as: 'q')]
    public $search = '';

    public $size = 'small';    // small | medium | large
    public $layout = 'roll';   // roll (printer label) | sheet (A4)

    /** ID aset terpilih (tetap tersimpan saat pindah halaman). */
    public $selected = [];

    public function updating($name) { if (in_array($name, ['jobsiteId', 'status', 'typeId', 'search'], true)) $this->resetPage(); }

    protected function query()
    {
        return Asset::query()
            ->when($this->jobsiteId, fn ($q) => $q->where('jobsite_id', $this->jobsiteId))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->typeId, fn ($q) => $q->where('asset_type_id', $this->typeId))
            ->when($this->search, function ($q) {
                $t = '%' . $this->search . '%';
                $q->where(fn ($w) => $w->where('asset_number', 'like', $t)->orWhere('old_asset_number', 'like', $t)->orWhere('serial_number', 'like', $t)
                    ->orWhereHas('holder', fn ($h) => $h->where('name', 'like', $t)));
            });
    }

    public function selectAllFiltered()
    {
        $ids = $this->query()->orderBy('asset_number')->limit(self::MAX)->pluck('id')->map(fn ($i) => (string) $i)->all();
        $this->selected = array_values(array_unique(array_merge($this->selected, $ids)));
        if (count($this->selected) > self::MAX) {
            $this->selected = array_slice($this->selected, 0, self::MAX);
            $this->dispatch('swal:error', ['title' => 'Dibatasi', 'text' => 'Maksimal ' . self::MAX . ' label per cetak. Persempit filter.']);
        }
    }

    public function togglePage(array $ids, bool $on)
    {
        $ids = array_map('strval', $ids);
        $this->selected = $on
            ? array_values(array_unique(array_merge($this->selected, $ids)))
            : array_values(array_diff($this->selected, $ids));
    }

    public function clearSelection() { $this->selected = []; }

    public function render()
    {
        $assets = $this->query()
            ->with(['assetType:id,name', 'assetSeries:id,name', 'jobsite:id,code', 'holder:id,name'])
            ->orderBy('asset_number')
            ->paginate(25, ['id', 'asset_number', 'old_asset_number', 'serial_number', 'status', 'asset_type_id', 'asset_series_id', 'jobsite_id', 'holder_user_id']);

        return view('livewire.master.asset.label-picker', [
            'assets' => $assets,
            'jobsites' => Jobsite::orderBy('code')->get(['id', 'code', 'name']),
            'types' => AssetType::orderBy('name')->get(['id', 'name']),
            'statuses' => Asset::select('status')->distinct()->orderBy('status')->pluck('status'),
            'filteredCount' => $this->query()->count(),
            'printUrl' => count($this->selected)
                ? route('asset.labels.print', ['ids' => implode(',', $this->selected), 'size' => $this->size, 'layout' => $this->layout])
                : null,
        ]);
    }
}
