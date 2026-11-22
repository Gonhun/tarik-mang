<?php

namespace App\Livewire\Profile;

use App\Models\Jobsite;
use App\Models\User;
use OwenIt\Auditing\Models\Audit; // Model dari paket laravel-auditing
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class Profil extends Component
{
    use WithPagination,WithFileUploads;

    public $name,$so,$jd,$pp,$wa; 
    public ?int $jobsite_id = null;
    public $existing_pp_path,$existing_so_path,$existing_jd_path;
    public $isDetailModalOpen = false;
    protected $paginationTheme = 'bootstrap';
    public $perPage = 15; // Jumlah item per halaman
    public $users;
    public ?int $manager_id = null;
    public $jobsistes;
    public $tab=0;
    public $selectedAudit = null;
    public $usersForManagerSelection = [];

    public $chartData = [];

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'wa' => 'required|string|max:25|unique:users,whatsapp,'. Auth::id(), 
            'jobsite_id' => 'required|exists:jobsites,id',
            'pp' => 'nullable|image|mimes:jpg,jpeg,png|max:1024',
            'so' => 'nullable|file|mimes:pdf,doc,docx,png,jpg|max:5120',
            'jd' => 'nullable|file|mimes:pdf,doc,docx,png,jpg|max:5120',
           
        ];
    }
    protected $messages = [
        'name.required' => 'Nama wajib diisi.',
        'name.max' => 'Nama maksimal 255 karakter.',
        'wa.required' => 'Nomor WhatsApp wajib diisi.',
        'wa.max' => 'Nomor WhatsApp maksimal 20 karakter.',
        'wa.unique' => 'Nomor WhatsApp sudah digunakan.',
        'jobsite_id.required' => 'Jobsite wajib dipilih.',
        'jobsite_id.exists' => 'Jobsite tidak valid.',
        'pp.image' => 'File foto profil harus berupa gambar.',
        'pp.mimes' => 'Format foto profil harus jpg, jpeg, atau png.',
        'pp.max' => 'Ukuran foto profil maksimal 1MB.',
        'so.file' => 'File struktur organisasi harus berupa file.',
        'so.mimes' => 'Format struktur organisasi harus pdf, doc, docx, png, atau jpg.',
        'so.max' => 'Ukuran struktur organisasi maksimal 5MB.',
        'jd.file' => 'File job description harus berupa file.',
        'jd.mimes' => 'Format job description harus pdf, doc, docx, png, atau jpg.',
        'jd.max' => 'Ukuran job description maksimal 5MB.',
       
    ];
    public function resetInputFields()
    {
        $this->name =  $this->users->name;
        $this->wa = Str::chopStart($this->users->whatsapp,'62') ;
        $this->jobsite_id = $this->users->jobsite_id;
        $this->existing_pp_path = $this->users->pp_path;
        $this->existing_so_path = $this->users->so_path;
        $this->existing_jd_path = $this->users->jd_path;
        $this->pp = null;
        $this->so = null;
        $this->jd = null;
    }
    // private function loadUsersForManagerSelection()
    // {
    //     $query = User::orderBy('name');
    //     // Saat mengedit, kecualikan diri sendiri dari daftar calon atasan
    //     if (Auth::id()) {
    //         $query->where('id', '!=', Auth::id())
    //               ->whereHas('department', function($q) {
    //                   $q->whereIn('code',['DIR',Auth::user()->department->code]);
    //               })
    //               ->whereHas('position', function($q) {
    //                   $q->where('name', 'not like', '%Staff%');
    //               });
    //     }
    //     $this->usersForManagerSelection = $query->get(['id', 'name', 'nik','department_id','position_id']);
    // }
    private function loadUsersForManagerSelection()
    {
        // Calon atasan: user ber-level "Kepala …" (sama dengan form admin), plus atasan saat ini.
        $currentManager = Auth::user()->manager_id;
        $this->usersForManagerSelection = User::query()
            ->where('id', '!=', Auth::id())
            ->where(fn ($q) => $q->whereHas('positionLevel', fn ($l) => $l->where('name', 'like', 'Kepala %'))
                ->when($currentManager, fn ($w) => $w->orWhere('id', $currentManager)))
            ->with(['department:id,code', 'position:id,name'])
            ->orderBy('name')
            ->get(['id', 'name', 'nik', 'department_id', 'position_id']);
    }
    public function store()
    {
        
        $this->validate();
        $user = User::findOrFail(Auth::id());

        $fileFields = [
            'pp' => 'pp_path',
            'so' => 'so_path',
            'jd' => 'jd_path',
        ];

        foreach ($fileFields as $field => $dbColumn) {
            $file = $this->{$field};
            $existingPath = $this->{"existing_{$field}_path"};

            if ($file) {
                if ($existingPath) {
                    Storage::disk('public')->delete("profil/" . Auth::id() . '/' . basename($existingPath));
                }

                $storedPath = $file->store("profil/" . Auth::id(), 'public');
                $user->update([
                    $dbColumn => $storedPath,
                ]);
            }
        }
        if($user->whatsapp != PhoneFormat($this->wa))
        {
            $user->update([
                'whatsapp' => PhoneFormat($this->wa)
            ]);
        }
       
        $this->users = $user->fresh();
        return redirect()->back()->with('success', 'User updated successfully.');
    }
    public function mount()
    {
        $this->users = User::findOrFail(Auth::id());
        // Auth::user()->position()->job_description_path
        $this->name =  $this->users->name;
        $this->wa = Str::chopStart($this->users->whatsapp,'62') ;
        $this->jobsite_id = $this->users->jobsite_id;
        $this->existing_pp_path = $this->users->pp_path;
        $this->existing_so_path = $this->users->so_path;
        $this->existing_jd_path = $this->users->jd_path;
        $this->jobsistes = Jobsite::orderBy('name','ASC')->get(['id','code','name']);
        $this->loadUsersForManagerSelection();
        $this->manager_id = $this->users->manager_id;

        // Struktur organisasi punya halamannya sendiri (organization-chart); tidak dimuat di sini.
    }
    private function buildTree($managerId, $usersByManager)
    {
        $tree = [];
       
        // Ambil semua bawahan langsung dari atasan saat ini
        $subordinates = $usersByManager->get($managerId, []);

        foreach ($subordinates as $user) {
            // Buat node untuk pengguna saat ini
            $node = [
                // 'x' adalah label yang akan ditampilkan di chart
                'x' => $user->name . ' (' . ($user->position->name ?? 'Jabatan TBD') . ')',
                // 'y' adalah nilai untuk ukuran kotak, bisa dibuat dinamis jika perlu
                'y' => 1,
                // Panggil kembali fungsi ini secara rekursif untuk mencari bawahan dari pengguna ini
                'data' => $this->buildTree($user->id, $usersByManager)
            ];

            // Jika seorang pengguna tidak memiliki bawahan, array 'data' akan kosong,
            // yang mana ini adalah format yang benar untuk ApexCharts.

            $tree[] = $node;
        }

        return $tree;
    }
    public function updatedManagerId($value)
    {
        if(!empty($value))
        {
            $user = User::findOrFail(Auth::id());
            if($user->manager_id != $this->manager_id)
            {
                $user->update([
                    'manager_id' => $this->manager_id,
                ]);
                $this->users = $user->fresh();
                session()->flash('manager', "Atasan Langsung berhasil di update!");
            }
        }
       
    }
    public function viewDetails(int $auditId)
    {
        $this->selectedAudit = Audit::find($auditId);
        if ($this->selectedAudit) {
            // old_values dan new_values biasanya sudah di-cast ke array oleh model Audit
            // Jika belum, Anda mungkin perlu json_decode di sini atau di view.
            // Model Audit dari paket biasanya sudah menangani ini.
        }
        $this->isDetailModalOpen = true;
    }

    public function closeDetailModal()
    {
        $this->isDetailModalOpen = false;
        $this->selectedAudit = null;
    }
    public function getCleanModelName($modelPath)
    {
        if (empty($modelPath)) return 'N/A';
        return class_basename($modelPath); // Mengambil nama class saja, misal "Asset" dari "App\Models\Asset"
    }
    public function setTab($value)
    {
        $this->tab = $value;
    }
    public function render()
    {
       
        $audits = Audit::where('user_id',[Auth::id()])->with('user')->orderBy('created_at', 'desc')->paginate($this->perPage);
        return view('livewire.profile.profil',[
            'user' => $this->users,
            'audits' => $audits
        ]);
    }
}
