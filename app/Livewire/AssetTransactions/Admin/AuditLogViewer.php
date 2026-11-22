<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use OwenIt\Auditing\Models\Audit; // Model dari paket laravel-auditing
use Livewire\WithPagination;

class AuditLogViewer extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedAudit = null;
    public $isDetailModalOpen = false;
    protected $paginationTheme = 'bootstrap';
    public $perPage = 15; // Jumlah item per halaman

    // Properti untuk filter (bisa ditambahkan nanti)
    // public $filterUserId;
    // public $filterEventType;
    // public $filterAuditableType;
    // public $dateFrom;
    // public $dateTo;

    public function render()
    {
        $query = Audit::with('user'); // Eager load relasi user (jika ada)

        if (!empty($this->search)) {
            $searchTerm = '%' . $this->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('event', 'like', $searchTerm)
                  ->orWhere('auditable_type', 'like', $searchTerm)
                  ->orWhere('url', 'like', $searchTerm)
                  ->orWhere('ip_address', 'like', $searchTerm)
                  ->orWhereHas('user', function ($subQuery) use ($searchTerm) {
                      $subQuery->where('name', 'like', $searchTerm);
                  });
            });
        }

        // Implementasi filter lanjutan jika propertinya sudah ada
        // if ($this->filterUserId) { $query->where('user_id', $this->filterUserId); }
        // if ($this->filterEventType) { $query->where('event', $this->filterEventType); }
        // ... dst untuk filter lain

        $audits = $query->orderBy('created_at', 'desc')->paginate($this->perPage);

        return view('livewire.admin.audit-log-viewer', [
            'audits' => $audits,
        ]); // Sesuaikan dengan layout utama Anda
    }

    public function viewDetails(int $auditId)
    {
        $this->selectedAudit = Audit::find($auditId);
        if ($this->selectedAudit) {
            // old_values dan new_values biasanya sudah di-cast ke array oleh model Audit
            // Jika belum, Anda mungkin perlu json_decode di sini atau di view.
            // Model Audit dari paket biasanya sudah menangani ini.
        }
        $this->isDetailModalOpen = true;
    }

    public function closeDetailModal()
    {
        $this->isDetailModalOpen = false;
        $this->selectedAudit = null;
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    // Helper untuk membersihkan nama model
    public function getCleanModelName($modelPath)
    {
        if (empty($modelPath)) return 'N/A';
        return class_basename($modelPath); // Mengambil nama class saja, misal "Asset" dari "App\Models\Asset"
    }
}
