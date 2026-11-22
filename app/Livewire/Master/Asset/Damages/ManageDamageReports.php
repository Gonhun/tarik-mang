<?php

namespace App\Livewire\Master\Asset\Damages;

use App\Models\Asset;
use App\Models\AssetDamageReport;
use App\Models\Jobsite;
use App\Models\User;
use App\Support\AssetDisposal;
use App\Support\WorkflowNotConfigured;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Laporan kerusakan aset.
 * Aktif (Under Repair / Awaiting Parts) -> aset "Repair".
 * Closed (selesai diperbaiki) / Cancelled -> aset kembali ke status sebelum laporan.
 * Beyond Economic Repair -> pengajuan disposal (perlu approval workflow DISPOSED).
 */
class ManageDamageReports extends Component
{
    use WithPagination, WithFileUploads;

    /** Nilai yang diizinkan database (CHECK constraint) dan ditampilkan di form. */
    public const STATUSES = [
        'Under Repair' => ['Sedang diperbaiki', 'warning'],
        'Awaiting Parts' => ['Menunggu suku cadang', 'info'],
        'Closed' => ['Selesai diperbaiki', 'success'],
        'Beyond Economic Repair' => ['Rusak berat → disposal', 'danger'],
        'Cancelled' => ['Dibatalkan', 'secondary'],
    ];
    public const OPEN_STATUSES = ['Reported', 'PendingAssessment', 'Under Repair', 'Awaiting Parts', 'AwaitingParts'];
    public const FINAL_STATUSES = ['Closed', 'Cancelled', 'Beyond Economic Repair', 'BeyondEconomicRepair', 'RepairComplete'];

    /** Status aset yang boleh dilaporkan rusak. */
    public const REPORTABLE_ASSET_STATUSES = ['Free', 'Used', 'Pre-Used', 'Repair', 'Demage'];

    protected $paginationTheme = 'bootstrap';

    // Filter daftar
    #[Url(as: 'q')]
    public $search = '';
    #[Url(as: 'status')]
    public $statusFilter = 'open';

    // Form
    public $isModalOpen = false;
    public $damage_report_id = null;
    public $from_jobsite_id = '';
    public $asset_id = null;
    public $searchAsset = '';
    public $reported_by_user_id;
    public $reporterSearch = '';
    public $report_date;
    public $damage_description = '';
    public $damage_status = 'Under Repair';
    public $assessment_notes, $repair_notes, $repair_cost, $repaired_date;
    public $photo1, $photo2, $other_document;
    public $existingPhoto1Path, $existingPhoto2Path, $existingOtherDocumentPath, $existingOtherDocumentName;

    public function updatingSearch() { $this->resetPage(); }
    public function updatingStatusFilter() { $this->resetPage(); }

    public function updatedFromJobsiteId()
    {
        $this->asset_id = null;
        $this->searchAsset = '';
    }

    public function chooseReporter($userId)
    {
        $this->reported_by_user_id = (int) $userId;
        $this->reporterSearch = '';
    }

    protected function rules()
    {
        return [
            'asset_id' => 'required|exists:assets,id',
            'reported_by_user_id' => 'required|exists:users,id',
            'report_date' => 'required|date|before_or_equal:today',
            'damage_description' => 'required|string|min:10|max:2000',
            'damage_status' => ['required', Rule::in(array_keys(self::STATUSES))],
            'assessment_notes' => 'nullable|string|max:2000',
            'repair_notes' => ($this->damage_status === 'Closed' ? 'required' : 'nullable') . '|string|max:2000',
            'repair_cost' => 'nullable|numeric|min:0',
            'repaired_date' => ($this->damage_status === 'Closed' ? 'required' : 'nullable') . '|date|after_or_equal:report_date',
            'photo1' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'photo2' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'other_document' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:5120',
        ];
    }

