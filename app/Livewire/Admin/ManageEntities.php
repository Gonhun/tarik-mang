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
        try {
            Entity::find($id)->delete(); // Ini akan melakukan soft delete jika model menggunakan trait SoftDeletes
            session()->flash('message', 'Entitas berhasil dihapus.');
        } catch (\Illuminate\Database\QueryException $e) {
            // Tangani error jika ada foreign key constraint yang mencegah penghapusan (onDelete('restrict'))
            session()->flash('error', 'Gagal menghapus entitas. Kemungkinan entitas ini masih digunakan oleh data aset.');
        } catch (\Exception $e) {
            session()->flash('error', 'Terjadi kesalahan saat menghapus entitas.');
        }
    }

    public function updatedSearch()
    {
        $this->resetPage(); // Reset pagination saat melakukan pencarian
    }
    
}
