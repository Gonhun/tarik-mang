<?php

namespace App\Livewire\AssetMutations;

use Livewire\Component;
use App\Models\AssetTransaction;
use App\Models\AssetTransactionItem;
use App\Models\ApprovalWorkflowStep;
use App\Models\Asset;
use App\Models\AssetRequestApproval;
use App\Models\Jobsite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;
use App\Models\CostCenter;
use App\Models\User;
use Livewire\WithFileUploads;

class ManageMutations extends Component
{
    use WithPagination,WithFileUploads;

    // Properti untuk Form
    public $transaction_id;
    public $from_jobsite_id, $to_jobsite_id, $transaction_date, $notes;
    public $selected_assets = [];
    public $assetsFromOrigin = []; // Aset yang bisa dipilih dari jobsite asal
    public $filteredAssets = [];
    public $searchAsset = '';
    public $isModalOpen = false;
    public $search = '';
    public $allJobsites = [];
    protected $paginationTheme = 'bootstrap';

    public $isOverallApprovalModalOpen = false;
    public $currentOverallApprovalId = null; // ID dari record di tabel asset_request_approvals
    public $overallApprovalAction = ''; // 'approve' atau 'reject'
    public $overallApprovalNotes = '';

    public $isReleaseModalOpen = false;
    public ?AssetTransaction $mutationToRelease = null;
    public $releaseDocument; // Untuk file upload

    public function mount()
    {
        $this->allJobsites = Jobsite::orderBy('name')->get(['id', 'name']);
        $this->transaction_date = now()->format('Y-m-d');
    }

    public function openReleaseModal($transactionId)
    {
        $this->mutationToRelease = AssetTransaction::find($transactionId);
        
        // Otorisasi sederhana
        if (Auth::user()->jobsite_id != $this->mutationToRelease->from_jobsite_id) {
            session()->flash('error', 'Anda tidak berhak melakukan aksi ini.');
            $this->mutationToRelease = null;
            return;
        }

        if ($this->mutationToRelease) {
            $this->reset('releaseDocument');
            $this->resetValidation();
            $this->isReleaseModalOpen = true;
        }
    }

    public function closeReleaseModal()
    {
        $this->isReleaseModalOpen = false;
        $this->mutationToRelease = null;
    }
    public function submitReleaseApproval()
    {
        if (!$this->mutationToRelease) return;

        // Validasi bahwa file bukti wajib di-upload
        $this->validate([
            'releaseDocument' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // Maks 5MB
        ], [
            'releaseDocument.required' => 'Bukti serah terima (surat jalan/resi) wajib di-upload.',
            'releaseDocument.mimes' => 'Format file harus PDF, JPG, atau PNG.',
        ]);

        DB::transaction(function () {
            // Simpan file bukti ke storage
            $documentName = $this->releaseDocument->getClientOriginalName();
            $documentPath = $this->releaseDocument->store("mutation_release_docs/{$this->mutationToRelease->id}", 'public');
            
            // 1. Update status transaksi utama dan simpan path dokumen
            $this->mutationToRelease->update([
                'status' => 'Delivery',
                'document_path' => $documentPath,
                'document_name' => $documentName,
            ]);

            // 2. Update status semua aset fisik yang terlibat
            $assetIds = $this->mutationToRelease->items->pluck('asset_id');
            Asset::whereIn('id', $assetIds)->update(['status' => 'Delivery']);
        });
        
        session()->flash('message', "Aset untuk mutasi {$this->mutationToRelease->transaction_number} telah dilepas.");
        $this->closeReleaseModal();
        
        // TODO: Kirim notifikasi ke PIC jobsite tujuan bahwa aset sedang dalam perjalanan.
    }
    public function canApproveOverallStep(AssetRequestApproval $approval): bool
    {
        if (!$approval) return false;
        
        // Cek apakah ada tahap sebelumnya yang masih 'Pending'
        $previousStepsPending = $this->assetRequest->approvals()
                                      ->where('step_order', '<', $approval->step_order)
                                      ->where('status', 'Pending')->exists();

        // Kondisi: status tahap ini 'Pending', user login adalah approvernya, DAN tidak ada tahap sebelumnya yang pending
        return $approval->status === 'Pending' && 
               Auth::id() == $approval->approver_user_id && 
               !$previousStepsPending;
    }
    public function openOverallApprovalModal($approvalId, $action)
    {
        $approval = AssetRequestApproval::find($approvalId);
        
        // Otorisasi sebelum membuka modal
        if (!$this->canApproveOverallStep($approval)) {
            $this->dispatch('show-toast', ['type' => 'error', 'message' => 'Anda tidak berhak melakukan aksi ini atau belum giliran Anda.']);
            return;
        }

        $this->currentOverallApprovalId = $approvalId;
        $this->overallApprovalAction = $action;
        $this->isOverallApprovalModalOpen = true;
    }

