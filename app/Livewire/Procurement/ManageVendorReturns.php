<?php

namespace App\Livewire\Procurement;

use Livewire\Component;
use App\Models\AssetRequest;
use App\Models\AssetRequestItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;

class ManageVendorReturns extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';
    public $isUpdateModalOpen = false;
    public ?AssetRequestItem $itemToUpdate = null;
    public $update_pr_number, $update_gr_number, $update_po_number;

    /**
     * Membuka modal untuk mengupdate data pengadaan item yang gagal QC.
     */
    public function openUpdateModal($itemId)
    {
        $this->itemToUpdate = AssetRequestItem::find($itemId);
        if ($this->itemToUpdate) {
           
            // Kosongkan field untuk diisi data baru
            $this->update_pr_number = $this->itemToUpdate->pr_number;
            $this->update_gr_number = $this->itemToUpdate->gr_number;
            $this->update_po_number = $this->itemToUpdate->po_number;
            $this->resetValidation();
            $this->isUpdateModalOpen = true;
          
        }
    }

    public function closeUpdateModal()
    {
        $this->isUpdateModalOpen = false;
        $this->itemToUpdate = null;
    }

    /**
     * Menyimpan data PR/Akunting/PO yang baru dan mengembalikan item ke alur QC.
     */
    public function saveUpdatedItemDetails()
    {
        if (!$this->itemToUpdate) return;

        $this->validate([
            'update_pr_number' => 'required|string|max:10',
            'update_gr_number' => 'required|string|max:10',
            'update_po_number' => 'required|string|max:12',
        ], [], [
            'update_pr_number' => 'Nomor PR Baru',
            'update_gr_number' => 'Nomor Akunting Baru',
            'update_po_number' => 'Nomor PO Baru',
        ]);

        DB::transaction(function () {
            // Update item dengan data pengadaan yang baru dan reset status QC
            $this->itemToUpdate->update([
                'pr_number' => $this->update_pr_number,
                'gr_number' => $this->update_gr_number,
                'po_number' => $this->update_po_number,
                'qc_status' => 'Pending', // Kembalikan ke antrian QC
            ]);

            // Cek apakah masih ada item lain yang gagal di pengajuan yang sama
            $otherFailedItems = $this->itemToUpdate->assetRequest->items()
                                    ->where('qc_status', 'Failed')
                                    ->exists();

            // Jika ini adalah item gagal terakhir yang diproses, ubah status pengajuan utama
            if (!$otherFailedItems) {
                // Kembalikan status ke tahap di mana input No. PO dilakukan
                $this->itemToUpdate->assetRequest->update(['status' => 'Pending PO Number']);
            }
        });
        
        session()->flash('message', 'Item telah dikembalikan ke alur QC dengan data pengadaan yang baru.');
        $this->closeUpdateModal();
    }

    /**
     * Aksi untuk memproses ulang item dengan membuat PO baru (ganti vendor).
     */
    public function reprocessForNewPo($itemId)
    {
        $item = AssetRequestItem::find($itemId);

        if ($item && $item->qc_status === 'Failed') {
            DB::transaction(function () use ($item) {
                // Reset data pengadaan lama dan status QC
                $item->update([
                    'pr_number' => null,
                    'gr_number' => null,
                    'po_number' => null,
                    'qc_status' => 'Pending', // Kembalikan ke 'Pending' agar muncul di menu QC lagi
                ]);

                // Cek apakah masih ada item lain yang gagal di pengajuan yang sama
                $otherFailedItems = $item->assetRequest->items()
                                        ->where('qc_status', 'Failed')
                                        ->exists();

                // Jika ini adalah item gagal terakhir, ubah status pengajuan utama
                if (!$otherFailedItems) {
                    $item->assetRequest->update(['status' => 'Pending PR & Accounting Number']);
                }
            });
            
            session()->flash('message', 'Item telah dikembalikan ke alur pengadaan untuk dibuatkan PO baru.');
        } else {
            session()->flash('error', 'Aksi tidak bisa dilakukan.');
        }
    }

    /**
     * Aksi untuk memproses ulang item dengan vendor yang sama (menunggu GR baru).
     */
    public function reprocessForNewGr($itemId)
    {
        $item = AssetRequestItem::find($itemId);

        if ($item && $item->qc_status === 'Failed') {
            DB::transaction(function () use ($item) {
                // Reset status QC, tapi pertahankan nomor PR/PO lama
                $item->update([
                    'qc_status' => 'Pending', // Kembalikan ke 'Pending' agar muncul di menu QC lagi
                ]);

                $otherFailedItems = $item->assetRequest->items()
                                        ->where('qc_status', 'Failed')
                                        ->exists();
                
                if (!$otherFailedItems) {
                    $item->assetRequest->update(['status' => 'Pending PO Number']);
                }
            });

            session()->flash('message', 'Item telah dikembalikan ke alur QC untuk menunggu barang pengganti (GR baru).');
        } else {
            session()->flash('error', 'Aksi tidak bisa dilakukan.');
        }
    }

    public function render()
    {
        // Ambil semua item yang gagal QC dari pengajuan yang statusnya 'Requires Return'
        $failedItems = AssetRequestItem::query()
            ->where('qc_status', 'Failed')
            ->whereHas('assetRequest', function ($query) {
                $query->where('status', 'Requires Return');
            })
            ->with(['assetRequest:id,request_number', 'assetSeries:id,name'])
            ->paginate(10);

        return view('livewire.procurement.manage-vendor-returns', [
            'failedItems' => $failedItems
        ]);
    }
}
