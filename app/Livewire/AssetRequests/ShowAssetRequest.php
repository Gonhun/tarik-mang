<?php

namespace App\Livewire\AssetRequests;

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

    public function openModalUpdatePic($id)
    {
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
        // Logika untuk mencari aset di stok yang cocok dengan item permintaan
        return Asset::where('status', 'Free')
            ->where('asset_type_id', $item->asset_type_id)
            ->with('jobsite:id,name')
            ->get();
    }

    protected function loadStockAssetsForItem($itemId)
    {
        $requestItem = $this->assetRequest->items->find($itemId);
        if ($requestItem) {
            $this->availableStockAssets[$itemId] = $this->findAvailableStock($requestItem);
        }
    }
    public function SendWa($user, $data)
    {

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
        $isSuccess = Mail::Mailer('smtp2')->to($user['karyawans']['Email'])->send(new MailAssetRequest($draftData));
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

        if ($fulfillmentType === 'stock') {
            $stockAsset = Asset::find($decision['selected_stock_asset_id']);
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
        $assetreq = AssetRequest::find($item->asset_request_id);
        $assetreq->update([
            'status' => 'Pending Approval'
        ]);
        $this->assetRequest->refresh();
        $this->dispatch('swal:success', [
            'title' => 'Berhasil!',
            'text' => "Keputusan untuk item #{$itemId} berhasil disimpan.",
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
        $this->dispatch('swal:success', [
            'title' => 'Berhasil!',
            'text' => "Validasi IT selesai. Pengajuan dilanjutkan ke tahap berikutnya.",
        ]);

        $this->assetRequest->refresh();
        // TODO: Kirim Notifikasi ke Approver Berikutnya
    }
    protected function generateMutationApprovalSteps(AssetTransaction $transaction)
    {
        // Cari user berdasarkan peran. Anda perlu logika untuk ini.
        // Asumsi Role model punya kolom 'code' atau 'name'.
        $kasiItUser = User::whereHas('position', fn($q) => $q->where('name', 'KADEP IT'))->first();
        $costControlUser = User::whereHas('position', fn($q) => $q->where('name', 'KASI CC'))->first();
        $originPicUser = User::where('jobsite_id', $transaction->from_jobsite_id)->whereHas('position', fn($q) => $q->where('name', 'Staff IT'))->first(); // PIC Jobsite Asal
        $destinationPicUser = User::where('jobsite_id', $transaction->to_jobsite_id)->whereHas('position', fn($q) => $q->where('name', 'Staff IT'))->first(); // PIC Jobsite Tujuan

        // Buat record untuk setiap tahap
        $transaction->approvals()->create([
            'step_order' => 1,
            'approver_role_name' => 'KADEP IT',
            'approver_user_id' => $kasiItUser ? $kasiItUser->id : null, // Fallback jika tidak ditemukan
            'status' => 'Pending',
        ]);

        $transaction->approvals()->create([
            'step_order' => 2,
            'approver_role_name' => 'Cost Control',
            'approver_user_id' => $costControlUser ? $costControlUser->id : null,
            'status' => 'Pending',
        ]);
        $transaction->approvals()->create([
            'step_order' => 3,
            'approver_role_name' => 'Staff IT',
            'approver_user_id' => $originPicUser ? $originPicUser->id : null,
            'status' => 'Pending',
        ]);


        // Tahap selanjutnya bisa jadi 'Pelepasan oleh Jobsite Asal' atau lainnya
        // ...

        // Update status utama transaksi ke status pending tahap pertama

        $transaction->save();

        // Kirim notifikasi WA ke Kasi IT
        if ($kasiItUser) {
            // Kirim notifikasi WhatsApp ke Kasi IT
            try {
                $waMessage = "Halo {$kasiItUser->name},\n\n"
                    . "Terdapat permintaan mutasi aset baru yang memerlukan persetujuan Anda.\n"
                    . "Nomor Transaksi: {$transaction->transaction_number}\n"
                    . "Silakan cek aplikasi Asset Management untuk detail dan proses persetujuan.\n"
                    . "ams.binasaranasukses.co.id";

                // Asumsikan ada helper sendWhatsappNotification($user, $message)
                if (function_exists('sendWhatsappNotification')) {
                    sendMessageWithWhatsApp($kasiItUser->whatsapp, $waMessage);
                } elseif (method_exists($kasiItUser, 'sendWhatsappNotification')) {
                    $kasiItUser->sendWhatsappNotification($waMessage);
                }
                // Jika Anda menggunakan job dispatch:
                // dispatch(new \App\Jobs\SendWhatsappNotification($kasiItUser, $waMessage));
            } catch (\Exception $e) {
                // Log error jika gagal kirim WA, tapi jangan hentikan proses
                Log::error('Gagal mengirim notifikasi WA ke Kasi IT: ' . $e->getMessage());
            }
        }
    }
    protected function generateTransactionNumber($typePrefix = 'TRX')
    {
        $year = date('y');
        $month = date('m');
        $jobsite = $this->assetRequest->jobsite->code;
        $prefix = "BSS/{$jobsite}/{$typePrefix}/{$year}{$month}/";
        $lastTransaction = AssetTransaction::where('transaction_number', 'LIKE', $prefix . '%')
            ->orderBy('transaction_number', 'desc')->first();
        $nextSequence = 1;
        if ($lastTransaction) {
            $lastSequence = intval(substr($lastTransaction->transaction_number, strlen($prefix)));
            $nextSequence = $lastSequence + 1;
        }
        return $prefix . str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
    }
    // --- LOGIKA UNTUK APPROVAL GLOBAL (SETELAH IT) ---
    public function openOverallApprovalModal($approvalId, $action)
    {
        $approval = AssetRequestApproval::find($approvalId);
        // abort_unless($this->canApproveOverallStep($approval), 403, 'Aksi tidak diizinkan.');

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
        $approval = AssetRequestApproval::with(['assetRequest.items', 'approver'])->find($this->currentOverallApprovalId);
        // abort_unless($this->canApproveOverallStep($approval), 403, 'Aksi tidak diizinkan.');
        // dd($approval);
        $this->validate(['overallApprovalNotes' => ($this->overallApprovalAction === 'reject' ? 'required|string|min:10' : 'nullable|string')]);

        DB::transaction(function () use ($approval) {
            $approval->update([
                'status' => ($this->overallApprovalAction === 'approve' ? 'Approved' : 'Rejected'),
                'action_at' => now(),
                'action_by_user_id' => Auth::id(),
                'notes' => $this->overallApprovalNotes,

            ]);
            $assetRequest = $approval->assetRequest;

            if ($this->overallApprovalAction === 'reject') {
                $draftData = [
                    'type' => 'REJECT',
                    'author' => $assetRequest->requester->name,
                    'draft' => $approval
                ];
                $assetRequest->update(['status' => 'Rejected', 'final_rejection_notes' => $this->overallApprovalNotes]);
                // $isSuccess = Mail::Mailer('smtp2')->to("arrachmansandry@gmail.com")->send(new MailAssetRequest($draftData));
                $isSuccess = Mail::Mailer('smtp2')->to($assetRequest->requester->karyawans->Email)->send(new MailAssetRequest($draftData));
            } else {
                $nextApproval = $assetRequest->approvals()->where('status', 'Pending')->first();
                // dd($nextApproval);
                if ($nextApproval) {
                    //$nextApproval->approver->whatsapp
                    //dd($assetRequest->request_number);
                    // Update status utama tidak perlu diubah, tetap 'Pending Approval'
                    // TODO: Kirim notifikasi ke approver berikutnya
                    $approveData= [
                        'type' => 'OUTSTANDING_APPROVER',
                        'approver' => $nextApproval,
                        'draft' => $approval
                    ];
                    $isApprove = Mail::Mailer('smtp2')->to($this->assetRequest->requester->karyawans->Email)->send(new MailAssetRequest($approveData));
            

                    $draftData = [
                        'type' => 'APPROVE',
                        'author' => $this->assetRequest->requester->name,
                        'draft' => $approval
                    ];
                    $isSuccess = Mail::Mailer('smtp2')->to($this->assetRequest->requester->karyawans->Email)->send(new MailAssetRequest($draftData));

                } else {
                    $mutationItems = $this->assetRequest->items->where('fulfillment_type', 'STOCK_MUTATION');
                    $newPurchaseItems = $this->assetRequest->items->where('fulfillment_type', 'NEW_PURCHASE');
                    $localStockItems = $this->assetRequest->items->where('fulfillment_type', 'STOCK_LOCAL');

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
                                    'status' => 'Pending Release', // Status awal mutasi: menunggu pelepasan dari site asal
                                    'created_by_user_id' => Auth::id(),
                                ]);

                                // Tambahkan semua item ke dalam transaksi mutasi ini
                                foreach ($items as $item) {
                                    $mutationTransaction->items()->create(['asset_id' => $item->assigned_asset_id, 'asset_request_item_id' => $item->id,]);
                                    // Update status aset fisik menjadi 'Pending Mutation'
                                    $item->assignedAsset->update(['status' => 'Pending Mutation']);
                                }
                                $this->generateMutationApprovalSteps($mutationTransaction);
                                // Kirim notifikasi ke PIC jobsite asal untuk melakukan pelepasan aset
                                $originJobsite = \App\Models\Jobsite::find($originJobsiteId);
                                $picUser = $originJobsite?->picUser; // pastikan relasi picUser ada di model Jobsite
                                if ($picUser) {
                                    $msg = "Halo {$picUser->name},\n\nTerdapat permintaan mutasi aset dari Jobsite *{$this->assetRequest->jobsite->code}* untuk memenuhi pengajuan aset No. *{$this->assetRequest->request_number}*.\n\nMohon untuk melakukan validasi dan proses pelepasan aset sesuai permintaan tersebut di ams.binasaranasukses.co.id. Terima kasih.";
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
                    // $assetRequest->update(['status' => 'Pending PR & Accounting Number']); // <-- PERUBAHAN UTAMA
                    // $stockMutationItems = $assetRequest->items->where('fulfillment_type', 'STOCK_MUTATION');

                    // if($stockMutationItems)
                    // {
                    //     foreach ($stockMutationItems as $item) {
                    //         // Lakukan sesuatu dengan item mutasi ini.
                    //         // Contoh: Membuat record baru di tabel asset_transactions (mutasi)
                    //         // atau mengirim notifikasi ke PIC jobsite asal.

                    //         // Anda bisa mengakses detail aset yang akan dimutasi dan lokasinya
                    //         $assetToMutate = $item->assignedAsset; // Asumsi relasi ini ada
                    //         $originJobsite = $assetToMutate->jobsite;
                    //         $destinationJobsite = $assetRequest->jobsite;

                    //         // Contoh Log
                    //         Log::info("Aset {$assetToMutate->asset_number} perlu dimutasi dari {$originJobsite->name} ke {$destinationJobsite->name}");

                    //         // Di sini Anda bisa memicu pembuatan transaksi mutasi baru
                    //         // AssetTransaction::create([...]);
                    //     }
                    // }
                    //  $this->sendNotificationToItAdmin($assetRequest);
                    // TODO: Trigger proses pengadaan atau notifikasi ke tim procurement
                }
            }
        });
        $this->dispatch('swal:success', [
            'title' => 'Berhasil!',
            'text' => "Status pengajuan berhasil diperbarui",
        ]);

        $this->closeOverallApprovalModal();
        $this->assetRequest->refresh();
    }

    public function canApproveOverallStep($approval): bool
    {
        if (!$approval || $this->assetRequest->status !== 'Pending Approval' || !$this->isAdmin)
            return false;

        if ($approval->step_order == 1) {
            $previousStepsPending = User::where('department_id', 8)->where('jobsite_id', $this->assetRequest->jobsite_id)->get();
        } else {
            $previousStepsPending = $this->assetRequest->approvals()
                ->where('step_order', '<', $approval->step_order)
                ->where('status', 'Pending')->exists() && Auth::id() == $approval->approver_user_id;
        }

        return $approval->status === 'Pending' && $previousStepsPending || $approval->status === 'Pending' && Auth::id() == $approval->approver_user_id;
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
        $secondApprovalStep = User::where('department_id', 8)->where('jobsite_id', $this->assetRequest->jobsite_id)->get();
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
        try {
            $approval = AssetRequestApproval::find($this->idToUpdate);
            if (!$approval) {
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
        return view('livewire.asset-requests.show-asset-request');
    }
}