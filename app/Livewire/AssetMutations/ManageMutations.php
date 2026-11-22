<?php

namespace App\Livewire\AssetMutations;

use App\Models\Asset;
use App\Models\AssetTransaction;
use App\Models\CostCenter;
use App\Models\Jobsite;
use App\Support\Workflow;
use App\Support\WorkflowNotConfigured;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Mutasi aset antar site.
 * Alur: Pending Approval (workflow MUTATION) -> Pending Release (site asal kirim + bukti) -> Delivery -> Completed (site tujuan terima).
 */
class ManageMutations extends Component
{
    use WithPagination, WithFileUploads;

    /** Status aset yang boleh dimutasi. */
    public const MUTABLE_STATUSES = ['Free', 'Used'];

    public const STATUS_COLORS = [
        'Pending Approval' => 'warning', 'Pending Release' => 'info', 'Delivery' => 'primary',
        'Completed' => 'success', 'Rejected' => 'danger', 'Cancelled' => 'secondary',
    ];

    protected $paginationTheme = 'bootstrap';

    // Filter daftar
    #[Url(as: 'q')]
    public $search = '';
    #[Url(as: 'status')]
    public $statusFilter = '';
    #[Url(as: 'scope')]
    public $scope = 'mine'; // mine = site saya (asal/tujuan) | all

    // Form permintaan
    public $isModalOpen = false;
    public $from_jobsite_id = '';
    public $to_jobsite_id = '';
    public $transaction_date;
    public $notes = '';
    public $selected_assets = [];
    public $searchAsset = '';

    // Modal pelepasan (site asal)
    public $isReleaseModalOpen = false;
    public $releaseId = null;
    public $releaseDocument;

    public function mount()
    {
        $this->transaction_date = now()->format('Y-m-d');
    }

    public function updatingSearch() { $this->resetPage(); }
    public function updatingStatusFilter() { $this->resetPage(); }
    public function updatingScope() { $this->resetPage(); }

    public function updatedFromJobsiteId()
    {
        // Ganti site asal -> pilihan aset lama tidak berlaku lagi.
        $this->selected_assets = [];
        $this->searchAsset = '';
        if ($this->to_jobsite_id == $this->from_jobsite_id) {
            $this->to_jobsite_id = '';
        }
    }

    protected function canManageSite(?int $jobsiteId): bool
    {
        $user = Auth::user();

        return (int) $user->jobsite_id === (int) $jobsiteId
            || $user->hasRole(['Administrator IT', 'Administrator']);
    }

    // ------------------------------------------------------------------
    // Buat permintaan mutasi
    // ------------------------------------------------------------------

    public function create()
    {
        // Cek workflow lebih dulu supaya user tidak mengisi form yang pasti gagal.
        if ($problem = Workflow::problem('MUTATION', Auth::user())) {
            $this->dispatch('swal:error', ['title' => 'Workflow belum siap', 'text' => $problem]);
            return;
        }
        $this->resetForm();
        $this->from_jobsite_id = (string) (Auth::user()->jobsite_id ?? '');
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
    }

    protected function resetForm(): void
    {
        $this->reset(['from_jobsite_id', 'to_jobsite_id', 'notes', 'selected_assets', 'searchAsset']);
        $this->transaction_date = now()->format('Y-m-d');
        $this->resetValidation();
    }

