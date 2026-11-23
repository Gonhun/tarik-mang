<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\AssetRequestResource;
use App\Livewire\Dashboard\Concerns\PendingApprovals;
use App\Models\AssetRequestApproval;
use App\Models\AssetTransactionApproval;
use App\Services\AssetRequestApprovalService;
use App\Services\TransactionApprovalService;
use App\Support\WorkflowNotConfigured;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Approval dari HP: daftar yang menunggu giliran saya + setujui/tolak.
 * Aturan giliran & approver sama persis dengan halaman web (service yang sama).
 */
class ApprovalController extends Controller
{
    use PendingApprovals;

    protected const TYPE_LABELS = ['MUTATION' => 'Mutasi aset', 'DISPOSED' => 'Disposal aset', 'HANDOVER' => 'Serah terima', 'RETURN' => 'Pengembalian'];

    /** GET /api/v1/approvals — semua yang menunggu keputusan saya. */
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $assetRequests = $this->myAssetRequestApprovals($userId)
            ->with(['assetRequest:id,request_number,requester_user_id,submitted_by_user_id,request_date,needed_date,purpose,jobsite_id,status',
                'assetRequest.requester:id,name,nik', 'assetRequest.jobsite:id,code'])
            ->orderBy('id')->limit(100)->get()
            ->map(fn (AssetRequestApproval $a) => [
                'kind' => 'asset_request',
                'approval_id' => $a->id,
                'step_order' => (int) $a->step_order,
                'role' => $a->approver_role_name,
                'reference_id' => (int) $a->asset_request_id,
                'number' => $a->assetRequest->request_number,
                'title' => 'Pengajuan aset',
                'subtitle' => ($a->assetRequest->requester->name ?? '-') . ' · ' . ($a->assetRequest->jobsite->code ?? '-'),
                'description' => $a->assetRequest->purpose,
                'date' => $a->assetRequest->request_date?->toDateString(),
            ]);

        $transactions = $this->myTransactionApprovals($userId)
            ->with(['transaction:id,transaction_number,transaction_type,notes,created_by_user_id,transaction_date,from_jobsite_id,to_jobsite_id,status',
                'transaction.creator:id,name', 'transaction.fromJobsite:id,code', 'transaction.toJobsite:id,code'])
            ->orderBy('id')->limit(100)->get()
            ->map(fn (AssetTransactionApproval $a) => [
                'kind' => 'transaction',
                'approval_id' => $a->id,
                'step_order' => (int) $a->step_order,
                'role' => $a->approver_role_name,
                'reference_id' => (int) $a->asset_transaction_id,
                'number' => $a->transaction->transaction_number,
                'title' => self::TYPE_LABELS[$a->transaction->transaction_type] ?? $a->transaction->transaction_type,
                'subtitle' => ($a->transaction->creator->name ?? '-')
                    . ($a->transaction->transaction_type === 'MUTATION' ? ' · ' . ($a->transaction->fromJobsite->code ?? '-') . ' → ' . ($a->transaction->toJobsite->code ?? '-') : ''),
                'description' => $a->transaction->notes,
                'date' => $a->transaction->transaction_date ? \Illuminate\Support\Carbon::parse($a->transaction->transaction_date)->toDateString() : null,
            ]);

