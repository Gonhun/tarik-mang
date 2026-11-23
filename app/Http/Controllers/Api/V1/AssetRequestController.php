<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\AssetRequestResource;
use App\Http\Resources\Api\UserResource;
use App\Models\AssetBrand;
use App\Models\AssetRequest;
use App\Models\AssetSeries;
use App\Models\AssetType;
use App\Models\User;
use App\Services\AssetRequestService;
use App\Support\Workflow;
use App\Support\WorkflowNotConfigured;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class AssetRequestController extends Controller
{
    public function __construct(protected AssetRequestService $service) {}

    // ------------------------------------------------------------------
    // Katalog untuk memilih item
    // ------------------------------------------------------------------

    /** GET /api/v1/catalog/asset-types */
    public function types()
    {
        return response()->json(['data' => AssetType::orderBy('name')->get(['id', 'name'])]);
    }

    /** GET /api/v1/catalog/asset-brands?asset_type_id= */
    public function brands(Request $request)
    {
        $data = $request->validate(['asset_type_id' => 'required|integer']);

        return response()->json(['data' => AssetBrand::where('asset_type_id', $data['asset_type_id'])->orderBy('name')->get(['id', 'name', 'asset_type_id'])]);
    }

    /** GET /api/v1/catalog/asset-series?asset_brand_id= */
    public function series(Request $request)
    {
        $data = $request->validate(['asset_brand_id' => 'required|integer']);

        return response()->json(['data' => AssetSeries::where('asset_brand_id', $data['asset_brand_id'])->orderBy('name')->get(['id', 'name', 'asset_brand_id', 'specifications'])]);
    }

    // ------------------------------------------------------------------
    // Form pengajuan
    // ------------------------------------------------------------------

    /**
     * GET /api/v1/asset-requests/beneficiaries?q=
     * Karyawan aktif satu departemen yang bisa diajukan (butuh permission "Ajukan Aset untuk Orang Lain"). Minimal 4 karakter.
     */
    public function beneficiaries(Request $request)
    {
        $user = $request->user();
        abort_unless(AssetRequestService::canSubmitForOthers($user), 403, 'Anda tidak punya izin mengajukan aset untuk orang lain.');
        $term = trim((string) $request->validate(['q' => 'required|string|min:4|max:100'])['q']);

        $users = AssetRequestService::eligibleBeneficiaries($user)
            ->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('nik', 'like', "%{$term}%"))
            ->with(['position:id,name', 'jobsite:id,code,name'])
            ->orderBy('name')->limit(10)
            ->get(['id', 'name', 'nik', 'position_id', 'jobsite_id']);

        return UserResource::collection($users);
    }

    /**
     * GET /api/v1/asset-requests/form?for_user_id=
     * Data awal form: penerima, item standar jabatan, alur persetujuan, dan kendala (bila ada).
     */
    public function form(Request $request)
    {
        $requester = $this->requesterOrFail($request);
        $requester->loadMissing(['position:id,name,job_description_name', 'department:id,code,name', 'jobsite:id,code,name']);

        $problems = $this->blockingProblems($requester);
        $preview = [];
        try {
            $preview = Workflow::plan('REQUEST_ASSET', $requester->position_id, $requester->jobsite_id);
        } catch (WorkflowNotConfigured $e) {
            // Sudah masuk $problems.
        }

        $items = collect($this->service->standardItems($requester));
        $types = AssetType::whereIn('id', $items->pluck('asset_type_id'))->pluck('name', 'id');
        $brands = AssetBrand::whereIn('id', $items->pluck('asset_brand_id'))->pluck('name', 'id');
        $series = AssetSeries::whereIn('id', $items->pluck('asset_series_id'))->pluck('name', 'id');

        return response()->json([
            'requester' => (new UserResource($requester))->resolve(),
            'for_other' => (int) $requester->id !== (int) $request->user()->id,
            'can_submit' => empty($problems),
            'problems' => $problems,
            'default_needed_date' => now()->addDays(14)->toDateString(),
            'standard_items' => $items->map(fn ($i) => $i + [
                'type_name' => $types[$i['asset_type_id']] ?? null,
                'brand_name' => $brands[$i['asset_brand_id']] ?? null,
                'series_name' => $series[$i['asset_series_id']] ?? null,
            ])->values(),
            'approval_flow' => array_map(fn ($p) => ['step_order' => $p['step_order'], 'role' => $p['role'], 'approver' => ['id' => $p['user']->id, 'name' => $p['user']->name]], $preview),
        ]);
    }

    /**
     * POST /api/v1/asset-requests (multipart/form-data atau JSON)
     * for_user_id?, purpose, needed_date, items[n][asset_type_id|asset_brand_id|asset_series_id|quantity|item_description|is_standard], ba_damage?, ba_budget_outside?
     */
    public function store(Request $request)
    {
        $requester = $this->requesterOrFail($request);

        $data = $request->validate([
            'purpose' => 'required|string|min:10|max:1000',
            'needed_date' => 'required|date|after_or_equal:today',
            'items' => 'required|array|min:1|max:50',
            'items.*.asset_type_id' => 'required|exists:asset_types,id',
            'items.*.asset_brand_id' => 'required|exists:asset_brands,id',
            'items.*.asset_series_id' => 'required|exists:asset_series,id',
            'items.*.quantity' => 'required|integer|min:1|max:1000',
            'items.*.item_description' => 'nullable|string|max:500',
            'items.*.is_standard' => 'required|boolean',
            'ba_damage' => 'nullable|file|mimes:pdf,doc,docx,png,jpg,jpeg|max:2048',
            'ba_budget_outside' => 'nullable|file|mimes:pdf,doc,docx,png,jpg,jpeg|max:2048',
        ], [
            'purpose.min' => 'Tujuan pengajuan minimal 10 karakter.',
            'items.required' => 'Minimal satu item aset harus diajukan.',
        ]);

        if ($problems = $this->blockingProblems($requester)) {
            return response()->json(['message' => $problems[0], 'problems' => $problems], 422);
        }

        // Hanya field item yang dikenal yang disimpan.
        $data['items'] = array_map(fn ($i) => [
            'asset_type_id' => $i['asset_type_id'],
            'asset_brand_id' => $i['asset_brand_id'],
            'asset_series_id' => $i['asset_series_id'],
            'quantity' => (int) $i['quantity'],
            'item_description' => $i['item_description'] ?? null,
            'is_standard' => filter_var($i['is_standard'], FILTER_VALIDATE_BOOLEAN),
        ], $data['items']);

        try {
            $assetRequest = $this->service->submit($requester, $request->user(), $data, $request->file('ba_damage'), $request->file('ba_budget_outside'));
        } catch (WorkflowNotConfigured $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('API: gagal membuat pengajuan aset: ' . $e->getMessage());
            return response()->json(['message' => 'Pengajuan gagal disimpan. Coba lagi atau hubungi tim IT.'], 500);
        }

        return (new AssetRequestResource($this->loadDetail($assetRequest)))
            ->additional(['message' => 'Pengajuan aset berhasil dibuat dan sedang menunggu persetujuan.'])
            ->response()->setStatusCode(201);
    }

    // ------------------------------------------------------------------
    // Daftar & detail
    // ------------------------------------------------------------------

    /** GET /api/v1/asset-requests?status= — pengajuan saya (sebagai penerima atau pengisi form). */
    public function index(Request $request)
    {
        $request->validate(['status' => 'nullable|string|max:100']);
        $userId = $request->user()->id;

        $requests = AssetRequest::where(fn ($q) => $q->where('requester_user_id', $userId)->orWhere('submitted_by_user_id', $userId))
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->with(['requester:id,name,nik', 'submitter:id,name,nik', 'jobsite:id,code,name'])
            ->withCount('items')
            ->orderByDesc('request_date')->orderByDesc('id')
            ->paginate(20);

        return AssetRequestResource::collection($requests);
    }

    /** GET /api/v1/asset-requests/{assetRequest} — akses sama dengan halaman detail di web. */
    public function show(AssetRequest $assetRequest)
    {
        abort_unless(Gate::allows('view-asset-request', $assetRequest), 403, 'Anda tidak punya akses ke pengajuan ini.');

        return new AssetRequestResource($this->loadDetail($assetRequest));
    }

    // ------------------------------------------------------------------

    protected function loadDetail(AssetRequest $r): AssetRequest
    {
        return $r->load([
            'requester:id,name,nik,position_id,department_id,jobsite_id', 'requester.position:id,name', 'requester.department:id,code,name', 'requester.jobsite:id,code,name',
            'submitter:id,name,nik', 'jobsite:id,code,name',
            'items.assetType:id,name', 'items.assetBrand:id,name', 'items.assetSeries:id,name',
            'approvals.approver:id,name', 'approvals.actionBy:id,name',
        ]);
    }

    /** Penerima dari for_user_id (atau diri sendiri); 403 bila tidak berhak mengajukan untuk orang itu. */
    protected function requesterOrFail(Request $request): User
    {
        $request->validate(['for_user_id' => 'nullable|integer']);
        $requester = AssetRequestService::resolveRequester($request->user(), $request->input('for_user_id'));
        abort_unless($requester, 403, 'Anda hanya bisa mengajukan untuk karyawan aktif di departemen Anda (perlu izin khusus).');

        return $requester;
    }

    /** Kendala yang membuat pengajuan tidak bisa dikirim (sama dengan form web). */
    protected function blockingProblems(User $requester): array
    {
        $requester->loadMissing('position');
        $who = (int) $requester->id === (int) auth()->id() ? 'Anda' : $requester->name;
        $problems = [];
        if (!$requester->position) {
            $problems[] = "Jabatan {$who} belum diatur. Hubungi tim IT/HR.";
        } elseif (empty($requester->position->job_description_name)) {
            $problems[] = "Jobdesk jabatan {$requester->position->name} belum diunggah. Hubungi tim IT/HR.";
        }
        if ($workflow = Workflow::problem('REQUEST_ASSET', $requester)) {
            $problems[] = $workflow;
        }

        return $problems;
    }
}
