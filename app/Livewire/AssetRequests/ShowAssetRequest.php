<?php

namespace App\Livewire\AssetRequests;

use App\Services\AssetRequestApprovalService;

use App\Mail\MailAssetRequest;
use App\Models\Jobsite;
use Exception;
use Livewire\Component;
use App\Models\AssetRequest;
use App\Models\AssetRequestItem;
use App\Models\AssetRequestApproval;
use App\Models\Asset;
use App\Models\AssetTransaction;
use App\Models\AssetTransactionApproval;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\WithFileUploads;
use Mail;

class ShowAssetRequest extends Component
{
    use WithFileUploads;

    public AssetRequest $assetRequest;
    public $isAdmin;

    // Properti untuk modal approval GLOBAL
    public $isOverallApprovalModalOpen = false;
    public $isUpdatePIcModalOpen = false;
    public $currentOverallApprovalId = null;
    public $overallApprovalAction = '';
    public $overallApprovalNotes = '';

    // Properti untuk keputusan IT per item
    public $itemDecisions = [];
    public $availableStockAssets = [];

    public $procurementData = [];

    // Properti untuk modal input PR/Akunting
    public $isProcurementModalOpen = false;
    public $input_pr_number;
    public $input_accounting_number;
    public $input_procurement_notes;
    public $nik_option = [], $site_option = [];
    public $idToUpdate, $picToUpdate, $siteToUpdate;

    protected $listeners = ['refreshRequest' => '$refresh'];

    /**
     * Mount komponen dan muat semua data relasi yang diperlukan.
     */
    public function mount(AssetRequest $assetRequest)
    {
        abort_unless(Auth::user()->can('view-asset-request', $assetRequest), 403);
        $this->isAdmin = Auth::user()->hasRole(['Administrator IT', 'Administrator']);
        $this->assetRequest = $assetRequest->load([
            'requester.jobsite',
            'jobsite',
            'items.assetType',
            'items.assetBrand',
            'items.assetSeries',
            'items.assignedAsset.jobsite',
            'items.assignedAsset',
            'approvals.approver',
            'approvals.actionBy'
        ]);

        // Inisialisasi state untuk form keputusan IT
        foreach ($this->assetRequest->items as $item) {
            $this->itemDecisions[$item->id] = [
                'fulfillment_type' => $item->fulfillment_type ?? 'new',
                'assigned_asset_id' => $item->assigned_asset_id,
                'notes' => $item->it_approval_notes,
            ];
            $this->procurementData[$item->id] = [
                'pr_number' => $item->pr_number,
                'accounting_number' => $item->accounting_number,
                'po_number' => $item->po_number, // <-- Tambahkan ini
                'procurement_notes' => $item->procurement_notes,
            ];
            $this->availableStockAssets[$item->id] = collect();
            if ($item->fulfillment_type && str_contains($item->fulfillment_type, 'STOCK')) {
                $this->loadStockAssetsForItem($item->id);
            }
        }
        $this->initializeItemDecisions();
    }

    /**
     * Ambil approval milik pengajuan ini saja (ID dari browser tidak boleh menunjuk pengajuan lain).
     */
    protected function findOwnApproval($approvalId, array $with = []): ?AssetRequestApproval
    {
        return AssetRequestApproval::with($with)
            ->where('asset_request_id', $this->assetRequest->id)
            ->find($approvalId);
    }

    public function openModalUpdatePic($id)
    {
        abort_unless($this->isAdmin && $this->findOwnApproval($id), 403, 'Aksi tidak diizinkan.');
        $this->idToUpdate = $id;
        $this->isUpdatePIcModalOpen = true;
        $this->site_option = Jobsite::pluck('id', 'code')->toArray();
        $this->dispatch('open-modal', true);
    }

    public function closeModalUpdatePic()
    {
        $this->isUpdatePIcModalOpen = false;
        $this->reset('picToUpdate', 'siteToUpdate');
    }

