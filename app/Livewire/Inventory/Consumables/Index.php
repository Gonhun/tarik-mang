<?php

namespace App\Livewire\Inventory\Consumables;

use App\Models\Jobsite;
use App\Models\MasterPart;
use App\Models\PartTransaction;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedJobsite = ''; 
    public $perPage = 10;
    public $jobsites;
    
    // --- Properti untuk Transaksi ---
    public $showTransactionModal = false;
    public $transactionType = ''; // IN atau OUT
    public $selectedPartId = null;
    public $partDetails = null;
    public $quantity = 0;
    public $notes = '';
    public $filterJobsite;
    public $transactionDate; // Tanggal transaksi

    // --- Properti untuk Mutasi ---
    public $showMutationModal = false;
    public $mutationHistory;
    
    protected $rules = [
        'quantity' => 'required|integer|min:1',
        'notes' => 'nullable|string',
    ];

    public function mount()
    {
        // Ambil daftar jobsite saat komponen dimuat
        $this->jobsites = Jobsite::orderBy('code')->get();
        // Atur Jobsite default jika diperlukan
        if ($this->jobsites->isNotEmpty()) {
            $this->selectedJobsite = $this->jobsites->where('id',Auth::user()->jobsite_id)->first()->id;
        }
        $this->transactionDate = now()->format('Y-m-d');
    }
    public function render()
    {
        $parts = MasterPart::query()
            ->when($this->search, function ($query) {
                $query->where('part_number', 'like', '%' . $this->search . '%')
                      ->orWhere('name', 'like', '%' . $this->search . '%');
            })
            // ->when($this->filterJobsite, function ($query) {
            //     $query->whereHas('transactions', function ($q) {
            //         $q->where('jobsite_id', $this->filterJobsite);
            //     });
            // })
            ->paginate($this->perPage)
            ->withQueryString();

        return view('livewire.inventory.consumables.index', [
            'parts' => $parts,
        ]);
    }
    public function openTransactionModal($partId, $type)
    {
        $this->reset(['quantity', 'notes']); // Reset form
        $this->selectedPartId = $partId;
        $this->transactionType = $type;
        $this->partDetails = MasterPart::findOrFail($partId);
        $this->showTransactionModal = true;
    }

    public function processTransaction()
    {
        $this->validate();

        $currentStock = $this->getStock($this->selectedPartId, $this->selectedJobsite);

        if ($this->transactionType === 'OUT' && $this->quantity > $currentStock) {
            session()->flash('error', 'Stok keluar melebihi stok yang tersedia (' . $currentStock . ').');
            return;
        }

        PartTransaction::create([
            'master_part_id' => $this->selectedPartId,
            'jobsite_id' => $this->selectedJobsite,
            'type' => $this->transactionType,
            'quantity' => $this->quantity,
            'notes' => $this->notes,
            'user_id' => Auth::id(),
            'transaction_date' => $this->transactionDate,
        ]);

        $this->showTransactionModal = false;
        session()->flash('message', 'Transaksi ' . $this->transactionType . ' berhasil diproses.');
        $this->dispatch('refreshComponent'); // Memicu refresh tampilan
    }

    // --- Logika Mutasi (Riwayat Transaksi) ---

    public function showMutation($partId)
    {
        $this->selectedPartId = $partId;
        $this->partDetails = MasterPart::findOrFail($partId);
        
        $this->mutationHistory = PartTransaction::where('master_part_id', $partId)
            ->where('jobsite_id', $this->selectedJobsite)
            ->orderByDesc('transaction_date')
            ->limit(50) // Batasi riwayat
            ->with('user', 'jobsite')
            ->get();
            
        $this->showMutationModal = true;
    }

    // Metode untuk menghitung Stok (Digunakan di View)
    public function getStock(int $partId, int $locationId): int
    {
        $part = MasterPart::find($partId);
        if ($part) {
            return $part->getStockByLocation($locationId);
        }
        return 0;
    }
    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSelectedJobsite()
    {
        $this->resetPage();
    }
    
  
}
