<?php

namespace App\Services;

use App\Models\AssetTransaction;
use App\Models\AssetTransactionApproval;
use App\Models\User;
use App\Support\AssetDisposal;
use App\Support\TransactionApprovalRepair;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Keputusan approval transaksi aset (mutasi, disposal, kehilangan), dipakai halaman web dan API mobile.
 */
class TransactionApprovalService
{
    /**
     * Boleh memutuskan bila: tahap masih Pending, transaksi belum lewat tahap approval,
     * user adalah approver yang ditunjuk, dan tidak ada tahap sebelumnya yang masih Pending.
     */
    public function canDecide(AssetTransactionApproval $approval, User $user): bool
    {
        $transaction = AssetTransaction::find($approval->asset_transaction_id);

        return $transaction
            && $approval->status === 'Pending'
            // Transaksi yang sudah lewat tahap approval (mis. Pending Release dari pengajuan aset) tidak bisa di-approve lagi.
            && !in_array($transaction->status, TransactionApprovalRepair::CLOSED_STATUSES, true)
            && (int) $user->id === (int) $approval->approver_user_id
            && !AssetTransactionApproval::where('asset_transaction_id', $approval->asset_transaction_id)
                ->where('step_order', '<', $approval->step_order)
                ->where('status', 'Pending')
                ->exists();
    }

    /**
     * Setujui / tolak satu tahap. Pemanggil wajib cek canDecide() dan validasi catatan (wajib saat menolak).
     */
    public function decide(AssetTransactionApproval $approval, User $actor, string $action, ?string $notes): AssetTransaction
    {
        if (!in_array($action, ['approve', 'reject'], true)) {
            throw new \InvalidArgumentException('Aksi harus approve atau reject.');
        }

        return DB::transaction(function () use ($approval, $actor, $action, $notes) {
            $approval->update([
                'status' => $action === 'approve' ? 'Approved' : 'Rejected',
                'action_at' => now(),
                'action_by_user_id' => $actor->id,
                'notes' => $notes,
            ]);

            $transaction = AssetTransaction::with('items.asset')->lockForUpdate()->find($approval->asset_transaction_id);
            $nextApproval = null;

            if ($action === 'reject') {
                $transaction->status = 'Rejected';
            } else {
                $nextApproval = AssetTransactionApproval::where('asset_transaction_id', $transaction->id)
                    ->where('status', 'Pending')->orderBy('step_order', 'asc')->first();
                if ($nextApproval) {
                    $transaction->status = 'Pending Approval';
                } else {
                    $transaction->status = $transaction->transaction_type === 'DISPOSED' ? 'Completed' : 'Pending Release';
                }
            }
            $transaction->save();

            if ($nextApproval) {
                $this->notifyApproverAfterCommit($nextApproval);
            } else {
                $this->processDecision($transaction);
                $this->notifyRequesterAfterCommit($transaction, $action === 'reject' ? 'Pengajuan Anda Ditolak' : 'Pengajuan Anda Telah Disetujui Sepenuhnya');
            }

            return $transaction->refresh();
        });
    }

