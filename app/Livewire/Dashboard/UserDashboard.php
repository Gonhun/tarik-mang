<?php

namespace App\Livewire\Dashboard;

use App\Livewire\Dashboard\Concerns\PendingApprovals;
use App\Models\Asset;
use App\Models\AssetRequest;
use App\Models\LicenseRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Dashboard karyawan: aset yang dipegang, status pengajuan sendiri,
 * approval yang menunggu (untuk atasan), dan kontak IT di site.
 */
class UserDashboard extends Component
{
    use PendingApprovals;

    /** Status pengajuan aset -> [label ramah, warna, langkah ke-n dari 5]. */
    public const REQUEST_STAGES = [
        'Pending IT Item Validation' => ['Dicek tim IT', 'info', 1],
        'Pending Approval' => ['Menunggu persetujuan', 'warning', 2],
        'Pending PR & Accounting Number' => ['Proses pembelian (PR)', 'primary', 3],
        'Pending PO' => ['Proses pembelian (PO)', 'primary', 3],
        'Pending QC' => ['Barang dicek (QC)', 'primary', 4],
        'Pending Asset Mutation' => ['Dikirim ke Anda', 'primary', 4],
        'Completed' => ['Selesai', 'success', 5],
        'Close' => ['Ditutup', 'secondary', 5],
        'Rejected' => ['Ditolak', 'danger', 5],
    ];

    public const CLOSED_REQUEST_STATUSES = ['Completed', 'Close', 'Rejected'];

    public function render()
    {
        $user = Auth::user();

        $myAssets = Asset::query()
            ->where('holder_user_id', $user->id)
            ->with(['assetType:id,name', 'assetBrand:id,name', 'assetSeries:id,name'])
            ->orderBy('asset_number')
            ->get(['id', 'asset_number', 'asset_type_id', 'asset_brand_id', 'asset_series_id', 'serial_number', 'status']);

        $myRequests = AssetRequest::query()
            ->where('requester_user_id', $user->id)
            // relasi approvals() sudah diurutkan per step_order
            ->with(['approvals' => fn ($q) => $q->where('status', 'Pending')->with('approver:id,name')])
            ->withCount('items')
            ->orderByDesc('request_date')
            ->orderByDesc('id')
            ->limit(5)
            ->get();
        $activeRequestCount = AssetRequest::where('requester_user_id', $user->id)
            ->whereNotIn('status', self::CLOSED_REQUEST_STATUSES)
            ->count();

        $licenseRequestCount = LicenseRequest::where('user_id', $user->id)
            ->whereIn('status', ['draft', 'Submitted'])
            ->count();

        $approvals = $this->pendingApprovalCounts($user->id);
        $approvalList = $approvals['asset'] > 0 ? $this->pendingAssetRequestList($user->id) : collect();
        $transactionApprovalList = $approvals['transaction'] > 0 ? $this->pendingTransactionList($user->id) : collect();

        $itContacts = $user->jobsite_id
            ? User::where('department_id', config('app.it_department_id'))
                ->where('jobsite_id', $user->jobsite_id)
                ->where('id', '!=', $user->id)
                ->orderBy('name')
                ->limit(4)
                ->get(['id', 'name', 'whatsapp', 'position_id'])
                ->load('position:id,name')
            : collect();

        return view('livewire.dashboard.user-dashboard', [
            'user' => $user->loadMissing(['jobsite:id,code,name', 'department:id,name', 'position:id,name']),
            'myAssets' => $myAssets,
            'myRequests' => $myRequests,
            'activeRequestCount' => $activeRequestCount,
            'licenseRequestCount' => $licenseRequestCount,
            'approvals' => $approvals,
            'approvalList' => $approvalList,
            'transactionApprovalList' => $transactionApprovalList,
            'itContacts' => $itContacts,
            'stages' => self::REQUEST_STAGES,
        ]);
    }
}
