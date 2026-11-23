<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Logika scan stock opname, dipakai halaman web (Livewire) dan API mobile.
 */
class StockOpnameService
{
    /** SN "kosong" yang tidak boleh dipakai untuk mencari aset. */
    public const PLACEHOLDER_SN = ['-', '--', 'N/A', 'NA', 'NONE', '0', 'TIDAK ADA'];

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

    /**
     * Cari aset dari kode hasil scan: No. aset -> No. aset lama -> Serial number (barcode batang SN).
     *
     * @return array{0: ?Asset, 1: string, 2: Collection} [aset, dicocokkan lewat, kandidat bila SN ganda]
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

    /**
     * Catat satu scan pada sesi opname.
     *
     * @return array{type: string, title: string, text: string, result: ?string, item: ?StockOpnameItem, asset: ?Asset, duplicate: bool}
     *   type: success | warning | danger | info (untuk warna/bunyi umpan balik).
     *   result: found | other_site | not_registered (null bila scan tidak dicatat).
     */
    public function scan(StockOpname $opname, string $raw, User $user): array
    {
        $none = ['result' => null, 'item' => null, 'asset' => null, 'duplicate' => false];

        if (!$opname->isOpen()) {
            return ['type' => 'danger', 'title' => 'Sesi sudah ditutup', 'text' => 'Buka sesi baru untuk melanjutkan.'] + $none;
        }

        $code = self::extractCode($raw);
        if ($code === '' || strlen($code) > 100) {
            return ['type' => 'info', 'title' => 'Kode kosong', 'text' => 'Scan QR label, barcode SN, atau ketik No. aset.'] + $none;
        }
        if (in_array(mb_strtoupper($code), self::PLACEHOLDER_SN, true)) {
            return ['type' => 'info', 'title' => 'Kode tidak valid', 'text' => "\"{$code}\" bukan No. aset / SN. Scan QR label atau barcode SN yang benar."] + $none;
        }

        $opname->loadMissing('jobsite:id,code,name');
        [$asset, $matchedBy, $candidates] = self::findAsset($code, $opname->jobsite_id, ['assetType:id,name', 'assetSeries:id,name', 'jobsite:id,code', 'holder:id,name']);

        // SN yang sama dipakai beberapa aset: jangan menebak, minta scan QR / No. aset.
        if ($matchedBy === 'sn_ganda') {
            return ['type' => 'warning', 'title' => "SN {$code} dipakai beberapa aset", 'text' => 'Cocok dengan: ' . $candidates->pluck('asset_number')->implode(', ') . '. Scan QR label atau ketik No. aset.'] + $none;
        }

        // Sudah discan di sesi ini?
        $dup = StockOpnameItem::where('stock_opname_id', $opname->id)
            ->when($asset, fn ($q) => $q->where('asset_id', $asset->id), fn ($q) => $q->whereNull('asset_id')->where('scanned_code', $code))
            ->first();
        if ($dup) {
            return [
                'type' => 'info',
                'title' => 'Sudah discan',
                'text' => ($asset->asset_number ?? $code) . ' sudah tercatat ' . $dup->scanned_at?->locale('id')->diffForHumans() . '.',
                'result' => $dup->result,
                'item' => $dup,
                'asset' => $asset,
                'duplicate' => true,
            ];
        }

        $result = !$asset ? 'not_registered' : ((int) $asset->jobsite_id === (int) $opname->jobsite_id ? 'found' : 'other_site');

        $item = StockOpnameItem::create([
            'stock_opname_id' => $opname->id,
            'asset_id' => $asset?->id,
            'scanned_code' => $code,
            'result' => $result,
            // Kondisi default Baik; bisa diubah setelah aset terlihat.
            'condition' => $asset ? 'baik' : null,
            'location_note' => null,
            'asset_status_at_scan' => $asset?->status,
            'asset_jobsite_id_at_scan' => $asset?->jobsite_id,
            'scanned_by' => $user->id,
            'scanned_at' => now(),
        ]);

        $desc = $asset ? trim(($asset->assetType->name ?? '') . ' ' . ($asset->assetSeries->name ?? '')) : '';
        $viaSn = $matchedBy === 'sn' ? ' (lewat SN)' : '';
        $feedback = match ($result) {
            'found' => ['type' => 'success', 'title' => "Ditemukan: {$asset->asset_number}{$viaSn}", 'text' => $desc . ($asset->holder ? ' · ' . $asset->holder->name : '') . " · status {$asset->status}"],
            'other_site' => ['type' => 'warning', 'title' => "Aset site lain: {$asset->asset_number}{$viaSn}", 'text' => "{$desc} tercatat di site " . ($asset->jobsite->code ?? '-') . ', bukan ' . ($opname->jobsite->code ?? '-') . '. Perlu mutasi / koreksi data.'],
            default => ['type' => 'danger', 'title' => 'Tidak terdaftar', 'text' => "Kode \"{$code}\" tidak cocok dengan No. aset, No. aset lama, maupun SN mana pun."],
        };

        return $feedback + ['result' => $result, 'item' => $item, 'asset' => $asset, 'duplicate' => false];
    }

    /** Item di sesi ini yang masih boleh diubah (sesi terbuka & aset terdaftar). */
    public function editableItem(StockOpname $opname, $itemId): ?StockOpnameItem
    {
        if (!$opname->isOpen()) return null;

        return StockOpnameItem::with('asset')->where('stock_opname_id', $opname->id)->whereNotNull('asset_id')->find($itemId);
    }

    /** Ubah kondisi aset yang discan (baik / rusak). */
    public function setCondition(StockOpnameItem $item, string $condition): bool
    {
        if (!in_array($condition, ['baik', 'rusak'], true)) return false;
        $item->update(['condition' => $condition]);

        return true;
    }

    /**
     * Simpan lokasi aset yang discan. Kosong = lokasi aset tetap seperti semula.
     * Diisi = catat di hasil opname dan perbarui lokasi aset (tercatat di log aset).
     */
    public function saveLocation(StockOpnameItem $item, ?string $location): void
    {
        $loc = trim((string) $location);
        if ($loc === '') return;

        $item->update(['location_note' => $loc]);
        if ($item->asset && $item->result === 'found') {
            $item->asset->update(['current_location_detail' => $loc]);
        }
    }

    /** Batalkan satu scan di sesi yang masih terbuka. */
    public function undo(StockOpname $opname, $itemId): bool
    {
        if (!$opname->isOpen()) return false;

        return (bool) StockOpnameItem::where('stock_opname_id', $opname->id)->whereKey($itemId)->delete();
    }
}
