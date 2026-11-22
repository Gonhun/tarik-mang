<?php

namespace App\Livewire\Master\Asset;

use Livewire\Component;
use App\Models\Asset;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

class MyAssets extends Component
{
    use WithPagination;

    public $search = '';
    protected $paginationTheme = 'bootstrap';
    protected $listeners = ['AcceptAssetId', 'deletePermissionConfirmed','refreshMyAssets' => '$refresh'];

    /**
     * Reset pagination saat melakukan pencarian.
     */
    public function updatedSearch()
    {
        $this->resetPage();
    }
    public function reportLost($assetId)
    {
        $this->dispatch('openDisposalModal', ['asset_id' => $assetId, 'mode' => 'LOST']);
    }

    public function openReturnModal($assetId)
    {
        $this->dispatch('openReturnModal', $assetId);
    }
    /**
     * Aset milik user yang login dan masih menunggu konfirmasi terima (Pre-Used).
     */
    protected function pendingAssetForMe($id): ?Asset
    {
        return Asset::with('assetSeries:id,name')
            ->whereKey($id)
            ->where('holder_user_id', Auth::id())
            ->where('status', 'Pre-Used')
            ->first();
    }

    public function confirmAccept($id)
    {
        $asset = $this->pendingAssetForMe($id);
        if (!$asset) {
            $this->dispatch('swal:error', ['title' => 'Gagal', 'text' => 'Aset tidak ditemukan atau tidak menunggu konfirmasi Anda.']);
            return;
        }

        $this->dispatch('swal:question', [
            'id' => $id,
            'title' => 'Terima aset?',
            'text' => 'Apakah Anda yakin aset ' . $asset->asset_number . ' (' . ($asset->assetSeries->name ?? '-') . ') sudah Anda terima?',
            'method' => 'AcceptAssetId'
        ]);
    }

    public function AcceptAssetId($id)
    {
        // Hanya pemegang aset yang boleh mengonfirmasi, dan hanya dari status Pre-Used.
        $asset = $this->pendingAssetForMe($id);
        if (!$asset) {
            $this->dispatch('swal:error', ['title' => 'Gagal', 'text' => 'Aset tidak ditemukan atau tidak menunggu konfirmasi Anda.']);
            return;
        }

        $asset->update(['status' => 'Used']);
        $this->dispatch('swal:success', [
            'id' => $id,
            'title' => 'Berhasil!',
            'text' => 'Aset ' . $asset->asset_number . ' telah diterima.',
        ]);
    }

    public function render()
    {
        // Ambil ID user yang sedang login
        $userId = Auth::id();

        // Buat query dasar untuk model Asset
        $query = Asset::query()
            // Kondisi utama: cari aset yang kolom 'holder_user_id'-nya adalah ID user yang login
            ->where('holder_user_id', $userId)
            // Eager load relasi untuk ditampilkan di tabel agar efisien
            ->with(['assetType', 'assetBrand', 'assetSeries', 'jobsite']);

        // Terapkan filter pencarian jika ada input
        if (!empty($this->search)) {
            $searchTerm = '%' . $this->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('asset_number', 'like', $searchTerm)
                  ->orWhere('serial_number', 'like', $searchTerm)
                  ->orWhereHas('assetType', fn($sq) => $sq->where('name', 'like', $searchTerm))
                  ->orWhereHas('assetBrand', fn($sq) => $sq->where('name', 'like', $searchTerm))
                  ->orWhereHas('assetSeries', fn($sq) => $sq->where('name', 'like', $searchTerm));
            });
        }

        // Ambil data dengan pagination
        $myAssets = $query->orderBy('updated_at', 'desc')->paginate(10);
        return view('livewire.master.asset.my-assets', [
            'myAssets' => $myAssets,
        ]);
    }
}