    protected function messages()
    {
        return [
            'asset_id.required' => 'Pilih aset yang rusak.',
            'damage_description.min' => 'Deskripsi kerusakan minimal 10 karakter.',
            'repair_notes.required' => 'Catatan perbaikan wajib diisi saat laporan ditutup.',
            'repaired_date.required' => 'Tanggal selesai perbaikan wajib diisi saat laporan ditutup.',
            'report_date.before_or_equal' => 'Tanggal lapor tidak boleh di masa depan.',
        ];
    }

    public function create()
    {
        $this->resetForm();
        $this->from_jobsite_id = (string) (Auth::user()->jobsite_id ?? '');
        $this->reported_by_user_id = Auth::id();
        $this->report_date = now()->format('Y-m-d');
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        $report = AssetDamageReport::with('asset')->findOrFail($id);
        if (in_array($report->damage_status, self::FINAL_STATUSES, true)) {
            $this->dispatch('swal:error', ['title' => 'Tidak bisa diubah', 'text' => "Laporan ini sudah berstatus {$report->damage_status}."]);
            return;
        }

        $this->resetForm();
        $this->damage_report_id = $report->id;
        $this->asset_id = $report->asset_id;
        $this->from_jobsite_id = (string) ($report->asset->jobsite_id ?? '');
        $this->reported_by_user_id = $report->reported_by_user_id;
        $this->report_date = optional($report->report_date)->format('Y-m-d');
        $this->damage_description = $report->damage_description;
        $this->damage_status = array_key_exists($report->damage_status, self::STATUSES) ? $report->damage_status : 'Under Repair';
        $this->assessment_notes = $report->assessment_notes;
        $this->repair_notes = $report->repair_notes;
        $this->repair_cost = $report->repair_cost;
        $this->repaired_date = optional($report->repaired_date)->format('Y-m-d');
        $this->existingPhoto1Path = $report->photo1_path;
        $this->existingPhoto2Path = $report->photo2_path;
        $this->existingOtherDocumentPath = $report->other_document_path;
        $this->existingOtherDocumentName = $report->other_document_name;
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
    }

    protected function resetForm(): void
    {
        $this->reset([
            'damage_report_id', 'from_jobsite_id', 'asset_id', 'searchAsset', 'reported_by_user_id', 'reporterSearch',
            'report_date', 'damage_description', 'damage_status', 'assessment_notes', 'repair_notes', 'repair_cost',
            'repaired_date', 'photo1', 'photo2', 'other_document',
            'existingPhoto1Path', 'existingPhoto2Path', 'existingOtherDocumentPath', 'existingOtherDocumentName',
        ]);
        $this->resetValidation();
    }

