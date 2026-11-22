<?php

namespace App\Livewire\AssetDisposals;

use Livewire\Component;
use App\Models\AssetTransaction;
use Livewire\WithPagination;

class ManageDisposed extends Component
{
    use WithPagination;

    public $search = '';
    protected $paginationTheme = 'bootstrap';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = AssetTransaction::query()
            // Hanya ambil transaksi dengan tipe 'PRE_DISPOSAL'
            ->where('transaction_type', 'DISPOSED')
            ->with([
                'items.asset:id,asset_number,serial_number', // Ambil info aset dari item
                'creator:id,name', // User yang membuat pengajuan
                'approvals.approver', // Ambil data approver
                'approvals.actionBy' // Ambil data user yang sudah memberi aksi
            ]);

        if (!empty($this->search)) {
            $searchTerm = '%' . $this->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('transaction_number', 'like', $searchTerm)
                  ->orWhere('status', 'like', $searchTerm)
                  ->orWhereHas('creator', fn($sq) => $sq->where('name', 'like', $searchTerm))
                  ->orWhereHas('items.asset', fn($sq) => $sq->where('asset_number', 'like', $searchTerm));
            });
        }

        $disposalRequests = $query->orderBy('transaction_date', 'desc')->paginate(10);

        return view('livewire.asset-disposals.manage-disposed', [
            'disposalRequests' => $disposalRequests,
        ]);
    }
}