    public function closeOverallApprovalModal()
    {
        $this->isOverallApprovalModalOpen = false;
        $this->reset('overallApprovalNotes', 'currentOverallApprovalId', 'overallApprovalAction');
        $this->resetValidation();
    }

    /**
     * Menyimpan keputusan dari modal approval global.
     */
    public function submitOverallApproval()
    {
        $approval = AssetRequestApproval::find($this->currentOverallApprovalId);
        abort_unless($this->canApproveOverallStep($approval), 403, 'Aksi tidak diizinkan.');

        $this->validate(
            ['overallApprovalNotes' => ($this->overallApprovalAction === 'reject' ? 'required|string|min:10' : 'nullable|string')],
            ['overallApprovalNotes.required' => 'Catatan wajib diisi jika menolak pengajuan.']
        );

        DB::transaction(function () use ($approval) {
            // 1. Update record approval saat ini
            $approval->update([
                'status' => ($this->overallApprovalAction === 'approve' ? 'Approved' : 'Rejected'),
                'action_at' => now(),
                'action_by_user_id' => Auth::id(),
                'notes' => $this->overallApprovalNotes,
            ]);

            $assetRequest = $this->assetRequest;

            if ($this->overallApprovalAction === 'reject') {
                // Jika ditolak, status utama pengajuan langsung menjadi 'Rejected'
                $assetRequest->update([
                    'status' => 'Rejected',
                    'final_rejection_notes' => $this->overallApprovalNotes
                ]);
                // TODO: Kirim notifikasi ke pemohon bahwa pengajuan ditolak
            } else { // Jika disetujui ('approve')
                // Cek apakah ada tahap approval berikutnya
                $nextApproval = $assetRequest->approvals()->where('status', 'Pending')->orderBy('step_order', 'asc')->first();

                if ($nextApproval) {
                    // Jika MASIH ADA tahap berikutnya, status utama tetap 'Pending Approval'
                    // Tidak perlu mengubah status utama, karena masih dalam proses
                    // TODO: Kirim notifikasi ke approver berikutnya ($nextApproval->approver)
                } else {
                    // Ini adalah persetujuan FINAL, ubah status utama
                    $assetRequest->update(['status' => 'Approved']);
                    // TODO: Trigger proses selanjutnya (input PR/Akunting) & notifikasi ke Admin IT
                }
            }
        });
        
        session()->flash('message', 'Status pengajuan berhasil diperbarui.');
        $this->closeOverallApprovalModal();
        $this->assetRequest->refresh();
    }
    public function updatedSearchAsset()
    {
        // Jika jobsite sudah dipilih, panggil ulang fungsi
        $this->filterAssets();
    }
    // Lifecycle hook saat jobsite asal dipilih
    public function changeFromJobsiteId($jobsiteId)
    {
        $this->filteredAssets = [];
        if (!empty($jobsiteId)) {
            $this->from_jobsite_id = $jobsiteId;
            $this->assetsFromOrigin = Asset::where('jobsite_id', $jobsiteId)
                ->whereIn('status', ['Free', 'Used'])
                ->get()
                ->toArray();
        } else {
            $this->assetsFromOrigin = [];
        }
    
        $this->filterAssets();
    }
    public function filterAssets()
    {
        $search = strtolower($this->searchAsset ?? '');

        // Jika pencarian kosong, tampilkan semua aset
        if ($search === '') {
            $this->filteredAssets = $this->assetsFromOrigin;
            return;
        }

        // Filter data tanpa mengubah selected_assets
        $this->filteredAssets = collect($this->assetsFromOrigin)
            ->filter(function ($asset) use ($search) {
                return str_contains(strtolower($asset['asset_number']), $search)
                    || str_contains(strtolower($asset['serial_number']), $search)
                    || str_contains(strtolower($asset['asset_series']['name'] ?? ''), $search)
                    || str_contains(strtolower($asset['asset_series']['material_code']['name'] ?? ''), $search);
            })
            ->values()
            ->toArray();
    }
    protected function rules()
    {
        return [
            'from_jobsite_id' => 'required|exists:jobsites,id',
            'to_jobsite_id' => 'required|exists:jobsites,id|different:from_jobsite_id',
            'transaction_date' => 'required|date',
            'notes' => 'required|string|min:10',
            'selected_assets' => 'required|array|min:1',
        ];
    }

