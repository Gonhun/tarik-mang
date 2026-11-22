<?php

namespace App\Livewire\Inventory\Consumables;

use App\Models\MasterPart;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

class MaseterParts extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 10;

    // Properti untuk Modal/Form Tambah/Edit
    public $partId = null;
    public $part_number = '';
    public $material_number = '';
    public $name = '';
    public $unit = '';
    public $default_price = 0;
    public $description = '';
    public $showModal = false;

    public function render()
    {
        $parts = MasterPart::query()
            ->when($this->search, function ($query) {
                $query->where('part_number', 'like', '%' . $this->search . '%')
                      ->orWhere('name', 'like', '%' . $this->search . '%');
            })
            ->paginate($this->perPage)
            ->withQueryString();
        return view('livewire.inventory.consumables.maseter-parts', [
            'parts' => $parts,
        ]);
    }
    public function updatingSearch()
    {
        $this->resetPage();
    }

    // --- Logika CRUD Sederhana ---

    public function openCreateModal()
    {
        $this->reset(['partId', 'part_number', 'name', 'unit', 'default_price', 'description','material_number']);
        $this->showModal = true;
    }

    public function store()
    {
        $this->validate([
            'part_number' => 'required|string|unique:master_parts,part_number,' . $this->partId,
            'material_number' => 'required|string|unique:master_parts,material_number,' . $this->partId, 
            'name' => 'required|string|max:255',
            'unit' => 'nullable|string|max:50',
            'default_price' => 'nullable|numeric|min:0',
        ]);

        MasterPart::updateOrCreate(['id' => $this->partId], [
           'part_number' => $this->part_number,
            'material_number' => $this->material_number, 
            'name' => $this->name,
            'unit' => $this->unit,
            'default_price' => $this->default_price,
            'description' => $this->description,
        ]);

        $this->showModal = false;
        $this->dispatch('close-modal'); // Dispatch event ke modal (jika menggunakan JS/Alpine)
    }

    public function edit($id)
    {
        $part = MasterPart::findOrFail($id);
        $this->partId = $part->id;
        $this->part_number = $part->part_number;
        $this->material_number = $part->material_number;
        $this->name = $part->name;
        $this->unit = $part->unit;
        $this->default_price = $part->default_price;
        $this->description = $part->description;
        $this->showModal = true;
    }

    public function delete($id)
    {
        MasterPart::destroy($id);
        // Refresh data setelah penghapusan
        $this->dispatch('refreshComponent'); 
    }
}
