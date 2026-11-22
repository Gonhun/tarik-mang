<?php

namespace App\Livewire\Sidebar;

use Livewire\Component;
use App\Models\AssetRequest;
use App\Models\AssetRequestItem;
use Illuminate\Support\Facades\Auth;

class MenuItem extends Component
{
    /**
     * Nama route yang akan dituju oleh link menu.
     * @var string
     */
    public $routeName;

    /**
     * Label atau teks yang akan ditampilkan di menu.
     * @var string
     */
    public $label;

    /**
     * (Opsional) Tipe badge yang akan dihitung.
     * Contoh: 'awaiting_my_action' untuk menghitung tugas approval.
     * @var string|null
     */
    public $badgeType = null;
    public $icon = null;
    /**
     * Jumlah yang akan ditampilkan di badge.
     * @var int
     */
    public $badgeCount = 0;

    /** Status aktif dihitung sekali saat halaman dimuat (saat poll, request-nya adalah request Livewire). */
    public bool $active = false;

    /**
     * Dijalankan saat komponen pertama kali dimuat.
     */
    public function mount($routeName, $label, $badgeType = null, $icon = null, $activePattern = null)
    {
        $this->active = request()->routeIs($activePattern ?? $routeName);
        $this->routeName = $routeName;
        $this->label = $label;
        $this->badgeType = $badgeType;
        $this->icon = $icon;
        // Hitung jumlah badge saat pertama kali dimuat
        // $this->updateBadgeCount();
    }

    /**
     * Method utama untuk menghitung jumlah badge berdasarkan tipenya.
     */
    public function updateBadgeCount()
    {
        // Hanya jalankan jika tipe badge sudah ditentukan
        if (!$this->badgeType) {
            return;
        }

        // Gunakan switch untuk menangani berbagai jenis logika badge
        switch ($this->badgeType) {
            case 'awaiting_my_action':
                $this->badgeCount = $this->getAwaitingMyActionCount();
                break;
            case 'asset_qc_count':
                $this->badgeCount = $this->getAssetQcCount();
                break;
            case 'return_asset_qc_count':
                $this->badgeCount = $this->getReturnAssetQcCount();
                break;
            // Anda bisa menambahkan case lain di sini, misalnya:
            // case 'pending_users':
            //     $this->badgeCount = \App\Models\User::where('is_approved', false)->count();
            //     break;
        }
    }

    /**
     * Logika spesifik untuk menghitung pengajuan yang menunggu aksi dari user saat ini.
     */
    private function getAwaitingMyActionCount(): int
    {
        $user = Auth::user();
        if (!$user) {
            return 0;
        }

        // Query ini mencari semua pengajuan yang statusnya pending DAN
        // user yang login adalah approver yang gilirannya telah tiba.
        $query = AssetRequest::query();
        
        // Status-status yang dianggap masih dalam proses approval
        $pendingStatuses = ['Pending IT Item Validation', 'Pending Approval'];

        $query->whereIn('status', $pendingStatuses)
            ->whereHas('approvals', function ($approvalQuery) use ($user) {
                // Cari tahapan approval di dalam pengajuan ini yang...
                $approvalQuery->where('status', 'Pending') // 1. Status tahapannya masih 'Pending'...
                              ->where('approver_user_id', $user->id) // 2. Ditujukan untuk user ini...
                              ->where('step_order', function ($stepSubQuery) {
                                    // 3. DAN merupakan tahapan dengan urutan terendah di antara semua yang masih pending
                                    //    untuk pengajuan ini. Ini memastikan approval berjalan sesuai urutan.
                                    $stepSubQuery->selectRaw('MIN(step_order)')
                                                 ->from('asset_request_approvals as sub_approvals')
                                                 ->whereColumn('sub_approvals.asset_request_id', 'asset_request_approvals.asset_request_id')
                                                 ->where('sub_approvals.status', 'Pending');
                              });
            });

        return $query->count();

        return $query->count();
    }
    private function getAssetQcCount(): int
    {
        $user = Auth::user();
        // Otorisasi: Hanya user yang punya izin yang bisa melihat count ini
        // Ganti 'process procurement' dengan nama permission yang sesuai
        if (!$user) {
            return 0;
        }

        // Hitung item yang merupakan pembelian baru, sudah punya No. PO,
        // tapi belum didaftarkan sebagai aset fisik.
        return AssetRequestItem::query()
            ->where('fulfillment_type', 'NEW_PURCHASE')
            ->where('qc_status', 'Pending')
            ->whereNotNull('gr_number')
            ->where('gr_number', '!=', '')
            ->where('gr_number', '!=', 'NULL')
            ->whereNotNull('po_number')
            ->where('po_number', '!=', '')
            ->where('po_number', '!=', 'NULL')
            ->whereDoesntHave('procuredAsset') // Cek jika belum ada aset yang terhubung
            ->count();
    }
    private function getReturnAssetQcCount(): int
    {
        $user = Auth::user();
        // Otorisasi: Hanya user yang punya izin yang bisa melihat count ini
        // Ganti 'process procurement' dengan nama permission yang sesuai
        if (!$user) {
            return 0;
        }

        // Hitung item yang merupakan pembelian baru, sudah punya No. PO,
        // tapi belum didaftarkan sebagai aset fisik.
        return AssetRequestItem::query()
            ->where('fulfillment_type', 'NEW_PURCHASE')
            ->where('qc_status','Failed')
            ->whereNotNull('gr_number')
            ->whereDoesntHave('procuredAsset') // Cek jika belum ada aset yang terhubung
            ->count();
    }

    /**
     * Merender view. Method ini akan dipanggil ulang oleh wire:poll.
     */
    public function render()
    {
        // Hitung ulang jumlah badge setiap kali komponen di-render
        $this->updateBadgeCount();
        return view('livewire.sidebar.menu-item');
    }
}
