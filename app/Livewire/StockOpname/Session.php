<?php

namespace App\Livewire\StockOpname;

use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Services\StockOpnameService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Pemindai stock opname: scan QR label (kamera HP), scanner USB, atau ketik nomor aset.
 */
class Session extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    #[Locked]
    public $opnameId;

    public $code = '';

    /** Item hasil scan terakhir yang sedang dinilai (kondisi & lokasi diisi SETELAH aset ditemukan). */
    #[Locked]
    public $lastItemId = null;
    public $lastLocation = '';

    #[Url(as: 'tab')]
    public $tab = 'missing'; // found | missing | other_site | not_registered
    public $search = '';

    /** Hasil scan terakhir untuk umpan balik: ['type' => success|warning|danger|info, 'title' => .., 'text' => ..] */
    public $last = null;

    public function mount(StockOpname $opname)
    {
        $this->opnameId = $opname->id;
    }

    protected function opname(): StockOpname
    {
        return StockOpname::with('jobsite:id,code,name')->findOrFail($this->opnameId);
    }

    public function setTab($tab)
    {
        $this->tab = in_array($tab, ['found', 'missing', 'other_site', 'not_registered'], true) ? $tab : 'missing';
        $this->resetPage();
    }

    public function updatingSearch() { $this->resetPage(); }

    /** Lihat StockOpnameService::extractCode() (tetap di sini karena dipakai halaman lain). */
    public static function extractCode(string $raw): string
    {
        return StockOpnameService::extractCode($raw);
    }

    /** SN "kosong" yang tidak boleh dipakai untuk mencari aset. */
    public const PLACEHOLDER_SN = StockOpnameService::PLACEHOLDER_SN;

    /** Lihat StockOpnameService::findAsset() (tetap di sini karena dipakai halaman Peminjaman). */
    public static function findAsset(string $code, ?int $preferJobsiteId = null, array $with = []): array
    {
        return StockOpnameService::findAsset($code, $preferJobsiteId, $with);
    }

    /** Dipanggil dari kamera (JS) atau form input. */
    public function scan($raw = null)
    {
        $opname = $this->opname();
        $code = StockOpnameService::extractCode((string) ($raw ?? $this->code));
        $this->code = '';
        // Kode kosong / terlalu panjang diabaikan tanpa umpan balik (mis. scanner USB mengirim Enter kosong).
        if ($opname->isOpen() && ($code === '' || strlen($code) > 100)) {
            return;
        }

        $res = app(StockOpnameService::class)->scan($opname, $code, Auth::user());
        $this->last = ['type' => $res['type'], 'title' => $res['title'], 'text' => $res['text']];

        if (!$opname->isOpen()) {
            return;
        }
        if ($res['item']) {
            // Kartu hasil: kondisi & lokasi bisa diisi/dikoreksi (hanya untuk aset terdaftar).
            $this->lastItemId = $res['item']->asset_id ? $res['item']->id : null;
            $this->lastLocation = '';
        }
        $this->dispatch('opname-feedback', type: $res['type']);
    }

    /** Ubah kondisi aset yang baru discan (baik / rusak). */
    public function setCondition($itemId, $condition)
    {
        $item = $this->editableItem($itemId);
        if (!$item) return;
        app(StockOpnameService::class)->setCondition($item, (string) $condition);
    }

    /**
     * Simpan lokasi aset yang baru discan. Kosong = lokasi aset tetap seperti semula.
     * Diisi = catat di hasil opname dan perbarui lokasi aset (tercatat di log aset).
     */
    public function saveLocation($itemId)
    {
        $item = $this->editableItem($itemId);
        if (!$item) return;
        $loc = trim((string) $this->lastLocation);
        if ($loc === '') {
            $this->dispatch('opname-location-saved', kept: true);
            return;
        }
        $this->validate(['lastLocation' => 'string|max:255']);

        app(StockOpnameService::class)->saveLocation($item, $loc);
        $this->lastLocation = '';
        $this->dispatch('opname-location-saved', kept: false);
    }

    /** Item di sesi ini yang masih boleh diubah (sesi terbuka & aset terdaftar). */
    protected function editableItem($itemId): ?StockOpnameItem
    {
        return app(StockOpnameService::class)->editableItem($this->opname(), $itemId);
    }

    public function undo($itemId)
    {
        if (!app(StockOpnameService::class)->undo($this->opname(), $itemId)) return;
        if ((int) $this->lastItemId === (int) $itemId) {
            $this->lastItemId = null;
        }
        $this->last = ['type' => 'info', 'title' => 'Scan dibatalkan', 'text' => 'Item dihapus dari hasil sesi ini.'];
    }

    public function close()
    {
        $opname = $this->opname();
        if (!$opname->isOpen()) return;

        $opname->update([
            'status' => 'closed',
            'closed_by' => Auth::id(),
            'closed_at' => now(),
            // Simpan jumlah final aset yang seharusnya ada saat sesi ditutup.
            'expected_count' => $opname->expectedAssetsQuery()->count(),
        ]);
        $this->tab = 'missing';
        $this->dispatch('swal:success', ['title' => 'Sesi ditutup', 'text' => "Hasil {$opname->code} tersimpan. Aset yang belum ditemukan bisa ditindaklanjuti dari tab \"Belum ditemukan\"."]);
    }

    public function reopen()
    {
        $opname = $this->opname();
        if ($opname->isOpen()) return;
        if (StockOpname::where('jobsite_id', $opname->jobsite_id)->where('status', 'open')->exists()) {
            $this->dispatch('swal:error', ['title' => 'Tidak bisa dibuka', 'text' => 'Site ini sudah punya sesi lain yang berjalan.']);
            return;
        }
        $opname->update(['status' => 'open', 'closed_by' => null, 'closed_at' => null]);
    }

    /** Unduh hasil (CSV, bisa dibuka di Excel). */
    public function export()
    {
        $opname = $this->opname();
        $scanned = StockOpnameItem::where('stock_opname_id', $opname->id)->with(['asset.assetType', 'asset.assetSeries', 'asset.holder', 'scanner', 'jobsiteAtScan'])->get();
        $scannedIds = $scanned->pluck('asset_id')->filter()->all();
        $missing = $opname->expectedAssetsQuery()->whereNotIn('id', $scannedIds)->with(['assetType', 'assetSeries', 'holder'])->get();
        $label = ['found' => 'Ditemukan', 'other_site' => 'Site lain', 'not_registered' => 'Tidak terdaftar'];

        return response()->streamDownload(function () use ($scanned, $missing, $label) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8
            fputcsv($out, ['Hasil', 'No. aset / kode', 'Tipe', 'Seri', 'Serial number', 'Status aset', 'Site tercatat', 'Pemegang', 'Kondisi', 'Lokasi', 'Discan oleh', 'Waktu scan'], ';');
            foreach ($scanned as $i) {
                fputcsv($out, [$label[$i->result] ?? $i->result, $i->asset->asset_number ?? $i->scanned_code, $i->asset->assetType->name ?? '', $i->asset->assetSeries->name ?? '', $i->asset->serial_number ?? '', $i->asset_status_at_scan, $i->jobsiteAtScan->code ?? '', $i->asset->holder->name ?? '', $i->condition, $i->location_note ?: ($i->asset->current_location_detail ?? ''), $i->scanner->name ?? '', optional($i->scanned_at)->format('Y-m-d H:i')], ';');
            }
            foreach ($missing as $a) {
                fputcsv($out, ['Belum ditemukan', $a->asset_number, $a->assetType->name ?? '', $a->assetSeries->name ?? '', $a->serial_number, $a->status, '', $a->holder->name ?? '', '', $a->current_location_detail, '', ''], ';');
            }
            fclose($out);
        }, str_replace('/', '-', $opname->code) . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render()
    {
        $opname = $this->opname()->load(['starter:id,name', 'closer:id,name']);
        $scannedIds = StockOpnameItem::where('stock_opname_id', $opname->id)->whereNotNull('asset_id')->pluck('asset_id');

        $counts = StockOpnameItem::where('stock_opname_id', $opname->id)
            ->selectRaw('result, count(*) as total')->groupBy('result')->pluck('total', 'result');
        $expected = $opname->isOpen() ? $opname->expectedAssetsQuery()->count() : $opname->expected_count;
        $missingCount = max(0, $expected - ($counts['found'] ?? 0));

        $term = trim($this->search) !== '' ? '%' . trim($this->search) . '%' : null;
        if ($this->tab === 'missing') {
            $list = $opname->expectedAssetsQuery()
                ->whereNotIn('id', $scannedIds)
                ->when($term, fn ($q) => $q->where(fn ($w) => $w->where('asset_number', 'like', $term)->orWhere('serial_number', 'like', $term)
                    ->orWhereHas('holder', fn ($h) => $h->where('name', 'like', $term))))
                ->with(['assetType:id,name', 'assetSeries:id,name', 'holder:id,name'])
                ->orderBy('asset_number')
                ->paginate(15);
        } else {
            $list = StockOpnameItem::where('stock_opname_id', $opname->id)
                ->where('result', $this->tab)
                ->when($term, fn ($q) => $q->where(fn ($w) => $w->where('scanned_code', 'like', $term)
                    ->orWhereHas('asset', fn ($a) => $a->where('asset_number', 'like', $term)->orWhere('serial_number', 'like', $term))))
                ->with(['asset.assetType:id,name', 'asset.assetSeries:id,name', 'asset.holder:id,name', 'jobsiteAtScan:id,code', 'scanner:id,name'])
                ->orderByDesc('scanned_at')
                ->paginate(15);
        }

        $lastItem = $this->lastItemId
            ? StockOpnameItem::with(['asset:id,asset_number,serial_number,current_location_detail,asset_type_id,asset_series_id', 'asset.assetType:id,name', 'asset.assetSeries:id,name'])->find($this->lastItemId)
            : null;

        $recent = StockOpnameItem::where('stock_opname_id', $opname->id)
            ->with('asset:id,asset_number')->orderByDesc('id')->limit(5)->get();

        return view('livewire.stock-opname.session', [
            'opname' => $opname,
            'counts' => $counts,
            'expected' => $expected,
            'missingCount' => $missingCount,
            'list' => $list,
            'recent' => $recent,
            'lastItem' => $lastItem,
        ]);
    }
}