        return response()->json([
            'data' => $assetRequests->concat($transactions)->values(),
            'counts' => ['asset_request' => $assetRequests->count(), 'transaction' => $transactions->count(), 'total' => $assetRequests->count() + $transactions->count()],
        ]);
    }

    /** GET /api/v1/approvals/transactions/{approval} — detail transaksi untuk diputuskan. */
    public function showTransaction(Request $request, AssetTransactionApproval $approval, TransactionApprovalService $service)
    {
        $trx = $approval->transaction()->with([
            'creator:id,name', 'fromJobsite:id,code,name', 'toJobsite:id,code,name',
            'items.asset:id,asset_number,serial_number,status,asset_type_id,asset_series_id',
            'items.asset.assetType:id,name', 'items.asset.assetSeries:id,name',
            'approvals.approver:id,name', 'approvals.actionBy:id,name',
        ])->first();
        $canDecide = $service->canDecide($approval, $request->user());
        abort_unless($canDecide || $trx->approvals->contains('approver_user_id', $request->user()->id), 403, 'Anda bukan approver transaksi ini.');

        return response()->json(['data' => [
            'approval_id' => $approval->id,
            'can_decide' => $canDecide,
            'transaction' => [
                'id' => $trx->id,
                'number' => $trx->transaction_number,
                'type' => $trx->transaction_type,
                'type_label' => self::TYPE_LABELS[$trx->transaction_type] ?? $trx->transaction_type,
                'status' => $trx->status,
                'date' => $trx->transaction_date ? \Illuminate\Support\Carbon::parse($trx->transaction_date)->toDateString() : null,
                'notes' => $trx->notes,
                'creator' => $trx->creator?->only(['id', 'name']),
                'from_jobsite' => $trx->fromJobsite?->only(['id', 'code', 'name']),
                'to_jobsite' => $trx->toJobsite?->only(['id', 'code', 'name']),
                'assets' => $trx->items->map(fn ($i) => $i->asset ? [
                    'id' => $i->asset->id, 'asset_number' => $i->asset->asset_number, 'serial_number' => $i->asset->serial_number,
                    'status' => $i->asset->status, 'type' => $i->asset->assetType?->name, 'series' => $i->asset->assetSeries?->name,
                ] : null)->filter()->values(),
                'approvals' => $trx->approvals->sortBy('step_order')->values()->map(fn ($a) => [
                    'id' => $a->id, 'step_order' => (int) $a->step_order, 'role' => $a->approver_role_name, 'status' => $a->status,
                    'approver' => $a->approver?->only(['id', 'name']), 'action_by' => $a->actionBy?->only(['id', 'name']), 'notes' => $a->notes,
                ]),
            ],
        ]]);
    }

    /** POST /api/v1/approvals/asset-requests/{approval}/decision  {action: approve|reject, notes} */
    public function decideAssetRequest(Request $request, AssetRequestApproval $approval, AssetRequestApprovalService $service)
    {
        $data = $this->validateDecision($request);
        abort_unless($service->canDecide($approval, $request->user()), 403, 'Bukan giliran Anda, atau tahap ini sudah diputuskan.');

        try {
            $assetRequest = $service->decide($approval, $request->user(), $data['action'], $data['notes'] ?? null);
        } catch (WorkflowNotConfigured $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('API: gagal memutuskan approval pengajuan aset: ' . $e->getMessage(), ['approval_id' => $approval->id]);
            return response()->json(['message' => 'Keputusan gagal disimpan. Coba lagi.'], 500);
        }

        return response()->json([
            'message' => $data['action'] === 'approve' ? 'Pengajuan disetujui.' : 'Pengajuan ditolak.',
            'data' => (new AssetRequestResource($assetRequest->load(['approvals.approver:id,name', 'approvals.actionBy:id,name'])))->resolve(),
        ]);
    }

    /** POST /api/v1/approvals/transactions/{approval}/decision  {action: approve|reject, notes} */
    public function decideTransaction(Request $request, AssetTransactionApproval $approval, TransactionApprovalService $service)
    {
        $data = $this->validateDecision($request);
        abort_unless($service->canDecide($approval, $request->user()), 403, 'Bukan giliran Anda, atau tahap ini sudah diputuskan.');

        try {
            $trx = $service->decide($approval, $request->user(), $data['action'], $data['notes'] ?? null);
        } catch (\Throwable $e) {
            Log::error('API: gagal memutuskan approval transaksi: ' . $e->getMessage(), ['approval_id' => $approval->id]);
            return response()->json(['message' => 'Keputusan gagal disimpan. Coba lagi.'], 500);
        }

        return response()->json([
            'message' => $data['action'] === 'approve' ? 'Transaksi disetujui.' : 'Transaksi ditolak.',
            'data' => ['id' => $trx->id, 'number' => $trx->transaction_number, 'status' => $trx->status],
        ]);
    }

    /** Catatan wajib (minimal 10 karakter) saat menolak, sama dengan web. */
    protected function validateDecision(Request $request): array
    {
        $reject = $request->input('action') === 'reject';

        return $request->validate([
            'action' => 'required|in:approve,reject',
            'notes' => $reject ? 'required|string|min:10|max:1000' : 'nullable|string|max:1000',
        ], [
            'notes.required' => 'Alasan penolakan wajib diisi.',
            'notes.min' => 'Alasan penolakan minimal 10 karakter.',
        ]);
    }
}
