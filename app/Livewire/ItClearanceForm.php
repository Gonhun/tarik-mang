<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\User;
use App\Models\Asset;
use App\Models\ItClearance;
use App\Models\ItClearanceItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ItClearanceForm extends Component
{
    public $clearanceId;
    public $userId;
    public $nik, $department, $position;
    public $resignDate, $checkDate;
    public $additionalNotes;

    // Properti untuk approver
    public $checkedByUserId, $knownByUserId, $approvedByUserId;

    // Properti untuk checklist
    public $checklistItems = [];

    // Data untuk dropdown
    public $allUsers = [];
    public $itUsers = [];
    public $kasiItUsers = [];
    public $kabagIcUsers = [];

    public function mount($clearanceId = null)
    {
        if ($clearanceId) {
            $this->loadOptions();
            $this->loadClearance($clearanceId);
        } else {
            $this->createNewForm(); // sudah memuat dropdown
        }
    }

    /** Data dropdown. */
    protected function loadOptions(): void
    {
        // Hanya kolom yang ditampilkan di dropdown (dulu seluruh kolom ribuan user disimpan di state).
        $this->allUsers = User::where('is_active', true)->orderBy('name')->get(['id', 'name', 'nik']);
        $this->itUsers = User::whereHas('position', fn($q) => $q->where('name', 'like', '%Staff IT%'))->orderBy('name')->get(['id', 'name']);
        $this->kasiItUsers = User::whereHas('position', fn($q) => $q->where('name', 'like', '%Kasi IT%'))->orderBy('name')->get(['id', 'name']);
        $this->kabagIcUsers = User::whereHas('position', fn($q) => $q->where('name', 'like', '%Kabag IC%'))->orderBy('name')->get(['id', 'name']);
    }

    public function createNewForm()
    {
        $this->reset();
        $this->loadOptions(); // dulu memanggil mount() → mount() memanggil createNewForm() lagi: berulang tanpa akhir
        $this->checkDate = now()->format('Y-m-d');
        $this->checkedByUserId = Auth::id(); // Default ke user login
        $this->checklistItems = $this->getStandardAccessItems(); // Mulai dengan item standar
    }

    public function loadClearance($id)
    {
        $clearance = ItClearance::with('items')->findOrFail($id);
        $this->clearanceId = $clearance->id;
        $this->userId = $clearance->user_id;
        $this->resignDate = $clearance->resign_date->format('Y-m-d');
        $this->checkDate = $clearance->check_date->format('Y-m-d');
        $this->additionalNotes = $clearance->additional_notes;
        $this->checkedByUserId = $clearance->checked_by_user_id;
        $this->knownByUserId = $clearance->known_by_user_id;
        $this->approvedByUserId = $clearance->approved_by_user_id;
        
        $this->updatedUserId($this->userId); // Muat detail user

        // Muat item checklist dari database
        $dbItems = [];
        foreach ($clearance->items as $item) {
            // Gunakan deskripsi sebagai kunci untuk mencegah duplikasi jika form di-load ulang
            $dbItems[$item->item_description] = [
                'description' => $item->item_description,
                'status' => $item->status,
                'notes' => $item->notes,
            ];
        }
        $this->checklistItems = array_values($dbItems); // Re-index array
    }

    /**
     * PERBAIKAN UTAMA: Method ini sekarang akan men-generate checklist secara dinamis.
     */
    public function updatedUserId($userId)
    {
        $user = User::with(['department', 'position', 'assetsHeld.assetSeries'])->find($userId);
        if ($user) {
            $this->nik = $user->nik;
            $this->department = $user->department->name ?? 'N/A';
            $this->position = $user->position->name ?? 'N/A';

            // Generate checklist baru
            $newChecklist = [];

            // 1. Tambahkan item untuk setiap aset yang dipegang pengguna
            if ($user->assetsHeld->isNotEmpty()) {
                foreach ($user->assetsHeld as $asset) {
                    $newChecklist[] = [
                        'description' => 'Pengembalian ' . ($asset->assetSeries->name ?? 'Aset') . ' (SN: ' . $asset->serial_number . ')',
                        'status' => 'N/A',
                        'notes' => '',
                    ];
                }
            } else {
                 $newChecklist[] = [
                    'description' => 'Pengembalian Aset Fisik',
                    'status' => 'X',
                    'notes' => 'Tidak ada aset yang terdata atas nama pengguna ini.',
                ];
            }

            // 2. Gabungkan dengan item checklist standar untuk akses
            $standardItems = $this->getStandardAccessItems();
            $this->checklistItems = array_merge($newChecklist, $standardItems);

        } else {
            // Jika user dikosongkan, kembali ke checklist default
            $this->reset(['nik', 'department', 'position']);
            $this->checklistItems = $this->getStandardAccessItems();
        }
    }

    /**
     * Helper untuk mendapatkan item checklist standar (non-aset).
     */
    protected function getStandardAccessItems()
    {
        $items = [
            'Akses Email Kantor Dinonaktifkan',
            'Akses Sistem Internal (ERP, SAP, File Server, dll) Dicabut',
            'Pengalihan Email / Forwarding diatur',
            'Akun GSuite / Microsoft 365 / Domain lain dinonaktifkan',
            'Akun VPN/ Remote Access dicabut',
            'Akses WiFi Internal & Aplikasi Mobile dicabut',
            'Backup data pribadi sudah diselesaikan (jika ada)',
            'Dokumen rahasia / proyek dikembalikan',
        ];

        $checklist = [];
        foreach ($items as $description) {
            $checklist[] = [
                'description' => $description,
                'status' => 'N/A',
                'notes' => '',
            ];
        }
        return $checklist;
    }

    public function saveClearance()
    {
        $this->validate([
            'userId' => 'required|exists:users,id',
            'resignDate' => 'required|date',
            'checkDate' => 'required|date',
            'checkedByUserId' => 'required|exists:users,id',
            'knownByUserId' => 'required|exists:users,id',
            'approvedByUserId' => 'required|exists:users,id',
            'checklistItems.*.status' => 'required|in:V,X,N/A',
            'checklistItems.*.notes' => 'nullable|string',
        ]);

        DB::transaction(function () {
            $clearance = ItClearance::updateOrCreate(
                ['id' => $this->clearanceId],
                [
                    'user_id' => $this->userId,
                    'resign_date' => $this->resignDate,
                    'check_date' => $this->checkDate,
                    'additional_notes' => $this->additionalNotes,
                    'checked_by_user_id' => $this->checkedByUserId,
                    'known_by_user_id' => $this->knownByUserId,
                    'approved_by_user_id' => $this->approvedByUserId,
                ]
            );

            // Hapus item lama untuk memastikan sinkronisasi
            if ($this->clearanceId) {
                $clearance->items()->delete();
            }

            foreach ($this->checklistItems as $itemData) {
                $clearance->items()->create([
                    'item_description' => $itemData['description'],
                    'status' => $itemData['status'],
                    'notes' => $itemData['notes'],
                ]);
            }
            
            if (!$this->clearanceId) {
                $this->clearanceId = $clearance->id;
            }
        });

        session()->flash('message', 'Form Checklist Clearing IT berhasil disimpan.');
    }
    public function render()
    {
        return view('livewire.it-clearance-form');
    }
}
