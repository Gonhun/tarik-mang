<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\AssetResource;
use App\Http\Resources\Api\StockOpnameItemResource;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Services\StockOpnameService;
use Illuminate\Http\Request;

/**
 * Stock opname dari HP (izin "Stock Opname", sama dengan menu web). Membuka/menutup sesi tetap di web.
 */
class StockOpnameController extends Controller
{
    public function __construct(protected StockOpnameService $service) {}

    /** GET /api/v1/stock-opnames?status=open|closed — sesi terbuka tampil paling atas. */
    public function index(Request $request)
    {
        $request->validate(['status' => 'nullable|in:open,closed']);

        $sessions = StockOpname::with(['jobsite:id,code,name', 'starter:id,name'])
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->withCount([
                'items as found_count' => fn ($q) => $q->where('result', 'found'),
                'items as other_site_count' => fn ($q) => $q->where('result', 'other_site'),
                'items as not_registered_count' => fn ($q) => $q->where('result', 'not_registered'),
            ])
            ->orderByRaw("case when status = 'open' then 0 else 1 end")
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json([
            'data' => $sessions->getCollection()->map(fn (StockOpname $o) => $this->summary($o)),
            'meta' => ['current_page' => $sessions->currentPage(), 'last_page' => $sessions->lastPage(), 'total' => $sessions->total()],
        ]);
    }

    /** GET /api/v1/stock-opnames/{opname} — ringkasan & hitungan per hasil. */
    public function show(StockOpname $opname)
    {
        $opname->load(['jobsite:id,code,name', 'starter:id,name', 'closer:id,name']);
        $counts = StockOpnameItem::where('stock_opname_id', $opname->id)->selectRaw('result, count(*) as total')->groupBy('result')->pluck('total', 'result');
        $expected = $opname->isOpen() ? $opname->expectedAssetsQuery()->count() : (int) $opname->expected_count;

        return response()->json(['data' => $this->summary($opname) + [
            'counts' => [
                'expected' => $expected,
                'found' => (int) ($counts['found'] ?? 0),
                'missing' => max(0, $expected - (int) ($counts['found'] ?? 0)),
                'other_site' => (int) ($counts['other_site'] ?? 0),
                'not_registered' => (int) ($counts['not_registered'] ?? 0),
            ],
            'closed_by' => $opname->closer?->only(['id', 'name']),
            'closed_at' => $opname->closed_at?->toIso8601String(),
        ]]);
    }

    /**
     * GET /api/v1/stock-opnames/{opname}/items?tab=found|missing|other_site|not_registered&q=
     * "missing" = aset yang seharusnya ada di site tapi belum discan.
     */
    public function items(Request $request, StockOpname $opname)
    {
        $data = $request->validate(['tab' => 'nullable|in:found,missing,other_site,not_registered', 'q' => 'nullable|string|max:100']);
        $tab = $data['tab'] ?? 'missing';
        $term = trim((string) ($data['q'] ?? '')) !== '' ? '%' . trim($data['q']) . '%' : null;

        if ($tab === 'missing') {
            $scannedIds = StockOpnameItem::where('stock_opname_id', $opname->id)->whereNotNull('asset_id')->pluck('asset_id');
            $assets = $opname->expectedAssetsQuery()
                ->whereNotIn('id', $scannedIds)
                ->when($term, fn ($q) => $q->where(fn ($w) => $w->where('asset_number', 'like', $term)->orWhere('serial_number', 'like', $term)
                    ->orWhereHas('holder', fn ($h) => $h->where('name', 'like', $term))))
                ->with(['assetType:id,name', 'assetSeries:id,name', 'holder:id,name,nik'])
                ->orderBy('asset_number')
                ->paginate(30);

            return AssetResource::collection($assets)->additional(['tab' => $tab]);
        }

        $items = StockOpnameItem::where('stock_opname_id', $opname->id)
            ->where('result', $tab)
            ->when($term, fn ($q) => $q->where(fn ($w) => $w->where('scanned_code', 'like', $term)
                ->orWhereHas('asset', fn ($a) => $a->where('asset_number', 'like', $term)->orWhere('serial_number', 'like', $term))))
            ->with(['asset.assetType:id,name', 'asset.assetSeries:id,name', 'asset.holder:id,name,nik', 'jobsiteAtScan:id,code', 'scanner:id,name'])
            ->orderByDesc('scanned_at')
            ->paginate(30);

        return StockOpnameItemResource::collection($items)->additional(['tab' => $tab]);
    }

