<?php

namespace App\Livewire\Dashboard\Concerns;

use App\Models\AssetRequestApproval;
use App\Models\AssetTransactionApproval;
use App\Models\LicenseRequestApproval;
use Illuminate\Database\Eloquent\Builder;

/**
 * Approval yang sedang menunggu giliran user: status Pending, user adalah approver,
 * dan semua tahap sebelumnya sudah selesai (ini tahap Pending paling awal).
 */
trait PendingApprovals
{
    protected function myAssetRequestApprovals(int $userId): Builder
    {
        return AssetRequestApproval::query()
            ->where('approver_user_id', $userId)
            ->where('status', 'Pending')
            ->whereHas('assetRequest', fn ($q) => $q->where('status', 'Pending Approval'))
            ->whereRaw(
                'step_order = (select min(a2.step_order) from asset_request_approvals a2 where a2.asset_request_id = asset_request_approvals.asset_request_id and a2.status = ?)',
                ['Pending']
            );
    }

    protected function myTransactionApprovals(int $userId): Builder
    {
        return AssetTransactionApproval::query()
            ->where('approver_user_id', $userId)
            ->where('status', 'Pending')
            ->whereHas('transaction', fn ($q) => $q->whereNotIn('status', ['Completed', 'Rejected']))
            ->whereRaw(
                'step_order = (select min(a2.step_order) from asset_transaction_approvals a2 where a2.asset_transaction_id = asset_transaction_approvals.asset_transaction_id and a2.status = ?)',
                ['Pending']
            );
    }

    protected function myLicenseApprovals(int $userId): Builder
    {
        return LicenseRequestApproval::query()
            ->where('user_id', $userId)
            ->where('status', 'Pending')
            ->whereRaw(
                'step_order = (select min(a2.step_order) from license_request_approvals a2 where a2.license_request_item_id = license_request_approvals.license_request_item_id and a2.status = ?)',
                ['Pending']
            );
    }

    /**
     * @return array{asset: int, transaction: int, license: int, total: int}
     */
    protected function pendingApprovalCounts(int $userId): array
    {
        $counts = [
            'asset' => $this->myAssetRequestApprovals($userId)->count(),
            'transaction' => $this->myTransactionApprovals($userId)->count(),
            'license' => $this->myLicenseApprovals($userId)->count(),
        ];
        $counts['total'] = array_sum($counts);

        return $counts;
    }

    /**
     * Daftar transaksi aset (disposal, kehilangan, mutasi) yang menunggu approval user.
     */
    protected function pendingTransactionList(int $userId, int $limit = 5)
    {
        return $this->myTransactionApprovals($userId)
            ->with(['transaction:id,transaction_number,transaction_type,notes,created_by_user_id,transaction_date', 'transaction.creator:id,name', 'transaction.items.asset:id,asset_number'])
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Daftar pengajuan aset yang menunggu approval user (untuk tabel).
     */
    protected function pendingAssetRequestList(int $userId, int $limit = 5)
    {
        return $this->myAssetRequestApprovals($userId)
            ->with(['assetRequest:id,request_number,requester_user_id,request_date,jobsite_id', 'assetRequest.requester:id,name', 'assetRequest.jobsite:id,code'])
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }
}
