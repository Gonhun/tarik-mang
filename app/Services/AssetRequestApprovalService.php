<?php

namespace App\Services;

use App\Mail\MailAssetRequest;
use App\Models\AssetRequest;
use App\Models\AssetRequestApproval;
use App\Models\AssetTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Keputusan approval pengajuan aset (tahap workflow REQUEST_ASSET), dipakai halaman web dan API mobile.
 */
class AssetRequestApprovalService
{
    public const ADMIN_ROLES = ['Administrator IT', 'Administrator'];

    /**
     * Boleh memutuskan tahap ini bila: pengajuan sedang "Pending Approval", tahap masih Pending,
     * semua tahap sebelumnya sudah selesai, dan user adalah approver tahap ini (admin boleh mewakili).
     */
    public function canDecide(AssetRequestApproval $approval, User $user): bool
    {
        $assetRequest = $approval->assetRequest;
        if ($approval->status !== 'Pending' || !$assetRequest || $assetRequest->status !== 'Pending Approval') {
            return false;
        }

        $isTurn = !AssetRequestApproval::where('asset_request_id', $approval->asset_request_id)
            ->where('step_order', '<', $approval->step_order)
            ->where('status', 'Pending')
            ->exists();

        return $isTurn && ((int) $user->id === (int) $approval->approver_user_id || $user->hasRole(self::ADMIN_ROLES));
    }

    /**
     * Setujui / tolak satu tahap. Pemanggil wajib cek canDecide() dan validasi catatan (wajib saat menolak).
     *
     * @throws \App\Support\WorkflowNotConfigured
     */
    public function decide(AssetRequestApproval $approval, User $actor, string $action, ?string $notes): AssetRequest
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
            $assetRequest = $approval->assetRequest()->with('requester.karyawans')->first();
            $requesterEmail = $assetRequest->requester->karyawans->Email ?? null;

            if ($action === 'reject') {
                $assetRequest->update(['status' => 'Rejected', 'final_rejection_notes' => $notes]);
                $this->mailAfterCommit($requesterEmail, ['type' => 'REJECT', 'author' => $assetRequest->requester->name, 'draft' => $approval]);

                return $assetRequest;
            }

            $nextApproval = $assetRequest->approvals()->where('status', 'Pending')->first();
            if ($nextApproval) {
                // Status utama tetap "Pending Approval".
                $this->mailAfterCommit($requesterEmail, ['type' => 'OUTSTANDING_APPROVER', 'approver' => $nextApproval, 'draft' => $approval]);
                $this->mailAfterCommit($requesterEmail, ['type' => 'APPROVE', 'author' => $assetRequest->requester->name, 'draft' => $approval]);
            } else {
                $this->processFullyApproved($assetRequest, $actor);
            }

            return $assetRequest->refresh();
        });
    }

    /**
     * Semua tahap persetujuan selesai: buat mutasi untuk item stok site lain, lalu tentukan status berikutnya
     * (PR & akunting bila ada pembelian baru, menunggu mutasi, atau selesai).
     */
    public function processFullyApproved(AssetRequest $assetRequest, User $actor): void
    {
        $assetRequest->load('items.assignedAsset.jobsite', 'jobsite');
        $mutationItems = $assetRequest->items->where('fulfillment_type', 'STOCK_MUTATION');
        $newPurchaseItems = $assetRequest->items->where('fulfillment_type', 'NEW_PURCHASE');

        DB::transaction(function () use ($assetRequest, $actor, $mutationItems, $newPurchaseItems) {
            if ($mutationItems->isNotEmpty()) {
                // Satu transaksi mutasi untuk setiap jobsite asal.
                foreach ($mutationItems->groupBy('assignedAsset.jobsite_id') as $originJobsiteId => $items) {
                    $mutationTransaction = AssetTransaction::create([
                        'transaction_number' => $this->generateTransactionNumber($assetRequest, 'MT'),
                        'transaction_type' => 'MUTATION',
                        'transaction_date' => now(),
                        'from_jobsite_id' => $originJobsiteId,
                        'to_jobsite_id' => $assetRequest->jobsite_id,
                        'notes' => "Mutasi aset untuk memenuhi Pengajuan Aset No: {$assetRequest->request_number}",
                        // Tanpa tahap approval: persetujuan sudah lewat workflow pengajuan; langsung menunggu pelepasan site asal.
                        'status' => 'Pending Release',
                        'created_by_user_id' => $actor->id,
                    ]);

                    foreach ($items as $item) {
                        $mutationTransaction->items()->create(['asset_id' => $item->assigned_asset_id, 'asset_request_item_id' => $item->id]);
                        $item->assignedAsset->update(['status' => 'Pending Mutation']);
                    }

                    // Notifikasi ke tim IT site asal untuk melepas aset (setelah commit; gagal WA tidak membatalkan proses).
                    $itOrigin = User::where('department_id', config('app.it_department_id'))
                        ->where('jobsite_id', $originJobsiteId)->whereNotNull('whatsapp')->limit(3)->get();
                    foreach ($itOrigin as $picUser) {
                        $msg = "Halo {$picUser->name},\n\nTerdapat permintaan mutasi aset ({$mutationTransaction->transaction_number}) dari Jobsite *{$assetRequest->jobsite->code}* untuk memenuhi pengajuan aset No. *{$assetRequest->request_number}*.\n\nMohon lakukan pelepasan aset di menu Mutasi: " . route('asset.mutasi-status', $mutationTransaction->id);
                        DB::afterCommit(function () use ($picUser, $msg) {
                            try {
                                sendMessageWithWhatsApp($picUser->whatsapp, $msg);
                            } catch (\Throwable $e) {
                                Log::warning('WA pelepasan mutasi gagal: ' . $e->getMessage());
                            }
                        });
                    }
                }
            }

            $nextStatus = 'Completed';
            if ($newPurchaseItems->isNotEmpty()) {
                $nextStatus = 'Pending PR & Accounting Number';
            } elseif ($mutationItems->isNotEmpty()) {
                $nextStatus = 'Pending Asset Mutation';
            }
            $assetRequest->update(['status' => $nextStatus]);
        });
    }

    public function generateTransactionNumber(AssetRequest $assetRequest, string $typePrefix = 'TRX'): string
    {
        $assetRequest->loadMissing('jobsite');
        $prefix = "BSS/{$assetRequest->jobsite->code}/{$typePrefix}/" . date('y') . date('m') . '/';
        $last = AssetTransaction::where('transaction_number', 'LIKE', $prefix . '%')
            ->orderBy('transaction_number', 'desc')->value('transaction_number');
        $next = $last ? intval(substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    /** Email dikirim setelah commit; gagal kirim tidak membatalkan keputusan approval. */
    protected function mailAfterCommit(?string $to, array $data): void
    {
        if (!$to) return;
        DB::afterCommit(function () use ($to, $data) {
            try {
                Mail::mailer('smtp2')->to($to)->send(new MailAssetRequest($data));
            } catch (\Throwable $e) {
                Log::error('Gagal kirim email approval pengajuan aset: ' . $e->getMessage());
            }
        });
    }
}