    public function store()
    {
        $this->selected_assets = array_values(array_unique(array_map('intval', (array) $this->selected_assets)));

        $this->validate([
            'from_jobsite_id' => 'required|exists:jobsites,id',
            'to_jobsite_id' => 'required|exists:jobsites,id|different:from_jobsite_id',
            'transaction_date' => 'required|date',
            'notes' => 'required|string|min:10|max:1000',
            'selected_assets' => 'required|array|min:1|max:200',
        ], [
            'to_jobsite_id.different' => 'Site tujuan harus berbeda dari site asal.',
            'notes.min' => 'Alasan mutasi minimal 10 karakter.',
            'selected_assets.required' => 'Pilih minimal satu aset.',
        ]);

        foreach (['to_jobsite_id' => 'tujuan', 'from_jobsite_id' => 'asal'] as $field => $label) {
            if (!CostCenter::where('jobsite_id', $this->$field)->exists()) {
                $this->addError($field, "Site {$label} belum memiliki Cost Center.");
                return;
            }
        }

        try {
            $transaction = DB::transaction(function () {
                // Aset harus benar-benar dari site asal dan berstatus boleh dimutasi (cek di server, bukan hanya di form).
                $assets = Asset::whereIn('id', $this->selected_assets)->lockForUpdate()->get();
                $invalid = $assets->filter(fn ($a) => (int) $a->jobsite_id !== (int) $this->from_jobsite_id
                    || !in_array($a->status, self::MUTABLE_STATUSES, true));
                if ($assets->count() !== count($this->selected_assets) || $invalid->isNotEmpty()) {
                    throw new \RuntimeException('Sebagian aset tidak lagi tersedia di site asal: '
                        . $invalid->pluck('asset_number')->implode(', ') . '. Muat ulang pilihan aset.');
                }

                $transaction = AssetTransaction::create([
                    'transaction_number' => generateMutationNumber('MT'),
                    'transaction_date' => $this->transaction_date,
                    'transaction_type' => 'MUTATION',
                    'from_jobsite_id' => $this->from_jobsite_id,
                    'to_jobsite_id' => $this->to_jobsite_id,
                    'notes' => trim($this->notes),
                    'status' => 'Pending Approval',
                    'created_by_user_id' => Auth::id(),
                ]);

                foreach ($assets as $asset) {
                    $transaction->items()->create([
                        'asset_id' => $asset->id,
                        // Disimpan agar status & pemegang bisa dipulihkan bila mutasi ditolak.
                        'metadata' => ['previous_status' => $asset->status, 'previous_holder_user_id' => $asset->holder_user_id],
                    ]);
                    $asset->update(['status' => 'Pending Mutation']);
                }

                $this->generateApproval($transaction);

                return $transaction;
            });
        } catch (WorkflowNotConfigured $e) {
            $this->dispatch('swal:error', ['title' => 'Workflow belum siap', 'text' => $e->getMessage()]);
            return;
        } catch (\RuntimeException $e) {
            $this->addError('selected_assets', $e->getMessage());
            return;
        } catch (\Throwable $e) {
            Log::error('Mutasi gagal dibuat: ' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
            $this->dispatch('swal:error', ['title' => 'Gagal', 'text' => 'Permintaan mutasi gagal disimpan. Coba lagi atau hubungi Administrator.']);
            return;
        }

        $this->isModalOpen = false;
        $this->resetForm();
        $transaction->refresh();
        $auto = $transaction->approvals()->where('notes', Workflow::AUTO_NOTE)->count();
        $this->dispatch('swal:success', [
            'title' => 'Mutasi diajukan',
            'text' => "{$transaction->transaction_number} ({$transaction->items()->count()} aset) "
                . ($transaction->status === 'Pending Release' ? 'sudah disetujui dan menunggu pelepasan site asal.' : 'menunggu persetujuan.')
                . ($auto ? " {$auto} tahap disetujui otomatis karena Anda approvernya." : ''),
        ]);
    }

    protected function generateApproval(AssetTransaction $transaction): void
    {
        // Workflow belum diatur / approver tidak lengkap -> exception, transaksi di-rollback.
        $plan = Workflow::plan('MUTATION', Auth::user()->position_id, Auth::user()->jobsite_id, $transaction->from_jobsite_id);

        // Tahap yang approvernya = pengaju langsung disetujui otomatis.
        $transaction->approvals()->createMany(Workflow::approvalRows($plan, Auth::id()));
        \App\Support\TransactionApprovalRepair::finishIfFullyApproved($transaction);

        $this->notifyAfterCommit(Workflow::firstPendingUser($plan, Auth::id()),
            "Ada pengajuan mutasi aset #{$transaction->transaction_number} yang membutuhkan persetujuan Anda.\n"
            . 'Buka: ' . route('asset.mutasi-status', $transaction->id));
    }

    protected function notifyAfterCommit($user, string $text): void
    {
        if (!$user || !$user->whatsapp) {
            return;
        }
        $message = "Halo {$user->name},\n\n{$text}";
        DB::afterCommit(function () use ($user, $message) {
            try {
                sendMessageWithWhatsApp($user->whatsapp, $message);
            } catch (\Throwable $e) {
                Log::warning('WA mutasi gagal: ' . $e->getMessage());
            }
        });
    }

    // ------------------------------------------------------------------
    // Perbaikan approval mutasi yang tidak lengkap (admin)
    // ------------------------------------------------------------------

    public function repairApproval($transactionId)
    {
        $trx = AssetTransaction::where('transaction_type', 'MUTATION')->find($transactionId);
        if (!$trx) return;

        if (!\App\Support\TransactionApprovalRepair::canReapply($trx)) {
            // Sudah ada keputusan: ganti approver per tahap di halaman detail.
            return $this->redirectRoute('asset.mutasi-status', ['transaction' => $trx->id]);
        }

        try {
            $plan = \App\Support\TransactionApprovalRepair::applyWorkflow($trx, Auth::user());
        } catch (\RuntimeException $e) {
            $this->dispatch('swal:error', ['title' => 'Tidak bisa diperbaiki', 'text' => $e->getMessage()]);
            return;
        }
        $this->dispatch('swal:success', [
            'title' => 'Approval diperbarui',
            'text' => "{$trx->transaction_number}: " . count($plan) . ' tahap dari workflow saat ini.',
        ]);
    }

    // ------------------------------------------------------------------
    // Pelepasan oleh site asal (Pending Release -> Delivery)
    // ------------------------------------------------------------------

    public function openReleaseModal($transactionId)
    {
        $trx = AssetTransaction::where('transaction_type', 'MUTATION')->find($transactionId);
        if (!$trx || $trx->status !== 'Pending Release' || !$this->canManageSite($trx->from_jobsite_id)) {
            $this->dispatch('swal:error', ['title' => 'Tidak bisa dilepas', 'text' => 'Mutasi ini belum/tidak lagi menunggu pelepasan, atau Anda bukan dari site asal.']);
            return;
        }
        $this->releaseId = $trx->id;
        $this->reset('releaseDocument');
        $this->resetValidation();
        $this->isReleaseModalOpen = true;
    }

    public function closeReleaseModal()
    {
        $this->isReleaseModalOpen = false;
        $this->releaseId = null;
    }

    public function submitReleaseApproval()
    {
        $this->validate([
            'releaseDocument' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ], [
            'releaseDocument.required' => 'Bukti serah terima (surat jalan/resi/foto) wajib diunggah.',
            'releaseDocument.mimes' => 'Format file harus PDF, JPG, atau PNG.',
            'releaseDocument.max' => 'Ukuran file maksimal 5 MB.',
        ]);

        $path = null;
        try {
            $trx = DB::transaction(function () use (&$path) {
                $trx = AssetTransaction::with('items.asset')->lockForUpdate()->find($this->releaseId);
                if (!$trx || $trx->status !== 'Pending Release' || !$this->canManageSite($trx->from_jobsite_id)) {
                    throw new \RuntimeException('Mutasi ini sudah diproses orang lain atau Anda tidak berhak.');
                }

                $path = $this->releaseDocument->store("mutation_release_docs/{$trx->id}", 'public');
                $trx->update([
                    'status' => 'Delivery',
                    'document_path' => $path,
                    'document_name' => $this->releaseDocument->getClientOriginalName(),
                ]);
                foreach ($trx->items as $item) {
                    $item->asset?->update(['status' => 'Delivery']);
                }

                return $trx;
            });
        } catch (\Throwable $e) {
            if ($path) Storage::disk('public')->delete($path);
            $this->dispatch('swal:error', ['title' => 'Gagal', 'text' => $e instanceof \RuntimeException ? $e->getMessage() : 'Pelepasan gagal disimpan.']);
            return;
        }

        $this->closeReleaseModal();
        $this->dispatch('swal:success', ['title' => 'Aset dilepas', 'text' => "Mutasi {$trx->transaction_number} dalam pengiriman. Site tujuan perlu konfirmasi penerimaan."]);
    }

    // ------------------------------------------------------------------
    // Konfirmasi penerimaan oleh site tujuan (Delivery -> Completed)
    // ------------------------------------------------------------------

    public function confirmReceipt($transactionId)
    {
        try {
            $trx = DB::transaction(function () use ($transactionId) {
                $trx = AssetTransaction::with('items.asset')->where('transaction_type', 'MUTATION')->lockForUpdate()->find($transactionId);
                if (!$trx || $trx->status !== 'Delivery' || !$this->canManageSite($trx->to_jobsite_id)) {
                    throw new \RuntimeException('Mutasi ini belum dikirim, sudah diterima, atau Anda bukan dari site tujuan.');
                }

                $trx->update(['status' => 'Completed']);
                foreach ($trx->items as $item) {
                    $item->asset?->update([
                        'jobsite_id' => $trx->to_jobsite_id,
                        'status' => 'Free',
                        'holder_user_id' => null,
                    ]);
                }

                return $trx;
            });
        } catch (\Throwable $e) {
            $this->dispatch('swal:error', ['title' => 'Gagal', 'text' => $e instanceof \RuntimeException ? $e->getMessage() : 'Konfirmasi penerimaan gagal.']);
            return;
        }

        $this->dispatch('swal:success', ['title' => 'Aset diterima', 'text' => "Mutasi {$trx->transaction_number} selesai. Aset kini berstatus Free di site tujuan."]);
    }

    public function render()
    {
        $user = Auth::user();

        $base = AssetTransaction::where('transaction_type', 'MUTATION')
            ->when($this->scope === 'mine' && $user->jobsite_id, fn ($q) => $q->where(fn ($w) => $w
                ->where('from_jobsite_id', $user->jobsite_id)->orWhere('to_jobsite_id', $user->jobsite_id)));

        $statusCounts = (clone $base)->select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status');

        $mutations = (clone $base)
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->search, function ($q) {
                $term = '%' . $this->search . '%';
                $q->where(fn ($w) => $w->where('transaction_number', 'like', $term)
                    ->orWhere('notes', 'like', $term)
                    ->orWhereHas('items.asset', fn ($a) => $a->where('asset_number', 'like', $term)->orWhere('serial_number', 'like', $term)));
            })
            ->with([
                'fromJobsite:id,code,name', 'toJobsite:id,code,name', 'creator:id,name',
                'items.asset:id,asset_number',
                'approvals' => fn ($q) => $q->with('approver:id,name'),
            ])
            ->withCount('items')
            ->orderByDesc('transaction_date')->orderByDesc('id')
            ->paginate(10);

        // Pilihan aset hanya dimuat saat form terbuka (tidak disimpan di state komponen).
        $availableAssets = collect();
        if ($this->isModalOpen && $this->from_jobsite_id) {
            $availableAssets = Asset::query()
                ->where('jobsite_id', $this->from_jobsite_id)
                ->whereIn('status', self::MUTABLE_STATUSES)
                ->when($this->searchAsset, function ($q) {
                    $term = '%' . $this->searchAsset . '%';
                    $q->where(fn ($w) => $w->where('asset_number', 'like', $term)
                        ->orWhere('serial_number', 'like', $term)
                        ->orWhereHas('assetSeries', fn ($s) => $s->where('name', 'like', $term)));
                })
                ->with(['assetType:id,name', 'assetSeries:id,name', 'holder:id,name'])
                ->orderBy('asset_number')
                ->limit(100)
                ->get(['id', 'asset_number', 'serial_number', 'status', 'asset_type_id', 'asset_series_id', 'holder_user_id']);
        }

        return view('livewire.asset-mutations.manage-mutations', [
            'mutations' => $mutations,
            'statusCounts' => $statusCounts,
            'allJobsites' => Jobsite::orderBy('code')->get(['id', 'code', 'name']),
            'availableAssets' => $availableAssets,
            'selectedCount' => count((array) $this->selected_assets),
            'releaseTrx' => $this->isReleaseModalOpen && $this->releaseId ? AssetTransaction::withCount('items')->find($this->releaseId) : null,
            'statusColors' => self::STATUS_COLORS,
            'userJobsiteId' => $user->jobsite_id,
            'isAdmin' => $user->hasRole(['Administrator IT', 'Administrator']),
            'canRepair' => \App\Support\TransactionApprovalRepair::canManage($user),
        ]);
    }
}
