<?php

namespace App\Support;

use App\Models\ApprovalWorkflowStep;
use App\Models\Asset;
use App\Models\AssetTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Pengajuan penghapusan aset (transaction_type DISPOSED) dengan tahapan approval dari workflow.
 *
 * Alasan:
 *  - DAMAGED : rusak berat (dari inspeksi / laporan kerusakan)  -> aset "Pre Disposed"
 *  - LOST    : kehilangan                                       -> aset "Missing"
 *  - OTHER   : alasan lain (usang, dll.)                        -> aset "Pre Disposed"
 * Disetujui penuh -> aset "Disposed". Ditolak -> status & pemegang aset dipulihkan (lihat restoreAfterRejection).
 *
 * Tabel asset_transactions hanya mengizinkan jenis transaksi tertentu (CHECK constraint),
 * jadi kehilangan dicatat sebagai DISPOSED dengan alasan LOST di metadata item.
 */
class AssetDisposal
{
    public const REASONS = [
        'DAMAGED' => 'Rusak berat',
        'LOST' => 'Kehilangan',
        'OTHER' => 'Lainnya',
    ];

    public const OPEN_STATUSES = ['Pending Approval', 'Pending KADEP IT', 'Pending Cost Control', 'Kepala Departemen', 'Draft'];

    /**
     * @throws RuntimeException jika workflow / approver belum dikonfigurasi atau sudah ada pengajuan berjalan.
     */
    /**
     * @param array $restoreTo Opsional: status/pemegang bila ditolak, mis. ['previous_status' => 'Repair', 'previous_holder_user_id' => null].
     */
    public static function create(Asset $asset, User $initiator, string $notes, string $reason = 'DAMAGED', array $restoreTo = []): AssetTransaction
    {
        $reason = array_key_exists($reason, self::REASONS) ? $reason : 'OTHER';

        $existing = self::openDisposalFor($asset);
        if ($existing) {
            // Inspeksi/laporan kerusakan boleh memakai pengajuan yang sama; pengajuan manual tidak boleh dobel.
            if ($reason === 'DAMAGED') {
                return $existing;
            }
            throw new RuntimeException("Aset ini sudah punya pengajuan disposal yang berjalan ({$existing->transaction_number}).");
        }

        // Kehilangan bisa punya workflow sendiri (tipe LOST); kalau belum diatur, pakai workflow DISPOSED.
        // Workflow / approver yang belum lengkap -> WorkflowNotConfigured, tidak ada data yang tersimpan.
        $type = $reason === 'LOST' ? 'LOST' : 'DISPOSED';
        $plan = Workflow::plan(
            $type,
            $initiator->position_id,
            $initiator->jobsite_id,
            $asset->jobsite_id ?? $initiator->jobsite_id,
            $type === 'LOST' ? ['DISPOSED'] : []
        );
        // Tahap yang approvernya = pengaju langsung disetujui otomatis.
        $approvals = Workflow::approvalRows($plan, $initiator->id);

        $label = self::REASONS[$reason];
        $transaction = AssetTransaction::create([
            'transaction_number' => generateMutationNumber('DPS'),
            'transaction_type' => 'DISPOSED',
            'transaction_date' => now(),
            'from_user_id' => $asset->holder_user_id,
            'from_jobsite_id' => $asset->jobsite_id,
            'notes' => "[{$label}] {$notes}",
            'status' => 'Pending Approval',
            'created_by_user_id' => $initiator->id,
        ]);

        $transaction->items()->create([
            'asset_id' => $asset->id,
            'condition_notes' => "{$label}: {$notes}",
            // Disimpan agar status & pemegang bisa dipulihkan bila pengajuan ditolak.
            'metadata' => array_merge([
                'reason' => $reason,
                'previous_status' => $asset->status,
                'previous_holder_user_id' => $asset->holder_user_id,
            ], array_intersect_key($restoreTo, array_flip(['previous_status', 'previous_holder_user_id']))),
        ]);

        $transaction->approvals()->createMany($approvals);

        // Kirim setelah commit, supaya tidak ada notifikasi untuk pengajuan yang batal tersimpan.
        $firstApproverId = Workflow::firstPendingUser($plan, $initiator->id)?->id;
        DB::afterCommit(fn () => self::notifyApprover($transaction, $firstApproverId));

        return $transaction;
    }

    /**
     * Panggil SETELAH status aset di-set oleh pemanggil: bila semua tahap sudah otomatis disetujui,
     * disposal langsung selesai (aset Disposed). Mengembalikan true bila selesai.
     */
    public static function finishIfAutoApproved(AssetTransaction $transaction): bool
    {
        return TransactionApprovalRepair::finishIfFullyApproved($transaction);
    }

    public static function openDisposalFor(Asset $asset): ?AssetTransaction
    {
        return AssetTransaction::where('transaction_type', 'DISPOSED')
            ->whereNotIn('status', ['Completed', 'Rejected', 'Cancelled', 'Disposed'])
            ->whereHas('items', fn ($q) => $q->where('asset_id', $asset->id))
            ->first();
    }

    /**
     * Status aset selama menunggu approval, sesuai alasan.
     */
    public static function pendingStatusFor(string $reason): string
    {
        return $reason === 'LOST' ? 'Missing' : 'Pre Disposed';
    }

    /**
     * Pulihkan aset bila pengajuan disposal ditolak.
     */
    public static function restoreAfterRejection(AssetTransaction $transaction): void
    {
        foreach ($transaction->items as $item) {
            $asset = $item->asset;
            if (!$asset || !in_array($asset->status, ['Pre Disposed', 'Pre-Disposed', 'Missing'], true)) {
                continue;
            }
            $meta = is_array($item->metadata) ? $item->metadata : [];
            $asset->update([
                'status' => $meta['previous_status'] ?? 'Free',
                'holder_user_id' => array_key_exists('previous_holder_user_id', $meta) ? $meta['previous_holder_user_id'] : $asset->holder_user_id,
            ]);
        }
    }

    /**
     * Disetujui penuh: aset dihapusbukukan.
     */
    public static function completeApproval(AssetTransaction $transaction): void
    {
        foreach ($transaction->items as $item) {
            $asset = $item->asset;
            if ($asset && in_array($asset->status, ['Pre Disposed', 'Pre-Disposed', 'Missing'], true)) {
                $asset->update(['status' => 'Disposed', 'holder_user_id' => null]);
            }
        }
    }

    /**
     * Alias lama; logika ada di Workflow::findApprover.
     */
    public static function findApprover(ApprovalWorkflowStep $step, ?int $jobsiteId): ?User
    {
        return Workflow::findApprover($step, $jobsiteId);
    }

    /**
     * WhatsApp ke approver. Kegagalan kirim tidak membatalkan pengajuan.
     */
    public static function notifyApprover(AssetTransaction $transaction, ?int $userId): void
    {
        $approver = $userId ? User::find($userId) : null;
        if (!$approver || empty($approver->whatsapp)) {
            return;
        }

        try {
            sendMessageWithWhatsApp($approver->whatsapp,
                "Halo {$approver->name},\n\n"
                . "Ada pengajuan {$transaction->transaction_number} yang menunggu persetujuan Anda.\n"
                . "Catatan: {$transaction->notes}\n\n"
                . "Buka: " . route('asset.mutasi-status', $transaction->id));
        } catch (\Throwable $e) {
            Log::warning("Notifikasi approver {$transaction->transaction_number} gagal: " . $e->getMessage());
        }
    }
}
