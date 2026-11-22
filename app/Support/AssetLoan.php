<?php

namespace App\Support;

use App\Models\Asset;
use App\Models\AssetDamageReport;
use App\Models\AssetTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Peminjaman sementara aset — tanpa tabel baru:
 *  - Pinjam  : transaksi HANDOVER bertanda "[Pinjam]", metadata item berisi tanggal kembali; aset "Borrowed".
 *  - Kembali : transaksi RETURN bertanda "[Kembali pinjam]"; aset "Free" (atau "Repair" + laporan kerusakan bila rusak).
 */
class AssetLoan
{
    public const TAG = '[Pinjam]';
    public const RETURN_TAG = '[Kembali pinjam]';
    public const MAX_DAYS = 90;

    /** Status aset yang boleh dipinjamkan. */
    public const LENDABLE = ['Free'];

    /** Transaksi pinjam yang sedang berjalan untuk aset ini. */
    public static function activeLoan(Asset $asset): ?AssetTransaction
    {
        if ($asset->status !== 'Borrowed' || !$asset->asset_transaction_id) {
            return null;
        }
        $trx = AssetTransaction::with('items')->find($asset->asset_transaction_id);

        return $trx && str_starts_with((string) $trx->notes, self::TAG) ? $trx : null;
    }

    /** Metadata pinjaman (due_date, purpose, ...) dari item transaksi. */
    public static function meta(AssetTransaction $trx, int $assetId): array
    {
        $item = $trx->items->firstWhere('asset_id', $assetId);

        return is_array($item?->metadata) ? $item->metadata : [];
    }

    public static function lend(Asset $asset, User $borrower, string $dueDate, string $purpose, User $actor): AssetTransaction
    {
        return DB::transaction(function () use ($asset, $borrower, $dueDate, $purpose, $actor) {
            $asset = Asset::whereKey($asset->id)->lockForUpdate()->firstOrFail();
            if (!in_array($asset->status, self::LENDABLE, true)) {
                throw new RuntimeException("Aset {$asset->asset_number} berstatus {$asset->status}; hanya aset Free yang bisa dipinjamkan.");
            }

            $trx = AssetTransaction::create([
                'transaction_number' => TransactionNumber::next('PJM'),
                'transaction_type' => 'HANDOVER',
                'transaction_date' => now(),
                'from_user_id' => $actor->id,
                'to_user_id' => $borrower->id,
                'from_jobsite_id' => $asset->jobsite_id,
                'to_jobsite_id' => $asset->jobsite_id,
                'notes' => self::TAG . ' ' . $purpose . ' (kembali ' . Carbon::parse($dueDate)->format('d-m-Y') . ')',
                'status' => 'Completed',
                'created_by_user_id' => $actor->id,
            ]);
            $trx->items()->create([
                'asset_id' => $asset->id,
                'condition_notes' => 'Dipinjamkan: ' . $purpose,
                'metadata' => [
                    'loan' => true,
                    'due_date' => Carbon::parse($dueDate)->toDateString(),
                    'purpose' => $purpose,
                    'previous_status' => $asset->status,
                ],
            ]);

            $asset->update([
                'status' => 'Borrowed',
                'holder_user_id' => $borrower->id,
                'asset_transaction_id' => $trx->id,
            ]);

            return $trx;
        });
    }

    /**
     * Terima pengembalian. $condition: baik | rusak.
     */
    public static function giveBack(Asset $asset, string $condition, ?string $notes, User $actor): AssetTransaction
    {
        return DB::transaction(function () use ($asset, $condition, $notes, $actor) {
            $asset = Asset::whereKey($asset->id)->lockForUpdate()->firstOrFail();
            $loan = self::activeLoan($asset);
            if (!$loan) {
                throw new RuntimeException("Aset {$asset->asset_number} tidak sedang dipinjam.");
            }
            $broken = $condition === 'rusak';

            $trx = AssetTransaction::create([
                'transaction_number' => TransactionNumber::next('RTN'),
                'transaction_type' => 'RETURN',
                'transaction_date' => now(),
                'from_user_id' => $asset->holder_user_id,
                'to_user_id' => $actor->id,
                'from_jobsite_id' => $asset->jobsite_id,
                'to_jobsite_id' => $asset->jobsite_id,
                'notes' => self::RETURN_TAG . " {$loan->transaction_number}. Kondisi: " . ($broken ? 'rusak' : 'baik') . ($notes ? ". {$notes}" : ''),
                'status' => 'Completed',
                'created_by_user_id' => $actor->id,
            ]);
            $trx->items()->create([
                'asset_id' => $asset->id,
                'condition_notes' => 'Kondisi: ' . ($broken ? 'rusak' : 'baik') . ($notes ? ". {$notes}" : ''),
                'metadata' => ['loan_return' => true, 'loan_transaction_id' => $loan->id, 'condition' => $broken ? 'rusak' : 'baik'],
            ]);

            if ($broken) {
                AssetDamageReport::create([
                    'asset_id' => $asset->id,
                    'report_date' => now(),
                    'damage_description' => "Rusak saat dikembalikan dari peminjaman {$loan->transaction_number}" . ($notes ? ": {$notes}" : '.'),
                    'damage_status' => 'Under Repair',
                    'asset_status_before_report' => 'Free',
                    'reported_by_user_id' => $actor->id,
                    'created_by_user_id' => $actor->id,
                ]);
            }

            $asset->update([
                'status' => $broken ? 'Repair' : 'Free',
                'holder_user_id' => null,
                'asset_transaction_return_id' => $trx->id,
            ]);

            return $trx;
        });
    }

    /** Perpanjang tanggal kembali. */
    public static function extend(Asset $asset, string $newDueDate): void
    {
        $loan = self::activeLoan($asset);
        if (!$loan) {
            throw new RuntimeException('Aset tidak sedang dipinjam.');
        }
        $item = $loan->items->firstWhere('asset_id', $asset->id);
        $meta = is_array($item->metadata) ? $item->metadata : [];
        $meta['extended_from'] = $meta['due_date'] ?? null;
        $meta['due_date'] = Carbon::parse($newDueDate)->toDateString();
        $item->update(['metadata' => $meta]);
    }

    /** Aset yang sedang dipinjam, lengkap dengan transaksi pinjamnya. */
    public static function activeAssetsQuery()
    {
        return Asset::where('status', 'Borrowed')
            ->whereHas('transaction', fn ($q) => $q->where('notes', 'like', SqlLike::prefix(self::TAG)));
    }
}