    public function create()
    {
        $this->resetInputFields();
        $this->openModal();
    }

    public function store()
    {
        $this->validate();
        $destinationHasCostCenter = CostCenter::where('jobsite_id', $this->to_jobsite_id)->exists();
        if (!$destinationHasCostCenter) {
            // Jika tidak ada Cost Center di jobsite tujuan, tambahkan error dan hentikan proses.
            $this->addError('to_jobsite_id', 'Mutasi gagal. Jobsite tujuan tidak memiliki Cost Center yang terdaftar.');
            return; // Hentikan eksekusi method
        }
        $sourceHasCostCenter = CostCenter::where('jobsite_id', $this->from_jobsite_id)->exists();
        if (!$sourceHasCostCenter) {
            // Jika tidak ada Cost Center di jobsite tujuan, tambahkan error dan hentikan proses.
            $this->addError('from_jobsite_id', 'Mutasi gagal. Jobsite pengirim tidak memiliki Cost Center yang terdaftar.');
            return; // Hentikan eksekusi method
        }
        try {
            DB::transaction(function () {
                $transaction = AssetTransaction::create([
                    'transaction_number' => generateMutationNumber('MT'),
                    'transaction_date' => $this->transaction_date,
                    'transaction_type' => 'MUTATION',
                    'from_jobsite_id' => $this->from_jobsite_id,
                    'to_jobsite_id' => $this->to_jobsite_id,
                    'notes' => $this->notes,
                    'status' => 'Pending Approval', // Status awal: menunggu persetujuan dari jobsite asal
                    'created_by_user_id' => Auth::id(),
                ]);
    
                foreach ($this->selected_assets as $asset_id) {
                    $transaction->items()->create(['asset_id' => $asset_id]);
                    Asset::find($asset_id)->update(['status' => 'Pending Mutation']);
                }
                $this->generateApproval($transaction);
            });
            session()->flash('message', 'Permintaan Mutasi Aset berhasil dibuat.');
            $this->closeModal();
            $this->resetInputFields();
        } catch (\Exception  $e) {
           
            $this->closeModal();
            $this->resetInputFields();
            $this->dispatch('swal:error', [
                'title' => 'Error!',
                'text' => $e->getMessage() . ' - ' . $e->getFile() . ' - ' . $e->getLine(),
            ]);
           
        }
    }
    protected function generateApproval(AssetTransaction $transaction)
    {
        $workflow = findWorkflowForContext('MUTATION', Auth::user()->position_id, Auth::user()->jobsite_id);
                if (!$workflow || $workflow->steps->isEmpty()) {
                    throw new \Exception('Alur persetujuan belum didefinisikan. Hubungi Administrator.');
                }
               
                foreach ($workflow->steps as $step) {
                    $approverPosition = $step->approverPositionLevel;
                    $approverDepartment = $step->department_id;
                    
                    $roleName = $approverPosition->name;
                    
                  
                    // Skip Project Manager if jobsite is SMD or JKT
                    if ($roleName === 'Kepala Proyek' && in_array($transaction->jobsite->code, ['SMD', 'JKT'])) {
                        continue;
                    }

                    // Base query
                    $userQuery = User::query();
                    if($step->user_id)
                    {
                        $userQuery->where('id',$step->user_id);
                        $approverUser = $userQuery->first();
                        if ($approverUser) {
                            $transaction->approvals()->create([
                                'asset_transaction_id' => $transaction->id,
                                'step_order' => $step->step_order,
                                'approver_role_name' => $step->approverPositionLevel->name ?? NULL,
                                'approver_user_id' => $approverUser->id,
                                'status' => 'Pending',
                            ]);
                            continue;
                        } else {
                            throw new \Exception("Konfigurasi error: Approver untuk jabatan '{$roleName}' tidak dapat ditemukan. Pengajuan dibatalkan.");
                        }
                        
                    }
                    if(!empty($approverDepartment))
                    {
                      
                        $userQuery->where('department_id',$approverDepartment)
                        ->whereHas('positionLevel', fn($q) => $q->where('name', $roleName))
                        ->where('jobsite_id', $transaction->jobsite_id);
                    }else{
                        $userQuery
                        ->whereHas('positionLevel', fn($q) => $q->where('name', $roleName))
                        ->where('jobsite_id', $transaction->jobsite_id);
                    }

                    $approverUser = $userQuery->first();

                  

                    if (!$approverUser) {
                        $fallbackQuery = User::query();
                        if(!empty($approverDepartment))
                        {
                            $fallbackQuery->where('department_id', $approverDepartment)
                        ->whereHas('positionLevel', fn($q) => $q->where('name', $roleName))
                        ->whereHas('jobsite', fn($q) => $q->where('code', 'JKT'));
                        }else{
                            $fallbackQuery
                        ->whereHas('positionLevel', fn($q) => $q->where('name', $roleName))
                        ->whereHas('jobsite', fn($q) => $q->where('code', 'JKT'));
                        }
                        
                        
                        $approverUser = $fallbackQuery->first();

                       
                    }
                    
                    if ($approverUser) {
                        $transaction->approvals()->create([
                            'asset_transaction_id' => $transaction->id,
                            'step_order' => $step->step_order,
                            'approver_role_name' => $step->approverPositionLevel->name ?? NULL,
                            'approver_user_id' => $approverUser->id,
                            'status' => 'Pending',
                        ]);
                        
                    } else {
                       
                        throw new \Exception("Konfigurasi error: Approver untuk jabatan '{$roleName}' tidak dapat ditemukan. Pengajuan dibatalkan.");
                    }
                }
                if ($step->step_order === 1) {
                    $message = "Halo {$approverUser->name},\n\n"
                    . "Ada pengajuan transaksi aset #{$transaction->transaction_number} yang membutuhkan persetujuan Anda.\n"
                    . "Jenis: " . ucwords(strtolower(str_replace('_', ' ', $transaction->transaction_type))) . "\n"
                    . "Tanggal: " . \Carbon\Carbon::parse($transaction->transaction_date)->format('d-m-Y') . "\n"
                    . "Status: {$transaction->status}\n\n"
                    . "Silakan login ke aplikasi AMS untuk melakukan approval."
                    . "ams.binasaranasukses.co.id";
                    sendMessageWithWhatsApp($approverUser->whatsapp, $message);
                }
    }
    protected function generateApprovalWorkflow(AssetTransaction $transaction, User $requester)
    {
        $transactionType = 'MUTATION'; // Sesuaikan dengan jenis transaksi
        $positionId = $requester->position_id;
        $jobsiteId = $requester->jobsite_id;

        // Panggil helper untuk mencari alur kerja yang sesuai
        $workflow = findWorkflowForContext($transactionType, $positionId, $jobsiteId);

        if (!$workflow || $workflow->steps->isEmpty()) {
            throw new \Exception('Alur persetujuan belum didefinisikan untuk proses ini. Hubungi Administrator.');
        }

        // Loop melalui langkah-langkah yang sudah terdefinisi
        foreach ($workflow->steps as $step) {
            // Panggil helper baru untuk menemukan user approver yang tepat untuk langkah ini
            $approverUser = $this->findApproverForStep($step, $transaction);

            if ($approverUser) {
                // Buat record approval
                $transaction->approvals()->create([
                    'step_order' => $step->step_order,
                    'approver_role_name' => $step->approverPosition->name ?? ($step->approverDepartment->name . ' - ' . $step->approverPositionLevel->name),
                    'approver_user_id' => $approverUser->id,
                    'status' => 'Pending',
                ]);

                // Kirim notifikasi ke approver pertama
                if ($step->step_order === 1) {
                    $message = "Halo {$approverUser->name},\n\n"
                    . "Ada pengajuan transaksi aset #{$transaction->transaction_number} yang membutuhkan persetujuan Anda.\n"
                    . "Jenis: " . ucwords(strtolower(str_replace('_', ' ', $transaction->transaction_type))) . "\n"
                    . "Tanggal: " . \Carbon\Carbon::parse($transaction->transaction_date)->format('d-m-Y') . "\n"
                    . "Status: {$transaction->status}\n\n"
                    . "Silakan login ke aplikasi AMS untuk melakukan approval."
                    . "ams.binasaranasukses.co.id";
                    sendMessageWithWhatsApp($approverUser->whatsapp, $message);
                }
            } else {
                $roleName = $step->approverPosition->name ?? ($step->approverDepartment->name . ' - ' . $step->approverPositionLevel->name);
                throw new \Exception("Konfigurasi error: Approver untuk peran '{$roleName}' tidak dapat ditemukan. Pengajuan dibatalkan.");
            }
        }
    }