    /** Efek keputusan akhir ke aset (disetujui penuh atau ditolak). */
    public function processDecision(AssetTransaction $transaction): void
    {
        $transaction->loadMissing('items.asset');

        // Disposal / kehilangan: pulihkan aset bila ditolak, hapus buku bila disetujui penuh.
        if ($transaction->transaction_type === 'DISPOSED') {
            if ($transaction->status === 'Rejected') {
                AssetDisposal::restoreAfterRejection($transaction);
            } elseif (in_array($transaction->status, ['Approved', 'Completed'], true)) {
                AssetDisposal::completeApproval($transaction);
            }
            return;
        }

        // Mutasi: aset baru pindah site saat site tujuan konfirmasi terima (halaman Mutasi).
        // Disetujui -> tetap "Pending Mutation" menunggu pelepasan. Ditolak -> pulihkan status & pemegang semula.
        if ($transaction->transaction_type === 'MUTATION') {
            if ($transaction->status === 'Rejected') {
                foreach ($transaction->items as $item) {
                    if ($item->asset && $item->asset->status === 'Pending Mutation') {
                        $meta = is_array($item->metadata) ? $item->metadata : [];
                        $item->asset->update([
                            'status' => $meta['previous_status'] ?? 'Free',
                            'holder_user_id' => array_key_exists('previous_holder_user_id', $meta) ? $meta['previous_holder_user_id'] : $item->asset->holder_user_id,
                        ]);
                    }
                }
            }
            return;
        }

        // Jenis lain (data lama).
        foreach ($transaction->items as $item) {
            if (!$item->asset) continue;
            if ($transaction->status === 'Rejected') {
                if (in_array($item->asset->status, ['Pending Mutation', 'Pre Disposed'], true)) {
                    $item->asset->update(['status' => 'Free']);
                }
            } elseif (in_array($transaction->status, ['Approved', 'Completed'], true)) {
                if ($item->asset->status === 'Pending Mutation') {
                    $item->asset->update(['jobsite_id' => $transaction->to_jobsite_id, 'status' => 'Free']);
                } elseif ($item->asset->status === 'Pre Disposed') {
                    $item->asset->update(['status' => 'Disposed']);
                }
            }
        }
    }

    protected function notifyApproverAfterCommit(AssetTransactionApproval $approval): void
    {
        $approver = $approval->approver;
        if (!$approver || !$approver->whatsapp) {
            Log::warning('Gagal kirim WA: Approver tidak punya nomor telepon.', ['approval_id' => $approval->id]);
            return;
        }
        $transaction = $approval->transaction;
        $message = "Halo {$approver->name},\n\n"
            . "Ada pengajuan transaksi aset #{$transaction->transaction_number} yang membutuhkan persetujuan Anda.\n"
            . 'Jenis: ' . ucwords(strtolower(str_replace('_', ' ', $transaction->transaction_type))) . "\n"
            . 'Tanggal: ' . \Carbon\Carbon::parse($transaction->transaction_date)->format('d-m-Y') . "\n"
            . "Status: {$transaction->status}\n\n"
            . 'Silakan login ke aplikasi AMS untuk melakukan approval.'
            . 'ams.binasaranasukses.co.id'
            . "\n" . route('asset.mutasi-status', $transaction->id);

        $this->whatsAppAfterCommit($approver->whatsapp, $message);
    }

    protected function notifyRequesterAfterCommit(AssetTransaction $transaction, string $subject): void
    {
        $requester = $transaction->creator;
        if (!$requester || !$requester->whatsapp) {
            Log::warning('Gagal kirim WA: Requester tidak punya nomor telepon.', ['transaction_id' => $transaction->id]);
            return;
        }
        $message = "Halo {$requester->name},\n\n"
            . "{$subject}\n"
            . "Transaksi aset #{$transaction->transaction_number}\n"
            . 'Jenis: ' . ucwords(strtolower(str_replace('_', ' ', $transaction->transaction_type))) . "\n"
            . 'Tanggal: ' . \Carbon\Carbon::parse($transaction->transaction_date)->format('d-m-Y') . "\n"
            . "Status: {$transaction->status}\n\n"
            . 'Cek detail di aplikasi AMS.';

        $this->whatsAppAfterCommit($requester->whatsapp, $message);
    }

    /** WA dikirim setelah commit; gagal kirim tidak membatalkan keputusan approval. */
    protected function whatsAppAfterCommit(string $number, string $message): void
    {
        DB::afterCommit(function () use ($number, $message) {
            try {
                sendMessageWithWhatsApp($number, $message);
            } catch (\Throwable $e) {
                Log::warning('Gagal kirim WA approval transaksi: ' . $e->getMessage());
            }
        });
    }
}