    /**
     * POST /api/v1/stock-opnames/{opname}/scan  {code}
     * Isi QR label / barcode SN / No. aset. "feedback.type" (success|warning|danger|info) untuk warna & bunyi di aplikasi.
     */
    public function scan(Request $request, StockOpname $opname)
    {
        $data = $request->validate(['code' => 'required|string|max:255']);
        $res = $this->service->scan($opname, $data['code'], $request->user());

        $item = $res['item']?->load(['asset.assetType:id,name', 'asset.assetSeries:id,name', 'asset.holder:id,name,nik', 'asset.jobsite:id,code,name', 'jobsiteAtScan:id,code', 'scanner:id,name']);

        return response()->json([
            'feedback' => ['type' => $res['type'], 'title' => $res['title'], 'text' => $res['text']],
            'recorded' => (bool) $item && !$res['duplicate'],
            'duplicate' => $res['duplicate'],
            'result' => $res['result'],
            // Kondisi & lokasi hanya bisa diisi untuk aset terdaftar.
            'editable' => (bool) $item?->asset_id && $opname->isOpen(),
            'item' => $item ? (new StockOpnameItemResource($item))->resolve() : null,
        ], $opname->isOpen() ? 200 : 409);
    }

    /** PATCH /api/v1/stock-opnames/{opname}/items/{item}  {condition?: baik|rusak, location?: string} */
    public function updateItem(Request $request, StockOpname $opname, $itemId)
    {
        $data = $request->validate([
            'condition' => 'nullable|in:baik,rusak',
            'location' => 'nullable|string|max:255',
        ]);
        $item = $this->service->editableItem($opname, $itemId);
        abort_unless($item, 404, 'Item tidak ditemukan, bukan aset terdaftar, atau sesi sudah ditutup.');

        if (!empty($data['condition'])) {
            $this->service->setCondition($item, $data['condition']);
        }
        if (array_key_exists('location', $data)) {
            $this->service->saveLocation($item, $data['location']);
        }

        return new StockOpnameItemResource($item->fresh()->load(['asset.assetType:id,name', 'asset.assetSeries:id,name', 'scanner:id,name']));
    }

    /** DELETE /api/v1/stock-opnames/{opname}/items/{item} — batalkan scan (sesi masih terbuka). */
    public function destroyItem(StockOpname $opname, $itemId)
    {
        abort_unless($this->service->undo($opname, $itemId), 404, 'Item tidak ditemukan atau sesi sudah ditutup.');

        return response()->json(['message' => 'Scan dibatalkan.']);
    }

    protected function summary(StockOpname $o): array
    {
        return [
            'id' => $o->id,
            'code' => $o->code,
            'title' => $o->title,
            'status' => $o->status,
            'is_open' => $o->isOpen(),
            'jobsite' => $o->jobsite?->only(['id', 'code', 'name']),
            'started_by' => $o->starter?->only(['id', 'name']),
            'started_at' => $o->started_at?->toIso8601String(),
            'expected_count' => $o->expected_count === null ? null : (int) $o->expected_count,
            'found_count' => isset($o->found_count) ? (int) $o->found_count : null,
            'other_site_count' => isset($o->other_site_count) ? (int) $o->other_site_count : null,
            'not_registered_count' => isset($o->not_registered_count) ? (int) $o->not_registered_count : null,
        ];
    }
}
