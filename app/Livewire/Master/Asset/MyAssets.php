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
    public function openReturnModal($assetId)
    {
        $this->dispatch('openReturnModal', $assetId);
    }
    public function confirmAccept($id)
    {
        $asset = Asset::find($id);
        if($asset){
            $this->dispatch('swal:question', [
                'id' => $id,
                'title' => 'Accept Asset?',
                'text' => 'Apakah anda yankin bahwa Asset '. $asset->assetSeries->name .' sudah anda terima' ,
                'method' => 'AcceptAssetId'
            ]);
            
        }
        
    }
    public function AcceptAssetId($id)
    {
        $asset = Asset::findOrFail($id);
        $asset->status = 'Used';
        $asset->save();
        $this->dispatch('swal:success', [
            'id' => $id,
            'title' => 'Berhasil!',
            'text' => 'Aset telah diterima.',
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
