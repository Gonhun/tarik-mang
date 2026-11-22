<?php

namespace App\Livewire\Components\Nav;

use App\Livewire\Dashboard\Concerns\PendingApprovals;
use App\Models\AssetRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Lonceng di samping foto profil: daftar hal yang menunggu persetujuan user (giliran dia).
 * Diperbarui otomatis tiap menit (wire:poll) selama tab browser terlihat.
 */
class ApprovalBell extends Component
{
    use PendingApprovals;

    /** Maksimum item yang ditampilkan di dropdown. */
    public const LIMIT = 8;

    protected function items(): array
    {
        $user = Auth::user();
        $items = collect();
        $counts = ['asset' => 0, 'transaction' => 0, 'license' => 0, 'it' => 0];

        // 1. Pengajuan aset (giliran saya)
        $assetQuery = $this->myAssetRequestApprovals($user->id);
        $counts['asset'] = (clone $assetQuery)->count();
        $assetQuery->with(['assetRequest:id,request_number,requester_user_id', 'assetRequest.requester:id,name'])
            ->latest('id')->limit(self::LIMIT)->get()
            ->each(fn ($a) => $items->push([
                'icon' => 'bx-clipboard', 'color' => 'primary',
                'title' => 'Pengajuan aset ' . ($a->assetRequest->request_number ?? ''),
                'meta' => ($a->assetRequest->requester->name ?? '-') . ' · sebagai ' . $a->approver_role_name,
                'url' => route('asset-requests.show', $a->asset_request_id),
                'at' => $a->updated_at,
            ]));

        // 2. Transaksi aset: mutasi, disposal, kehilangan
        $trxQuery = $this->myTransactionApprovals($user->id);
        $counts['transaction'] = (clone $trxQuery)->count();
        $trxQuery->with(['transaction:id,transaction_number,transaction_type,notes,created_by_user_id', 'transaction.creator:id,name'])
            ->latest('id')->limit(self::LIMIT)->get()
            ->each(function ($a) use ($items) {
                $trx = $a->transaction;
                $isLost = $trx && $trx->transaction_type === 'DISPOSED' && str_starts_with((string) $trx->notes, '[Kehilangan]');
                $label = match (true) {
                    $isLost => 'Kehilangan aset',
                    ($trx->transaction_type ?? null) === 'DISPOSED' => 'Disposal aset',
                    ($trx->transaction_type ?? null) === 'MUTATION' => 'Mutasi aset',
                    default => 'Transaksi aset',
                };
                $items->push([
                    'icon' => $isLost ? 'bx-error-circle' : (($trx->transaction_type ?? null) === 'MUTATION' ? 'bx-transfer' : 'bx-trash'),
                    'color' => $isLost ? 'warning' : 'danger',
                    'title' => $label . ' ' . ($trx->transaction_number ?? ''),
                    'meta' => 'Diajukan ' . ($trx->creator->name ?? '-'),
                    'url' => route('asset.mutasi-status', $a->asset_transaction_id),
                    'at' => $a->updated_at,
                ]);
            });

        // 3. Lisensi
        $licQuery = $this->myLicenseApprovals($user->id);
        $counts['license'] = (clone $licQuery)->count();
        $licQuery->with(['request.request:id,request_no,user_id', 'request.request.user:id,name'])
            ->latest('id')->limit(self::LIMIT)->get()
            ->each(fn ($a) => $items->push([
                'icon' => 'bx-key', 'color' => 'info',
                'title' => 'Pengajuan lisensi ' . ($a->request->request->request_no ?? ''),
                'meta' => 'Diajukan ' . ($a->request->request->user->name ?? '-'),
                'url' => route('requests.license.main'),
                'at' => $a->updated_at,
            ]));

        // 4. Tim IT: item pengajuan aset menunggu validasi IT di site sendiri
        if ($user->isItStaff() && $user->jobsite_id) {
            $itQuery = AssetRequest::where('status', 'Pending IT Item Validation')->where('jobsite_id', $user->jobsite_id);
            $counts['it'] = (clone $itQuery)->count();
            $itQuery->with('requester:id,name')->latest('id')->limit(self::LIMIT)->get()
                ->each(fn ($r) => $items->push([
                    'icon' => 'bx-check-shield', 'color' => 'success',
                    'title' => 'Validasi IT ' . $r->request_number,
                    'meta' => 'Pemohon ' . ($r->requester->name ?? '-'),
                    'url' => route('asset-requests.show', $r->id),
                    'at' => $r->updated_at,
                ]));
        }

        return [
            'total' => array_sum($counts),
            'counts' => $counts,
            'items' => $items->sortByDesc(fn ($i) => (string) $i['at'])->take(self::LIMIT)->values(),
        ];
    }

    /**
     * Tampilan sementara saat lonceng dimuat (lazy), supaya halaman tidak menunggu query notifikasi.
     */
    public function placeholder()
    {
        return <<<'HTML'
        <li class="nav-item me-3 me-xl-2">
            <span class="nav-link p-1 text-muted" aria-label="Memuat notifikasi"><i class="bx bx-bell fs-4"></i></span>
        </li>
        HTML;
    }

    public function render()
    {
        return view('livewire.components.nav.approval-bell', $this->items());
    }
}
