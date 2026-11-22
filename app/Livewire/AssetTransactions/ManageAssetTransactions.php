<?php

namespace App\Livewire\AssetTransactions; // Ganti jika nama direktori/namespace Anda berbeda

use Livewire\Component;
use App\Models\AssetTransaction;        // Model utama yang baru
use App\Models\AssetTransactionItem;   // Model item yang baru
use App\Models\Asset;
use App\Models\User;
use App\Models\AssetType;
use App\Models\AssetBrand;
use App\Models\AssetSeries; // Tambahkan jika Anda ingin memfilter Seri Aset juga
use App\Models\AssetStatusLog; // Jika masih digunakan terpisah
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ManageAssetTransactions extends Component // Ganti nama class jika berbeda
{
    use WithPagination, WithFileUploads;

    // Properti untuk filter aset yang tersedia
    public $filterAssetTypeId = null;
    public $filterAssetBrandId = null;
    // public $filterAssetSeriesId = null; // Opsional jika ingin filter sampai seri
    public $assetTypesForFilter = [];
    public $assetBrandsForFilter = [];
    // public $assetSeriesForFilter = []; // Opsional

    // Properti untuk form AssetTransaction
    public $asset_transaction_id, $transaction_number, $transaction_date, $transaction_type = 'HANDOVER'; // Default tipe
    public $from_user_id, $to_user_id, $notes, $status = 'Draft';
    public $document; // Untuk upload file (nama generik)
    public $existing_document_path, $existing_document_name;

    // Properti untuk item aset
    public $selected_assets = [];
    public $asset_conditions = [];
    public $condition_notes_per_asset = [];

    // Data untuk dropdown
    public $users = [];
    public $availableAssets = [];

    public $isModalOpen = false;
    public $search = '';
    protected $paginationTheme = 'bootstrap';

    // Untuk modal upload dokumen saat penyelesaian
    public $transactionToCompleteId = null;
    public $isUploadDocModalOpen = false;
    public $documentForCompletion;

    protected $listeners = ['doFinalizeTransaction']; // Listener untuk konfirmasi JS

    public function mount()
    {
        $authUserId = Auth::id();
        $this->from_user_id = $authUserId; // Default "from" adalah user login saat create

        $query = User::orderBy('name');
        // if ($authUserId) { // Jika Anda ingin mengecualikan user login dari semua dropdown user
        //     $query->where('id', '!=', $authUserId);
        // }
        $this->users = $query->get(['id', 'name', 'nik']);

        $this->assetTypesForFilter = AssetType::orderBy('name')->get(['id', 'name', 'code']);
        $this->assetBrandsForFilter = collect();
        // $this->assetSeriesForFilter = collect();
        $this->loadAvailableAssets();
        $this->transaction_date = now()->format('Y-m-d');
    }

    // --- Lifecycle Hooks untuk Filter Dinamis ---
    public function updatedFilterAssetTypeId($typeId)
    {
        if (!empty($typeId)) {
            $this->assetBrandsForFilter = AssetBrand::where('asset_type_id', $typeId)
                                            ->orderBy('name')->get(['id', 'name', 'code']);
        } else {
            $this->assetBrandsForFilter = collect();
        }
        $this->filterAssetBrandId = null;
        // $this->filterAssetSeriesId = null;
        // $this->assetSeriesForFilter = collect();
        // $this->selected_assets = []; // Pertimbangkan apakah mau di-reset
        // $this->asset_conditions = [];
        // $this->condition_notes_per_asset = [];
        $this->loadAvailableAssets();
    }
    public function submitSelected()
    {
        // Misalnya ingin ambil aset terpilih
        dd('aaa');

        // Lakukan sesuatu, misalnya emit event, update status, dll.
    }
    public function updatedFilterAssetBrandId($brandId) // Jika Anda ingin filter sampai brand
    {
        // if (!empty($brandId)) {
        //     $this->assetSeriesForFilter = AssetSeries::where('asset_brand_id', $brandId)
        //                                     ->orderBy('name')->get(['id', 'name']);
        // } else {
        //     $this->assetSeriesForFilter = collect();
        // }
        // $this->filterAssetSeriesId = null;
        $this->loadAvailableAssets();
    }
    // public function updatedFilterAssetSeriesId() { $this->loadAvailableAssets(); }

    public function PengembalianLoadAsset()
    {
        if($this->transaction_type != 'HANDOVER')
        {
            
            $this->loadAvailableAssets();
        }
    }
    public function loadAvailableAssets()
    {
        
        if($this->transaction_type == 'HANDOVER')
        {
            $query = Asset::query();
            $query->whereIn('status', ['Free'])
            ->with(['assetType:id,name,code', 'assetBrand:id,name,code', 'assetSeries:id,name']);
            if ($this->filterAssetTypeId) {
                $query->where('asset_type_id', $this->filterAssetTypeId);
            }
            if ($this->filterAssetBrandId) {
                $query->where('asset_brand_id', $this->filterAssetBrandId);
            }
            // if ($this->filterAssetSeriesId) {
            //     $query->where('asset_series_id', $this->filterAssetSeriesId);
            // }
    
            if (!empty($this->selected_assets)) {
                $query->whereNotIn('id', $this->selected_assets);
            }
    
            // (Opsional) Kecualikan aset yang ada di transaksi 'Draft' lain
            $query->whereDoesntHave('transactionItems.assetTransaction', function ($q_transaction) {
                $q_transaction->where('status', 'Draft');
                if ($this->asset_transaction_id) {
                    $q_transaction->where('id', '!=', $this->asset_transaction_id);
                }
            });
            $this->availableAssets = $query->orderBy('asset_number')->get();
        }else{
            if($this->from_user_id)
            {
                
                $query = Asset::query();
                $query->whereIn('status', ['Used'])
                ->where('holder_user_id', $this->from_user_id)
                ->with(['assetType:id,name,code', 'assetBrand:id,name,code', 'assetSeries:id,name']); 
                if ($this->filterAssetTypeId) {
                    $query->where('asset_type_id', $this->filterAssetTypeId);
                }
                if ($this->filterAssetBrandId) {
                    $query->where('asset_brand_id', $this->filterAssetBrandId);
                }
                // if ($this->filterAssetSeriesId) {
                //     $query->where('asset_series_id', $this->filterAssetSeriesId);
                // }
                if (!empty($this->selected_assets)) {
                    $query->whereNotIn('id', $this->selected_assets);
                }
        
                // (Opsional) Kecualikan aset yang ada di transaksi 'Draft' lain
                $query->whereDoesntHave('transactionItems.assetTransaction', function ($q_transaction) {
                    $q_transaction->where('status', 'Draft');
                    if ($this->asset_transaction_id) {
                        $q_transaction->where('id', '!=', $this->asset_transaction_id);
                    }
                });
                $this->availableAssets = $query->orderBy('asset_number')->get();
            }
            
        }
        
    }

    public function generateTransactionNumber()
    {
        $year = date('Y');
        $month = date('m');
        $typePrefix = 'TRX'; // Default
        
        switch ($this->transaction_type) {
            case 'HANDOVER':
                $typePrefix = 'BAST'; // Berita Acara Serah Terima
                break;
            case 'RETURN':
                $typePrefix = 'BAPR'; // Berita Acara Pengembalian
                break;
            // Tambahkan case lain jika perlu
        }

        $prefix = "{$typePrefix}/{$year}/{$month}/";
        $lastTransaction = AssetTransaction::where('transaction_number', 'LIKE', $prefix . '%')
                            ->orderBy('transaction_number', 'desc')->first();
        $nextSequence = 1;
        if ($lastTransaction) {
            $lastSequence = intval(substr($lastTransaction->transaction_number, strlen($prefix)));
            $nextSequence = $lastSequence + 1;
        }
        $this->transaction_number = $prefix . str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
    }

    protected function rules()
    {
        $rules = [
            'transaction_number' => 'required|string|unique:asset_transactions,transaction_number,' . $this->asset_transaction_id,
            'transaction_date' => 'required|date',
            'transaction_type' => 'required|in:HANDOVER,RETURN,DAMAGE_REPORT', // Sesuaikan dengan enum Anda
            'from_user_id' => 'required_if:transaction_type,HANDOVER|required_if:transaction_type,RETURN|exists:users,id|nullable',
            'to_user_id' => 'required_if:transaction_type,HANDOVER|exists:users,id|nullable', // Untuk RETURN, to_user_id bisa jadi admin/gudang
            'notes' => 'nullable|string',
            'status' => 'required|in:Draft,Completed,Cancelled,Pending Inspection,Processed', // Sesuaikan dengan enum Anda
            'document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'selected_assets' => 'required|array|min:1',
            'selected_assets.*' => 'exists:assets,id',
            'asset_conditions.*' => 'nullable|string|max:255',
            'condition_notes_per_asset.*' => 'nullable|string',
        ];

        if ($this->to_user_id && $this->from_user_id) {
             $rules['to_user_id'] .= '|different:from_user_id';
        }
        
        // Validasi dokumen jika status Completed (saat create/edit)
        if ($this->status === 'Completed' && $this->transaction_type === 'HANDOVER') { // Contoh untuk Handover
             if ($this->asset_transaction_id) { // Editing
                if (empty($this->existing_document_path) && !$this->document) {
                    $rules['document'] = ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];
                }
            } else { // Creating
                if (!$this->document) {
                    $rules['document'] = ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];
                }
            }
        }
        // Tambahkan validasi serupa untuk tipe 'RETURN' jika perlu dokumen wajib saat 'Processed'

        return $rules;
    }

     protected $messages = [
        'to_user_id.different' => 'Penerima tidak boleh sama dengan pemberi/pengembali.',
        'selected_assets.required' => 'Minimal satu aset harus dipilih.',
        'document.required' => 'Dokumen wajib di-upload jika status transaksi adalah "Completed" (atau status final lainnya).',
    ];


    public function create($type = 'HANDOVER') // Terima tipe transaksi saat membuat
    {
        $this->resetInputFields();
        $this->transaction_type = $type; // Set tipe transaksi
        $this->generateTransactionNumber();
        $this->status = 'Draft';
        $this->transaction_date = now()->format('Y-m-d');
        $this->from_user_id = Auth::id(); // Default dari user login
        if($type == 'HANDOVER')
        {
            $this->loadAvailableAssets();
        }
        
        $this->openModal();
    }

    public function store()
    {
        $this->validate(); // Akan menggunakan method rules() di atas

        DB::transaction(function () {
            $dataTransaction = [
                'transaction_number' => $this->transaction_number,
                'transaction_date' => $this->transaction_date,
                'transaction_type' => $this->transaction_type,
                'from_user_id' => $this->from_user_id,
                'to_user_id' => $this->to_user_id,
                'notes' => $this->notes,
                'status' => $this->status,
                'created_by_user_id' => Auth::id(),
            ];

            if ($this->document) {
                if ($this->asset_transaction_id && $this->existing_document_path) {
                    Storage::disk('public')->delete($this->existing_document_path);
                }
                $folder = strtolower($this->transaction_type) . '_documents'; // e.g., handover_documents
                $dataTransaction['document_name'] = $this->document->getClientOriginalName();
                $dataTransaction['document_path'] = $this->document->store($folder, 'public');
            }

            $assetTransaction = AssetTransaction::updateOrCreate(['id' => $this->asset_transaction_id], $dataTransaction);

            if ($this->asset_transaction_id) {
                $assetTransaction->items()->delete();
            }

            foreach ($this->selected_assets as $asset_id) {
                $asset = Asset::find($asset_id);
                AssetTransactionItem::create([
                    'asset_transaction_id' => $assetTransaction->id,
                    'asset_id' => $asset_id,
                    'condition_notes' => $this->condition_notes_per_asset[$asset_id] ?? ($this->asset_conditions[$asset_id] ?? null), // Gabungkan atau pilih salah satu
                    'metadata' => json_encode([
                        'previous_asset_status' => $asset ? $asset->status : null,
                        'previous_asset_holder_name' => $asset && $asset->holder ? $asset->holder->name : null,
                        'condition_at_transaction' => $this->asset_conditions[$asset_id] ?? null, // Jika masih dipakai terpisah
                    ]),
                ]);
            }
            
            if ($assetTransaction->status === 'Completed' || $assetTransaction->status === 'Processed') {
                $this->processAssetUpdatesAfterTransaction($assetTransaction);
            }
        });

        session()->flash('message', $this->asset_transaction_id ? 'Transaksi Aset berhasil diperbarui.' : 'Transaksi Aset berhasil dibuat.');
        $this->closeModal();
        $this->resetInputFields();
        $this->dispatch('fileInputReset', ['inputId' => 'document_upload_field_id']); // Ganti dengan ID field upload Anda
    }

    public function edit($id)
    {
        $transaction = AssetTransaction::with('items')->findOrFail($id);
        $this->asset_transaction_id = $id;
        $this->transaction_number = $transaction->transaction_number;
        $this->transaction_date = $transaction->transaction_date->format('Y-m-d');
        $this->transaction_type = $transaction->transaction_type;
        $this->from_user_id = $transaction->from_user_id;
        $this->to_user_id = $transaction->to_user_id;
        $this->notes = $transaction->notes;
        $this->status = $transaction->status;
        $this->existing_document_path = $transaction->document_path;
        $this->existing_document_name = $transaction->document_name;
        
        $this->selected_assets = $transaction->items->pluck('asset_id')->toArray();
        $this->asset_conditions = $transaction->items->pluck('metadata.condition_at_transaction', 'asset_id')->toArray(); // Ambil dari metadata
        $this->condition_notes_per_asset = $transaction->items->pluck('condition_notes', 'asset_id')->toArray();

        $this->document = null;
        $this->filterAssetTypeId = null; // Reset filter
        $this->filterAssetBrandId = null;
        $this->assetBrandsForFilter = collect();
        $this->loadAvailableAssets(); // Muat aset yang bisa ditambahkan
        $this->openModal();
    }
    
    // Menggantikan initiateCompleteHandover
    public function initiateFinalizeTransaction($transactionId)
    {
        $transaction = AssetTransaction::find($transactionId);
        if (!$transaction) { /* ... error ... */ return; }
        if ($transaction->status !== 'Draft' && $transaction->status !== 'Pending Inspection') { /* ... error ... */ return; }

        $this->transactionToCompleteId = $transaction->id;
        
        if (empty($transaction->document_path)) {
            $this->documentForCompletion = null;
            $this->resetValidation('documentForCompletion');
            $this->isUploadDocModalOpen = true;
        } else {
            $this->dispatch('confirm-finalize-transaction', [
                'transactionId' => $transactionId,
                'message' => "Dokumen untuk transaksi No. {$transaction->transaction_number} sudah ada. Lanjutkan menyelesaikan transaksi ini?"
            ]);
        }
    }

    // Menggantikan doFinalizeHandover
    public function doFinalizeTransaction($transactionId)
    {
        $this->finalizeTransactionLogic($transactionId);
    }

    // Menggantikan uploadBastAndFinalize
    public function uploadDocumentAndFinalize()
    {
        $this->validate([
            'documentForCompletion' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ], [ /* ... pesan error ... */ ]);

        $transaction = AssetTransaction::find($this->transactionToCompleteId);
        // ... (error handling jika tidak ditemukan) ...

        if ($transaction->document_path) { Storage::disk('public')->delete($transaction->document_path); }
        
        $folder = strtolower($transaction->transaction_type) . '_documents';
        $transaction->document_name = $this->documentForCompletion->getClientOriginalName();
        $transaction->document_path = $this->documentForCompletion->store($folder . '/' . $transaction->id, 'public');
        $transaction->save();

        $this->finalizeTransactionLogic($this->transactionToCompleteId);
        $this->closeUploadDocModal();
    }

    // Menggantikan finalizeHandoverLogic
    protected function finalizeTransactionLogic($transactionId)
    {
        $transaction = AssetTransaction::with('items.asset')->findOrFail($transactionId);
        // ... (pengecekan status dan dokumen seperti sebelumnya) ...
        if ($transaction->status !== 'Draft' && $transaction->status !== 'Pending Inspection') { /* error */ return; }
        if (empty($transaction->document_path) && ($transaction->transaction_type == 'HANDOVER' || $transaction->transaction_type == 'RETURN')) { /* error, dokumen wajib untuk tipe tertentu */ return; }


        DB::transaction(function () use ($transaction) {
            $finalStatus = 'Completed'; // Default
            // if ($transaction->transaction_type == 'RETURN') {
            //     $finalStatus = 'Completed'; // Atau status lain yang sesuai untuk return
            // }
            $transaction->status = $finalStatus;
            $transaction->save();
            $this->processAssetUpdatesAfterTransaction($transaction);
        });
        session()->flash('message', "Transaksi No. {$transaction->transaction_number} berhasil diselesaikan.");
    }
    
    // Menggantikan processAssetUpdatesAfterHandover
    protected function processAssetUpdatesAfterTransaction(AssetTransaction $transaction)
    {
        foreach ($transaction->items as $item) {
            $asset = $item->asset;
            if ($asset) {
                $previousStatus = $asset->status;
                $newStatus = $asset->status; // Default tetap
                $newHolderId = $asset->holder_user_id;

                if ($transaction->transaction_type === 'HANDOVER') {
                    $newStatus = 'Used';
                    $newHolderId = $transaction->to_user_id;
                } elseif ($transaction->transaction_type === 'RETURN') {
                    // Logika status baru setelah return, bisa dari item->metadata atau default 'Free'/'Repair'
                    $newStatus = $item->metadata['new_asset_status_after_return'] ?? 'Free'; // Ambil dari metadata jika ada
                    if ($item->condition_notes && stripos($item->condition_notes, 'rusak') !== false && $newStatus == 'Free') {
                         // Jika ada catatan kerusakan dan statusnya jadi Free, mungkin harusnya 'Repair'
                         // $newStatus = 'Repair';
                    }
                    $newHolderId = null; // Aset dikembalikan, tidak ada pemegang individu
                }
                // Tambahkan logika untuk tipe transaksi lain

                $asset->status = $newStatus;
                $asset->holder_user_id = $newHolderId;
                $asset->save();

                // Pencatatan di AssetStatusLog (jika masih dipakai terpisah)
                // AssetStatusLog::create([...]);
            }
        }
    }

    public function closeUploadDocModal() { 
        $this->isUploadDocModalOpen = false;
        $this->transactionToCompleteId = null;
        $this->documentForCompletion = null; // Reset file input untuk modal ini
        $this->resetValidation('documentForCompletion'); 
     }
    private function resetInputFields(){ 
        $this->asset_transaction_id = null;
        $this->transaction_number = '';
        $this->transaction_date = now()->format('Y-m-d'); // Set ke tanggal hari ini
        $this->transaction_type = 'HANDOVER'; // Default ke 'HANDOVER' atau tipe lain yang paling umum
        
        $this->from_user_id = Auth::id(); // Default "dari user" adalah user yang sedang login
        $this->to_user_id = null;
        $this->notes = '';
        $this->status = 'Draft'; // Default status

        $this->document = null; // Reset input file
        $this->existing_document_path = null;
        $this->existing_document_name = null;

        $this->selected_assets = [];
        $this->asset_conditions = [];
        $this->condition_notes_per_asset = [];

        // Reset filter untuk daftar aset yang tersedia
        $this->filterAssetTypeId = null;
        $this->filterAssetBrandId = null;
        // $this->filterAssetSeriesId = null; // Jika ada

        $this->assetBrandsForFilter = collect(); // Kosongkan koleksi merek untuk filter
        // $this->assetSeriesForFilter = collect(); // Kosongkan koleksi seri untuk filter

        $this->loadAvailableAssets(); // Muat ulang daftar aset yang tersedia dengan filter kosong

        $this->resetValidation();
     }
    public function openModal(){ $this->isModalOpen = true; }
    public function closeModal(){ $this->isModalOpen = false; }
    public function render() { 
        // Memulai query ke model AssetTransaction
        $query = AssetTransaction::query();

        // Eager load relasi untuk menghindari N+1 problem di view dan untuk pencarian
        // Sesuaikan relasi ini dengan yang benar-benar Anda butuhkan di view tabel daftar
        $query->with([
            'fromUser:id,name,nik', // User yang menyerahkan/mengembalikan
            'toUser:id,name,nik',     // User yang menerima
            'creator:id,name',    // User yang membuat transaksi
            'items',                // Item aset dalam transaksi ini
            // Anda bisa juga eager load detail aset di item jika sering diakses di loop utama,
            // tapi bisa jadi berat jika item banyak. Alternatifnya, load saat item di-loop di view.
            // 'items.asset:id,asset_number,serial_number'
        ]);

        // Logika Pencarian
        if (!empty($this->search)) {
            $searchTerm = '%' . $this->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('transaction_number', 'like', $searchTerm)
                  ->orWhere('transaction_type', 'like', $searchTerm)
                  ->orWhere('status', 'like', $searchTerm)
                  ->orWhereHas('fromUser', function ($subQuery) use ($searchTerm) {
                      $subQuery->where('name', 'like', $searchTerm)
                               ->orWhere('nik', 'like', $searchTerm);
                  })
                  ->orWhereHas('toUser', function ($subQuery) use ($searchTerm) {
                      $subQuery->where('name', 'like', $searchTerm)
                               ->orWhere('nik', 'like', $searchTerm);
                  })
                  ->orWhereHas('items.asset', function ($subQuery) use ($searchTerm) { // Cari berdasarkan nomor aset di item
                        $subQuery->where('asset_number', 'like', $searchTerm)
                                 ->orWhere('serial_number', 'like', $searchTerm);
                  });
                // Anda bisa menambahkan pencarian berdasarkan tanggal jika diperlukan,
                // tapi itu memerlukan parsing tanggal dari string pencarian.
            });
        }

        // Pengurutan data, misalnya berdasarkan tanggal transaksi terbaru
        $transactions = $query->orderBy('transaction_date', 'desc')
                              ->orderBy('id', 'desc') // Pengurutan sekunder untuk konsistensi
                              ->paginate(10); // Jumlah item per halaman

        // Mengirim data ke view
        return view('livewire.asset-transactions.manage-asset-transactions', [
            'transactions' => $transactions,
        ]);
    }
    // Hapus cancelHandover(), ganti dengan cancelTransaction() jika perlu
}