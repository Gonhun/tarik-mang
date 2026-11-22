<?php

namespace App\Livewire\Auth;

use App\Models\Department;
use App\Models\Jobsite;
use App\Models\KaryawanErp;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\Position;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Spatie\Permission\Models\Role;


#[Layout('components.layouts.guest')] 
class Register extends Component
{
    public string $name = '';
    public string $nik = '';
    public string $email = '';
    public string $whatsapp_number = '';
    public ?int $jobsite_id = null; // Bisa null jika opsional
    public ?int $department_id = null; // Bisa null jika opsional
    public string $password = '';

    public $jobsites;
    public $departments;

    public function mount()
    {
        if (Auth::check()) {
            // return redirect()->route('dashboard');
        }
        $this->jobsites = Jobsite::orderBy('code')->get(['id', 'code','name']);
        $this->departments = Department::orderBy('code')->get(['id', 'code','name']);
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'nik' => 'required|string|max:20|unique:users,nik', // Sesuaikan max jika perlu
            'whatsapp_number' => 'required|string|max:20|unique:users,whatsapp', // Sesuaikan validasi (misal: numeric, starts_with)
            'jobsite_id' => 'required|exists:jobsites,id', // Ganti nullable menjadi 'required' jika wajib
            'department_id' => 'required|exists:departments,id', // Ganti nullable menjadi 'required' jika wajib
            'password' => 'required|string|min:8|max:255',
        ];
    }

    protected array $messages = [
        'name.required' => 'Nama wajib diisi.',
        'nik.required' => 'NIK wajib diisi.',
        'nik.unique' => 'NIK ini sudah terdaftar.',
        'whatsapp_number.required' => 'Alamat email wajib diisi.',
        'whatsapp_number.unique' => 'No whatsapp ini sudah terdaftar.',
        'jobsite_id.exists' => 'Jobsite yang dipilih tidak valid.',
        'department_id.exists' => 'Departemen yang dipilih tidak valid.',
        'password.required' => 'Password wajib diisi.',
        'password.min' => 'Password minimal terdiri dari 8 karakter.',
    ];

    public function updated(string $propertyName): void
    {
        $this->validateOnly($propertyName);
    }

    /**
     * NIK harus ada di data HRD dan nomor WhatsApp harus sama dengan nomor telepon di HRD.
     * Tanpa ini, siapa pun bisa mendaftar memakai NIK karyawan lain.
     */
    protected function verifyAgainstHrd(string $nik, string $whatsapp): bool
    {
        try {
            $karyawan = KaryawanErp::where('NIK', $nik)->first(['NIK', 'Telp']);
        } catch (\Throwable $e) {
            Log::error('Register: gagal cek data HRD: ' . $e->getMessage());
            $this->addError('nik', 'Data karyawan tidak dapat diverifikasi saat ini. Coba lagi nanti.');
            return false;
        }

        if (!$karyawan) {
            $this->addError('nik', 'NIK tidak terdaftar di data karyawan.');
            return false;
        }

        $hrdPhone = PhoneFormat($karyawan->Telp ?? '');
        if ($hrdPhone === '' || $hrdPhone !== PhoneFormat($whatsapp)) {
            $this->addError('whatsapp_number', 'No WhatsApp tidak sesuai dengan data HRD. Hubungi tim IT/HRD.');
            return false;
        }

        return true;
    }

    public function registerUser()
    {
        $defaultRoleName = 'Karyawan';
        $validatedData = $this->validate();

        $throttleKey = 'register|' . request()->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $this->addError('nik', 'Terlalu banyak percobaan. Coba lagi dalam ' . RateLimiter::availableIn($throttleKey) . ' detik.');
            return;
        }
        RateLimiter::hit($throttleKey, 300);

        if (!$this->verifyAgainstHrd($validatedData['nik'], $validatedData['whatsapp_number'])) {
            return;
        }

        $staffPosition = Position::where('department_id', $validatedData['department_id'])
                         ->where('name', 'like', 'Staff%')
                         ->first();
        $user = User::create([
            'name' => ucwords($validatedData['name']),
            'nik' => $validatedData['nik'],
            'whatsapp' => PhoneFormat($validatedData['whatsapp_number']),
            'jobsite_id' => $validatedData['jobsite_id'],
            'department_id' => $validatedData['department_id'],
            'position_id' => $staffPosition->id ?? null,
            'password' => Hash::make($validatedData['password']),

        ]);
        $user->assignRole($defaultRoleName);
        Auth::login($user,true);
        request()->session()->regenerate();
        return redirect()->route('dashboard'); // atau rute lain setelah registrasi berhasil
    }

    public function render()
    {
        return view('livewire.auth.register');
    }
}
