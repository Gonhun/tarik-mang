<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\User;
use App\Models\Department;
use App\Models\Jobsite;
use App\Models\Position;
use App\Models\PositionLevel;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\UsersImport;

#[Title('Daftar User')]
class ManageUsers extends Component
{
    use WithPagination;
    use WithFileUploads;
    #[Validate('file|mimes:xlsx,xls|max:2048')]
    public $excelFile;
    // Properti untuk form
    public $user_id, $name, $nik,  $whatsapp_number,$manager_id;
    public $department_id, $jabatan_id, $jobsite_id, $position_level_id;
    /** Satu role per user: ID role yang dipilih di form. */
    public $user_role = null;
    /** Role lama user (bila lebih dari satu), untuk peringatan di form. */
    #[\Livewire\Attributes\Locked] public array $previousRoles = [];

    /** Urutan prioritas saat user lama punya beberapa role. */
    private const ROLE_PRIORITY = ['Administrator', 'Administrator IT', 'Administrator CC', 'Staff IT', 'Karyawan'];

    // Properti UI
    public $isModalOpen = false;
    public bool $showImport = false;
    protected $paginationTheme = 'bootstrap';
    public $mode = 'create'; // 'create' atau 'edit'

    // Filter (tersimpan di URL)
    #[Url(as: 'q')] public string $search = '';
    #[Url(as: 'site')] public $filterJobsite = '';
    #[Url(as: 'dept')] public $filterDepartment = '';
    #[Url(as: 'role')] public $filterRole = '';
    #[Url(as: 'level')] public $filterLevel = '';
    #[Url(as: 'status')] public string $filterStatus = '';      // '' | active | inactive
    #[Url(as: 'akun')] public string $filterAccount = '';       // '' | ready | pending
    #[Url(as: 'data')] public string $filterData = '';          // '' | no_wa | no_manager | no_position | no_role
    #[Url(as: 'urut')] public string $sort = 'name';            // name | nik | newest | assets
    #[Url(as: 'per')] public int $perPage = 15;

    public const SORTS = ['name' => 'Nama A–Z', 'nik' => 'NIK', 'newest' => 'Terbaru dibuat', 'assets' => 'Aset terbanyak'];
    public const DATA_FILTERS = ['no_wa' => 'Tanpa WhatsApp', 'no_manager' => 'Tanpa atasan', 'no_position' => 'Tanpa jabatan', 'no_role' => 'Tanpa role'];

    // Kembali ke halaman 1 setiap filter / pencarian / urutan berubah.
    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFilterJobsite(): void { $this->resetPage(); }
    public function updatingFilterDepartment(): void { $this->resetPage(); }
    public function updatingFilterRole(): void { $this->resetPage(); }
    public function updatingFilterLevel(): void { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }
    public function updatingFilterAccount(): void { $this->resetPage(); }
    public function updatingFilterData(): void { $this->resetPage(); }
    public function updatingSort(): void { $this->resetPage(); }
    public function updatingPerPage(): void { $this->resetPage(); }

    public function resetFilters(): void
    {
        $this->reset(['search', 'filterJobsite', 'filterDepartment', 'filterRole', 'filterLevel', 'filterStatus', 'filterAccount', 'filterData', 'sort']);
        $this->resetPage();
    }

    /** Klik kartu ringkasan. */
    public function quickFilter(string $type): void
    {
        $this->filterStatus = in_array($type, ['active', 'inactive'], true) ? $type : '';
        $this->filterAccount = $type === 'pending' ? 'pending' : '';
        $this->resetPage();
    }

    public function uploadExcel()
    {   
        try {
            $this->validate([
                'excelFile' => 'required|file|mimes:xlsx,xls|max:2048'
            ]);
            Excel::Import(new UsersImport, $this->excelFile);
            session()->flash('global-message', 'File Excel berhasil diupload dan diproses.');
            $this->reset('excelFile');
        } catch (\Exception $e) {
            session()->flash('error', 'Terjadi kesalahan saat memproses file: ' . $e->getMessage());
            $this->reset('excelFile');
        }
        $this->resetValidation();
        $this->resetErrorBag();
    }
    /** Ganti departemen di form: jabatan lama tidak berlaku lagi. */
    public function updatedDepartmentId(): void
    {
        $this->jabatan_id = null;
    }

