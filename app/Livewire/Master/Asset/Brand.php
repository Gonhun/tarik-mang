<?php

namespace App\Livewire\Master\Asset;

use Livewire\Component;
use App\Models\AssetBrand;
use App\Models\AssetType; // Untuk mengambil daftar tipe aset
use Livewire\WithPagination;
use Illuminate\Validation\Rule;
use Livewire\WithFileUploads; // <-- 1. Import trait untuk upload file
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Validate;

class Brand extends Component
{
    use WithPagination,WithFileUploads;
    #[Validate('nullable|image|max:2048')]
    public $photo; // <-- 3. Properti untuk file upload baru
    public $existing_photo_path;

    public $asset_brand_id, $asset_type_id, $name;
    public $assetTypes = []; // Untuk menyimpan daftar tipe aset
    public $isModalOpen = false;
    public $search = '';
    protected $paginationTheme = 'bootstrap';

    protected function rules()
    {
        return [
            'asset_type_id' => 'required|exists:asset_types,id',
            'name' => 'required|string|max:255',
            'photo' => 'nullable|image|max:2048',
        ];
    }

    protected $messages = [
        'asset_type_id.required' => 'Tipe aset wajib dipilih.',
        'asset_type_id.exists' => 'Tipe aset tidak valid.',
        'name.required' => 'Nama merek aset wajib diisi.',
        'photo.image' => 'File harus berupa gambar.',
        'photo.max' => 'Ukuran gambar maksimal 2MB.',
    ];

    public function mount()
    {
        // Ambil daftar tipe aset untuk dropdown
        $this->assetTypes = AssetType::orderBy('name')->get(['id', 'name']);
    }

    public function render()
    {
        $query = AssetBrand::with('assetType'); // Eager load relasi assetType

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhereHas('assetType', function ($subQuery) {
                      $subQuery->where('name', 'like', '%' . $this->search . '%');
                  });
            });
        }

        $assetBrands = $query->orderBy('id', 'DESC')->paginate(10);

        return view('livewire.master.brand', [
            'assetBrands' => $assetBrands,
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
        $this->asset_brand_id = null;
        $this->asset_type_id = null; // Atau set ke default jika ada
        $this->name = '';
        $this->photo = null; // <-- Reset properti foto
        $this->existing_photo_path = null; // Reset path foto yang ada
        $this->resetValidation();
    }

    public function store()
    {
        $validatedData = $this->validate();
        $dataToStore = [
            'asset_type_id' => $validatedData['asset_type_id'],
            'name' => $validatedData['name'],
        ];

        if ($this->photo) { // Jika ada file foto baru yang diupload
            // Hapus foto lama jika ada dan ini adalah proses update
            if ($this->asset_brand_id && $this->existing_photo_path) {
                Storage::disk('public')->delete($this->existing_photo_path);
            }
            // Simpan foto baru ke 'storage/app/public/asset_brand_photos'
            $dataToStore['photo_path'] = $this->photo->store('asset_brand_photos', 'public');
        } elseif (!$this->asset_brand_id) {
            // Jika ini adalah create dan tidak ada foto diupload, set photo_path ke null
            $dataToStore['photo_path'] = null;
        }
        // Jika ini update dan tidak ada foto baru diupload, jangan ubah photo_path yang sudah ada (Eloquent akan menanganinya).

        AssetBrand::updateOrCreate(['id' => $this->asset_brand_id], $dataToStore);
       

        session()->flash('message',
            $this->asset_brand_id ? 'Merek Aset berhasil diperbarui.' : 'Merek Aset berhasil ditambahkan.');

        $this->closeModal();
        $this->resetInputFields();
    }

    public function edit($id)
    {
        $assetBrand = AssetBrand::findOrFail($id);
        $this->asset_brand_id = $id;
        $this->asset_type_id = $assetBrand->asset_type_id;
        $this->name = $assetBrand->name;
        $this->existing_photo_path = $assetBrand->photo_path; // <-- Simpan path foto yang ada
        $this->photo = null; // Reset input file baru
        $this->openModal();
    }

    public function delete($id)
    {
         try {
            $assetBrand = AssetBrand::find($id);
            if ($assetBrand) {
                // Hapus foto dari storage jika ada
                if ($assetBrand->photo_path) {
                    Storage::disk('public')->delete($assetBrand->photo_path);
                }
                $assetBrand->delete();
                session()->flash('message', 'Merek Aset berhasil dihapus.');
            } else {
                session()->flash('error', 'Merek Aset tidak ditemukan.');
            }
           
        } catch (\Illuminate\Database\QueryException $e) {
             session()->flash('error', 'Gagal menghapus merek aset. Kemungkinan merek ini masih digunakan oleh data lain.');
        } catch (\Exception $e) {
            session()->flash('error', 'Terjadi kesalahan saat menghapus merek aset.');
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }
    
}
