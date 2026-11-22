<?php

namespace App\Livewire\Auth;

use App\Models\LoginwaSession;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Carbon\Carbon;

#[Layout('components.layouts.guest')] 
class Login extends Component
{
    public $step=1;
    public string $nik=''; 
    public string $password = '';
    public $confirm_password,$new_password;
    public bool $showPassword = false;
    public bool $remember = true;

    protected array $rules = [
        'nik' => 'required|min:5|max:20',
        'password' => 'required|min:4',
    ];
   protected array $messages = [
        'nik.required' => 'NIK wajib diisi.',
        'nik.string' => 'NIK harus berupa teks.',
        'nik.max' => 'NIK maksimal 20 karakter.',
        'nik.numeric' => 'NIK harus berupa angka.',
        // 'nik.digits' => 'NIK harus terdiri dari 16 digit.',
        'password.required' => 'Password wajib diisi.',
        'password.min' => 'Password minimal terdiri dari 8 karakter.',
    ];
    public function mount()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
    }
    public function togglePassword()
    {
        $this->showPassword = !$this->showPassword;
    }
    public function updated(string $propertyName): void
    {
        $this->validateOnly($propertyName);
    }
    public function authenticate()
    {
        
        $this->validate();
        
        // Menggunakan NIK untuk autentikasi
        if (Auth::attempt(['nik' => $this->nik, 'password' => $this->password], $this->remember)) {
            request()->session()->regenerate();
           
            return redirect()->intended(route('dashboard'));
        }
        
       
       
        $userExists = User::where('nik', $this->nik)->exists();
        if (!$userExists) {
            $this->dispatch('swal:error', [
                'title' => 'Error!',
                'text' => 'Username atau Password salah!',
            ]);
            // $this->addError('nik', 'NIK tidak terdaftar.');
        } else {
            $this->dispatch('swal:error', [
                'title' => 'Error!',
                'text' => 'Username atau Password salah!',
            ]);
            // $this->addError('password', 'Password yang Anda masukkan salah.'); 
        }
       
       
    }
    public function cekNik()
    {
        if (!empty($this->nik)) {
            $this->validate([
                'nik' => ['required','min:5'],
            ]);
            $user=User::where('nik',$this->nik)->first();
            if($user)
            {
                if (empty($user->password)) {
                    $this->step=3;
                    $this->dispatch('step-updated', ['step' => $this->step]);
                } else {
                    // Password ada
                    $this->step=2;
                    $this->dispatch('step-updated', ['step' => $this->step]);
                }
            }else
            {
                $this->dispatch('swal:error', [
                    'title' => 'Error!',
                    'text' => 'NIK tidak di temukan!',
                ]);
            }
            
        }
    }
    public function createdPassword()
    {
        $this->validate([
            'new_password' => 'required|min:8',
            'confirm_password' => 'required|same:new_password',
        ]);
    
        $user = User::where('nik', $this->nik)->first();
    
        if ($user) {
            $user->password = bcrypt($this->new_password); // Enkripsi password
            $user->save();
    
            // Login user setelah update password
            if (Auth::attempt(['nik' => $this->nik, 'password' => $this->new_password], $this->remember)) {
                session()->regenerate();
    
                // Tampilkan notifikasi berhasil
                $this->dispatch('swal:success', [
                    'title' => 'Berhasil!',
                    'text' => 'Password berhasil dibuat. Anda akan dialihkan ke dashboard.',
                ]);
    
                return $this->redirect(route('dashboard'));
            } else {
                $this->dispatch('swal:error', [
                    'title' => 'Berhasil!',
                    'text' => 'Gagal login dengan password baru.',
                ]);
              
            }
        } else {
            $this->dispatch('swal:error', [
                'title' => 'Berhasil!',
                'text' => 'User dengan NIK tersebut tidak ditemukan.',
            ]);
        }
    }
    public function render()
    {
        $expiredSession = LoginwaSession::where('expires_at', '<=', now())->first();
        if($expiredSession)
        {
            // getTokenWa();
        }
        // $targetPhone = '62 852-7395-9891'; // Contoh nomor tujuan
        // $message = 'Ini adalah pesan tes dari aplikasi AMS.';
        // $isSuccess = sendMessageWithWhatsApp($targetPhone, $message);

        // if ($isSuccess) {
        //     session()->flash('message', 'Pesan berhasil dikirim!');
        // } else {
        //     session()->flash('error', 'Gagal mengirim pesan. Silakan cek log untuk detail.');
        // }
        return view('livewire.auth.login');
    }
}