    /**
     * Method helper baru untuk mencari user approver yang tepat.
     * Ini menyatukan semua logika pencarian Anda yang kompleks.
     */
    protected function findApproverForStep(ApprovalWorkflowStep $step, AssetTransaction $transaction): ?User
    {
        $step->loadMissing('approverPosition', 'approverDepartment', 'approverPositionLevel');

        $userQuery = User::query();

        // Prioritas 1: Jika jabatan spesifik ditentukan di master workflow
        if ($step->approver_position_id) {
            $userQuery->where('position_id', $step->approver_position_id);
        }
        // Prioritas 2: Jika kombinasi departemen dan level jabatan ditentukan
        else {
            if ($step->approver_department_id) {
                $userQuery->whereHas('position', fn($q) => $q->where('department_id', $step->approver_department_id));
            }
            if ($step->approver_position_level_id) {
                $userQuery->whereHas('position', fn($q) => $q->where('position_level_id', $step->approver_position_level_id));
            }
        }

        // Terapkan filter konteks, misalnya approver harus dari jobsite asal mutasi
        $userQuery->where('jobsite_id', $transaction->from_jobsite_id);

        $approver = $userQuery->first();

        // Jika tidak ditemukan di jobsite asal, coba cari di jobsite Head Office (sebagai fallback)
        if (!$approver) {
            $userQuery = User::query(); // Mulai query baru
            
            // Logika pencarian yang sama seperti di atas
            if ($step->approver_position_id) {
                $userQuery->where('position_id', $step->approver_position_id);
            } else {
                if ($step->approver_department_id) $userQuery->whereHas('position', fn($q) => $q->where('department_id', $step->approver_department_id));
                if ($step->approver_position_level_id) $userQuery->whereHas('position', fn($q) => $q->where('position_level_id', $step->approver_position_level_id));
            }

            // Filter fallback ke jobsite Head Office (asumsi kode 'JKT')
            $userQuery->whereHas('jobsite', fn($q) => $q->where('code', 'JKT'));
            $approver = $userQuery->first();
        }

        return $approver;
    }
    // Aksi untuk menyetujui pelepasan aset dari jobsite asal
    public function approveRelease($transactionId)
    {
        $transaction = AssetTransaction::with('items')->findOrFail($transactionId);
        $user = Auth::user();

        // Otorisasi Sederhana: Pastikan user berada di jobsite asal
        // Untuk produksi, gunakan Gate atau Policy yang lebih robust
        if ($user->jobsite_id != $transaction->from_jobsite_id) {
            session()->flash('error', 'Anda tidak berhak melakukan aksi ini.');
            return;
        }

        // Pastikan status transaksi sesuai
        if ($transaction->status !== 'Pending Release') {
            session()->flash('error', 'Aksi ini tidak bisa dilakukan karena status transaksi bukan "Pending Release".');
            return;
        }

        DB::transaction(function () use ($transaction) {
            // 1. Update status transaksi utama
            $transaction->update(['status' => 'Delivery']);

            // 2. Update status semua aset fisik yang terlibat
            $assetIds = $transaction->items->pluck('asset_id');
            Asset::whereIn('id', $assetIds)->update(['status' => 'Delivery']);
        });
        
        // Log::info("Mutasi #{$transaction->id} disetujui untuk dilepas oleh user #{$user->id}");
        session()->flash('message', "Aset untuk mutasi {$transaction->transaction_number} telah dilepas dan berstatus 'Dalam Perjalanan'.");

        // TODO: Kirim notifikasi ke PIC jobsite tujuan bahwa aset sedang dalam perjalanan.
    }

