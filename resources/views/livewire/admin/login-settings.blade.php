<div>
  <div class="container-fluid py-4">
    <div class="page-header">
      <div>
        <h4 class="mb-1">Tampilan Login</h4>
        <div class="page-subtitle">
          Atur gambar, logo, dan teks di halaman login.
          @if ($updatedAt) Terakhir diubah {{ \Carbon\Carbon::parse($updatedAt)->locale('id')->diffForHumans() }} oleh {{ $updatedBy }}.@endif
        </div>
      </div>
      <a href="{{ route('login') }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Buka di tab baru (akan menampilkan halaman login hanya jika Anda keluar)">
        <i class="bx bx-link-external me-1"></i>Halaman login
      </a>
    </div>

    <div class="row g-4">
      {{-- Form --}}
      <div class="col-xl-5">
        <form wire:submit="save" class="card">
          <div class="card-body">
            <h6 class="text-body-secondary text-uppercase small fw-semibold">Gambar latar</h6>
            <div class="mb-2">
              <input type="file" wire:model="background" accept="image/jpeg,image/png,image/webp" class="form-control @error('background') is-invalid @enderror">
              @error('background') <div class="invalid-feedback">{{ $message }}</div> @enderror
              <small class="text-body-secondary">JPG / PNG / WebP, minimal 800×600, maks 4 MB. Disarankan foto lanskap 1600×1200 (mis. area tambang, kantor, tim IT).</small>
              <div wire:loading wire:target="background" class="small text-primary mt-1"><span class="spinner-border spinner-border-sm me-1"></span>Mengunggah…</div>
            </div>
            @if ($hasBackground)
              <button type="button" class="btn btn-sm btn-label-danger mb-3" wire:click="removeBackground" wire:confirm="Hapus gambar latar? Panel login kembali ke warna ungu polos.">
                <i class="bx bx-trash me-1"></i>Hapus gambar latar
              </button>
            @endif

            <label class="form-label mt-2 d-flex justify-content-between" for="overlay">
              <span>Lapisan warna di atas gambar</span><span class="text-body-secondary">{{ $overlay }}%</span>
            </label>
            <input id="overlay" type="range" min="0" max="90" step="5" wire:model.live="overlay" class="form-range">
            <small class="text-body-secondary d-block mb-4">Makin tinggi, gambar makin gelap dan teks makin mudah dibaca.</small>

            <h6 class="text-body-secondary text-uppercase small fw-semibold">Logo</h6>
            <input type="file" wire:model="logo" accept="image/png,image/jpeg,image/webp,image/svg+xml" class="form-control @error('logo') is-invalid @enderror">
            @error('logo') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <small class="text-body-secondary d-block">Bawaan: logo BSS. PNG transparan disarankan, maks 1 MB.</small>
            @if ($hasCustomLogo)
              <button type="button" class="btn btn-sm btn-label-secondary mt-2" wire:click="resetLogo"><i class="bx bx-reset me-1"></i>Kembali ke logo BSS</button>
            @endif

            <h6 class="text-body-secondary text-uppercase small fw-semibold mt-4">Teks</h6>
            <label class="form-label" for="headline">Judul</label>
            <textarea id="headline" rows="2" wire:model.live.debounce.400ms="headline" class="form-control @error('headline') is-invalid @enderror"></textarea>
            @error('headline') <div class="invalid-feedback">{{ $message }}</div> @enderror

            <label class="form-label mt-3">Poin keunggulan</label>
            @foreach ($points as $i => $p)
              <div class="input-group mb-2" wire:key="pt-{{ $i }}">
                <input type="text" wire:model.live.debounce.400ms="points.{{ $i }}" class="form-control @error('points.' . $i) is-invalid @enderror" placeholder="Poin {{ $i + 1 }}">
                <button type="button" class="btn btn-outline-secondary" wire:click="removePoint({{ $i }})" aria-label="Hapus poin"><i class="bx bx-x"></i></button>
                @error('points.' . $i) <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
            @endforeach
            @if (count($points) < 5)
              <button type="button" class="btn btn-sm btn-label-primary" wire:click="addPoint"><i class="bx bx-plus me-1"></i>Tambah poin</button>
            @endif

            <div class="form-check form-switch mt-4">
              <input class="form-check-input" type="checkbox" id="show_character" wire:model.live="show_character">
              <label class="form-check-label" for="show_character">Tampilkan maskot animasi di atas form</label>
            </div>
          </div>
          <div class="card-footer border-top d-flex gap-2">
            <button type="button" class="btn btn-label-secondary me-auto" wire:click="resetText"><i class="bx bx-reset me-1"></i>Teks bawaan</button>
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save,background,logo">
              <span wire:loading.remove wire:target="save">Simpan</span>
              <span wire:loading wire:target="save"><span class="spinner-border spinner-border-sm me-1"></span>Menyimpan…</span>
            </button>
          </div>
        </form>
      </div>

      {{-- Pratinjau --}}
      <div class="col-xl-7">
        <div class="card">
          <div class="card-header"><h5 class="card-title">Pratinjau</h5><small class="text-body-secondary">Perubahan berlaku setelah disimpan</small></div>
          <div class="card-body">
            <div class="login-preview rounded overflow-hidden border">
              <div class="login-preview__brand" style="{{ $previewBg ? "background-image: url('" . e($previewBg) . "');" : '' }}">
                <div class="login-preview__overlay" style="opacity: {{ $previewBg ? $overlay / 100 : 1 }}"></div>
                <div class="login-preview__content">
                  <div class="login-preview__logo"><img src="{{ $previewLogo }}" alt="Logo"></div>
                  <div>
                    <div class="fw-bold mb-2" style="font-size: 1.05rem; line-height: 1.3">{{ $headline }}</div>
                    @foreach (array_filter($points) as $p)
                      <div class="small mb-1"><i class="bx bx-check-circle me-1"></i>{{ $p }}</div>
                    @endforeach
                  </div>
                  <small class="opacity-75">© {{ date('Y') }} PT Bina Sarana Sukses</small>
                </div>
              </div>
              <div class="login-preview__form">
                @if ($show_character)<div class="login-preview__char"><i class="bx bx-desktop"></i></div>@endif
                <div class="fw-semibold mb-1">Masuk ke AMS</div>
                <div class="small text-body-secondary mb-3">Gunakan NIK karyawan Anda.</div>
                <div class="form-control form-control-sm mb-2 text-body-secondary">NIK</div>
                <div class="btn btn-primary btn-sm w-100">Lanjutkan</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <style>
    .login-preview { display: grid; grid-template-columns: 5fr 6fr; min-height: 340px; background: var(--bs-body-bg); }
    .login-preview__brand { position: relative; background: linear-gradient(160deg, var(--bs-primary), #4f52d9); background-size: cover; background-position: center; color: #fff; }
    .login-preview__overlay { position: absolute; inset: 0; background: linear-gradient(160deg, rgba(40, 42, 120, .95), rgba(20, 20, 60, .95)); }
    .login-preview__content { position: relative; height: 100%; display: flex; flex-direction: column; justify-content: space-between; gap: 1rem; padding: 1.25rem; }
    .login-preview__logo { background: #fff; border-radius: .5rem; padding: .4rem .6rem; width: fit-content; }
    .login-preview__logo img { height: 26px; display: block; }
    .login-preview__form { align-self: center; justify-self: center; width: 78%; background: var(--bs-paper-bg, #fff); border-radius: .75rem; padding: 1.25rem; box-shadow: 0 .25rem 1rem rgba(0,0,0,.06); text-align: center; }
    .login-preview__char { font-size: 2.5rem; color: var(--bs-primary); line-height: 1; margin-bottom: .5rem; }
    @media (max-width: 575.98px) { .login-preview { grid-template-columns: 1fr; } }
  </style>
</div>
