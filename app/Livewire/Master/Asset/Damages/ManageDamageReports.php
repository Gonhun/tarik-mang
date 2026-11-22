<?php

namespace App\Livewire\Master\Asset\Damages;

use Livewire\Component;
use App\Models\AssetDamageReport;
use App\Models\Asset;
use App\Models\User;
use App\Models\AssetStatusLog;
use App\Models\AssetTransaction;
use App\Models\Jobsite;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ManageDamageReports extends Component
{
    use WithPagination, WithFileUploads;

    // Properti untuk form modal
    public $damage_report_id,$assetEdit, $asset_id, $reported_by_user_id, $report_date, $damage_description;
    public $damage_status, $assessment_notes, $repair_notes, $repair_cost, $repaired_date;
    public $photo1, $photo2, $other_document;
    public $existingPhoto1Path, $existingPhoto2Path, $existingOtherDocumentPath, $existingOtherDocumentName;
    public $from_jobsite_id;
    // Data untuk dropdown di form
    public $assetsForSelection = [];
    public $usersForReporter = [];
    public $allJobsites = [];
    public $selected_assets = [];

    public $filteredAssets = [];
    public $searchAsset = '';
    // Properti UI
    public $isEdit = false;
    public $isModalOpen = false;
    public $search = '';
    protected $paginationTheme = 'bootstrap';

    // Daftar status untuk dropdown
    //'Reported', 'Pending Assessment', 'Assessment Complete',  'Repair Complete', 
    public $damageStatuses = [
        'Under Repair','Awaiting Parts', 'Beyond Economic Repair', 'Closed', 'Cancelled'
    ];

    public function mount()
    {
        $this->allJobsites = Jobsite::orderBy('name')->get(['id', 'name']);
        $this->usersForReporter = User::orderBy('name')->get(['id', 'name']);
    }
    public function updatedFromJobsiteId($jobsiteId)
    {
       
        $this->changeFromJobsiteId($jobsiteId);
    }
    public function updatedSearchAsset()
    {
        // Jika jobsite sudah dipilih, panggil ulang fungsi
        $this->filterAssets();
    }
    public function changeFromJobsiteId($jobsiteId)
    {
        $this->filteredAssets = [];
        if (!empty($jobsiteId)) {
            $this->assetsForSelection = Asset::where('jobsite_id', $jobsiteId)
                ->whereNotIn('status', ['Disposed', 'Missing'])
                ->get()
                ->toArray();
        } else {
            $this->assetsForSelection = [];
        }
    
        $this->filterAssets();
    }
    public function filterAssets()
    {
        $search = strtolower($this->searchAsset ?? '');

        // Jika pencarian kosong, tampilkan semua aset
        if ($search === '') {
            $this->filteredAssets = $this->assetsForSelection;
            return;
        }

        // Filter data tanpa mengubah selected_assets
        $this->filteredAssets = collect($this->assetsForSelection)
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
            'asset_id' => 'required|exists:assets,id',
            'reported_by_user_id' => 'required|exists:users,id',
            'report_date' => 'required|date',
            'damage_description' => 'required|string|min:10',
            'damage_status' => ['required', Rule::in($this->damageStatuses)],
            'assessment_notes' => 'nullable|string',
            'repair_notes' => 'nullable|string',
            'repair_cost' => 'nullable|numeric|min:0',
            'repaired_date' => 'nullable|date|after_or_equal:report_date',
            'photo1' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,png|max:5120',
            'photo2' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,png|max:5120',
            'other_document' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,png|max:5120',
        ];
    }
    public function render()
    {
        $query = AssetDamageReport::with(['asset:id,asset_number,serial_number', 'reporter:id,name']);

        if (!empty($this->search)) {
            $searchTerm = '%' . $this->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('damage_description', 'like', $searchTerm)
                  ->orWhereHas('asset', fn($sq) => $sq->where('asset_number', 'like', $searchTerm)
                                                    ->orWhere('serial_number', 'like', $searchTerm))
                  ->orWhereHas('reporter', fn($sq) => $sq->where('name', 'like', $searchTerm));
            });
        }

        $damageReports = $query->orderBy('report_date', 'desc')->orderBy('id', 'desc')->paginate(10);
        return view('livewire.master.asset.damages.manage-damage-reports',[
            'damageReports' => $damageReports,
        ]);
    }
    private function storeFile($fileProperty, $existingPathProperty, $folderName)
    {
        if ($this->$fileProperty) {
            if ($this->damage_report_id && $this->$existingPathProperty) {
                Storage::disk('public')->delete($this->$existingPathProperty);
            }
            $recordId = $this->damage_report_id ?? 'new';
            return $this->$fileProperty->store("asset_damage_files/{$recordId}/{$folderName}", 'public');
        }
        return $this->$existingPathProperty;
    }

    public function create()
    {
        $this->resetInputFields();
        $this->damage_status = 'Reported';
        $this->report_date = now()->format('Y-m-d');
        $this->reported_by_user_id = Auth::id();
        $this->openModal();
    }

    public function store()
    {
       
        $validatedData = $this->validate();
        
        DB::transaction(function () use ($validatedData) {
           
            $dataToStore = [
                'asset_id' => $this->asset_id,
                'reported_by_user_id' => $this->reported_by_user_id,
                'report_date' => $this->report_date,
                'damage_description' => $this->damage_description,
                'damage_status' => $this->damage_status,
                'assessment_notes' => $this->assessment_notes,
                'repair_notes' => $this->repair_notes,
                'repair_cost' => $this->repair_cost,
                'repaired_date' => $this->repaired_date,
                'updated_by_user_id' => Auth::id(),
            ];

            if (!$this->damage_report_id) {
                $dataToStore['created_by_user_id'] = Auth::id();
            }
           
            $damageReport = AssetDamageReport::updateOrCreate(['id' => $this->damage_report_id], $dataToStore);
            // perbaiki: in_array membutuhkan dua argumen, perbaiki sintaksnya atau hapus jika tidak diperlukan
            // Jika ingin melakukan sesuatu jika damage_status TIDAK salah satu dari status berikut:
            // Perbaikan logika update status aset berdasarkan status laporan kerusakan
           
            // Jika status bukan 'Beyond Economic Repair', 'Closed', atau 'Cancelled', set status aset ke 'Repair'
            if (!in_array($this->damage_status, ['Beyond Economic Repair', 'Closed', 'Cancelled'])) {
                Asset::where('id', $this->asset_id)->update(['status' => 'Repair']);
                
            }

            // Jika status 'Closed' atau 'Cancelled', cek apakah aset punya holder
            if (in_array($this->damage_status, ['Closed', 'Cancelled'])) {
                $asset = Asset::find($this->asset_id);
                    if ($asset && $asset->holder_user_id) {
                        Asset::where('id', $this->asset_id)->update(['status' => 'Used']);
                    } else {
                        Asset::where('id', $this->asset_id)->update(['status' => 'Free']);
                    }
            }

            // Jika status 'Beyond Economic Repair', set status aset ke 'Pre-Disposed'
            if ($this->damage_status === 'Beyond Economic Repair') {
                Asset::where('id', $this->asset_id)->update(['status' => 'Pre-Disposed']);
            }
            // Handle file uploads
            $updatePaths = [];
            if ($this->photo1) $updatePaths['photo1_path'] = $this->storeFile('photo1', 'existingPhoto1Path', 'photos');
            if ($this->photo2) $updatePaths['photo2_path'] = $this->storeFile('photo2', 'existingPhoto2Path', 'photos');
            if ($this->other_document) {
                 $updatePaths['other_document_path'] = $this->storeFile('other_document', 'existingOtherDocumentPath', 'documents');
                 $updatePaths['other_document_name'] = $this->other_document->getClientOriginalName();
            }
            if(!empty($updatePaths)) $damageReport->update($updatePaths);

            // Update status aset utama berdasarkan status laporan kerusakan
            $this->updateMainAssetStatus($damageReport);
        });

        session()->flash('message', $this->damage_report_id ? 'Laporan Kerusakan berhasil diperbarui.' : 'Laporan Kerusakan berhasil dibuat.');
        $this->closeModal();
    }
    
    protected function updateMainAssetStatus(AssetDamageReport $report)
    {
        $asset = $report->asset;
       
        if (!$asset) return;

        $previousAssetStatus = $asset->status;
        $newAssetStatus = $asset->status;

        if (in_array($report->damage_status, ['Reported', 'Pending Assessment', 'Under Repair', 'Awaiting Parts'])) {
            $newAssetStatus = 'Repair';
        } elseif ($report->damage_status === 'Beyond Economic Repair') {
            $newAssetStatus = 'Pre Disposed'; // Atau status khusus 'Beyond Repair'
            $this->generateTransaction($report);
        } elseif ($report->damage_status === 'Repair Complete' || $report->damage_status === 'Closed') {
            // Kembalikan ke 'Free' jika sudah diperbaiki atau kasus ditutup
            $newAssetStatus = 'Free';
        }

        if ($asset->status !== $newAssetStatus) {
            $asset->update(['status' => $newAssetStatus]);
           
        }
    }
    protected function generateTransaction(AssetDamageReport $report)
    {
        $asset = $report->asset;
        $user =Auth::user();
        $transactionNumber=generateMutationNumber('DPS');
        $transaction = AssetTransaction::create([
            'transaction_number' => $transactionNumber,
            'transaction_type' => 'DISPOSED',
            'transaction_date' => now(),
            'from_user_id' => $asset->holder_user_id,
            'notes' => 'Transaksi disposal otomatis dari laporan kerusakan #'.$report->id,
            'status' => 'Pending Approval', // Status awal disposal
            'created_by_user_id' => $user ? $user->id : null,
        ]);
        
        // Tambahkan item ke transaksi disposal
        $transaction->items()->create([
            'asset_id' => $asset->id,
            'condition_notes' => 'Aset didispose karena kerusakan berat. Laporan kerusakan #'.$report->id,
        ]);
        $this->generateMutationApprovalSteps($transaction);        
    }

    protected function generateMutationApprovalSteps(AssetTransaction $transaction)
    {
        $workflow = findWorkflowForContext('DISPOSED', $transaction->position_id, $transaction->jobsite_id);
        if (!$workflow || $workflow->steps->isEmpty()) {
            throw new \Exception('Alur persetujuan belum didefinisikan. Hubungi Administrator.');
        }
        foreach ($workflow->steps as $step) {
            $approverPosition = $step->approverPositionLevel;
            $approverDepartment = $step->department_id;
            $roleName = $approverPosition->name;
            $transaction->approvals()->create([
                'step_order' => $step->step_order,
                'approver_role_name' => $roleName,
                'approver_user_id' => $approverUser->id ?? null, // Fallback jika tidak ditemukan
                'status' => 'Pending',
            ]);
        }
        $transaction->status = $transaction->approvals()->first()->approver_role_name; // Sesuaikan dengan enum Anda
        $transaction->save();

        
        // if ($kasiItUser) {
        //     $number = $kasiItUser->whatsapp;
        //     $msg = "Terdapat permintaan persetujuan untuk penghapusan aset. Silakan cek dan lakukan persetujuan pada sistem.";
        //     $success = sendMessageWithWhatsApp($number,$msg);
        //     if($success)
        //     {
        //         $this->dispatch('swal:success', [
        //             'title' => 'Berhasil!',
        //             'text' => 'Pesan di kirim ke '. $kasiItUser->name .' 🙏',
        //         ]);
        //     }
        // }
    }
    public function edit($id)
    {
        $report = AssetDamageReport::findOrFail($id);
        $this->damage_report_id = $id;
        $this->asset_id = $report->asset->id;
        $this->assetEdit = $report->asset;
        $this->reported_by_user_id = $report->reported_by_user_id;
        $this->report_date = $report->report_date->format('Y-m-d');
        $this->damage_description = $report->damage_description;
        $this->damage_status = $report->damage_status;
        $this->assessment_notes = $report->assessment_notes;
        $this->repair_notes = $report->repair_notes;
        $this->repair_cost = $report->repair_cost;
        $this->repaired_date = $report->repaired_date ? $report->repaired_date->format('Y-m-d') : null;
        $this->existingPhoto1Path = $report->photo1_path;
        $this->existingPhoto2Path = $report->photo2_path;
        $this->existingOtherDocumentPath = $report->other_document_path;
        $this->existingOtherDocumentName = $report->other_document_name;
        $this->isEdit=true;
        $this->openModal();
    }

    public function delete($id)
    {
        $report = AssetDamageReport::find($id);
        if ($report) {
            if ($report->photo1_path) Storage::disk('public')->delete($report->photo1_path);
            if ($report->photo2_path) Storage::disk('public')->delete($report->photo2_path);
            if ($report->other_document_path) Storage::disk('public')->delete($report->other_document_path);
            $report->delete();
            session()->flash('message', 'Laporan Kerusakan berhasil dihapus.');
        }
    }
    public function selectSingleAsset($assetId)
    {
        // toggle (klik ulang akan hilangkan centang)
        $this->selected_assets = $this->selected_assets === $assetId ? null : $assetId;
    }
    private function resetInputFields() { $this->reset(); $this->mount();$this->isEdit=false; }
    public function openModal() { $this->isModalOpen = true; }
    public function closeModal() 
    { 
        $this->selected_assets = [];
        $this->assetsForSelection = [];
        $this->filteredAssets = [];
        $this->searchAsset = '';
        $this->assetEdit='';
        $this->isModalOpen = false;
        $this->isEdit=false; 
    }
    public function updatedSearch() { $this->resetPage(); }
}