    public function updatedSiteToUpdate()
    {
        $this->dispatch('reinit-pic-select');
    }

    public function initializeItemDecisions()
    {
        foreach ($this->assetRequest->items as $item) {
            // Cek ketersediaan stok untuk item ini
            $stock = $this->findAvailableStock($item);
            $hasStock = $stock->isNotEmpty();

            // Inisialisasi state untuk form keputusan IT
            $this->itemDecisions[$item->id] = [
                'fulfillment_type' => $item->fulfillment_type ?? ($hasStock ? 'stock' : 'new'), // <-- Default ke 'stock' jika ada
                'assigned_asset_id' => $item->assigned_asset_id,
                'notes' => $item->it_approval_notes,
                'has_stock' => $hasStock, // <-- Simpan informasi ketersediaan stok
            ];

            $this->availableStockAssets[$item->id] = $stock;
        }
    }

    /**
     * Lifecycle Hook saat tipe pemenuhan per item diubah oleh IT.
     */
    public function updatedItemDecisions($value, $key)
    {
        $parts = explode('.', $key);
        $itemId = $parts[0];
        $property = $parts[1];

        if ($property === 'fulfillment_type' && $value === 'stock') {
            $this->loadStockAssetsForItem($itemId);
            // } elseif ($property === 'fulfillment_type' && $value === 'new') {
            //     $this->availableStockAssets[$itemId] = collect();
            //     $this->itemDecisions[$itemId]['assigned_asset_id'] = null;
        }
    }
    protected function findAvailableStock(AssetRequestItem $item)
    {
        // Aset Free bertipe sama yang belum dipilih item pengajuan lain yang masih berjalan.
        // Urutan: seri sama dulu, lalu site pemohon dulu. Dibatasi agar halaman tetap ringan.
        $reserved = AssetRequestItem::whereNotNull('assigned_asset_id')
            ->where('id', '!=', $item->id)
            ->whereHas('assetRequest', fn ($q) => $q->whereNotIn('status', ['Completed', 'Close', 'Rejected']))
            ->pluck('assigned_asset_id');

        return Asset::where('status', 'Free')
            ->where('asset_type_id', $item->asset_type_id)
            ->whereNotIn('id', $reserved)
            ->with(['jobsite:id,code,name', 'assetSeries:id,name'])
            ->orderByRaw('case when asset_series_id = ? then 0 else 1 end', [(int) $item->asset_series_id])
            ->orderByRaw('case when jobsite_id = ? then 0 else 1 end', [(int) $this->assetRequest->jobsite_id])
            ->orderBy('asset_number')
            ->limit(30)
            ->get(['id', 'asset_number', 'serial_number', 'jobsite_id', 'asset_series_id']);
    }

