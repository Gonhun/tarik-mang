<?php

namespace App\Livewire\StockOpname;

use App\Models\Asset;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
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

    /**
     * Ambil nomor aset dari isi QR: bisa nomor aset langsung, atau link halaman aset (/asset/show/{nomor}).
     */
    public static function extractCode(string $raw): string
    {
        $raw = trim($raw);
        if (preg_match('#/asset/show/([^/?\#]+)#i', $raw, $m)) {
            return trim(urldecode($m[1]));
        }

        return $raw;
    }

    /** SN "kosong" yang tidak boleh dipakai untuk mencari aset. */
    public const PLACEHOLDER_SN = ['-', '--', 'N/A', 'NA', 'NONE', '0', 'TIDAK ADA'];

    /**
     * Cari aset dari kode hasil scan: No. aset -> No. aset lama -> Serial number (barcode batang SN).
     *
     * @return array{0: ?Asset, 1: string, 2: \Illuminate\Support\Collection} [aset, dicocokkan lewat, kandidat bila SN ganda]
     */
    public static function findAsset(string $code, ?int $preferJobsiteId = null, array $with = []): array
    {
        $code = trim($code);
        // Kode kosong / placeholder (mis. "-" yang dipakai sebagai No. lama atau SN) tidak boleh mencocokkan aset mana pun.
        if ($code === '' || in_array(mb_strtoupper($code), self::PLACEHOLDER_SN, true)) {
            return [null, '', collect()];
        }

        $asset = Asset::with($with)->where('asset_number', $code)->first();
        if ($asset) return [$asset, 'no_aset', collect()];

        $asset = Asset::with($with)->where('old_asset_number', $code)->first();
        if ($asset) return [$asset, 'no_lama', collect()];

        $bySn = Asset::with($with)->where('serial_number', $code)->limit(5)->get();
        if ($bySn->count() === 1) return [$bySn->first(), 'sn', collect()];
        if ($bySn->count() > 1) {
            // SN sama di beberapa aset: utamakan aset di site yang sedang diperiksa.
            $here = $preferJobsiteId ? $bySn->where('jobsite_id', $preferJobsiteId) : collect();
            if ($here->count() === 1) return [$here->first(), 'sn', collect()];
            return [null, 'sn_ganda', $bySn];
        }

        return [null, '', collect()];
    }

    /** Dipanggil dari kamera (JS) atau form input. */
    public function scan($raw = null)
    {
        $opname = $this->opname();
        if (!$opname->isOpen()) {
            $this->last = ['type' => 'danger', 'title' => 'Sesi sudah ditutup', 'text' => 'Buka sesi baru untuk melanjutkan.'];
            return;
        }

        $code = self::extractCode((string) ($raw ?? $this->code));
        $this->code = '';
        if ($code === '' || strlen($code) > 100) {
            return;
        }
        if (in_array(mb_strtoupper($code), self::PLACEHOLDER_SN, true)) {
            $this->last = ['type' => 'info', 'title' => 'Kode tidak valid', 'text' => "\"{$code}\" bukan No. aset / SN. Scan QR label atau barcode SN yang benar."];
            $this->dispatch('opname-feedback', type: 'info');
            return;
        }

        [$asset, $matchedBy, $candidates] = self::findAsset($code, $opname->jobsite_id, ['assetType:id,name', 'assetSeries:id,name', 'jobsite:id,code', 'holder:id,name']);

        // SN yang sama dipakai beberapa aset: jangan menebak, minta scan QR / No. aset.
        if ($matchedBy === 'sn_ganda') {
            $this->last = ['type' => 'warning', 'title' => "SN {$code} dipakai beberapa aset", 'text' => 'Cocok dengan: ' . $candidates->pluck('asset_number')->implode(', ') . '. Scan QR label atau ketik No. aset.'];
            $this->dispatch('opname-feedback', type: 'warning');
            return;
        }

        // Sudah discan di sesi ini?
        $dup = StockOpnameItem::where('stock_opname_id', $opname->id)
            ->when($asset, fn ($q) => $q->where('asset_id', $asset->id), fn ($q) => $q->whereNull('asset_id')->where('scanned_code', $code))
            ->first();
        if ($dup) {
            // Tampilkan lagi agar kondisi / lokasinya masih bisa dikoreksi.
            $this->lastItemId = $dup->asset_id ? $dup->id : null;
            $this->lastLocation = '';
            $this->last = ['type' => 'info', 'title' => 'Sudah discan', 'text' => ($asset->asset_number ?? $code) . ' sudah tercatat ' . $dup->scanned_at?->locale('id')->diffForHumans() . '.'];
            $this->dispatch('opname-feedback', type: 'info');
            return;
        }

        $result = !$asset ? 'not_registered' : ((int) $asset->jobsite_id === (int) $opname->jobsite_id ? 'found' : 'other_site');

        StockOpnameItem::create([
            'stock_opname_id' => $opname->id,
            'asset_id' => $asset?->id,
            'scanned_code' => $code,
            'result' => $result,
            // Kondisi default Baik; bisa diubah di kartu hasil setelah aset terlihat.
            'condition' => $asset ? 'baik' : null,
            'location_note' => null,
            'asset_status_at_scan' => $asset?->status,
            'asset_jobsite_id_at_scan' => $asset?->jobsite_id,
            'scanned_by' => Auth::id(),
            'scanned_at' => now(),
        ]);

        $this->lastItemId = StockOpnameItem::where('stock_opname_id', $opname->id)->latest('id')->value('id');
        $this->lastLocation = '';

        $desc = $asset ? trim(($asset->assetType->name ?? '') . ' ' . ($asset->assetSeries->name ?? '')) : '';
        $this->last = match ($result) {
            'found' => ['type' => 'success', 'title' => "Ditemukan: {$asset->asset_number}" . ($matchedBy === 'sn' ? ' (lewat SN)' : ''), 'text' => $desc . ($asset->holder ? ' · ' . $asset->holder->name : '') . " · status {$asset->status}"],
            'other_site' => ['type' => 'warning', 'title' => "Aset site lain: {$asset->asset_number}" . ($matchedBy === 'sn' ? ' (lewat SN)' : ''), 'text' => "{$desc} tercatat di site " . ($asset->jobsite->code ?? '-') . ', bukan ' . ($opname->jobsite->code ?? '-') . '. Perlu mutasi / koreksi data.'],
            default => ['type' => 'danger', 'title' => 'Tidak terdaftar', 'text' => "Kode \"{$code}\" tidak cocok dengan No. aset, No. aset lama, maupun SN mana pun."],
        };
        $this->dispatch('opname-feedback', type: $this->last['type']);
    }

    /** Ubah kondisi aset yang baru discan (baik / rusak). */
    public function setCondition($itemId, $condition)
    {
        $item = $this->editableItem($itemId);
        if (!$item || !in_array($condition, ['baik', 'rusak'], true)) return;
        $item->update(['condition' => $condition]);
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

        $item->update(['location_note' => $loc]);
        if ($item->asset && $item->result === 'found') {
            $item->asset->update(['current_location_detail' => $loc]);
        }
        $this->lastLocation = '';
        $this->dispatch('opname-location-saved', kept: false);
    }

    /** Item di sesi ini yang masih boleh diubah (sesi terbuka & aset terdaftar). */
    protected function editableItem($itemId): ?StockOpnameItem
    {
        $opname = $this->opname();
        if (!$opname->isOpen()) return null;

        return StockOpnameItem::with('asset')->where('stock_opname_id', $opname->id)->whereNotNull('asset_id')->find($itemId);
    }

    public function undo($itemId)
    {
        $opname = $this->opname();
        if (!$opname->isOpen()) return;
        StockOpnameItem::where('stock_opname_id', $opname->id)->whereKey($itemId)->delete();
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
