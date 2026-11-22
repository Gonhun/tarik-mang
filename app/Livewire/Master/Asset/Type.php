<?php

namespace App\Livewire\Master\Asset;

use Livewire\Component;
use App\Models\AssetType;
use Livewire\WithPagination; // Untuk pagination
use Illuminate\Validation\Rule;
use Livewire\WithFileUploads; // <-- 1. Import trait untuk upload file
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Validate;

class Type extends Component
{
    use WithFileUploads;
    use WithPagination; // Menggunakan trait pagination

    public $asset_type_id,$name,$requires_bast,$code;
    #[Validate('nullable|image|max:2048')]
    public $photo; // <-- 3. Properti untuk file upload baru
    public $existing_photo_path; // Untuk menampilkan/mengelola foto yang sudah ada saat edit

    public $isModalOpen = false;
    public $search = '';
    protected $paginationTheme = 'bootstrap'; // Menggunakan tema Bootstrap untuk pagination

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'requires_bast' => 'required|boolean',
            'photo' => 'nullable|image|max:2048',
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('asset_types', 'code')->ignore($this->asset_type_id)
            ],
        ];
    }

    protected $messages = [
        'name.required' => 'Nama tipe aset wajib diisi.',
        'photo.image' => 'File harus berupa gambar.',
        'photo.max' => 'Ukuran gambar maksimal 2MB.',
        'requires_bast.required' => 'Kolom "Perlu BAST" wajib diisi.',
        'requires_bast.boolean' => 'Kolom "Perlu BAST" harus berupa pilihan Ya atau Tidak.',
        'code.required' => 'Kode Tipe Aset wajib diisi.',
        'code.unique' => 'Kode ini sudah digunakan oleh Tipe Aset lain.',
    ];

    public function render()
    {
        $query = AssetType::query();

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%');
            });
        }

        $assetTypes = $query->orderBy('id', 'DESC')->paginate(10);
        // dd($assetTypes);
        return view('livewire.master.type', [
            'assetTypes' => $assetTypes,
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
        $this->resetValidation(); // Hapus error validasi saat modal ditutup
    }

    private function resetInputFields()
    {
        $this->asset_type_id = null;
        $this->name = '';
        $this->requires_bast = true;
        $this->photo = null; // <-- Reset properti foto
        $this->existing_photo_path = null; // Reset path foto yang ada
        $this->resetValidation();
    }

    public function store()
    {
        $validatedData = $this->validate();
        $dataToStore = [
            'name' => $validatedData['name'],
            'requires_bast' => $validatedData['requires_bast'],
            'code' => strtoupper($validatedData['code']),
        ];
        if ($this->photo) { 
            if ($this->asset_type_id && $this->existing_photo_path) {
                Storage::disk('public')->delete($this->existing_photo_path);
            }
            $filename = time() . '.' . $this->photo->getClientOriginalExtension();
            $filePath=$this->photo->store('asset_type_photos', 'public');
            $this->photo->storeAs('asset_type_photos', $filename, 'public');
            $dataToStore['photo_path'] = $filePath;
        } elseif (!$this->asset_type_id) {
            $dataToStore['photo_path'] = null;
        }
        AssetType::updateOrCreate(['id' => $this->asset_type_id], $dataToStore);

        session()->flash('message',
            $this->asset_type_id ? 'Tipe Aset berhasil diperbarui.' : 'Tipe Aset berhasil ditambahkan.');

        $this->closeModal();
        $this->resetInputFields();
    }

    public function edit($id)
    {
        $assetType = AssetType::findOrFail($id);
        $this->asset_type_id = $id;
        $this->name = $assetType->name;
        $this->code = $assetType->code;
        $this->requires_bast = $assetType->requires_bast;
        $this->existing_photo_path = $assetType->photo_path; // <-- Simpan path foto yang ada
        $this->photo = null; // Reset input file baru

        $this->openModal();
    }

    public function delete($id)
    {
        try {
            $assetType = AssetType::find($id);
            if ($assetType) {
                // Hapus foto dari storage jika ada
                if ($assetType->photo_path) {
                    Storage::disk('public')->delete($assetType->photo_path);
                }
                $assetType->delete(); // Ini akan soft delete jika model menggunakan SoftDeletes
                session()->flash('message', 'Tipe Aset berhasil dihapus.');
            } else {
                session()->flash('error', 'Tipe Aset tidak ditemukan.');
            }
        } catch (\Illuminate\Database\QueryException $e) {
            // Menangani error jika ada foreign key constraint
             session()->flash('error', 'Gagal menghapus tipe aset. Kemungkinan tipe aset ini masih digunakan oleh data lain.');
        } catch (\Exception $e) {
            session()->flash('error', 'Terjadi kesalahan saat menghapus tipe aset.');
        }
    }

    // Memperbarui data pencarian saat input berubah
    public function updatedSearch()
    {
        $this->resetPage(); // Reset pagination saat melakukan pencarian
    }
    
}