    protected function loadStockAssetsForItem($itemId)
    {
        $requestItem = $this->assetRequest->items->find($itemId);
        if ($requestItem) {
            $this->availableStockAssets[$itemId] = $this->findAvailableStock($requestItem);
        }
    }
    public function SendWa($approvalId)
    {
        // Data penerima & isi diambil dari server, bukan dari browser.
        $approval = $this->findOwnApproval($approvalId, ['approver.karyawans']);
        abort_unless($approval && $approval->status === 'Pending' && $approval->approver, 403, 'Aksi tidak diizinkan.');

        $email = $approval->approver->karyawans->Email ?? null;
        if (!$email) {
            $this->dispatch('swal:error', [
                'title' => 'Error!',
                'text' => "Email approver tidak ditemukan.",
            ]);
            return;
        }

        $user = ['name' => $approval->approver->name];
        $data = $this->assetRequest->loadMissing('requester')->toArray();

        $messageBody = "Halo *{$user['name']}* \n";
        $messageBody .= "Ada pengajuan yang perlu anda action di AMS\n";
        $messageBody .= "No Pengajuan : *{$data['request_number']}*\n";
        $messageBody .= "User Request : *{$data['requester']['name']}*\n";
        $messageBody .= "Pesan ini dilakukan Follow Up melalui ams.binasaranasukses.co.id\n";
        // $isSuccess = sendMessageWithWhatsApp($user['whatsapp'], $messageBody);
        $draftData = [
            'type' => 'OUTSTANDING_APPROVER',
            'approver' => $user['name'],
            'draft' => $data
        ];
        $isSuccess = Mail::Mailer('smtp2')->to($email)->send(new MailAssetRequest($draftData));
        // dd($isSuccess);


        // $isSuccess = sendMessageWithWhatsAppV2(
        //     $user['whatsapp'],
        //     $user['name'],
        //     '9b3f1404-1315-4963-9525-9c6914fc8e35', // message template ID (contoh)
        //     [
        //         ['key' => '1', "value_text" => $user['name']],
        //         ['key' => '2', "value_text" => $data['request_number']],
        //         ['key' => '3', "value_text" => $data['requester']['name']],
        //         ['key' => '4', "value_text" => "Follow Up"],
        //         ['key' => '5', "value_text" => "ams.binasaranasukses.co.id/asset-requests/show/" . $data['id']],
        //     ] // body params untuk template
        // );
        if ($isSuccess) {
            $this->dispatch('swal:success', [
                'title' => 'Berhasil!',
                'text' => "Pesan berhasil dikirim ke '{$user['name']}'",
            ]);
        } else {
            $this->dispatch('swal:error', [
                'title' => 'Error!',
                'text' => "Gagal Mengirim Pesan Follow Up!",
            ]);
        }

    }
    // --- LOGIKA UNTUK VALIDASI IT PER ITEM ---
    public function saveItemItDecision($itemId)
    {
        abort_unless($this->isCurrentUserTheItPic(), 403, 'Aksi tidak diizinkan.');

        $item = AssetRequestItem::find($itemId);
        $decision = $this->itemDecisions[$itemId];

        $this->validate([
            "itemDecisions.{$itemId}.fulfillment_type" => 'required|in:new,stock',
            "itemDecisions.{$itemId}.selected_stock_asset_id" => Rule::requiredIf($decision['fulfillment_type'] == 'stock'),
            // Catatan WAJIB jika memilih 'Beli Baru' PADAHAL stok tersedia
            "itemDecisions.{$itemId}.notes" => Rule::requiredIf(
                $decision['fulfillment_type'] === 'new' && $decision['has_stock']
            ),
        ], [
            'itemDecisions.*.selected_stock_asset_id.required' => 'Aset stok wajib dipilih.',
            'itemDecisions.*.notes.required' => 'Alasan wajib diisi jika memilih "Beli Baru" padahal stok tersedia.',
        ]);

        $fulfillmentType = $decision['fulfillment_type'];
        $assignedAssetId = null;

        if ($item->asset_request_id != $this->assetRequest->id || $item->it_approval_status !== 'Pending') {
            $this->dispatch('swal:error', ['title' => 'Tidak bisa disimpan', 'text' => 'Item ini sudah diputuskan atau bukan bagian pengajuan ini.']);
            return;
        }

        if ($fulfillmentType === 'stock') {
            $stockAsset = Asset::find($decision['selected_stock_asset_id']);
            $alreadyReserved = $stockAsset && AssetRequestItem::where('assigned_asset_id', $stockAsset->id)
                ->where('id', '!=', $item->id)
                ->whereHas('assetRequest', fn ($q) => $q->whereNotIn('status', ['Completed', 'Close', 'Rejected']))
                ->exists();
            if (!$stockAsset || $stockAsset->status !== 'Free' || $alreadyReserved) {
                $this->addError("itemDecisions.{$itemId}.selected_stock_asset_id", 'Aset ini sudah tidak tersedia (bukan Free atau sudah dipilih pengajuan lain). Pilih aset lain.');
                $this->loadStockAssetsForItem($itemId);
                return;
            }
            $assignedAssetId = $stockAsset->id;
            $fulfillmentType = ($stockAsset->jobsite_id == $this->assetRequest->jobsite_id) ? 'STOCK_LOCAL' : 'STOCK_MUTATION';
            $AssetApproval = AssetRequestApproval::where('asset_request_id', $item->asset_request_id)
                ->whereIn('approver_user_id', ['1011', '1014', '911']);
            $AssetApproval->delete();
        } else {
            $fulfillmentType = 'NEW_PURCHASE';
        }

        $item->update([
            'it_approval_status' => 'Approved',
            'it_approver_id' => Auth::id(),
            'it_approval_at' => now(),
            'it_approval_notes' => $decision['notes'],
            'fulfillment_type' => $fulfillmentType,
            'assigned_asset_id' => $assignedAssetId,
        ]);
        // Lanjut ke tahap persetujuan HANYA bila semua item sudah divalidasi IT.
        $remaining = AssetRequestItem::where('asset_request_id', $item->asset_request_id)->where('it_approval_status', 'Pending')->count();
        $autoCompleted = false;
        if ($remaining === 0) {
            AssetRequest::find($item->asset_request_id)->update(['status' => 'Pending Approval']);
            $this->assetRequest->refresh();
            // Semua tahap sudah disetujui otomatis (pemohon = approver) -> langsung proses.
            if ($this->assetRequest->approvals()->exists() && !$this->assetRequest->approvals()->where('status', '!=', 'Approved')->exists()) {
                try {
                    $this->processFullyApprovedRequest();
                    $autoCompleted = true;
                } catch (\App\Support\WorkflowNotConfigured $e) {
                    $this->dispatch('swal:error', ['title' => 'Workflow belum siap', 'text' => $e->getMessage()]);
                }
            }
        }
        $this->assetRequest->refresh();
        $this->dispatch('swal:success', [
            'title' => 'Keputusan tersimpan',
            'text' => $autoCompleted
                ? 'Semua item divalidasi dan seluruh tahap sudah disetujui otomatis. Pengajuan langsung diproses.'
                : ($remaining === 0
                ? 'Semua item sudah divalidasi. Pengajuan lanjut ke tahap persetujuan.'
                : "Masih ada {$remaining} item yang perlu divalidasi."),
        ]);

    }

