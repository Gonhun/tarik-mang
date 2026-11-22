<?php

namespace App\Livewire\Licenses\Software;

use App\Models\Softwares;
use Exception;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Main extends Component
{
    use WithPagination;
    public $isModalOpen = false;
    public $search;
    public $software_id, $category_id, $manufacture_id, $name, $version, $description, $is_active;
    public $category_options = [], $manufacture_options = [];

    public function mount()
    {
        $this->category_options = \App\Models\SoftwareCategory::pluck('name', 'id')->toArray();
        $this->manufacture_options = \App\Models\SoftwareManufacturer::pluck('name', 'id')->toArray();
    }

    public function render()
    {
        return view('livewire.licenses.software.main');
    }

    public function create()
    {
        $this->resetInputFields();
        $this->isModalOpen = true;
        $this->dispatch('open-software-modal', [
            'manufacturer_id' => null,
            'category_id' => null
        ]);
    }

    public function edit($id)
    {
        $software = Softwares::findOrFail($id);
        $this->software_id = $id;
        $this->manufacture_id = $software->manufacture_id;
        $this->category_id = $software->category_id;
        $this->name = $software->name;
        $this->version = $software->version;

        $this->isModalOpen = true;

        // Kirim data ke JS agar Select2 diset nilainya
        $this->dispatch('open-software-modal', [
            'manufacturer_id' => $this->software_manufacturer_id,
            'category_id' => $this->software_category_id
        ]);
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->resetValidation();
    }

    public function resetInputFields()
    {
        $this->name = '';
        $this->version = '';
        $this->description = '';
        $this->is_active = true;
        $this->category_id = null;
        $this->manufacture_id = null;
        $this->resetValidation();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    #[Computed]
    public function datas()
    {
        $query = Softwares::query()->with(['manufacturer', 'category']);

        if (!empty($this->search)) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        if (!empty($this->category_id)) {
            $query->where('category_id', $this->category_id);
        }

        if (!empty($this->manufacture_id)) {
            $query->where('manufacture_id', $this->manufacture_id);
        }

        return $query->orderBy('id', 'DESC')->paginate(10);
    }

    public function toggleStatus($id)
    {
        $software = Softwares::findOrFail($id);
        $software->is_active = !$software->is_active;
        $software->save();
        $this->dispatch('swal:success', ['type' => 'success', 'message' => 'Status software berhasil diperbarui!']);
    }

    public function store()
    {
        try {
            DB::beginTransaction();
            $this->validate([
                'name' => 'required|string|max:255',
                'version' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'is_active' => 'boolean',
                'category_id' => 'required|exists:software_category,id',
                'manufacture_id' => 'required|exists:software_manufacturer,id',
            ]);

            Softwares::updateOrCreate(
                ['id' => $this->software_id],
                [
                    'name' => $this->name,
                    'version' => $this->version,
                    'description' => $this->description,
                    'is_active' => $this->is_active,
                    'category_id' => $this->category_id,
                    'manufacture_id' => $this->manufacture_id,
                ]
            );
            DB::commit();
            $this->closeModal();
            $this->dispatch('swal:success', ['type' => 'success', 'message' => $this->software_id ? 'Software berhasil diperbarui!' : 'Software berhasil disimpan!']);
        } catch (Exception $e) {
            DB::rollBack();
            $this->dispatch('swal:error', ['type' => 'error', 'message' => 'Terjadi kesalahan saat menyimpan software!']);
        }
    }
    
}