    public function store()
    {
        $this->validate();

        $stored = [];
        try {
            $result = DB::transaction(function () use (&$stored) {
                $asset = Asset::whereKey($this->asset_id)->lockForUpdate()->firstOrFail();
                $report = $this->damage_report_id ? AssetDamageReport::lockForUpdate()->findOrFail($this->damage_report_id) : null;

                if ($report && in_array($report->damage_status, self::FINAL_STATUSES, true)) {
                    throw new \RuntimeException('Laporan ini sudah final dan tidak bisa diubah.');
                }
                if (!$report) {
                    if (!in_array($asset->status, self::REPORTABLE_ASSET_STATUSES, true)) {
                        throw new \RuntimeException("Aset {$asset->asset_number} berstatus {$asset->status} dan tidak bisa dilaporkan rusak.");
                    }
                    if ($open = AssetDamageReport::where('asset_id', $asset->id)->whereIn('damage_status', self::OPEN_STATUSES)->first()) {
                        throw new \RuntimeException("Aset ini masih punya laporan kerusakan aktif (#{$open->id}). Perbarui laporan itu.");
                    }
                }

                $data = [
                    'asset_id' => $asset->id,
                    'reported_by_user_id' => $this->reported_by_user_id,
                    'report_date' => $this->report_date,
                    'damage_description' => trim($this->damage_description),
                    'damage_status' => $this->damage_status,
                    'assessment_notes' => $this->assessment_notes,
                    'repair_notes' => $this->repair_notes,
                    'repair_cost' => $this->repair_cost !== '' ? $this->repair_cost : null,
                    'repaired_date' => $this->repaired_date ?: null,
                    'updated_by_user_id' => Auth::id(),
                ];
                if (!$report) {
                    $data['created_by_user_id'] = Auth::id();
                    // Disimpan agar status aset bisa dipulihkan saat laporan ditutup / dibatalkan.
                    $data['asset_status_before_report'] = $asset->status === 'Repair' ? 'Free' : $asset->status;
                    $report = AssetDamageReport::create($data);
                } else {
                    $report->update($data);
                }

                foreach (['photo1' => ['photo1_path', 'photos'], 'photo2' => ['photo2_path', 'photos'], 'other_document' => ['other_document_path', 'documents']] as $prop => [$column, $folder]) {
                    if ($this->$prop) {
                        $path = $this->$prop->store("asset_damage_files/{$report->id}/{$folder}", 'public');
                        $stored[] = $path;
                        $update = [$column => $path];
                        if ($prop === 'other_document') {
                            $update['other_document_name'] = $this->other_document->getClientOriginalName();
                        }
                        $report->update($update);
                    }
                }

                return [$report, $this->applyAssetStatus($report, $asset)];
            });
        } catch (WorkflowNotConfigured $e) {
            foreach ($stored as $p) Storage::disk('public')->delete($p);
            $this->addError('damage_status', $e->getMessage());
            return;
        } catch (\RuntimeException $e) {
            foreach ($stored as $p) Storage::disk('public')->delete($p);
            $this->dispatch('swal:error', ['title' => 'Tidak bisa disimpan', 'text' => $e->getMessage()]);
            return;
        } catch (\Throwable $e) {
            foreach ($stored as $p) Storage::disk('public')->delete($p);
            Log::error('Simpan laporan kerusakan gagal: ' . $e->getMessage());
            $this->dispatch('swal:error', ['title' => 'Gagal', 'text' => 'Laporan gagal disimpan. Coba lagi atau hubungi Administrator.']);
            return;
        }

        [$report, $assetNote] = $result;
        $this->isModalOpen = false;
        $this->dispatch('swal:success', ['title' => 'Laporan tersimpan', 'text' => "Laporan #{$report->id}: {$assetNote}"]);
    }

    /**
     * Sesuaikan status aset dengan status laporan. Mengembalikan keterangan untuk notifikasi.
     */
    protected function applyAssetStatus(AssetDamageReport $report, Asset $asset): string
    {
        switch ($report->damage_status) {
            case 'Under Repair':
            case 'Awaiting Parts':
                $asset->update(['status' => 'Repair']);
                return "aset {$asset->asset_number} berstatus Repair.";

            case 'Closed':
            case 'Cancelled':
                $restore = $report->asset_status_before_report ?: ($asset->holder_user_id ? 'Used' : 'Free');
                if (in_array($restore, ['Repair', 'Demage'], true)) {
                    $restore = $asset->holder_user_id ? 'Used' : 'Free';
                }
                $asset->update(['status' => $restore]);
                return "aset {$asset->asset_number} kembali berstatus {$restore}.";

            case 'Beyond Economic Repair':
                // Workflow DISPOSED belum siap -> WorkflowNotConfigured, semua perubahan dibatalkan.
                $disposal = AssetDisposal::create($asset, Auth::user(),
                    "Rusak berat dari laporan kerusakan #{$report->id}: {$report->damage_description}",
                    'DAMAGED', ['previous_status' => 'Repair']);
                $asset->update(['status' => 'Pre Disposed']);
                if (AssetDisposal::finishIfAutoApproved($disposal)) {
                    return "disposal {$disposal->transaction_number} disetujui otomatis (Anda approver semua tahap); aset Disposed.";
                }
                return "pengajuan disposal {$disposal->transaction_number} dibuat dan menunggu approval.";
        }

        return '';
    }