    public function finalizeItStage()
    {
        abort_unless($this->isCurrentUserTheItPic(), 403, 'Aksi tidak diizinkan.');

        $this->assetRequest->load('items.assignedAsset.jobsite');

        $pendingItemsCount = $this->assetRequest->items()->where('it_approval_status', 'Pending')->count();
        if ($pendingItemsCount > 0) {
            session()->flash('error', "Masih ada {$pendingItemsCount} item yang menunggu validasi Anda.");
            return;
        }

        $mutationItems = $this->assetRequest->items->where('fulfillment_type', 'STOCK_MUTATION');
        $newPurchaseItems = $this->assetRequest->items->where('fulfillment_type', 'NEW_PURCHASE');
        $localStockItems = $this->assetRequest->items->where('fulfillment_type', 'STOCK_LOCAL');

        try {
        DB::transaction(function () use ($mutationItems, $newPurchaseItems, $localStockItems) {

            // ================================================================
            // PROSES UNTUK ITEM YANG MEMERLUKAN MUTASI
            // ================================================================
            if ($mutationItems->isNotEmpty()) {
                // Kelompokkan item mutasi berdasarkan jobsite asalnya
                $itemsGroupedByOrigin = $mutationItems->groupBy('assignedAsset.jobsite_id');

                foreach ($itemsGroupedByOrigin as $originJobsiteId => $items) {
                    // Buat SATU transaksi mutasi untuk setiap jobsite asal
                    $mutationTransaction = AssetTransaction::create([
                        'transaction_number' => $this->generateTransactionNumber('MT'), // Helper dengan prefix 'MT'
                        'transaction_type' => 'MUTATION',
                        'transaction_date' => now(),
                        'from_jobsite_id' => $originJobsiteId,
                        'to_jobsite_id' => $this->assetRequest->jobsite_id, // Jobsite tujuan pemohon
                        'notes' => "Mutasi aset untuk memenuhi Pengajuan Aset No: {$this->assetRequest->request_number}",
                        'status' => 'Pending Approval', // Status awal mutasi: menunggu pelepasan dari site asal
                        'created_by_user_id' => $this->assetRequest->approvals->where('approver_role_name', 'Staff IT')->first()?->approver_id,
                    ]);

                    // Tambahkan semua item ke dalam transaksi mutasi ini
                    foreach ($items as $item) {

                        $mutationTransaction->items()->create([
                            'asset_id' => $item->assigned_asset_id,
                            'asset_request_item_id' => $item->id,
                        ]);
                        // Update status aset fisik menjadi 'Pending Mutation'
                        $item->assignedAsset->update(['status' => 'Pending Mutation']);
                    }
                    $this->generateMutationApprovalSteps($mutationTransaction);
                    // Kirim notifikasi ke PIC jobsite asal untuk melakukan pelepasan aset
                    $originJobsite = \App\Models\Jobsite::find($originJobsiteId);
                    $picUser = $originJobsite?->picUser; // pastikan relasi picUser ada di model Jobsite
                    if ($picUser) {
                        $msg = "Halo {$picUser->name},\n\nTerdapat permintaan mutasi aset dari Jobsite *{$this->assetRequest->jobsite->code}* untuk memenuhi pengajuan aset No. *{$this->assetRequest->request_number}*.\n\nMohon untuk melakukan validasi dan proses pelepasan aset sesuai permintaan tersebut. Terima kasih.";
                        sendMessageWithWhatsApp($picUser->whatsapp, $msg);
                    }
                }
            }
            $nextStatus = 'Completed'; // Asumsi selesai jika tidak ada pembelian baru

            if ($newPurchaseItems->isNotEmpty()) {
                // Jika ada item yang harus dibeli baru, alur berlanjut ke proses PR/Accounting/PO
                $nextStatus = 'Pending PR & Accounting Number';
            } elseif ($mutationItems->isNotEmpty()) {
                // Jika tidak ada beli baru tapi ada mutasi, status bisa jadi menunggu mutasi selesai
                $nextStatus = 'Pending Asset Mutation';
            }

            // Update status pengajuan utama
            $this->assetRequest->update(['status' => $nextStatus]);
        });
        } catch (\App\Support\WorkflowNotConfigured $e) {
            // Workflow/approver belum lengkap: semua perubahan dibatalkan, beri tahu user.
            $this->dispatch('swal:error', ['title' => 'Workflow belum siap', 'text' => $e->getMessage()]);
            return;
        }
        $this->dispatch('swal:success', [
            'title' => 'Berhasil!',
            'text' => "Validasi IT selesai. Pengajuan dilanjutkan ke tahap berikutnya.",
        ]);

        $this->assetRequest->refresh();
        // TODO: Kirim Notifikasi ke Approver Berikutnya
    }
    protected function generateMutationApprovalSteps(AssetTransaction $transaction)
    {
        // Approval mutasi dari workflow MUTATION, approver dicari di site asal.
        // Dulu approver di-hardcode dan diisi null bila tidak ditemukan, sehingga transaksi tertahan tanpa approver.
        $plan = \App\Support\Workflow::plan('MUTATION', Auth::user()->position_id, Auth::user()->jobsite_id, $transaction->from_jobsite_id);

        // Tahap yang approvernya = pembuat mutasi langsung disetujui otomatis.
        $transaction->approvals()->createMany(\App\Support\Workflow::approvalRows($plan, Auth::id()));
        \App\Support\TransactionApprovalRepair::finishIfFullyApproved($transaction);

        $first = \App\Support\Workflow::firstPendingUser($plan, Auth::id());
        if ($first && $first->whatsapp) {
            $message = "Halo {$first->name},\n\nTerdapat permintaan mutasi aset {$transaction->transaction_number} yang memerlukan persetujuan Anda.\n"
                . 'Buka: ' . route('asset.mutasi-status', $transaction->id);
            DB::afterCommit(function () use ($first, $message) {
                try {
                    sendMessageWithWhatsApp($first->whatsapp, $message);
                } catch (\Throwable $e) {
                    Log::warning('WA approver mutasi gagal: ' . $e->getMessage());
                }
            });
        }
    }

