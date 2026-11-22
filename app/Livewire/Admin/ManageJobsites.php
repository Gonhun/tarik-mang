<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Jobsite; // Pastikan model Jobsite Anda sudah ada
use Livewire\WithPagination; // Jika Anda ingin menampilkan daftar juga
use Illuminate\Validation\Rule;

class ManageJobsites extends Component
{
    use WithPagination; // Hapus jika tidak ada daftar dan pagination

    // Properti untuk form
    public $jobsite_id; // Untuk mode edit, null saat create
    public $name;
    public $code;
    public $location;
    public $isEdit=False;

    public $isModalOpen = false; // Untuk mengontrol visibilitas modal form
    protected $paginationTheme = 'bootstrap'; // Jika menggunakan pagination

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'code' => [
                'required',
                'string',
                'max:50', // Sesuaikan panjang maksimal
                Rule::unique('jobsites', 'code')->ignore($this->jobsite_id), // Kode harus unik di antara yang aktif
            ],
            'location' => 'nullable|string|max:500',
        ];
    }

    protected $messages = [
        'name.required' => 'Nama jobsite wajib diisi.',
        'code.required' => 'Kode jobsite wajib diisi.',
        'code.unique' => 'Kode jobsite sudah digunakan.',
        'code.max' => 'Kode jobsite maksimal 50 karakter.',
    ];
    public $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }
    public function render()
    {
        // Bagian ini untuk menampilkan daftar jobsites (opsional jika hanya fokus pada form tambah)
        
        // Fitur search
       

        // Ubah query jobsites agar mendukung pencarian
        $jobsites = Jobsite::query()
            ->when($this->search, function ($query) {
                $search = trim($this->search);
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy('name', 'asc')
            ->paginate(10);
        return view('livewire.admin.manage-jobsites', [
            'jobsites' => $jobsites, // Kirim data ini jika Anda menampilkan daftar
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
        $this->jobsite_id = null;
        $this->name = '';
        $this->code = '';
        $this->location = '';
        $this->isEdit=false;
        $this->resetValidation();
    }

    public function store()
    {
        $validatedData = $this->validate();

        Jobsite::updateOrCreate(['id' => $this->jobsite_id], [
            'name' => $validatedData['name'],
            'code' => strtoupper($validatedData['code']), // Simpan kode dalam huruf besar
            'location' => $this->location, // Ambil langsung dari properti
        ]);
        $this->dispatch('swal:success', [
            'title' => 'Berhasil!',
            'text' => $this->jobsite_id ? 'Jobsite berhasil diperbarui.' : 'Jobsite berhasil ditambahkan.',
        ]);

        $this->closeModal();
        $this->resetInputFields();
    }

    public function edit($id) // Method edit jika Anda membuat CRUD lengkap
    {
        $jobsite = Jobsite::findOrFail($id);
        $this->jobsite_id = $id;
        $this->name = $jobsite->name;
        $this->code = $jobsite->code;
        $this->location = $jobsite->location;
        $this->isEdit=True;
        $this->openModal();
    }

    public function delete($id)
    {
        $row = \App\Models\Jobsite::find($id);
        if (!$row) return;

        // Cek pemakaian DULU (data master dihapus permanen / soft delete tidak ditolak database).
        if ($reasons = \App\Support\MasterUsage::reasons('jobsite', (int) $row->id)) {
            $this->dispatch('swal:error', ['title' => 'Tidak bisa dihapus', 'text' => \App\Support\MasterUsage::message('Jobsite ' . $row->name, $reasons)]);
            return;
        }

        try {
            $row->delete();
            $this->dispatch('swal:success', ['title' => 'Dihapus', 'text' => 'Jobsite ' . $row->name . ' berhasil dihapus.']);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Hapus Jobsite #' . $row->id . ' gagal: ' . $e->getMessage());
            $this->dispatch('swal:error', ['title' => 'Tidak bisa dihapus', 'text' => 'Jobsite ' . $row->name . ' masih terhubung dengan data lain.']);
        }
    }

}
