<?php

namespace App\Livewire\Auth;

use App\Models\Department;
use App\Models\Jobsite;
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
            'password' => 'required|string|min:8',
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

    public function registerUser()
    {   
        $defaultRoleName = 'Karyawan';
        $validatedData = $this->validate();
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
        return redirect()->route('dashboard'); // atau rute lain setelah registrasi berhasil
    }

    public function render()
    {
        return view('livewire.auth.register');
    }
}
