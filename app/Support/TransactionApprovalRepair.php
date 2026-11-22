<?php

namespace App\Support;

use App\Models\AssetTransaction;
use App\Models\AssetTransactionApproval;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Perbaikan approval untuk transaksi aset yang SUDAH berjalan (mutasi, disposal, kehilangan):
 *  - applyWorkflow(): buat ulang tahapan dari workflow saat ini (bila belum ada tahap yang disetujui/ditolak).
 *  - reassign(): ganti approver satu tahap yang masih Pending.
 * Semua perubahan tercatat di audit log (model approval & transaksi auditable).
 */
class TransactionApprovalRepair
{
    /** Status transaksi yang tidak lagi menunggu approval. */
    public const CLOSED_STATUSES = ['Completed', 'Rejected', 'Cancelled', 'Pending Release', 'Delivery', 'Pending Inspection', 'Disposed'];

    public static function canManage(?User $user): bool
    {
        return $user && ($user->hasRole(['Administrator IT', 'Administrator']) || $user->can('Add Workflow'));
    }

    /** Jenis workflow untuk transaksi ini. */
    public static function workflowType(AssetTransaction $trx): string
    {
        if ($trx->transaction_type === 'DISPOSED' && str_starts_with((string) $trx->notes, '[Kehilangan]')) {
            return 'LOST';
        }

        return $trx->transaction_type;
    }

    /** Query transaksi yang tertahan: masih berjalan tapi tanpa tahapan, atau ada tahap Pending tanpa approver. */
    public static function stuckQuery(): Builder
    {
        return AssetTransaction::query()
            ->whereIn('transaction_type', ['DISPOSED', 'MUTATION'])
            ->whereNotIn('status', self::CLOSED_STATUSES)
            ->where(fn ($q) => $q->doesntHave('approvals')
                ->orWhereHas('approvals', fn ($a) => $a->where('status', 'Pending')->whereNull('approver_user_id')));
    }

    /**
     * Apakah workflow boleh diterapkan ulang: transaksi masih berjalan dan belum ada tahap yang diputuskan.
     * (Bila sudah ada yang disetujui, ganti approver per tahap saja agar riwayat keputusan tidak hilang.)
     */
    public static function canReapply(AssetTransaction $trx): bool
    {
        return in_array($trx->transaction_type, ['DISPOSED', 'MUTATION'], true)
            && !in_array($trx->status, self::CLOSED_STATUSES, true)
            && !$trx->approvals()->whereIn('status', ['Approved', 'Rejected'])->exists();
    }

    /**
     * Bila semua tahap sudah disetujui (mis. semua tahap otomatis karena approver = pengaju),
     * lanjutkan transaksi: mutasi -> "Pending Release", disposal -> "Completed" + aset Disposed.
     */
    public static function finishIfFullyApproved(AssetTransaction $trx): bool
    {
        $approvals = $trx->approvals()->get(['id', 'status']);
        if ($approvals->isEmpty() || $approvals->contains(fn ($a) => $a->status !== 'Approved')) {
            return false;
        }

        if ($trx->transaction_type === 'MUTATION') {
            $trx->update(['status' => 'Pending Release']);
        } elseif ($trx->transaction_type === 'DISPOSED') {
            $trx->update(['status' => 'Completed']);
            AssetDisposal::completeApproval($trx->load('items.asset'));
        }

        return true;
    }

    /** Rencana approver dari workflow saat ini (tanpa menyimpan). */
    public static function plan(AssetTransaction $trx): array
    {
        $trx->loadMissing(['creator', 'items.asset']);
        $type = self::workflowType($trx);
        $initiator = $trx->creator;
        $approverSite = $trx->from_jobsite_id ?? $trx->items->first()?->asset?->jobsite_id ?? $initiator?->jobsite_id;

        return Workflow::plan(
            $type,
            $initiator?->position_id,
            $initiator?->jobsite_id ?? $approverSite,
            $approverSite,
            $type === 'LOST' ? ['DISPOSED'] : []
        );
    }

