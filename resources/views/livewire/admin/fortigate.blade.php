@php
    $statusMeta = [
        'online' => ['Online', 'success'],
        'offline' => ['Offline', 'danger'],
        'auth_error' => ['Token ditolak', 'warning'],
    ];
@endphp
<div>
  <div class="container-fluid py-4">
    <div class="page-header">
      <div>
        <h4 class="mb-1">Fortigate</h4>
        <div class="page-subtitle">Perangkat Fortigate di setiap jobsite.</div>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="checkAll" wire:loading.attr="disabled" wire:target="checkAll">
          <i class="bx bx-refresh me-1" wire:loading.class="bx-spin" wire:target="checkAll"></i> Cek semua status
        </button>
        <button type="button" class="btn btn-primary btn-sm" wire:click="create">
          <i class="bx bx-plus me-1"></i> Tambah Fortigate
        </button>
      </div>
    </div>

    {{-- Ringkasan --}}
    <div class="row g-3 mb-4">
      @foreach ([
          ['Perangkat', $summary['total'], 'bx-shield-quarter', 'primary'],
          ['Jobsite', $summary['sites'], 'bx-buildings', 'info'],
          ['Online', $summary['online'], 'bx-check-circle', 'success'],
          ['Bermasalah', $summary['problem'], 'bx-error', 'danger'],
      ] as [$label, $value, $icon, $color])
        <div class="col-6 col-md-3">
          <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3 py-3">
              <span class="avatar flex-shrink-0"><span class="avatar-initial rounded bg-label-{{ $color }}"><i class="bx {{ $icon }} fs-4"></i></span></span>
              <div>
                <div class="fs-4 fw-semibold lh-1">{{ $value }}</div>
                <small class="text-body-secondary">{{ $label }}</small>
              </div>
            </div>
          </div>
        </div>
      @endforeach
    </div>

    {{-- Filter --}}
    <div class="card mb-4">
      <div class="card-body py-3 d-flex flex-wrap gap-2">
        <select wire:model.live="jobsiteFilter" class="form-select form-select-sm" style="max-width: 220px">
          <option value="">Semua jobsite</option>
          @foreach ($siteOptions as $s)
            <option value="{{ $s->id }}">{{ $s->code }}</option>
          @endforeach
          <option value="none">Tanpa jobsite</option>
        </select>
        <input type="search" wire:model.live.debounce.300ms="search" class="form-control form-control-sm" style="max-width: 260px" placeholder="Cari nama / alamat…">
        <span class="ms-auto small text-body-secondary align-self-center" wire:loading.remove wire:target="checkAll">Status di kartu adalah hasil cek terakhir. Data lengkap dimuat saat detail dibuka.</span>
        <span class="ms-auto small text-body-secondary align-self-center" wire:loading wire:target="checkAll">
          <span class="spinner-border spinner-border-sm me-1"></span> Mengecek perangkat…
        </span>
      </div>
    </div>

    {{-- Daftar per jobsite --}}
    @forelse ($groups as $site => $devices)
      <h6 class="text-body-secondary text-uppercase small fw-semibold mb-2 mt-1"><i class="bx bx-map-pin me-1"></i>{{ $site }}</h6>
      <div class="row g-3 mb-4">
        @foreach ($devices as $d)
          @php
              [$label, $tone] = !$d->is_active ? ['Nonaktif', 'secondary'] : ($statusMeta[$d->last_status] ?? ['Belum dicek', 'secondary']);
              $info = $d->last_info ?? [];
          @endphp
          <div class="col-md-6 col-xl-4" wire:key="fg-{{ $d->id }}">
            <div class="card h-100 {{ $d->is_active ? '' : 'opacity-75' }}">
              <div class="card-body">
                <div class="d-flex align-items-start gap-2 mb-2">
                  <div class="min-w-0 flex-grow-1">
                    <h6 class="mb-0 text-truncate"><a href="{{ route('admin.fortigate.show', $d) }}" class="text-heading">{{ $d->name }}</a></h6>
                    <small class="text-body-secondary text-truncate d-block">{{ parse_url($d->base_url, PHP_URL_HOST) }}{{ parse_url($d->base_url, PHP_URL_PORT) ? ':' . parse_url($d->base_url, PHP_URL_PORT) : '' }}{{ $d->vdom ? ' · VDOM ' . $d->vdom : '' }}</small>
                  </div>
                  <span class="badge bg-label-{{ $tone }}">{{ $label }}</span>
                </div>

                @if ($d->last_status === 'online' && $info)
                  <div class="small text-body-secondary mb-2 text-truncate">{{ collect([$info['hostname'] ?? null, $info['model'] ?? null, $info['version'] ?? null])->filter()->implode(' · ') }}</div>
                @elseif ($d->last_error && $d->is_active)
                  <div class="small text-{{ $d->last_status === 'auth_error' ? 'warning' : 'danger' }} mb-2 text-truncate" title="{{ $d->last_error }}"><i class="bx bx-error-circle"></i> {{ $d->last_error }}</div>
                @endif
                @if ($d->notes)
                  <div class="small text-body-secondary mb-2 text-truncate">{{ $d->notes }}</div>
                @endif

                <small class="text-body-secondary">
                  {{ $d->last_checked_at ? 'Status terakhir ' . $d->last_checked_at->locale('id')->diffForHumans() : 'Belum pernah dicek' }}
                </small>
              </div>
              <div class="card-footer border-top d-flex flex-wrap gap-1 py-2">
                <a href="{{ route('admin.fortigate.show', $d) }}" class="btn btn-sm btn-primary">
                  <i class="bx bx-right-arrow-alt me-1"></i>Buka detail
                </a>
                <div class="ms-auto d-flex gap-1">
                  <button type="button" class="btn btn-sm btn-icon btn-text-secondary" title="Cek sekarang" wire:click="checkOne({{ $d->id }})" wire:loading.attr="disabled" wire:target="checkOne({{ $d->id }})">
                    <i class="bx bx-refresh" wire:loading.class="bx-spin" wire:target="checkOne({{ $d->id }})"></i>
                  </button>
                  <button type="button" class="btn btn-sm btn-icon btn-text-secondary" title="Ubah" wire:click="edit({{ $d->id }})"><i class="bx bx-edit"></i></button>
                  <button type="button" class="btn btn-sm btn-icon btn-text-danger" title="Hapus" wire:click="delete({{ $d->id }})"
                          wire:confirm="Hapus perangkat {{ $d->name }}? Token API-nya ikut terhapus."><i class="bx bx-trash"></i></button>
                </div>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    @empty
      <div class="card">
        <div class="card-body">
          <div class="empty-state">
            <i class="bx bx-shield-quarter"></i>
            {{ $search || $jobsiteFilter ? 'Tidak ada perangkat yang cocok dengan filter.' : 'Belum ada Fortigate. Klik "Tambah Fortigate" untuk mendaftarkan perangkat pertama.' }}
          </div>
        </div>
      </div>
    @endforelse
  </div>

  {{-- Form tambah / ubah --}}
  @if ($showForm)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.45)" wire:keydown.escape.window="closeForm">
      <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form class="modal-content" wire:submit="save">
          <div class="modal-header">
            <h5 class="modal-title">{{ $editingId ? 'Ubah Fortigate' : 'Tambah Fortigate' }}</h5>
            <button type="button" class="btn-close" wire:click="closeForm" aria-label="Tutup"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label" for="fg-name">Nama perangkat</label>
                <input id="fg-name" type="text" wire:model="name" class="form-control @error('name') is-invalid @enderror" placeholder="mis. Fortigate MOB Office">
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-6">
                <label class="form-label" for="fg-site">Jobsite</label>
                <select id="fg-site" wire:model="jobsite_id" class="form-select @error('jobsite_id') is-invalid @enderror">
                  <option value="">— Tanpa jobsite —</option>
                  @foreach ($jobsites as $j)
                    <option value="{{ $j->id }}">{{ $j->code }} — {{ $j->name }}</option>
                  @endforeach
                </select>
                @error('jobsite_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-8">
                <label class="form-label" for="fg-url">Alamat Fortigate</label>
                <input id="fg-url" type="text" wire:model="base_url" class="form-control @error('base_url') is-invalid @enderror" placeholder="https://192.168.4.1:8443">
                @error('base_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <small class="text-body-secondary">Alamat GUI admin, tanpa <code>/api/v2</code>.</small>
              </div>
              <div class="col-md-4">
                <label class="form-label" for="fg-vdom">VDOM <span class="text-body-secondary fw-normal">(opsional)</span></label>
                <input id="fg-vdom" type="text" wire:model="vdom" class="form-control @error('vdom') is-invalid @enderror" placeholder="root">
                @error('vdom') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-12">
                <label class="form-label" for="fg-token">API token</label>
                <input id="fg-token" type="password" wire:model="api_token" autocomplete="new-password" class="form-control @error('api_token') is-invalid @enderror"
                       placeholder="{{ $editingId ? 'Kosongkan jika tidak diganti' : 'Token REST API admin' }}">
                @error('api_token') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <small class="text-body-secondary">Buat di Fortigate: System → Administrators → Create New → REST API Admin. Tambahkan IP server AMS di <i>Trusted Hosts</i>. Token disimpan terenkripsi.</small>
              </div>
              <div class="col-md-6">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="fg-ssl" wire:model="verify_ssl">
                  <label class="form-check-label" for="fg-ssl">Verifikasi sertifikat SSL</label>
                </div>
                <small class="text-body-secondary">Matikan untuk sertifikat self-signed (bawaan Fortigate).</small>
              </div>
              <div class="col-md-6">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="fg-active" wire:model="is_active">
                  <label class="form-check-label" for="fg-active">Aktif (ikut dicek dan ditampilkan)</label>
                </div>
              </div>
              <div class="col-12">
                <label class="form-label" for="fg-notes">Catatan <span class="text-body-secondary fw-normal">(opsional)</span></label>
                <textarea id="fg-notes" wire:model="notes" rows="2" class="form-control" placeholder="Lokasi rak, ISP, PIC, dll."></textarea>
              </div>
            </div>

            @if ($testResult)
              <div class="alert alert-{{ $testOk ? 'success' : 'danger' }} small mt-3 mb-0 d-flex gap-2">
                <i class="bx {{ $testOk ? 'bx-check-circle' : 'bx-error-circle' }} fs-5"></i><div class="text-break">{{ $testResult }}</div>
              </div>
            @endif
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary me-auto" wire:click="testConnection" wire:loading.attr="disabled" wire:target="testConnection">
              <span wire:loading.remove wire:target="testConnection"><i class="bx bx-plug me-1"></i>Tes koneksi</span>
              <span wire:loading wire:target="testConnection"><span class="spinner-border spinner-border-sm me-1"></span>Menguji…</span>
            </button>
            <button type="button" class="btn btn-label-secondary" wire:click="closeForm">Batal</button>
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">Simpan</button>
          </div>
        </form>
      </div>
    </div>
  @endif
</div>