    protected function generateTransactionNumber($typePrefix = 'TRX')
    {
        return app(AssetRequestApprovalService::class)->generateTransactionNumber($this->assetRequest, $typePrefix);
    }
    // --- LOGIKA UNTUK APPROVAL GLOBAL (SETELAH IT) ---
    public function openOverallApprovalModal($approvalId, $action)
    {
        $approval = $this->findOwnApproval($approvalId);
        abort_unless(in_array($action, ['approve', 'reject'], true), 400);
        abort_unless($this->canApproveOverallStep($approval), 403, 'Aksi tidak diizinkan.');

        $this->currentOverallApprovalId = $approvalId;
        $this->overallApprovalAction = $action;
        $this->isOverallApprovalModalOpen = true;
    }

    public function closeOverallApprovalModal()
    {
        $this->isOverallApprovalModalOpen = false;
        $this->reset('overallApprovalNotes');
    }

    public function submitOverallApproval()
    {
        $approval = $this->findOwnApproval($this->currentOverallApprovalId, ['assetRequest.items', 'approver']);
        abort_unless(in_array($this->overallApprovalAction, ['approve', 'reject'], true), 400);
        abort_unless($this->canApproveOverallStep($approval), 403, 'Aksi tidak diizinkan.');
        $this->validate(['overallApprovalNotes' => ($this->overallApprovalAction === 'reject' ? 'required|string|min:10' : 'nullable|string')]);

        try {
            app(AssetRequestApprovalService::class)->decide($approval, Auth::user(), $this->overallApprovalAction, $this->overallApprovalNotes);
        } catch (\App\Support\WorkflowNotConfigured $e) {
            // Workflow/approver belum lengkap: semua perubahan dibatalkan, beri tahu user.
            $this->dispatch('swal:error', ['title' => 'Workflow belum siap', 'text' => $e->getMessage()]);
            return;
        }
        $this->dispatch('swal:success', [
            'title' => 'Berhasil!',
            'text' => "Status pengajuan berhasil diperbarui",
        ]);

        $this->closeOverallApprovalModal();
        $this->assetRequest->refresh();
    }