    // Aksi untuk konfirmasi penerimaan aset di jobsite tujuan
    public function confirmReceipt($transactionId)
    {
        $transaction = AssetTransaction::with('items')->findOrFail($transactionId);
        $user = Auth::user();

        // Otorisasi Sederhana: Pastikan user berada di jobsite tujuan
        if ($user->jobsite_id != $transaction->to_jobsite_id) {
            session()->flash('error', 'Anda tidak berhak melakukan aksi ini.');
            return;
        }

        // Pastikan status transaksi sesuai
        if ($transaction->status !== 'Delivery') {
            session()->flash('error', 'Aksi ini tidak bisa dilakukan karena status transaksi bukan "Delivery".');
            return;
        }
        
        DB::transaction(function () use ($transaction) {
            // 1. Update status transaksi utama menjadi Selesai
            $transaction->update(['status' => 'Completed']);

            // 2. Update detail setiap aset fisik yang terlibat
            foreach ($transaction->items as $item) {
                Asset::find($item->asset_id)->update([
                    'jobsite_id' => $transaction->to_jobsite_id, // Pindahkan jobsite aset
                    'status' => 'Free', // Aset kembali menjadi 'Free' di lokasi baru
                    'holder_user_id' => null, // Reset pemegang aset
                    // Anda juga bisa mereset detail lokasi lainnya jika ada
                ]);
            }
        });
        
        // Log::info("Penerimaan mutasi #{$transaction->id} dikonfirmasi oleh user #{$user->id}");
        session()->flash('message', "Aset untuk mutasi {$transaction->transaction_number} telah diterima di lokasi baru.");

        // TODO: Kirim notifikasi ke pembuat permintaan atau PIC asal bahwa mutasi telah selesai.
    }

