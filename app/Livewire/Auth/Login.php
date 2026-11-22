<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Login bertahap:
 *  1. Masukkan NIK.
 *  2. Masukkan password (akun yang sudah punya password).
 *  3. Buat password baru (akun yang belum pernah login).
 */
#[Layout('components.layouts.guest')]
#[Title('Masuk')]
class Login extends Component
{
    public int $step = 1;
    public string $nik = '';
    public string $password = '';
    public string $new_password = '';
    public string $confirm_password = '';
    public bool $showPassword = false;
    public bool $remember = true;

    /** Nama pemilik NIK, ditampilkan di langkah 2/3 agar user yakin akunnya benar. */
    public string $accountName = '';

    protected array $messages = [
        'nik.required' => 'NIK wajib diisi.',
        'nik.min' => 'NIK minimal 5 karakter.',
        'nik.max' => 'NIK maksimal 20 karakter.',
        'password.required' => 'Password wajib diisi.',
        'new_password.required' => 'Password baru wajib diisi.',
        'new_password.min' => 'Password baru minimal 8 karakter.',
        'confirm_password.required' => 'Ulangi password baru.',
        'confirm_password.same' => 'Konfirmasi password tidak sama.',
    ];

    public function mount()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
    }

    /** Enter / tombol utama: jalankan aksi sesuai langkah. */
    public function submit()
    {
        return match ($this->step) {
            2 => $this->authenticate(),
            3 => $this->createdPassword(),
            default => $this->cekNik(),
        };
    }

    /** Kembali ke langkah NIK. */
    public function back(): void
    {
        $this->reset(['password', 'new_password', 'confirm_password', 'showPassword', 'accountName']);
        $this->resetErrorBag();
        $this->step = 1;
        $this->dispatch('login-step', step: 1);
    }

    public function togglePassword(): void
    {
        $this->showPassword = !$this->showPassword;
    }

    /** Hapus pesan error sebuah field begitu user mengubahnya. */
    public function updated(string $property): void
    {
        $this->resetErrorBag($property);
    }

    /**
     * Batasi percobaan per NIK + IP. True jika harus ditolak (pesan dipasang di $field).
     */
    protected function tooManyAttempts(string $action, string $field, int $maxAttempts = 5): bool
    {
        $key = $this->throttleKey($action);
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $this->fail($field, 'Terlalu banyak percobaan. Coba lagi dalam ' . RateLimiter::availableIn($key) . ' detik.');
            return true;
        }
        RateLimiter::hit($key, 60);

        return false;
    }

    protected function throttleKey(string $action): string
    {
        return $action . '|' . Str::lower($this->nik) . '|' . request()->ip();
    }

    protected function fail(string $field, string $message): void
    {
        $this->addError($field, $message);
        $this->dispatch('login-failed');
    }

    public function cekNik(): void
    {
        $this->nik = trim($this->nik);
        $this->validate(['nik' => 'required|min:5|max:20']);

        if ($this->tooManyAttempts('cek-nik', 'nik', 10)) {
            return;
        }

        $user = User::where('nik', $this->nik)->first(['id', 'name', 'password']);
        if (!$user) {
            $this->fail('nik', 'NIK tidak ditemukan. Periksa kembali atau hubungi tim IT.');
            return;
        }

        $this->accountName = (string) $user->name;
        $this->step = empty($user->password) ? 3 : 2;
        $this->dispatch('login-step', step: $this->step);
    }

    public function authenticate()
    {
        $this->validate(['nik' => 'required|min:5|max:20', 'password' => 'required']);

        if ($this->tooManyAttempts('login', 'password')) {
            return;
        }

        if (Auth::attempt(['nik' => $this->nik, 'password' => $this->password], $this->remember)) {
            RateLimiter::clear($this->throttleKey('login'));
            request()->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        $this->password = '';
        $this->fail('password', 'Password salah.');
    }

    public function createdPassword()
    {
        $this->validate([
            'new_password' => 'required|min:8',
            'confirm_password' => 'required|same:new_password',
        ]);

        if ($this->tooManyAttempts('create-password', 'new_password')) {
            return;
        }

        $user = User::where('nik', $this->nik)->first();
        if (!$user) {
            $this->back();
            $this->fail('nik', 'NIK tidak ditemukan.');
            return;
        }

        // Hanya akun yang BELUM punya password boleh membuat password di sini.
        // Tanpa cek ini, siapa pun bisa menimpa password user lain.
        if (!empty($user->password)) {
            $this->reset(['new_password', 'confirm_password']);
            $this->step = 2;
            $this->dispatch('login-step', step: 2);
            $this->fail('password', 'Akun ini sudah memiliki password. Silakan masukkan password Anda.');
            return;
        }

        $user->password = Hash::make($this->new_password);
        $user->save();

        Auth::login($user, $this->remember);
        request()->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
