<?php

namespace App\Http\Controllers\pdf;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetTransaction;
use App\Models\CostCenter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use OwenIt\Auditing\Models\Audit;

/**
 * Cetak (ulang) berita acara untuk transaksi mana pun, termasuk yang lama.
 * Route lama (asset.bast, asset.pengembalian) hanya bisa mencetak transaksi terakhir aset.
 */
class AssetBaPrint extends Controller
{
    public const PRINTABLE_TYPES = ['HANDOVER', 'RETURN', 'MUTATION'];

    public function view(AssetTransaction $transaction, Asset $asset)
    {
        $user = Auth::user();
        abort_unless($user->can('Master Asset') || $user->can('view-asset-transaction', $transaction), 403);

        // Aset harus benar-benar bagian dari transaksi ini.
        abort_unless($transaction->items()->where('asset_id', $asset->id)->exists(), 404);
        abort_unless(in_array($transaction->transaction_type, self::PRINTABLE_TYPES, true), 404, 'Jenis transaksi ini tidak punya berita acara.');

        $transaction->load([
            'items.asset.assetType', 'items.asset.assetBrand', 'items.asset.assetSeries',
            'fromUser.department', 'toUser.department', 'fromJobsite', 'toJobsite',
            'approvals.approver', 'approvals.actionBy', 'creator',
        ]);

        $safeNumber = str_replace(['/', '\\'], '-', $transaction->transaction_number);

        if ($transaction->transaction_type === 'MUTATION') {
            $departmentId = $transaction->creator?->department_id;
            $destinationCostCenter = $departmentId
                ? CostCenter::where('jobsite_id', $transaction->to_jobsite_id)->where('department_id', $departmentId)->first()
                : null;
            $fromCostCenter = $departmentId
                ? CostCenter::where('jobsite_id', $transaction->from_jobsite_id)->where('department_id', $departmentId)->first()
                : null;

            return Pdf::loadView('pdf.mutasi_asset', [
                'bast' => $transaction,
                'destinationCostCenter' => $destinationCostCenter,
                'fromCostCenter' => $fromCostCenter,
            ])->setPaper('A4', 'portrait')->stream("MUTASI-{$safeNumber}.pdf");
        }

        // Template BAST & pengembalian membaca $bast (aset) + relasi transaksinya.
        // Pakai salinan aset agar relasi bisa diarahkan ke transaksi yang dicetak (bukan transaksi terakhir).
        $bast = $asset->replicate();
        $bast->id = $asset->id;

        if ($transaction->transaction_type === 'RETURN') {
            $bast->setRelation('returnTransaction', $transaction);

            return Pdf::loadView('pdf.pengembalian_asset', compact('bast'))
                ->setPaper('A4', 'portrait')
                ->stream("PENGEMBALIAN-{$safeNumber}.pdf");
        }

        // HANDOVER: stempel "diterima" di template bergantung pada status aset.
        $bast->setRelation('transaction', $transaction);
        $bast->status = $this->handoverReceiptStatus($asset, $transaction);

        return Pdf::loadView('pdf.bast_asset', compact('bast'))
            ->setPaper('A4', 'portrait')
            ->stream("BAST-{$safeNumber}.pdf");
    }

    /**
     * Status penerimaan untuk BAST ini: 'Used' jika penerima sudah konfirmasi, 'Pre-Used' jika belum.
     * Untuk BAST lama, dicek dari audit log dan transaksi sesudahnya.
     */
    protected function handoverReceiptStatus(Asset $asset, AssetTransaction $transaction): string
    {
        if ((int) $asset->asset_transaction_id === (int) $transaction->id) {
            return $asset->status === 'Pre-Used' ? 'Pre-Used' : 'Used';
        }

        $confirmedInAudit = Audit::query()
            ->where('auditable_type', Asset::class)
            ->where('auditable_id', $asset->id)
            ->where('created_at', '>=', $transaction->created_at)
            ->where('new_values', 'like', '%"status":"Used"%')
            ->exists();

        $hasLaterTransaction = $asset->transactions()
            ->where('asset_transactions.id', '>', $transaction->id)
            ->exists();

        return $confirmedInAudit || $hasLaterTransaction ? 'Used' : 'Pre-Used';
    }
}