    /**
     * Terapkan workflow saat ini: hapus tahap Pending lama, buat tahap baru, status kembali "Pending Approval".
     *
     * @throws WorkflowNotConfigured|RuntimeException
     */
    public static function applyWorkflow(AssetTransaction $trx, User $actor): array
    {
        if (!self::canManage($actor)) {
            throw new RuntimeException('Anda tidak berhak memperbaiki approval.');
        }

        return DB::transaction(function () use ($trx, $actor) {
            $trx = AssetTransaction::lockForUpdate()->findOrFail($trx->id);
            if (!self::canReapply($trx)) {
                throw new RuntimeException("Workflow tidak bisa diterapkan ulang untuk {$trx->transaction_number} (sudah ada keputusan approval atau transaksi tidak lagi menunggu). Gunakan Ganti approver per tahap.");
            }

            $plan = self::plan($trx);

            $trx->approvals()->where('status', 'Pending')->get()->each->delete();
            // Tahap yang approvernya = pembuat transaksi langsung disetujui otomatis.
            $trx->approvals()->createMany(Workflow::approvalRows($plan, $trx->created_by_user_id));
            $trx->update(['status' => 'Pending Approval']);
            self::finishIfFullyApproved($trx);

            Log::info("Workflow diterapkan ulang untuk {$trx->transaction_number} oleh user #{$actor->id}", [
                'approvers' => array_map(fn ($p) => $p['step_order'] . ':' . $p['user']->id, $plan),
            ]);

            $first = Workflow::firstPendingUser($plan, $trx->created_by_user_id);
            DB::afterCommit(fn () => self::notify($first, $trx));

            return $plan;
        });
    }

    /**
     * Ganti approver satu tahap yang masih Pending.
     *
     * @throws RuntimeException
     */
    public static function reassign(AssetTransactionApproval $approval, User $newApprover, User $actor): void
    {
        if (!self::canManage($actor)) {
            throw new RuntimeException('Anda tidak berhak mengganti approver.');
        }

        DB::transaction(function () use ($approval, $newApprover, $actor) {
            $approval = AssetTransactionApproval::with('transaction')->lockForUpdate()->findOrFail($approval->id);
            if ($approval->status !== 'Pending') {
                throw new RuntimeException('Hanya tahap yang masih menunggu yang bisa diganti approvernya.');
            }
            if (in_array($approval->transaction->status, self::CLOSED_STATUSES, true)) {
                throw new RuntimeException('Transaksi ini sudah tidak menunggu approval.');
            }

            $old = $approval->approver_user_id;
            $approval->update(['approver_user_id' => $newApprover->id]);
            Log::info("Approver tahap {$approval->step_order} {$approval->transaction->transaction_number} diganti #{$old} -> #{$newApprover->id} oleh #{$actor->id}");

            // Status lama yang tidak dikenali (mis. "Kepala Departemen") dikembalikan ke status standar.
            if ($approval->transaction->status !== 'Pending Approval') {
                $approval->transaction->update(['status' => 'Pending Approval']);
            }

            // Beri tahu approver baru bila tahap ini yang sedang giliran.
            $isTurn = !AssetTransactionApproval::where('asset_transaction_id', $approval->asset_transaction_id)
                ->where('step_order', '<', $approval->step_order)->where('status', 'Pending')->exists();
            if ($isTurn) {
                $trx = $approval->transaction;
                DB::afterCommit(fn () => self::notify($newApprover, $trx));
            }
        });
    }

    protected static function notify(?User $user, AssetTransaction $trx): void
    {
        if (!$user || empty($user->whatsapp)) {
            return;
        }
        try {
            sendMessageWithWhatsApp($user->whatsapp, "Halo {$user->name},\n\nAda {$trx->transaction_number} yang menunggu persetujuan Anda.\nBuka: " . route('asset.mutasi-status', $trx->id));
        } catch (\Throwable $e) {
            Log::warning("Notifikasi approver {$trx->transaction_number} gagal: " . $e->getMessage());
        }
    }
}