    public function render()
    {
        $statusCounts = AssetDamageReport::select('damage_status', DB::raw('count(*) as total'))->groupBy('damage_status')->pluck('total', 'damage_status');

        $reports = AssetDamageReport::query()
            ->with(['asset:id,asset_number,serial_number,status,jobsite_id,asset_type_id,asset_series_id', 'asset.jobsite:id,code', 'asset.assetType:id,name', 'asset.assetSeries:id,name', 'reporter:id,name'])
            ->when($this->statusFilter === 'open', fn ($q) => $q->whereIn('damage_status', self::OPEN_STATUSES))
            ->when($this->statusFilter && $this->statusFilter !== 'open', fn ($q) => $q->where('damage_status', $this->statusFilter))
            ->when($this->search, function ($q) {
                $term = '%' . $this->search . '%';
                $q->where(fn ($w) => $w->where('damage_description', 'like', $term)
                    ->orWhereHas('asset', fn ($a) => $a->where('asset_number', 'like', $term)->orWhere('serial_number', 'like', $term))
                    ->orWhereHas('reporter', fn ($r) => $r->where('name', 'like', $term)));
            })
            ->orderByDesc('report_date')->orderByDesc('id')
            ->paginate(10);

        // Data form hanya dimuat saat modal terbuka (tidak disimpan di state komponen).
        $assetOptions = collect();
        $reporterOptions = collect();
        $selectedAsset = null;
        if ($this->isModalOpen) {
            $selectedAsset = $this->asset_id ? Asset::with(['assetType:id,name', 'assetSeries:id,name', 'holder:id,name', 'jobsite:id,code'])->find($this->asset_id) : null;
            if (!$this->damage_report_id && $this->from_jobsite_id) {
                $assetOptions = Asset::query()
                    ->where('jobsite_id', $this->from_jobsite_id)
                    ->whereIn('status', self::REPORTABLE_ASSET_STATUSES)
                    ->whereDoesntHave('damageReports', fn ($q) => $q->whereIn('damage_status', self::OPEN_STATUSES))
                    ->when($this->searchAsset, function ($q) {
                        $term = '%' . $this->searchAsset . '%';
                        $q->where(fn ($w) => $w->where('asset_number', 'like', $term)->orWhere('serial_number', 'like', $term)
                            ->orWhereHas('assetSeries', fn ($s) => $s->where('name', 'like', $term)));
                    })
                    ->with(['assetType:id,name', 'assetSeries:id,name', 'holder:id,name'])
                    ->orderBy('asset_number')->limit(50)
                    ->get(['id', 'asset_number', 'serial_number', 'status', 'asset_type_id', 'asset_series_id', 'holder_user_id']);
            }
            if (strlen(trim($this->reporterSearch)) >= 2) {
                $term = '%' . trim($this->reporterSearch) . '%';
                $reporterOptions = User::where(fn ($w) => $w->where('name', 'like', $term)->orWhere('nik', 'like', $term))
                    ->orderBy('name')->limit(8)->get(['id', 'name', 'nik']);
            }
        }

        return view('livewire.master.asset.damages.manage-damage-reports', [
            'damageReports' => $reports,
            'statusCounts' => $statusCounts,
            'openCount' => $statusCounts->only(self::OPEN_STATUSES)->sum(),
            'statuses' => self::STATUSES,
            'allJobsites' => $this->isModalOpen ? Jobsite::orderBy('code')->get(['id', 'code', 'name']) : collect(),
            'assetOptions' => $assetOptions,
            'selectedAsset' => $selectedAsset,
            'reporter' => $this->isModalOpen && $this->reported_by_user_id ? User::find($this->reported_by_user_id, ['id', 'name', 'nik']) : null,
            'reporterOptions' => $reporterOptions,
            'editingReport' => $this->isModalOpen && $this->damage_report_id
                ? AssetDamageReport::with(['creator:id,name', 'updater:id,name'])->find($this->damage_report_id)
                : null,
        ]);
    }
}
