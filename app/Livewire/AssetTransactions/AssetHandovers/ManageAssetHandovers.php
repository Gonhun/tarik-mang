<?php

namespace App\Livewire\AssetHandovers;

use Livewire\Component;
use App\Models\AssetTransaction;
use App\Models\AssetTransactionItem;
use App\Models\Asset;
use App\Models\User;
use App\Models\AssetType;  // Pastikan di-import
use App\Models\AssetBrand; // Pastikan di-import
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ManageAssetHandovers extends Component
{
    use WithPagination, WithFileUploads;

    public $filterAssetTypeId = null;
    public $filterAssetBrandId = null;
    public $assetTypesForFilter = [];      // Untuk dropdown filter Tipe Aset
    public $assetBrandsForFilter = [];

    // Properti untuk form BAST
    public $asset_handover_id, $handover_number, $handover_date;
    public $giver_user_id, $receiver_user_id, $notes, $status = 'Draft';
    public $bast_document; // Untuk upload file
    public $existing_bast_document_path, $existing_bast_document_name;

    // Properti untuk item aset
    public $selected_assets = []; // Array berisi ID aset yang dipilih
    public $asset_conditions = []; // Array [asset_id => kondisi]
    public $condition_notes_per_asset = []; // Array [asset_id => catatan kondisi]

    // Data untuk dropdown
    public $users = [];
    public $availableAssets = []; // Aset yang berstatus 'Free' atau status lain yang relevan

    public $isModalOpen = false;
    public $search = '';
    protected $paginationTheme = 'bootstrap';
    public $handoverToCompleteId = null; // Menyimpan ID BAST yang akan diselesaikan/diupload dokumennya
    public $isUploadBastModalOpen = false;
    public $bastDocumentForCompletion; // Untuk file input di modal upload BAST

    // Daftarkan listener untuk konfirmasi JavaScript
    protected $listeners = ['doFinalizeHandover'];

    // Method ini dipanggil oleh tombol "Selesaikan BAST" di tabel
    public function initiateCompleteHandover($handoverId)
    {
        $handover = AssetTransaction::find($handoverId);

        if (!$handover) {
            session()->flash('error', 'BAST tidak ditemukan.');
            return;
        }

        if ($handover->status !== 'Draft') {
            session()->flash('error', "BAST No. {$handover->handover_number} sudah dalam status '{$handover->status}'.");
            return;
        }

        $this->handoverToCompleteId = $handover->id; // Simpan ID untuk digunakan nanti

        if (empty($handover->bast_document_path)) {
            // Dokumen belum ada, buka modal untuk upload
            $this->bastDocumentForCompletion = null; // Reset input file
            $this->resetValidation('bastDocumentForCompletion'); // Reset validasi spesifik
            $this->isUploadBastModalOpen = true;
        } else {
            // Dokumen sudah ada, minta konfirmasi JavaScript untuk langsung menyelesaikan
            $this->dispatchBrowserEvent('confirm-finalize-handover', [
                'handoverId' => $handoverId,
                'message' => "Dokumen BAST untuk No. {$handover->handover_number} sudah ada. Lanjutkan menyelesaikan BAST ini?"
            ]);
        }
    }

    // Method yang dipanggil setelah user konfirmasi 'Ya' dari JavaScript (jika dokumen sudah ada)
    public function doFinalizeHandover($handoverId)
    {
        $this->finalizeHandoverLogic($handoverId);
    }

    // Method yang dipanggil oleh form di modal upload BAST
    public function uploadBastAndFinalize()
    {
        $this->validate([
            'bastDocumentForCompletion' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // Maks 5MB
        ], [
            'bastDocumentForCompletion.required' => 'File BAST wajib diupload.',
            'bastDocumentForCompletion.mimes' => 'File BAST harus berformat PDF, JPG, JPEG, atau PNG.',
            'bastDocumentForCompletion.max' => 'Ukuran file BAST maksimal 5MB.',
        ]);

        $handover = AssetTransaction::find($this->handoverToCompleteId);
        if (!$handover) {
            session()->flash('error', 'BAST tidak ditemukan untuk upload dokumen.');
            $this->closeUploadBastModal();
            return;
        }

        // Hapus dokumen lama jika ada (seharusnya tidak ada jika masuk ke modal ini dari jalur "dokumen belum ada")
        // Tapi sebagai pengaman, jika modal ini bisa dipakai re-upload
        if ($handover->bast_document_path) {
            Storage::disk('public')->delete($handover->bast_document_path);
        }

        // Simpan dokumen baru
        $handover->bast_document_name = $this->bastDocumentForCompletion->getClientOriginalName();
        // Simpan dalam folder spesifik per BAST ID untuk kerapian
        $handover->bast_document_path = $this->bastDocumentForCompletion->store("bast_documents/{$handover->id}", 'public');
        $handover->save(); // Simpan path dokumen dulu

        // Lanjutkan ke proses penyelesaian
        $this->finalizeHandoverLogic($this->handoverToCompleteId);
        $this->closeUploadBastModal();
    }

    // Logika inti untuk menyelesaikan BAST (setelah dokumen dipastikan ada)
    protected function finalizeHandoverLogic($handoverId)
    {
        $handover = AssetTransaction::with('items.asset')->findOrFail($handoverId); // Load relasi

        // Double check status dan keberadaan dokumen (penting)
        if ($handover->status !== 'Draft') {
            session()->flash('error', "BAST No. {$handover->handover_number} sudah dalam status '{$handover->status}'.");
            return;
        }
        if (empty($handover->bast_document_path)) {
            session()->flash('error', "Dokumen BAST untuk No. {$handover->handover_number} belum ada. Tidak bisa diselesaikan.");
            // Mungkin buka modal upload lagi jika ini terjadi karena suatu hal
            // $this->isUploadBastModalOpen = true;
            return;
        }

        DB::transaction(function () use ($handover) {
            $handover->status = 'Completed';
            $handover->save();
            $this->processAssetUpdatesAfterHandover($handover); // Method ini sudah ada sebelumnya
        });

        session()->flash('message', "BAST No. {$handover->handover_number} berhasil diselesaikan dan status aset terkait telah diupdate.");
    }

    public function closeUploadBastModal()
    {
        $this->isUploadBastModalOpen = false;
        $this->bastDocumentForCompletion = null;
        $this->handoverToCompleteId = null;
        $this->resetValidation(); // Reset semua validasi
    }
    public function mount()
    {
        $authUserId = Auth::id(); // Dapatkan ID pengguna yang sedang login
        $this->giver_user_id=Auth::id();
        $query = User::orderBy('name');

        if ($authUserId) {
            // Tambahkan kondisi untuk mengecualikan pengguna yang sedang login
            $query->where('id', '!=', $authUserId);
            // Alternatif yang lebih mudah dibaca (Laravel 8+):
            // $query->whereNot('id', $authUserId);
        }

        $this->users = $query->get(['id', 'name', 'nik']);
        $this->assetTypesForFilter = AssetType::orderBy('name')->get(['id', 'name', 'code']); // Untuk filter
        $this->assetBrandsForFilter = collect(); // Inisialisasi
        $this->loadAvailableAssets();
        $this->handover_date = now()->format('Y-m-d');
    }
    public function changeFilterAssetTypeId()
    {
        if (!empty($this->filterAssetTypeId)) {
            $this->assetBrandsForFilter = AssetBrand::where('asset_type_id', $this->filterAssetTypeId)
                                            ->orderBy('name')->get(['id', 'name', 'code']);
        } else {
            $this->assetBrandsForFilter = collect();
        }
        $this->filterAssetBrandId = null; // Reset filter merek
        $this->loadAvailableAssets();     // Muat ulang aset yang tersedia berdasarkan filter baru
    }

    // Lifecycle hook saat filter Merek Aset berubah
    public function changeFilterAssetBrandId()
    {
        $this->loadAvailableAssets(); // Muat ulang aset yang tersedia berdasarkan filter baru
    }
    public function loadAvailableAssets()
    {
        $query = Asset::query()
            ->whereIn('status', ['Free']) // Hanya aset dengan status 'Free' atau status relevan lain
            ->whereDoesntHave('handoverItems.assetHandover', function ($query) {
                $query->where('status', 'Draft');
            })
            ->with(['assetType:id,name,code', 'assetBrand:id,name,code', 'assetSeries:id,name']); // Eager load untuk display
            if ($this->filterAssetTypeId) {
                $query->where('asset_type_id', $this->filterAssetTypeId);
            }
    
            if ($this->filterAssetBrandId) {
                $query->where('asset_brand_id', $this->filterAssetBrandId);
            }
    
            // Kecualikan aset yang sudah dipilih di form BAST saat ini
            if (!empty($this->selected_assets)) {
                $query->whereNotIn('id', $this->selected_assets);
            }
            $this->availableAssets = $query->orderBy('asset_number')->get();            
    }

    // Fungsi untuk generate nomor BAST (panggil saat create)
    public function generateHandoverNumber()
    {
        // Format: BAST/TAHUN/BULAN/XXXX (nomor urut 4 digit)
        $year = date('Y');
        $month = date('m');
        $prefix = "BAST/{$year}/{$month}/";

        $lastHandover = AssetHandover::where('handover_number', 'LIKE', $prefix . '%')
                            ->orderBy('handover_number', 'desc')->first();

        $nextSequence = 1;
        if ($lastHandover) {
            $lastSequence = intval(substr($lastHandover->handover_number, strlen($prefix)));
            $nextSequence = $lastSequence + 1;
        }
        $this->handover_number = $prefix . str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
    }

    protected function rules()
    {
        return [
            'handover_number' => 'required|string|unique:asset_handovers,handover_number,' . $this->asset_handover_id,
            'handover_date' => 'required|date',
            'giver_user_id' => 'required|exists:users,id',
            'receiver_user_id' => 'required|exists:users,id|different:giver_user_id',
            'notes' => 'nullable|string',
            'status' => 'required|in:Draft,Completed,Cancelled',
            'bast_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120', // Maks 5MB
            'selected_assets' => 'required|array|min:1',
            'selected_assets.*' => 'exists:assets,id',
            'asset_conditions.*' => 'nullable|string|max:255',
            'condition_notes_per_asset.*' => 'nullable|string',
        ];
    }
     protected $messages = [
        'receiver_user_id.different' => 'Penerima tidak boleh sama dengan pemberi.',
        'selected_assets.required' => 'Minimal satu aset harus dipilih untuk diserahterimakan.',
    ];


    public function create()
    {
        $this->resetInputFields();
        $this->generateHandoverNumber(); // Generate nomor BAST baru
        $this->status = 'Draft'; // Status default
        $this->handover_date = now()->format('Y-m-d'); // Tanggal hari ini
        $this->giver_user_id = Auth::id(); // Default pemberi adalah user login
        $this->loadAvailableAssets(); // Pastikan aset terbaru dimuat
        $this->openModal();
    }

    public function store()
    {
        $validatedData = $this->validate(); // Validasi aturan umum dulu

        // ===== VALIDASI KUSTOM UNTUK DOKUMEN JIKA STATUS COMPLETED =====
        if ($this->status === 'Completed') {
            $hasExistingDocument = $this->asset_handover_id && !empty($this->existing_bast_document_path);
            $hasNewDocument = !empty($this->bast_document);

            if (!$hasExistingDocument && !$hasNewDocument) {
                // Jika status "Completed" tapi tidak ada dokumen lama (saat edit) DAN tidak ada dokumen baru yang diupload
                $this->addError('bast_document', 'Dokumen BAST wajib di-upload jika status BAST adalah "Completed".');
                return; // Hentikan proses
            }
        }
        // ===== AKHIR VALIDASI KUSTOM =====

        DB::transaction(function () use ($validatedData) { // Perhatikan penggunaan $validatedData jika ada
            $dataHandover = [
                'handover_number' => $this->handover_number,
                'handover_date' => $this->handover_date,
                'giver_user_id' => $this->giver_user_id,
                'receiver_user_id' => $this->receiver_user_id,
                'notes' => $this->notes,
                'status' => $this->status,
                'created_by_user_id' => Auth::id(),
            ];

            if ($this->bast_document) {
                // Hapus dokumen lama jika ini update dan ada dokumen baru
                if ($this->asset_handover_id && $this->existing_bast_document_path) {
                    Storage::disk('public')->delete($this->existing_bast_document_path);
                }
                $dataHandover['bast_document_name'] = $this->bast_document->getClientOriginalName();
                $dataHandover['bast_document_path'] = $this->bast_document->store('bast_documents', 'public');
            } elseif ($this->asset_handover_id && !empty($this->existing_bast_document_path)) {
                // Jika edit dan tidak ada file baru, pertahankan file lama (tidak perlu masuk $dataHandover)
                // Namun, jika statusnya Completed, kita sudah cek di atas bahwa existing_bast_document_path harus ada
            }


            $assetHandover = AssetHandover::updateOrCreate(['id' => $this->asset_handover_id], $dataHandover);

            // Hapus item lama jika ini adalah update, lalu tambahkan item baru
            // (Pastikan selected_assets dan kondisi juga divalidasi dengan baik)
            if ($this->asset_handover_id) {
                $assetHandover->items()->delete(); 
            }

            foreach ($this->selected_assets as $asset_id) {
                $asset = Asset::find($asset_id);
                AssetHandoverItem::create([
                    'asset_handover_id' => $assetHandover->id,
                    'asset_id' => $asset_id,
                    'asset_condition_at_handover' => $this->asset_conditions[$asset_id] ?? null,
                    'condition_notes' => $this->condition_notes_per_asset[$asset_id] ?? null,
                    'previous_asset_status' => $asset ? $asset->status : null,
                    'previous_asset_holder_name' => $asset && $asset->holder ? $asset->holder->name : null,
                ]);
            }
            
            // Jika status BAST adalah 'Completed' dari form, proses update aset
            if ($assetHandover->status === 'Completed') {
                $this->processAssetUpdatesAfterHandover($assetHandover);
            }

        });

        session()->flash('message', $this->asset_handover_id ? 'BAST berhasil diperbarui.' : 'BAST berhasil dibuat.');
        $this->closeModal();
        $this->resetInputFields();
        $this->dispatch('fileInputReset', ['inputId' => 'bast_document']); // Kirim event untuk reset file input
    }

    public function edit($id)
    {
        $handover = AssetHandover::with('items')->findOrFail($id);
        $this->asset_handover_id = $id;
        $this->handover_number = $handover->handover_number;
        $this->handover_date = $handover->handover_date->format('Y-m-d');
        $this->giver_user_id = $handover->giver_user_id;
        $this->receiver_user_id = $handover->receiver_user_id;
        $this->notes = $handover->notes;
        $this->status = $handover->status;
        $this->existing_bast_document_path = $handover->bast_document_path;
        $this->existing_bast_document_name = $handover->bast_document_name;
        
        $this->selected_assets = $handover->items->pluck('asset_id')->toArray();
        $this->asset_conditions = $handover->items->pluck('asset_condition_at_handover', 'asset_id')->toArray();
        $this->condition_notes_per_asset = $handover->items->pluck('condition_notes', 'asset_id')->toArray();

        $this->bast_document = null;

        // Reset filter dan muat ulang available assets
        $this->filterAssetTypeId = null;
        $this->filterAssetBrandId = null;
        $this->assetBrandsForFilter = collect();
        $this->loadAvailableAssets(); // Ini akan memuat aset yang 'Free' dan belum ada di $selected_assets

        $this->openModal();
    }

    protected function processAssetUpdatesAfterHandover(AssetHandover $handover)
    {
        foreach ($handover->items as $item) {
            $asset = $item->asset; // Ambil model asset yang sudah di-load
            if ($asset) {
                $newStatus = 'Used'; // Status default setelah BAST
                $asset->status = $newStatus;
                $asset->holder_user_id = $handover->receiver_user_id;
                $asset->save();
              
            }
        }
    }


    // ... resetInputFields(), openModal(), closeModal(), render(), delete() ...
    // Pastikan delete() juga menghapus file BAST jika ada.
    public function delete($id)
    {
        $handover = AssetHandover::find($id);

        if (!$handover) {
            session()->flash('error', 'BAST tidak ditemukan.');
            return;
        }

        // Aturan Bisnis: Hanya BAST dengan status 'Draft' yang bisa dibatalkan.
        // Anda bisa menyesuaikan ini jika ada status lain yang juga boleh dibatalkan.
        if ($handover->status !== 'Draft') {
            session()->flash('error', "BAST No. {$handover->handover_number} dengan status '{$handover->status}' tidak dapat dibatalkan.");
            return;
        }

        // Jika BAST dibatalkan, apa yang terjadi dengan dokumen yang mungkin sudah diupload?
        // Opsi 1: Hapus dokumen fisik.
        // Opsi 2: Biarkan dokumen fisik (untuk histori), mungkin tambahkan flag atau pindahkan.
        // Untuk contoh ini, kita akan hapus dokumen fisiknya jika ada.
        if ($handover->bast_document_path) {
            Storage::disk('public')->delete($handover->bast_document_path);
            // Anda mungkin juga ingin mengosongkan field path dan nama dokumen di database.
            // $handover->bast_document_path = null;
            // $handover->bast_document_name = null;
            // Namun, jika statusnya 'Cancelled', mungkin lebih baik biarkan path-nya untuk histori,
            // tapi pastikan file fisiknya sudah tidak relevan atau dihapus.
            // Keputusan ini tergantung kebutuhan audit Anda.
            // Untuk konsistensi dengan "delete" sebelumnya, kita hapus fisik dan bisa null-kan path.
            $handover->bast_document_path = null; // Kosongkan path setelah file dihapus
            $handover->bast_document_name = null;
        }
        
        // Apa yang terjadi dengan AssetHandoverItem?
        // Jika BAST dibatalkan, item-itemnya secara logis tidak lagi relevan untuk serah terima aktif.
        // Anda bisa memilih untuk menghapusnya atau membiarkannya (karena BAST induknya masih ada dengan status 'Cancelled').
        // Jika foreign key di asset_handover_items ke asset_handovers adalah onDelete('cascade'),
        // maka menghapus BAST (bahkan soft delete) akan menghapus items.
        // Karena kita hanya mengubah status, items akan tetap ada. Ini biasanya oke untuk histori.

        $handover->status = 'Cancelled';
        $handover->save();

        session()->flash('message', "BAST No. {$handover->handover_number} berhasil dibatalkan.");
    }

    private function resetInputFields(){
        $this->asset_handover_id = null;
        $this->handover_number = '';
        $this->handover_date = now()->format('Y-m-d');
        $this->giver_user_id = Auth::id();
        $this->receiver_user_id = null;
        $this->notes = '';
        $this->status = 'Draft';
        $this->bast_document = null;
        $this->existing_bast_document_path = null;
        $this->existing_bast_document_name = null;
        
        $this->selected_assets = [];
        $this->asset_conditions = [];
        $this->condition_notes_per_asset = [];

        // Reset filter juga
        $this->filterAssetTypeId = null;
        $this->filterAssetBrandId = null;
        $this->assetBrandsForFilter = collect();
        $this->loadAvailableAssets(); // Muat ulang available assets tanpa filter
        $this->resetValidation();
    }

    public function openModal(){ $this->isModalOpen = true; }
    public function closeModal(){ $this->isModalOpen = false; }

    public function render()
    {
        $query = AssetHandover::with(['giver', 'receiver', 'creator', 'items']);
        if (!empty($this->search)) {
            $query->where('handover_number', 'like', '%' . $this->search . '%')
                ->orWhereHas('receiver', fn($q) => $q->where('name', 'like', '%' . $this->search . '%'))
                ->orWhereHas('giver', fn($q) => $q->where('name', 'like', '%' . $this->search . '%'));
        }
        $handovers = $query->orderBy('handover_date', 'desc')->orderBy('id', 'desc')->paginate(10);
        return view('livewire.asset-handovers.manage-asset-handovers', [
            'handovers' => $handovers
        ]);
    }
}