    /**
     * Semua tahap persetujuan selesai: buat mutasi untuk item stok site lain, lalu tentukan status berikutnya
     * (PR & akunting bila ada pembelian baru, menunggu mutasi, atau selesai).
     */
    protected function processFullyApprovedRequest(): void
    {
        app(AssetRequestApprovalService::class)->processFullyApproved($this->assetRequest, Auth::user());
        $this->assetRequest->refresh();
    }

    /**
     * Boleh memutuskan tahap ini bila: pengajuan sedang "Pending Approval", tahap masih Pending,
     * semua tahap sebelumnya sudah selesai, dan user adalah approver tahap ini (admin boleh mewakili).
     */
    public function canApproveOverallStep($approval): bool
    {
        if (!$approval || (int) $approval->asset_request_id !== (int) $this->assetRequest->id) {
            return false;
        }

        return app(AssetRequestApprovalService::class)->canDecide($approval, Auth::user());
    }

    public function isCurrentUserTheItPic(): bool
    {
        $user = Auth::user();
        if (!$user)
            return false;

        // Cari tahap approval pertama, yang seharusnya adalah IT Site
        // Cek: user adalah approver di step 1 ATAU user hanya ada satu di jobsite sebagai IT
        $firstApprovalStep = $this->assetRequest
            ->approvals()
            ->where('step_order', 1)
            ->first();
        $secondApprovalStep = User::where('department_id', config('app.it_department_id'))->where('jobsite_id', $this->assetRequest->jobsite_id)->get();
        // Jika tidak ada approval step ke-1 (null), coba alternatif: validasi user IT pada jobsite ini

        // User adalah PIC IT jika:
        // 1. Ada tahap approval pertama
        // 2. ID user sama dengan approver_user_id di tahap pertama
        // 3. (Opsional) Jabatan user sesuai dengan peran di tahap pertama
        return
            ($firstApprovalStep && $user->id === (int) $firstApprovalStep->approver_user_id)
            ||
            ($secondApprovalStep->contains('id', $user->id));
    }

