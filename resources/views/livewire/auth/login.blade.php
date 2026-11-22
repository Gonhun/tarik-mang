@php
    $ls = \App\Support\LoginSettings::all();
    $bgUrl = \App\Support\LoginSettings::backgroundUrl();
    $logoUrl = \App\Support\LoginSettings::logoUrl();
@endphp
<div class="auth-split">
  {{-- Panel identitas (desktop). Gambar, logo, dan teks diatur Administrator di Administrasi > Tampilan Login. --}}
  <aside class="auth-split__brand d-none d-lg-flex" @if ($bgUrl) style="background-image: url('{{ $bgUrl }}');" @endif>
    <div class="auth-split__overlay" style="opacity: {{ $bgUrl ? ((int) $ls['overlay']) / 100 : 1 }}"></div>
    <div class="auth-split__inner">
      <div class="d-flex align-items-center gap-3">
        <span class="auth-logo"><img src="{{ $logoUrl }}" alt="PT Bina Sarana Sukses"></span>
        <div class="lh-sm">
          <div class="fw-bold fs-5">AMS</div>
          <small class="opacity-75">IT Operations</small>
        </div>
      </div>

      <div class="auth-split__copy">
        <h1 class="text-white fw-bold mb-3">{{ $ls['headline'] }}</h1>
        @if (!empty($ls['points']))
          <ul class="list-unstyled mb-0 d-grid gap-3">
            @foreach ($ls['points'] as $point)
              <li class="d-flex gap-3"><i class="bx bx-check-circle fs-4"></i><span>{{ $point }}</span></li>
            @endforeach
          </ul>
        @endif
      </div>

      <small class="opacity-75">© {{ date('Y') }} PT Bina Sarana Sukses</small>
    </div>
  </aside>

  {{-- Form --}}
  <main class="auth-split__form">
    <div class="auth-form-box">
      <div class="d-flex d-lg-none align-items-center justify-content-center gap-2 mb-3">
        <img src="{{ $logoUrl }}" alt="PT Bina Sarana Sukses" style="height: 30px">
        <span class="fw-bold fs-5 text-heading">AMS <span class="fw-medium text-body-secondary">IT Ops</span></span>
      </div>

      @if ($ls['show_character'])
        {{-- Maskot SVG (tanpa file/library eksternal): mata mengikuti NIK, tangan menutup mata saat mengetik password. --}}
        <div class="auth-character" wire:ignore>
          <svg id="mascot" class="mascot" viewBox="0 0 200 180" role="img" aria-label="Maskot AMS">
            <ellipse cx="100" cy="168" rx="58" ry="7" class="mascot-shadow"/>
            <g class="mascot-body">
              <rect x="88" y="128" width="24" height="22" rx="4" class="mascot-stand"/>
              <rect x="66" y="146" width="68" height="10" rx="5" class="mascot-stand"/>
              <rect x="30" y="22" width="140" height="110" rx="18" class="mascot-frame"/>
              <rect x="42" y="34" width="116" height="86" rx="10" class="mascot-screen"/>
              <circle cx="100" cy="28" r="2.5" class="mascot-cam"/>
              <ellipse cx="62" cy="96" rx="9" ry="5" class="mascot-cheek"/>
              <ellipse cx="138" cy="96" rx="9" ry="5" class="mascot-cheek"/>
              <g class="mascot-eyes">
                <ellipse cx="76" cy="74" rx="11" ry="13" class="mascot-eye"/>
                <ellipse cx="124" cy="74" rx="11" ry="13" class="mascot-eye"/>
                <g class="mascot-pupils">
                  <circle cx="76" cy="76" r="6" class="mascot-pupil"/>
                  <circle cx="124" cy="76" r="6" class="mascot-pupil"/>
                  <circle cx="78.5" cy="73" r="2" fill="#fff"/>
                  <circle cx="126.5" cy="73" r="2" fill="#fff"/>
                </g>
              </g>
              <path class="mascot-mouth mascot-mouth--smile" d="M86 100 Q100 112 114 100"/>
              <path class="mascot-mouth mascot-mouth--sad" d="M88 108 Q100 98 112 108"/>
              <g class="mascot-hands">
                <rect x="52" y="140" width="40" height="26" rx="13" class="mascot-hand mascot-hand--left"/>
                <rect x="108" y="140" width="40" height="26" rx="13" class="mascot-hand mascot-hand--right"/>
              </g>
            </g>
          </svg>
        </div>
      @else
        <div class="d-none d-lg-flex justify-content-center mb-3"><img src="{{ $logoUrl }}" alt="PT Bina Sarana Sukses" style="height: 40px"></div>
      @endif

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
    position: relative;
    color: #fff;
    background-color: #4f52d9;
    background-image:
      radial-gradient(circle at 15% 85%, rgba(255,255,255,.12) 0, transparent 45%),
      linear-gradient(160deg, var(--bs-primary) 0%, #4f52d9 100%);
    background-size: cover;
    background-position: center;
  }
  /* Lapisan warna di atas gambar latar agar teks tetap terbaca. */
  .auth-split__overlay {
    position: absolute; inset: 0;
    background: linear-gradient(160deg, rgba(40, 42, 120, .95) 0%, rgba(20, 20, 60, .95) 100%);
  }
  .auth-split__inner {
    position: relative;
    display: flex; flex-direction: column; justify-content: space-between; gap: 2rem;
    padding: 2.5rem 3rem; width: 100%;
  }
  .auth-logo {
    background: #fff; border-radius: .6rem; padding: .5rem .75rem;
    display: inline-flex; align-items: center; box-shadow: 0 .25rem 1rem rgba(0,0,0,.15);
  }
  .auth-logo img { height: 34px; width: auto; display: block; }
  .auth-split__copy h1 { text-shadow: 0 1px 8px rgba(0,0,0,.25); }
  .auth-split__copy { max-width: 28rem; }
  .auth-split__copy h1 { font-size: 1.85rem; line-height: 1.3; }
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
  .auth-character { width: 150px; margin: 0 auto .75rem; }
  .mascot { width: 100%; height: auto; display: block; overflow: visible; }
  .mascot-shadow { fill: rgba(34, 48, 62, .08); }
  .mascot-frame { fill: var(--bs-primary); }
  .mascot-screen { fill: #fff; }
  .mascot-stand { fill: #8f92ff; }
  .mascot-cam { fill: rgba(255,255,255,.6); }
  .mascot-cheek { fill: #ffb4c6; opacity: .7; }
  .mascot-eye { fill: #eef0ff; }
  .mascot-pupil { fill: #2b2c6e; }
  .mascot-mouth { fill: none; stroke: #2b2c6e; stroke-width: 4; stroke-linecap: round; transition: opacity .2s; }
  .mascot-mouth--sad { opacity: 0; }
  .mascot-hand { fill: #ffd2a8; stroke: #e7a978; stroke-width: 2; }
  .mascot-body { transform-origin: 100px 150px; animation: mascot-float 3.2s ease-in-out infinite; }
  .mascot-pupils { transition: transform .15s ease-out; }
  .mascot-eyes { transform-origin: 100px 74px; animation: mascot-blink 4.5s infinite; }
  .mascot-hands rect { transition: transform .35s cubic-bezier(.34,1.56,.64,1); transform-box: fill-box; }
  /* Menutup mata saat mengetik password */
  .mascot.is-covering .mascot-hand--left { transform: translate(12px, -80px) rotate(-6deg); }
  .mascot.is-covering .mascot-hand--right { transform: translate(-12px, -80px) rotate(6deg); }
  .mascot.is-covering .mascot-pupils { transform: translateY(3px); }
  /* Gagal login: menggeleng + cemberut */
  .mascot.is-sad .mascot-mouth--smile { opacity: 0; }
  .mascot.is-sad .mascot-mouth--sad { opacity: 1; }
  .mascot.is-shake .mascot-body { animation: mascot-shake .5s ease-in-out; }
  /* Berhasil: lompat kecil */
  .mascot.is-happy .mascot-body { animation: mascot-hop .6s ease-in-out 2; }
  @keyframes mascot-float { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-4px); } }
  @keyframes mascot-blink { 0%,92%,100% { transform: scaleY(1); } 95% { transform: scaleY(.1); } }
  @keyframes mascot-shake { 0%,100% { transform: translateX(0); } 20%,60% { transform: translateX(-8px) rotate(-2deg); } 40%,80% { transform: translateX(8px) rotate(2deg); } }
  @keyframes mascot-hop { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-12px); } }
  @media (prefers-reduced-motion: reduce) {
    .mascot-body, .mascot-eyes, .mascot.is-shake .mascot-body, .mascot.is-happy .mascot-body { animation: none !important; }
  }
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
  // Maskot: mata mengikuti NIK, tutup mata saat password, menggeleng saat gagal.
  const mascot = () => document.getElementById('mascot');
  const set = (cls, on) => mascot()?.classList.toggle(cls, on);
  const look = (len) => {
    const p = mascot()?.querySelector('.mascot-pupils');
    if (!p) return;
    // Bergerak dari kiri ke kanan sesuai panjang NIK, sedikit menunduk ke form.
    const x = Math.max(-5, Math.min(5, -5 + (len || 0) * 0.8));
    p.style.transform = `translate(${x}px, ${len ? 3 : 0}px)`;
  };

  document.addEventListener('focusin', (e) => {
    if (e.target.type === 'password') set('is-covering', true);
    if (e.target.id === 'nik') look(e.target.value.length);
  });
  document.addEventListener('focusout', (e) => {
    if (e.target.type === 'password') set('is-covering', false);
    if (e.target.id === 'nik') look(0);
  });
  document.addEventListener('input', (e) => {
    if (e.target.id === 'nik') look(e.target.value.length);
    set('is-sad', false);
  });

  $wire.on('login-failed', () => {
    set('is-covering', false);
    set('is-sad', true);
    set('is-shake', false); void mascot()?.getBoundingClientRect(); set('is-shake', true);
    setTimeout(() => set('is-shake', false), 600);
  });
  $wire.on('login-step', () => { set('is-sad', false); set('is-happy', true); setTimeout(() => set('is-happy', false), 1300); });
</script>
@endscript
