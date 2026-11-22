<div class="auth-split">
  {{-- Panel identitas (desktop) --}}
  <aside class="auth-split__brand d-none d-lg-flex">
    <div class="d-flex align-items-center gap-2">
      <span class="app-brand-mark auth-mark"><i class="bx bx-cube-alt"></i></span>
      <div class="lh-sm">
        <div class="fw-bold fs-5">AMS</div>
        <small class="opacity-75">IT Operations</small>
      </div>
    </div>

    <div class="auth-split__copy">
      <h1 class="text-white fw-bold mb-3">Aset IT tercatat rapi, dari gudang sampai ke tangan user.</h1>
      <ul class="list-unstyled mb-0 d-grid gap-3">
        <li class="d-flex gap-3"><i class="bx bx-package fs-4"></i><span>Inventaris, label QR, dan stock opname</span></li>
        <li class="d-flex gap-3"><i class="bx bx-transfer fs-4"></i><span>Serah terima, pengembalian, mutasi, dan peminjaman</span></li>
        <li class="d-flex gap-3"><i class="bx bx-check-shield fs-4"></i><span>Persetujuan berjenjang sesuai workflow</span></li>
      </ul>
    </div>

    <small class="opacity-75">© {{ date('Y') }} PT Bina Sarana Sukses</small>
  </aside>

  {{-- Form --}}
  <main class="auth-split__form">
    <div class="auth-form-box">
      <div class="d-flex d-lg-none align-items-center justify-content-center gap-2 mb-2">
        <span class="app-brand-mark"><i class="bx bx-cube-alt"></i></span>
        <span class="fw-bold fs-5 text-heading">AMS <span class="fw-medium text-body-secondary">IT Ops</span></span>
      </div>

      <div class="auth-character" wire:ignore>
        <canvas id="riveCanvas" width="400" height="400" aria-hidden="true"></canvas>
      </div>

      <div class="text-center mb-4">
        @if ($step === 1)
          <h4 class="mb-1">Masuk ke AMS</h4>
          <p class="text-body-secondary mb-0">Gunakan NIK karyawan Anda.</p>
        @elseif ($step === 2)
          <h4 class="mb-1">Halo, {{ Str::of($accountName)->explode(' ')->first() ?: 'kembali' }}</h4>
          <p class="text-body-secondary mb-0">Masukkan password untuk melanjutkan.</p>
        @else
          <h4 class="mb-1">Buat password</h4>
          <p class="text-body-secondary mb-0">Ini login pertama Anda. Buat password minimal 8 karakter.</p>
        @endif
      </div>

      @if ($step > 1)
        <div class="auth-account mb-4">
          <i class="bx bx-user-circle fs-4 text-primary"></i>
          <div class="min-w-0 flex-grow-1">
            <div class="fw-medium text-heading text-truncate">{{ $accountName }}</div>
            <small class="text-body-secondary">NIK {{ $nik }}</small>
          </div>
          <button type="button" class="btn btn-sm btn-text-primary" wire:click="back">Ganti</button>
        </div>
      @endif

      <form wire:submit="submit" novalidate>
        @if ($step === 1)
          <div class="mb-4">
            <label for="nik" class="form-label">NIK</label>
            <div class="input-group input-group-merge @error('nik') is-invalid @enderror">
              <span class="input-group-text"><i class="bx bx-id-card"></i></span>
              <input wire:model="nik" type="text" id="nik" inputmode="numeric" autocomplete="username"
                     class="form-control @error('nik') is-invalid @enderror"
                     placeholder="Contoh: 12345678" autofocus />
            </div>
            @error('nik') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
          </div>
        @elseif ($step === 2)
          <input type="text" class="d-none" autocomplete="username" value="{{ $nik }}" readonly />
          <div class="mb-3">
            <label class="form-label" for="password">Password</label>
            <div class="input-group input-group-merge @error('password') is-invalid @enderror">
              <span class="input-group-text"><i class="bx bx-lock-alt"></i></span>
              <input wire:model="password" type="{{ $showPassword ? 'text' : 'password' }}" id="password"
                     autocomplete="current-password" autofocus
                     class="form-control @error('password') is-invalid @enderror" placeholder="Password" />
              <button type="button" class="input-group-text" wire:click="togglePassword"
                      aria-label="{{ $showPassword ? 'Sembunyikan password' : 'Tampilkan password' }}">
                <i class="bx {{ $showPassword ? 'bx-hide' : 'bx-show' }}"></i>
              </button>
            </div>
            @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
          </div>
          <div class="form-check mb-4">
            <input class="form-check-input" wire:model="remember" type="checkbox" id="remember-me" />
            <label class="form-check-label" for="remember-me">Ingat saya di perangkat ini</label>
          </div>
        @else
          <input type="text" class="d-none" autocomplete="username" value="{{ $nik }}" readonly />
          <div class="mb-3">
            <label class="form-label" for="new_password">Password baru</label>
            <div class="input-group input-group-merge @error('new_password') is-invalid @enderror">
              <span class="input-group-text"><i class="bx bx-lock-alt"></i></span>
              <input wire:model="new_password" type="{{ $showPassword ? 'text' : 'password' }}" id="new_password"
                     autocomplete="new-password" autofocus
                     class="form-control @error('new_password') is-invalid @enderror" placeholder="Minimal 8 karakter" />
              <button type="button" class="input-group-text" wire:click="togglePassword"
                      aria-label="{{ $showPassword ? 'Sembunyikan password' : 'Tampilkan password' }}">
                <i class="bx {{ $showPassword ? 'bx-hide' : 'bx-show' }}"></i>
              </button>
            </div>
            @error('new_password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
          </div>
          <div class="mb-4">
            <label class="form-label" for="confirm_password">Ulangi password baru</label>
            <div class="input-group input-group-merge @error('confirm_password') is-invalid @enderror">
              <span class="input-group-text"><i class="bx bx-lock"></i></span>
              <input wire:model="confirm_password" type="{{ $showPassword ? 'text' : 'password' }}" id="confirm_password"
                     autocomplete="new-password"
                     class="form-control @error('confirm_password') is-invalid @enderror" placeholder="Ketik ulang password" />
            </div>
            @error('confirm_password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
          </div>
        @endif

        <button type="submit" class="btn btn-primary w-100 btn-lg" wire:loading.attr="disabled" wire:target="submit">
          <span wire:loading.remove wire:target="submit">
            {{ $step === 1 ? 'Lanjutkan' : ($step === 2 ? 'Masuk' : 'Simpan & masuk') }}
            <i class="bx bx-right-arrow-alt ms-1"></i>
          </span>
          <span wire:loading wire:target="submit">
            <span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Memproses…
          </span>
        </button>
      </form>

      <p class="text-center text-body-secondary small mt-4 mb-0">
        Lupa password atau NIK tidak terdaftar? Hubungi tim IT.
      </p>
    </div>
  </main>
</div>

@push('styles')
<style>
  .auth-split {
    min-height: 100vh;
    display: grid;
    grid-template-columns: 1fr;
    background: var(--bs-body-bg);
  }
  @media (min-width: 992px) {
    .auth-split { grid-template-columns: minmax(380px, 5fr) 7fr; }
  }
  .auth-split__brand {
    flex-direction: column;
    justify-content: space-between;
    gap: 2rem;
    padding: 2.5rem 3rem;
    color: #fff;
    background:
      radial-gradient(circle at 15% 85%, rgba(255,255,255,.12) 0, transparent 45%),
      linear-gradient(160deg, var(--bs-primary) 0%, #4f52d9 100%);
  }
  .auth-split__copy { max-width: 28rem; }
  .auth-split__copy h1 { font-size: 1.85rem; line-height: 1.3; }
  .auth-mark { background: rgba(255,255,255,.18); }
  .auth-split__form {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem 1rem;
  }
  .auth-form-box {
    width: 100%;
    max-width: 400px;
    background: var(--bs-paper-bg, #fff);
    border-radius: .75rem;
    box-shadow: 0 .25rem 1.5rem rgba(34, 48, 62, .08);
    padding: 1.75rem 1.5rem 2rem;
  }
  @media (min-width: 576px) {
    .auth-form-box { padding: 2rem 2.25rem 2.25rem; }
  }
  .auth-character {
    width: 168px;
    height: 168px;
    margin: 0 auto .5rem;
  }
  .auth-character canvas { width: 100%; height: 100%; display: block; }
  .auth-account {
    display: flex;
    align-items: center;
    gap: .75rem;
    padding: .625rem .75rem;
    border: 1px solid var(--bs-border-color);
    border-radius: .5rem;
    background: var(--bs-body-bg);
  }
  .input-group.is-invalid .input-group-text { border-color: var(--bs-form-invalid-border-color, #ff3e1d); }
</style>
@endpush

@script
<script>
  // Karakter animasi: angkat tangan saat mengetik password, reaksi gagal saat error.
  const canvas = document.getElementById('riveCanvas');
  let inputs = [];
  const input = (name) => inputs.find((i) => i.name.toLowerCase() === name.toLowerCase());

  if (canvas && window.rive) {
    const r = new rive.Rive({
      src: @js(asset('assets/riv/login_screen_character.riv')),
      canvas,
      autoplay: true,
      stateMachines: 'State Machine 1',
      onLoad: () => {
        r.resizeDrawingSurfaceToCanvas();
        inputs = r.stateMachineInputs('State Machine 1') || [];
        setHands($wire.step > 1);
      },
    });
  }

  function setHands(up) {
    const i = input('hands_up');
    if (i) i.value = up;
  }
  function fire(name) {
    const i = input(name);
    if (i && typeof i.fire === 'function') i.fire();
  }

  document.addEventListener('focusin', (e) => {
    if (e.target.type === 'password') setHands(true);
    if (e.target.id === 'nik') { const c = input('Check'); if (c) c.value = true; }
  });
  document.addEventListener('focusout', (e) => {
    if (e.target.type === 'password') setHands(false);
    if (e.target.id === 'nik') { const c = input('Check'); if (c) c.value = false; }
  });
  document.addEventListener('input', (e) => {
    if (e.target.id !== 'nik') return;
    const look = input('Look');
    if (look) look.value = Math.min(e.target.value.length * 5, 100);
  });

  $wire.on('login-step', ({ step }) => setHands(step > 1));
  $wire.on('login-failed', () => fire('fail'));
</script>
@endscript