    public function submitUpdatePic()
    {
        abort_unless($this->isAdmin, 403, 'Aksi tidak diizinkan.');
        $this->validate(['picToUpdate' => 'required|exists:users,id']);

        try {
            $approval = $this->findOwnApproval($this->idToUpdate);
            if (!$approval || $approval->status !== 'Pending') {
                $this->dispatch('error', [
                    'title' => 'Error!',
                    'text' => "Approval step tidak ditemukan.",
                ]);
                return;
            }

            DB::beginTransaction();
            $approval->update(['approver_user_id' => $this->picToUpdate]);
            DB::commit();
            $this->closeModalUpdatePic();
            $this->dispatch('swal:success', [
                'title' => 'Berhasil!',
                'text' => "PIC berhasil diubah.",
            ]);
            $this->assetRequest->refresh();

        } catch (Exception $e) {
            DB::rollBack();
            $this->dispatch('swal:error', [
                'title' => 'Error!',
                'text' => "Gagal mengubah PIC.",
            ]);
            Log::error("Error updating PIC for approval ID {$this->idToUpdate}: " . $e->getMessage());
        }
    }

    public function render()
    {
        $this->nik_option = User::where('jobsite_id', $this->siteToUpdate)->get(['id', 'nik', 'name'])->toArray();
        // Pengajuan yang menunggu approval tapi tahapnya kosong / tanpa approver (data lama) -> beri tahu user.
        $approvalProblem = null;
        if ($this->assetRequest->status === 'Pending Approval') {
            $pending = $this->assetRequest->approvals->where('status', 'Pending');
            if ($this->assetRequest->approvals->isEmpty()) {
                $approvalProblem = 'Pengajuan ini tidak memiliki tahapan approval. ' . \App\Support\Workflow::ADMIN_HINT;
            } elseif ($orphan = $pending->whereNull('approver_user_id')->sortBy('step_order')->first()) {
                $approvalProblem = "Tahap {$orphan->step_order} ({$orphan->approver_role_name}) belum punya approver. " . \App\Support\Workflow::ADMIN_HINT;
            }
        }

        $this->assetRequest->loadMissing(['requester.department:id,code,name', 'requester.position:id,name']);
        $approvals = $this->assetRequest->approvals->sortBy('step_order')->values();
        $currentApproval = $this->assetRequest->status === 'Pending Approval' ? $approvals->firstWhere('status', 'Pending') : null;

        return view('livewire.asset-requests.show-asset-request', [
            'approvalProblem' => $approvalProblem,
            'approvalsOrdered' => $approvals,
            'currentApproval' => $currentApproval,
            'myTurnApproval' => $currentApproval && $this->canApproveOverallStep($currentApproval) ? $currentApproval : null,
            'isItPic' => $this->isCurrentUserTheItPic(),
            'itPendingCount' => $this->assetRequest->items->where('it_approval_status', 'Pending')->count(),
            'stages' => \App\Livewire\AssetRequests\Dashboard::STAGES,
        ]);
    }
}