    // ... (render, resetInputFields, open/closeModal, dll.) ...

    private function resetInputFields()
    {
        $this->transaction_id = null;
        $this->from_jobsite_id = null;
        $this->to_jobsite_id = null;
        $this->notes = '';
        $this->searchAsset = '';
        $this->selected_assets = [];
        $this->assetsFromOrigin = [];
        $this->filteredAssets = [];
        $this->transaction_date = now()->format('Y-m-d');
        $this->resetValidation();
    }

    private function generateMutationNumber() { 
        $year = date('y');
        $month = date('m');
        $jobsite = Auth::user()->jobsite->code;
        $prefix = "BSS/{$jobsite}/MT/{$year}{$month}/";
        $lastTransaction = AssetTransaction::where('transaction_number', 'LIKE', $prefix . '%')
                            ->orderBy('transaction_number', 'desc')->first();
        $nextSequence = 1;
        if ($lastTransaction) {
            $lastSequence = intval(substr($lastTransaction->transaction_number, strlen($prefix)));
            $nextSequence = $lastSequence + 1;
        }
        return $prefix . str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
     }
    public function openModal() { 
        $this->isModalOpen = true; 
        $this->assetsFromOrigin = [];
    }
    public function closeModal() { $this->isModalOpen = false; }

    public function render()
    {
        // Query untuk menampilkan daftar transaksi mutasi
        $mutations = AssetTransaction::where('transaction_type', 'MUTATION')
            ->with(['fromJobsite', 'toJobsite', 'creator', 'items'])
            ->orderBy('transaction_date', 'desc')
            ->paginate(10);

        return view('livewire.asset-mutations.manage-mutations', [
            'mutations' => $mutations
        ]);
    }
}
