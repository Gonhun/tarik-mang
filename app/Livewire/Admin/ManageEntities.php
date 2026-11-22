<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Entity;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;


class ManageEntities extends Component
{
    use WithPagination;

    public $entity_id, $code, $name;
    public $isModalOpen = false;
    public $search = '';
    protected $paginationTheme = 'bootstrap';

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'code' => [
                'required',
                'string',
                'max:50', // Sesuaikan panjang maksimal jika perlu
                Rule::unique('entities', 'code')->ignore($this->entity_id),
            ],
        ];
    }

    protected $messages = [
        'name.required' => 'Nama entitas wajib diisi.',
        'code.required' => 'Kode entitas wajib diisi.',
        'code.unique' => 'Kode entitas sudah digunakan.',
        'code.max' => 'Kode entitas maksimal 50 karakter.',
    ];

    public function render()
    {
        $query = Entity::query();

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('code', 'like', '%' . $this->search . '%');
            });
        }

        $entities = $query->orderBy('id', 'DESC')->paginate(10);

        return view('livewire.admin.manage-entities', [
            'entities' => $entities,
        ]); // Sesuaikan dengan layout utama Anda
    }

    public function create()
    {
        $this->resetInputFields();
        $this->openModal();
    }

    public function openModal()
    {
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->resetValidation();
    }

    private function resetInputFields()
    {
        $this->entity_id = null;
        $this->code = '';
        $this->name = '';
        $this->resetValidation();
    }

    public function store()
    {
        $this->validate();

        Entity::updateOrCreate(['id' => $this->entity_id], [
            'code' => strtoupper($this->code), // Simpan kode dalam huruf besar
            'name' => $this->name,
        ]);
        $this->dispatch('swal:success', [
            'title' => 'Berhasil!',
            'text' => $this->entity_id ? 'Entitas berhasil diperbarui.' : 'Entitas berhasil ditambahkan.',
        ]);
        $this->closeModal();
        $this->resetInputFields();
    }

    public function edit($id)
    {
        $entity = Entity::findOrFail($id);
        $this->entity_id = $id;
        $this->code = strtoupper($entity->code);
        $this->name = ucwords($entity->name);

        $this->openModal();
    }

    public function delete($id)
    {
        $row = \App\Models\Entity::find($id);
        if (!$row) return;

        // Cek pemakaian DULU (data master dihapus permanen / soft delete tidak ditolak database).
        if ($reasons = \App\Support\MasterUsage::reasons('entity', (int) $row->id)) {
            $this->dispatch('swal:error', ['title' => 'Tidak bisa dihapus', 'text' => \App\Support\MasterUsage::message('Entitas ' . $row->name, $reasons)]);
            return;
        }

        try {
            $row->delete();
            $this->dispatch('swal:success', ['title' => 'Dihapus', 'text' => 'Entitas ' . $row->name . ' berhasil dihapus.']);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Hapus Entitas #' . $row->id . ' gagal: ' . $e->getMessage());
            $this->dispatch('swal:error', ['title' => 'Tidak bisa dihapus', 'text' => 'Entitas ' . $row->name . ' masih terhubung dengan data lain.']);
        }
    }
    public function updatedSearch()
    {
        $this->resetPage(); // Reset pagination saat melakukan pencarian
    }
    
}
