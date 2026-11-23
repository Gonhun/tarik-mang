<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\AssetResource;
use App\Models\Asset;
use App\Services\StockOpnameService;
use App\Support\AssetHolding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AssetController extends Controller
{
    protected const WITH = ['assetType:id,name', 'assetBrand:id,name', 'assetSeries:id,name', 'jobsite:id,code,name', 'holder:id,name,nik'];

    /**
     * GET /api/v1/assets/mine?status=pending|used|borrowed&q=
     * Aset yang sedang dipegang user (definisi sama dengan halaman "Aset saya").
     */
    public function mine(Request $request)
    {
        $request->validate(['status' => 'nullable|in:pending,used,borrowed', 'q' => 'nullable|string|max:100']);
        $base = AssetHolding::query()->where('holder_user_id', $request->user()->id);

        $counts = (clone $base)->selectRaw("count(*) as total,
                sum(case when status = 'Pre-Used' then 1 else 0 end) as pending,
                sum(case when status = 'Used' then 1 else 0 end) as used,
                sum(case when status = 'Borrowed' then 1 else 0 end) as borrowed")->first();

        $status = ['pending' => 'Pre-Used', 'used' => 'Used', 'borrowed' => 'Borrowed'][$request->input('status')] ?? null;
        $term = trim((string) $request->input('q'));
        $assets = (clone $base)->with(self::WITH)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w->where('asset_number', 'like', "%{$term}%")
                ->orWhere('serial_number', 'like', "%{$term}%")
                ->orWhereHas('assetType', fn ($s) => $s->where('name', 'like', "%{$term}%"))
                ->orWhereHas('assetSeries', fn ($s) => $s->where('name', 'like', "%{$term}%"))))
            ->orderBy('asset_number')
            ->paginate(20);

        return AssetResource::collection($assets)->additional(['counts' => [
            'total' => (int) $counts->total, 'pending' => (int) $counts->pending,
            'used' => (int) $counts->used, 'borrowed' => (int) $counts->borrowed,
        ]]);
    }

    /**
     * GET /api/v1/assets/lookup?code=
     * Cari aset dari hasil scan QR label (No. aset / link label), No. aset lama, atau barcode SN.
     */
    public function lookup(Request $request)
    {
        $data = $request->validate(['code' => 'required|string|max:255']);
        $code = StockOpnameService::extractCode($data['code']);
        [$asset, $matchedBy, $candidates] = StockOpnameService::findAsset($code, null, self::WITH);

        if ($matchedBy === 'sn_ganda') {
            return response()->json([
                'message' => "SN {$code} dipakai beberapa aset. Scan QR label atau ketik No. aset.",
                'candidates' => $candidates->pluck('asset_number')->values(),
            ], 409);
        }
        if (!$asset) {
            return response()->json(['message' => "Aset dengan kode \"{$code}\" tidak ditemukan."], 404);
        }
        abort_unless(Gate::allows('view-asset', $asset), 403, 'Anda tidak punya akses ke aset ini.');

        return (new AssetResource($asset))->additional(['matched_by' => $matchedBy]);
    }

    /** GET /api/v1/assets/{asset} — detail aset (akses sama dengan halaman detail aset di web). */
    public function show(Asset $asset)
    {
        abort_unless(Gate::allows('view-asset', $asset), 403, 'Anda tidak punya akses ke aset ini.');

        return new AssetResource($asset->load(array_merge(self::WITH, ['entity:id,code,name', 'departement:id,code,name'])));
    }
}