    public function toggleApproved($id)
    {
        $user = User::find($id);
        if (!$user) {
            return;
        }
        if ($user->id === Auth::id() || !$this->canManageUser($user)) {
            $this->dispatch('swal:error', ['title' => 'Tidak diizinkan', 'text' => 'Anda tidak bisa mengubah status akun ini.']);
            return;
        }
        $user->is_active = !$user->is_active;
        $user->save();
    }
    protected function rules()
    {
         $userIdToIgnore = $this->user_id ?? null;
        
        return [
            'name' => 'required|string|max:255',
            'nik' => [
                'required',
                'string',
                'max:25', // Sesuaikan
                Rule::unique('users', 'nik')->ignore($userIdToIgnore),
            ],
            'whatsapp_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('users', 'whatsapp')->ignore($userIdToIgnore),
            ],
            'jabatan_id' => 'required|exists:positions,id',
            'department_id' => 'required|exists:departments,id',
            'jobsite_id' => 'required|exists:jobsites,id',
            'manager_id' => ['nullable', 'exists:users,id', Rule::notIn([$this->user_id])],
            // 'password' => $passwordRules,
            'user_role' => 'required|exists:roles,id',
            'position_level_id' => 'required|exists:position_levels,id'
        ];
    }

    protected $messages = [
        'name.required' => 'Nama wajib diisi.',
        'nik.required' => 'NIK wajib diisi.',
        'nik.unique' => 'NIK sudah terdaftar.',
        'nik.max' => 'NIK maksimal 25 karakter.',
        'whatsapp_number.required' => 'Nomor WhatsApp wajib diisi.',
        'whatsapp_number.max' => 'Nomor WhatsApp maksimal 20 karakter.',
        'whatsapp_number.unique' => 'Nomor WhatsApp sudah terdaftar.',
        'email.required' => 'Email wajib diisi.',
        'email.email' => 'Format email tidak valid.',
        'email.unique' => 'Email sudah terdaftar.',
        'jabatan_id.required' => 'Jabatan wajib dipilih.',
        'jabatan_id.exists' => 'Jabatan yang dipilih tidak valid.',
        'department_id.required' => 'Departemen wajib dipilih.',
        'department_id.exists' => 'Departemen yang dipilih tidak valid.',
        'jobsite_id.required' => 'Jobsite wajib dipilih.',
        'jobsite_id.exists' => 'Jobsite yang dipilih tidak valid.',
        'manager_id.exists' => 'Manager yang dipilih tidak valid.',
        'manager_id.not_in' => 'User tidak dapat memilih dirinya sendiri sebagai manager.',
        'position_id.required' => 'Jabatan wajib dipilih.',
        'user_role.required' => 'Pilih satu role.',
        'user_role.exists' => 'Role yang dipilih tidak valid.',
        'position_level_id.required' => 'Level Jabatan wajib dipilih.',
        'position_level_id.exists' => 'Level Jabatan yang dipilih tidak valid.',
    ];


    /** Query user sesuai filter. $except: lewati filter tertentu (untuk angka ringkasan). */
    protected function filteredQuery(array $except = [])
    {
        $q = User::query();

        if (trim($this->search) !== '') {
            $term = '%' . \App\Support\SqlLike::escape(trim($this->search)) . '%';
            $q->where(fn ($w) => $w->where('name', 'like', $term)
                ->orWhere('nik', 'like', $term)
                ->orWhere('whatsapp', 'like', $term)
                ->orWhereHas('position', fn ($p) => $p->where('name', 'like', $term)));
        }
        $q->when($this->filterJobsite, fn ($w) => $w->where('jobsite_id', $this->filterJobsite))
          ->when($this->filterDepartment, fn ($w) => $w->where('department_id', $this->filterDepartment))
          ->when($this->filterLevel, fn ($w) => $w->where('position_level_id', $this->filterLevel))
          ->when($this->filterRole === 'none', fn ($w) => $w->doesntHave('roles'))
          ->when($this->filterRole && $this->filterRole !== 'none', fn ($w) => $w->whereHas('roles', fn ($r) => $r->where('roles.id', $this->filterRole)));

        if (!in_array('status', $except, true)) {
            $q->when($this->filterStatus === 'active', fn ($w) => $w->where('is_active', true))
              ->when($this->filterStatus === 'inactive', fn ($w) => $w->where(fn ($x) => $x->where('is_active', false)->orWhereNull('is_active')));
        }
        if (!in_array('account', $except, true)) {
            $q->when($this->filterAccount === 'ready', fn ($w) => $w->whereNotNull('password'))
              ->when($this->filterAccount === 'pending', fn ($w) => $w->whereNull('password'));
        }

        match ($this->filterData) {
            'no_wa' => $q->where(fn ($w) => $w->whereNull('whatsapp')->orWhere('whatsapp', '')),
            'no_manager' => $q->whereNull('manager_id'),
            'no_position' => $q->whereNull('position_id'),
            'no_role' => $q->doesntHave('roles'),
            default => null,
        };

        return $q;
    }

    /** Subquery jumlah aset yang sedang dipegang. */
    protected function withAssetCount($q)
    {
        return $q->addSelect(['assets_held' => \App\Models\Asset::selectRaw('count(*)')
            ->whereColumn('assets.holder_user_id', 'users.id')->whereNull('assets.deleted_at')]);
    }

    public function render()
    {
        $query = $this->withAssetCount($this->filteredQuery()->select('users.*'))
            ->with(['position:id,name', 'department:id,name', 'jobsite:id,code,name', 'manager:id,name', 'roles:id,name', 'positionLevel:id,name']);

        match ($this->sort) {
            'nik' => $query->orderBy('nik'),
            'newest' => $query->orderByDesc('users.id'),
            'assets' => $query->orderByDesc('assets_held')->orderBy('name'),
            default => $query->orderBy('name'),
        };

        $summaryBase = $this->filteredQuery(['status', 'account']);
        $summary = (clone $summaryBase)->selectRaw("
                count(*) as total,
                sum(case when is_active = 1 then 1 else 0 end) as active,
                sum(case when password is null then 1 else 0 end) as pending
            ")->reorder()->first();

        $filtersActive = collect([$this->search, $this->filterJobsite, $this->filterDepartment, $this->filterRole, $this->filterLevel, $this->filterStatus, $this->filterAccount, $this->filterData])
            ->filter(fn ($v) => $v !== '' && $v !== null)->count();

        $departments = Department::orderBy('name')->get(['id', 'name']);

        return view('livewire.admin.manage-users', [
            'usersCollection' => $query->paginate($this->perPage),
            'summary' => [
                'total' => (int) $summary->total,
                'active' => (int) $summary->active,
                'inactive' => (int) $summary->total - (int) $summary->active,
                'pending' => (int) $summary->pending,
            ],
            'filtersActive' => $filtersActive,
            'departments' => $departments,
            'jobsites' => Jobsite::orderBy('code')->get(['id', 'name', 'code']),
            'allRoles' => Role::orderBy('name')->get(['id', 'name']),
            'allLevels' => PositionLevel::orderBy('name')->get(['id', 'name']),
            // Data form hanya dimuat saat modal terbuka.
            'jabatans' => $this->isModalOpen && $this->department_id ? Position::where('department_id', $this->department_id)->orderBy('name')->get(['id', 'name']) : collect(),
            'usersForManagerSelection' => $this->isModalOpen ? $this->managerOptions() : collect(),
            'sorts' => self::SORTS,
            'dataFilters' => self::DATA_FILTERS,
        ]);
    }

    /** Ekspor CSV sesuai filter aktif. */
    public function export()
    {
        $query = $this->withAssetCount($this->filteredQuery()->select('users.*'))
            ->with(['position:id,name', 'department:id,name', 'jobsite:id,code', 'manager:id,name', 'roles:id,name', 'positionLevel:id,name'])
            ->orderBy('name');

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8
            fputcsv($out, ['NIK', 'Nama', 'WhatsApp', 'Jobsite', 'Departemen', 'Jabatan', 'Level', 'Atasan', 'Role', 'Status', 'Akun', 'Aset dipegang']);
            $query->chunk(500, function ($users) use ($out) {
                foreach ($users as $u) {
                    fputcsv($out, [
                        $u->nik, $u->name, $u->whatsapp, $u->jobsite->code ?? '', $u->department->name ?? '', $u->position->name ?? '',
                        $u->positionLevel->name ?? '', $u->manager->name ?? '', $u->roles->pluck('name')->implode(', '),
                        $u->is_active ? 'Aktif' : 'Nonaktif', $u->password ? 'Sudah buat password' : 'Belum login', $u->assets_held,
                    ]);
                }
            });
            fclose($out);
        }, 'daftar-user-' . now()->format('Ymd-His') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Role yang hanya boleh diberikan / dikelola oleh administrator. */
    private const ADMIN_ROLES = ['Administrator', 'Administrator IT'];

    protected function actorIsAdmin(): bool
    {
        return Auth::user()->hasRole(self::ADMIN_ROLES);
    }

    /** Non-admin tidak boleh mengubah / menghapus / reset akun administrator. */
    protected function canManageUser(User $user): bool
    {
        return $this->actorIsAdmin() || !$user->hasRole(self::ADMIN_ROLES);
    }

    /**
     * Reset password: password dikosongkan, user membuat password baru sendiri saat login (alur "buat password").
     */
    public function RessetPassowrd($userid)
    {
        $user = User::find($userid);
        if (!$user) {
            $this->dispatch('swal:error', ['title' => 'Gagal', 'text' => 'User tidak ditemukan.']);
            return;
        }
        if (!$this->canManageUser($user) || $user->id === Auth::id()) {
            $this->dispatch('swal:error', ['title' => 'Tidak diizinkan', 'text' => 'Anda tidak bisa mereset password akun ini.']);
            return;
        }

        $user->update(['password' => null]);
        $this->dispatch('swal:success', [
            'title' => 'Password direset',
            'text' => "{$user->name} ({$user->nik}) akan diminta membuat password baru saat login berikutnya.",
        ]);
        $this->closeModal();
        $this->resetInputFields();
    }

    public function create()
    {
        $this->mode = 'create';
        $this->resetInputFields();
        $this->openModal();
    }
    /** Calon atasan: user ber-level "Kepala …" (kecuali diri sendiri). */
    protected function managerOptions()
    {
        return User::query()
            ->when($this->user_id, fn ($q) => $q->where('id', '!=', $this->user_id))
            ->whereHas('positionLevel', fn ($q) => $q->where('name', 'like', 'Kepala %'))
            ->with(['department:id,name', 'positionLevel:id,name'])
            ->orderBy('name')
            ->get(['id', 'name', 'nik', 'department_id', 'position_level_id']);
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        if (!$this->canManageUser($user)) {
            $this->dispatch('swal:error', [
                'title' => 'Error!',
                'text' => 'Tidak bisa mengedit Administrator!',
            ]);
            $this->closeModal();
            $this->resetInputFields();
            return;
        }
        $this->mode = 'edit';
        $this->user_id = $id;
        $this->name = $user->name;
        $this->nik = $user->nik;
        $this->whatsapp_number = $user->whatsapp;
        $this->department_id = $user->department_id;
        $this->jabatan_id = $user->position_id;
        $this->jobsite_id = $user->jobsite_id;
        $this->manager_id = $user->manager_id;
        // Satu role per user. Bila user lama punya beberapa, pilih yang prioritasnya tertinggi.
        $roles = $user->roles->sortBy(fn ($r) => ($i = array_search($r->name, self::ROLE_PRIORITY, true)) === false ? 99 : $i);
        $this->user_role = $roles->first()?->id;
        $this->previousRoles = $roles->count() > 1 ? $roles->pluck('name')->all() : [];
        $this->position_level_id = $user->position_level_id;
        $this->openModal();
    }

    public function store()
    {
       
        $validatedData = $this->validate($this->rules());

        // Hanya administrator yang boleh memberi role administrator (cegah menaikkan hak akses sendiri).
        $requestedRoles = Role::whereKey($this->user_role)->pluck('name');
        if (!$this->actorIsAdmin() && $requestedRoles->intersect(self::ADMIN_ROLES)->isNotEmpty()) {
            $this->addError('user_role', 'Hanya administrator yang boleh memberi role Administrator.');
            return;
        }
        if ($this->user_id && ($existing = User::find($this->user_id)) && !$this->canManageUser($existing)) {
            $this->addError('user_role', 'Anda tidak bisa mengubah akun administrator.');
            return;
        }

        $dataToStore = [
            'name' => ucwords($validatedData['name']),
            'nik' => $validatedData['nik'],
            'whatsapp' => PhoneFormat($validatedData['whatsapp_number']),
            'department_id' => $validatedData['department_id'],
            'position_id' => $validatedData['jabatan_id'],
            'jobsite_id' => $validatedData['jobsite_id'],
            'manager_id' => $validatedData['manager_id'],
            'position_level_id' => $validatedData['position_level_id'],
        ];
       
        $user = User::updateOrCreate(['id' => $this->user_id], $dataToStore);
     
        // Ganti semua role lama dengan satu role yang dipilih.
        $user->syncRoles([Role::findOrFail($this->user_role)]);
       
        $this->dispatch('swal:success', ['title' => 'Tersimpan',
            'text' => $this->user_id ? "Data {$user->name} diperbarui." : "Pengguna {$user->name} ditambahkan."]);

        $this->closeModal();
        $this->resetInputFields();
    }

    public function delete($id)
    {
        $user = User::find($id);
        if (!$user) return;

        // Data user dipakai di banyak tempat (aset, approval, transaksi) dan dihapus permanen: cek dulu.
        $reasons = [];
        if ($user->id === Auth::id()) $reasons[] = 'tidak bisa menghapus akun sendiri';
        if (!$this->canManageUser($user)) $reasons[] = 'akun administrator hanya bisa dihapus administrator';
        if (($n = \App\Models\Asset::where('holder_user_id', $user->id)->count()) > 0) $reasons[] = "masih memegang {$n} aset";
        $pending = \App\Models\AssetRequestApproval::where('approver_user_id', $user->id)->where('status', 'Pending')->count()
            + \App\Models\AssetTransactionApproval::where('approver_user_id', $user->id)->where('status', 'Pending')->count();
        if ($pending > 0) $reasons[] = "masih menjadi approver di {$pending} tahap yang menunggu";
        if (\App\Models\ApprovalWorkflowStep::where('user_id', $user->id)->exists()) $reasons[] = 'dipakai sebagai approver di workflow';

        if ($reasons) {
            $this->dispatch('swal:error', ['title' => 'Tidak bisa dihapus', 'text' => "{$user->name}: " . implode('; ', $reasons) . '.']);
            return;
        }

        try {
            $user->delete();
            $this->dispatch('swal:success', ['title' => 'Dihapus', 'text' => "Pengguna {$user->name} dihapus."]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Hapus user #{$user->id} gagal: " . $e->getMessage());
            $this->dispatch('swal:error', ['title' => 'Tidak bisa dihapus', 'text' => "{$user->name} masih terhubung dengan data lain (riwayat transaksi/pengajuan)."]);
        }
    }


    public function openModal()
    {
        $this->isModalOpen = true;
    }
    public function closeModal() { $this->isModalOpen = false; $this->resetValidation();
        $this->resetErrorBag(); }

    private function resetInputFields()
    {
        $this->user_role = null;
        $this->previousRoles = [];
        $this->user_id = null;
        $this->name = '';
        $this->nik = '';
        $this->whatsapp_number = '';
        $this->department_id = null;
        $this->jabatan_id = null;
        $this->jobsite_id = null;
        $this->manager_id = null; 
        $this->position_level_id = null;
        $this->resetValidation();
        $this->resetErrorBag();
    }

}
