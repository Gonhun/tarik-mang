@php
    $avatar = fn ($u) => $u->pp_path ? asset('storage/' . $u->pp_path) : null;
    $initials = fn ($name) => collect(explode(' ', trim($name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    $tones = ['primary', 'success', 'info', 'warning', 'danger', 'secondary'];
@endphp
<div>
  <div class="container-fluid py-4">
    <div class="page-header">
      <div>
        <h4 class="mb-1">Daftar User</h4>
        <div class="page-subtitle">Kelola akun, jabatan, atasan, dan role pengguna AMS.</div>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="$toggle('showImport')">
          <i class="bx bx-upload me-1"></i> Import Excel
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="export" wire:loading.attr="disabled" wire:target="export">
          <i class="bx bx-download me-1"></i> Ekspor CSV
        </button>
        <button type="button" class="btn btn-sm btn-primary" wire:click="create">
          <i class="bx bx-user-plus me-1"></i> Tambah User
        </button>
      </div>
    </div>

    @if (session()->has('global-message'))
      <div class="alert alert-success">{{ session('global-message') }}</div>
    @endif
    @if (session()->has('error'))
      <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Import Excel --}}
    @if ($showImport)
      <div class="card mb-4">
        <div class="card-body">
          <form wire:submit="uploadExcel" class="row g-2 align-items-start">
            <div class="col-md">
              <input type="file" class="form-control @error('excelFile') is-invalid @enderror" wire:model="excelFile" accept=".xlsx,.xls">
              @error('excelFile') <div class="invalid-feedback">{{ $message }}</div> @enderror
              <small class="text-body-secondary">File .xlsx / .xls, maksimal 2 MB. Data dicocokkan berdasarkan NIK.</small>
            </div>
            <div class="col-md-auto">
              <button type="submit" class="btn btn-primary w-100" wire:loading.attr="disabled" wire:target="uploadExcel,excelFile">
                <span wire:loading.remove wire:target="uploadExcel">Upload &amp; proses</span>
                <span wire:loading wire:target="uploadExcel"><span class="spinner-border spinner-border-sm me-1"></span>Memproses…</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    @endif

    {{-- Ringkasan (klik untuk memfilter) --}}
    <div class="row g-3 mb-4">
      @foreach ([
          ['', 'Total user', $summary['total'], 'bx-group', 'primary', $filterStatus === '' && $filterAccount === ''],
          ['active', 'Aktif', $summary['active'], 'bx-check-circle', 'success', $filterStatus === 'active'],
          ['inactive', 'Nonaktif', $summary['inactive'], 'bx-block', 'secondary', $filterStatus === 'inactive'],
          ['pending', 'Belum pernah login', $summary['pending'], 'bx-time-five', 'warning', $filterAccount === 'pending'],
      ] as [$key, $label, $value, $icon, $tone, $on])
        <div class="col-6 col-xl-3">
          <button type="button" wire:click="quickFilter('{{ $key }}')" class="card w-100 h-100 text-start border {{ $on ? 'border-' . $tone : 'border-0' }}">
            <div class="card-body d-flex align-items-center gap-3 py-3">
              <span class="avatar flex-shrink-0"><span class="avatar-initial rounded bg-label-{{ $tone }}"><i class="bx {{ $icon }} fs-4"></i></span></span>
              <div>
                <div class="fs-4 fw-semibold lh-1">{{ number_format($value) }}</div>
                <small class="text-body-secondary">{{ $label }}</small>
              </div>
            </div>
          </button>
        </div>
      @endforeach
    </div>

    <div class="card">
      {{-- Filter --}}
      <div class="card-body border-bottom">
        <div class="row g-2">
          <div class="col-12 col-lg-4">
            <div class="input-group input-group-merge">
              <span class="input-group-text"><i class="bx bx-search"></i></span>
              <input type="search" wire:model.live.debounce.400ms="search" class="form-control" placeholder="Cari nama, NIK, WhatsApp, jabatan…">
            </div>
          </div>
          <div class="col-6 col-md-4 col-lg-2">
            <select wire:model.live="filterJobsite" class="form-select">
              <option value="">Semua jobsite</option>
              @foreach ($jobsites as $j)<option value="{{ $j->id }}">{{ $j->code }}</option>@endforeach
            </select>
          </div>
          <div class="col-6 col-md-4 col-lg-3">
            <select wire:model.live="filterDepartment" class="form-select">
              <option value="">Semua departemen</option>
              @foreach ($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
            </select>
          </div>
          <div class="col-6 col-md-4 col-lg-3">
            <select wire:model.live="filterRole" class="form-select">
              <option value="">Semua role</option>
              @foreach ($allRoles as $r)<option value="{{ $r->id }}">{{ $r->name }}</option>@endforeach
              <option value="none">Tanpa role</option>
            </select>
          </div>
          <div class="col-6 col-md-4 col-lg-2">
            <select wire:model.live="filterLevel" class="form-select">
              <option value="">Semua level</option>
              @foreach ($allLevels as $l)<option value="{{ $l->id }}">{{ $l->name }}</option>@endforeach
            </select>
          </div>
          <div class="col-6 col-md-4 col-lg-2">
            <select wire:model.live="filterStatus" class="form-select">
              <option value="">Semua status</option>
              <option value="active">Aktif</option>
              <option value="inactive">Nonaktif</option>
            </select>
          </div>
          <div class="col-6 col-md-4 col-lg-2">
            <select wire:model.live="filterAccount" class="form-select">
              <option value="">Semua akun</option>
              <option value="ready">Sudah buat password</option>
              <option value="pending">Belum pernah login</option>
            </select>
          </div>
          <div class="col-6 col-md-4 col-lg-2">
            <select wire:model.live="filterData" class="form-select">
              <option value="">Kelengkapan data</option>
              @foreach ($dataFilters as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
            </select>
          </div>
          <div class="col-6 col-md-4 col-lg-2">
            <select wire:model.live="sort" class="form-select" aria-label="Urutkan">
              @foreach ($sorts as $k => $label)<option value="{{ $k }}">Urut: {{ $label }}</option>@endforeach
            </select>
          </div>
          <div class="col-12 col-lg-2 d-flex align-items-center gap-2">
            @if ($filtersActive)
              <button type="button" class="btn btn-sm btn-label-secondary w-100" wire:click="resetFilters">
                <i class="bx bx-x me-1"></i>Reset filter ({{ $filtersActive }})
              </button>
            @endif
          </div>
        </div>
      </div>

      <div class="d-flex justify-content-between align-items-center px-4 py-2 small text-body-secondary">
        <span>
          {{ number_format($usersCollection->total()) }} user
          <span wire:loading.delay class="ms-2"><span class="spinner-border spinner-border-sm"></span></span>
        </span>
        <span class="d-flex align-items-center gap-2">
          Tampilkan
          <select wire:model.live="perPage" class="form-select form-select-sm" style="width: auto">
            @foreach ([15, 30, 50, 100] as $n)<option value="{{ $n }}">{{ $n }}</option>@endforeach
          </select>
        </span>
      </div>

      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th>User</th>
              <th>Jabatan</th>
              <th>Jobsite</th>
              <th>Atasan</th>
              <th>Role</th>
              <th class="text-center">Aset</th>
              <th class="text-center">Aktif</th>
              <th class="text-end">Aksi</th>
            </tr>
          </thead>
          <tbody wire:loading.class="opacity-50">
            @forelse ($usersCollection as $user)
              <tr wire:key="u-{{ $user->id }}">
                <td style="min-width: 240px">
                  <div class="d-flex align-items-center gap-3">
                    <div class="avatar flex-shrink-0 {{ $user->is_active ? '' : 'opacity-50' }}">
                      @if ($avatar($user))
                        <img src="{{ $avatar($user) }}" alt="" class="rounded-circle object-fit-cover w-100 h-100" loading="lazy">
                      @else
                        <span class="avatar-initial rounded-circle bg-label-{{ $tones[$user->id % count($tones)] }}">{{ $initials($user->name) }}</span>
                      @endif
                    </div>
                    <div class="min-w-0">
                      <div class="fw-medium text-heading text-truncate">{{ $user->name }}</div>
                      <small class="text-body-secondary d-block">
                        {{ $user->nik }}
                        @if ($user->whatsapp)
                          · <i class="bx bxl-whatsapp text-success"></i>{{ $user->whatsapp }}
                        @else
                          · <span class="text-warning">tanpa WA</span>
                        @endif
                      </small>
                      @unless ($user->password)
                        <span class="badge bg-label-warning" style="font-size: .65rem">Belum pernah login</span>
                      @endunless
                    </div>
                  </div>
                </td>
                <td style="min-width: 180px">
                  <div class="text-truncate" style="max-width: 220px">{{ $user->position->name ?? '—' }}</div>
                  <small class="text-body-secondary">{{ $user->department->name ?? '—' }}{{ $user->positionLevel ? ' · ' . $user->positionLevel->name : '' }}</small>
                </td>
                <td><span class="badge bg-label-secondary">{{ $user->jobsite->code ?? '—' }}</span></td>
                <td class="small" style="min-width: 140px">{{ $user->manager->name ?? '—' }}</td>
                <td>
                  @forelse ($user->roles as $role)
                    <span class="badge bg-label-{{ in_array($role->name, ['Administrator', 'Administrator IT'], true) ? 'danger' : 'info' }} me-1 mb-1">{{ $role->name }}</span>
                  @empty
                    <span class="badge bg-label-secondary">Tanpa role</span>
                  @endforelse
                </td>
                <td class="text-center">
                  @if ($user->assets_held > 0)
                    <span class="badge rounded-pill bg-primary">{{ $user->assets_held }}</span>
                  @else
                    <span class="text-body-secondary">0</span>
                  @endif
                </td>
                <td class="text-center">
                  <div class="form-check form-switch d-inline-block mb-0">
                    <input class="form-check-input" type="checkbox" role="switch" {{ $user->is_active ? 'checked' : '' }}
                           wire:click="toggleApproved({{ $user->id }})"
                           wire:confirm="{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }} akun {{ $user->name }}?"
                           aria-label="Status aktif {{ $user->name }}">
                  </div>
                </td>
                <td class="text-end">
                  <div class="dropdown">
                    <button class="btn btn-sm btn-icon btn-text-secondary" data-bs-toggle="dropdown" aria-label="Aksi"><i class="bx bx-dots-vertical-rounded"></i></button>
                    <ul class="dropdown-menu dropdown-menu-end">
                      <li><button type="button" class="dropdown-item" wire:click="edit({{ $user->id }})"><i class="bx bx-edit me-2"></i>Ubah</button></li>
                      @if ($user->password && $user->id !== auth()->id())
                        <li><button type="button" class="dropdown-item" wire:click="RessetPassowrd({{ $user->id }})"
                                    wire:confirm="Reset password {{ $user->name }}? User akan diminta membuat password baru saat login."><i class="bx bx-key me-2"></i>Reset password</button></li>
                      @endif
                      <li><hr class="dropdown-divider"></li>
                      <li><button type="button" class="dropdown-item text-danger" wire:click="delete({{ $user->id }})"
                                  wire:confirm="Hapus user {{ $user->name }} secara permanen?"><i class="bx bx-trash me-2"></i>Hapus</button></li>
                    </ul>
                  </div>
                </td>
              </tr>
            @empty
              <tr><td colspan="8">
                <div class="empty-state"><i class="bx bx-user-x"></i>
                  {{ $filtersActive ? 'Tidak ada user yang cocok dengan filter.' : 'Belum ada user.' }}
                  @if ($filtersActive)
                    <div class="mt-2"><button type="button" class="btn btn-sm btn-outline-primary" wire:click="resetFilters">Reset filter</button></div>
                  @endif
                </div>
              </td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if ($usersCollection->hasPages())
        <div class="card-footer border-top">{{ $usersCollection->links() }}</div>
      @endif
    </div>
  </div>

  {{-- Modal tambah / ubah --}}
  @if ($isModalOpen)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.45)" wire:keydown.escape.window="closeModal">
      <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <form class="modal-content" wire:submit="store">
          <div class="modal-header">
            <h5 class="modal-title">{{ $user_id ? 'Ubah User' : 'Tambah User' }}</h5>
            <button type="button" class="btn-close" wire:click="closeModal" aria-label="Tutup"></button>
          </div>
          <div class="modal-body">
            <h6 class="text-body-secondary text-uppercase small fw-semibold mb-2">Identitas</h6>
            <div class="row g-3 mb-4">
              <div class="col-md-5">
                <label for="name" class="form-label">Nama lengkap <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" wire:model="name">
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-3">
                <label for="nik" class="form-label">NIK <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('nik') is-invalid @enderror" id="nik" wire:model="nik" inputmode="numeric">
                @error('nik') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-4">
                <label for="whatsapp_number" class="form-label">WhatsApp <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('whatsapp_number') is-invalid @enderror" id="whatsapp_number" wire:model="whatsapp_number" inputmode="tel" placeholder="08xx / 628xx">
                @error('whatsapp_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
            </div>

            <h6 class="text-body-secondary text-uppercase small fw-semibold mb-2">Penempatan</h6>
            <div class="row g-3 mb-4">
              <div class="col-md-4">
                <label for="department_id" class="form-label">Departemen <span class="text-danger">*</span></label>
                <select wire:model.live="department_id" id="department_id" class="form-select @error('department_id') is-invalid @enderror">
                  <option value="">Pilih departemen</option>
                  @foreach ($departments as $department)<option value="{{ $department->id }}">{{ $department->name }}</option>@endforeach
                </select>
                @error('department_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-4">
                <label for="jabatan_id" class="form-label">Jabatan <span class="text-danger">*</span></label>
                <select wire:model="jabatan_id" id="jabatan_id" class="form-select @error('jabatan_id') is-invalid @enderror" @disabled(!$department_id)>
                  <option value="">{{ $department_id ? 'Pilih jabatan' : 'Pilih departemen dulu' }}</option>
                  @foreach ($jabatans as $jabatan)<option value="{{ $jabatan->id }}">{{ $jabatan->name }}</option>@endforeach
                </select>
                @error('jabatan_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-4">
                <label for="position_level_id" class="form-label">Level <span class="text-danger">*</span></label>
                <select wire:model="position_level_id" id="position_level_id" class="form-select @error('position_level_id') is-invalid @enderror">
                  <option value="">Pilih level</option>
                  @foreach ($allLevels as $lvl)<option value="{{ $lvl->id }}">{{ $lvl->name }}</option>@endforeach
                </select>
                @error('position_level_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-4">
                <label for="jobsite_id" class="form-label">Jobsite <span class="text-danger">*</span></label>
                <select wire:model="jobsite_id" id="jobsite_id" class="form-select @error('jobsite_id') is-invalid @enderror">
                  <option value="">Pilih jobsite</option>
                  @foreach ($jobsites as $jobsite)<option value="{{ $jobsite->id }}">{{ $jobsite->code }} — {{ $jobsite->name }}</option>@endforeach
                </select>
                @error('jobsite_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-8">
                <label for="manager_id" class="form-label">Atasan langsung</label>
                <select wire:model="manager_id" id="manager_id" class="form-select @error('manager_id') is-invalid @enderror">
                  <option value="">Tidak ada atasan langsung</option>
                  @foreach ($usersForManagerSelection as $m)
                    <option value="{{ $m->id }}">{{ $m->name }} · {{ $m->nik }} · {{ $m->positionLevel?->name }}{{ $m->department ? ' · ' . $m->department->name : '' }}</option>
                  @endforeach
                </select>
                @error('manager_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <small class="text-body-secondary">Hanya user dengan level "Kepala …" yang muncul.</small>
              </div>
            </div>

            <h6 class="text-body-secondary text-uppercase small fw-semibold mb-2">Role <span class="text-danger">*</span> <span class="text-body-secondary fw-normal text-lowercase">(pilih satu)</span></h6>
            @if ($previousRoles)
              <div class="alert alert-warning small py-2 d-flex gap-2">
                <i class="bx bx-error fs-5"></i>
                <div>User ini sebelumnya punya {{ count($previousRoles) }} role ({{ implode(', ', $previousRoles) }}). Setelah disimpan hanya role yang dipilih di bawah yang tersisa.</div>
              </div>
            @endif
            <div class="row g-2 mb-2" role="radiogroup">
              @foreach ($allRoles as $role)
                <div class="col-sm-6">
                  <label class="form-check border rounded px-3 py-2 mb-0 d-flex align-items-center gap-2 {{ (string) $user_role === (string) $role->id ? 'border-primary bg-label-primary' : '' }}" for="role_{{ $role->id }}">
                    <input class="form-check-input m-0" type="radio" name="user_role" value="{{ $role->id }}" wire:model.live="user_role" id="role_{{ $role->id }}">
                    <span>{{ $role->name }}</span>
                  </label>
                </div>
              @endforeach
            </div>
            @error('user_role') <div class="text-danger small">{{ $message }}</div> @enderror

            <div class="alert alert-info d-flex gap-2 small mt-4 mb-0">
              <i class="bx bx-info-circle fs-5"></i>
              <div>User membuat password sendiri saat login pertama dengan NIK. Admin tidak perlu mengisi password.
                @if ($user_id) Gunakan <b>Reset password</b> bila user lupa password.@endif
              </div>
            </div>
          </div>
          <div class="modal-footer">
            @if ($user_id)
              <button type="button" class="btn btn-outline-warning me-auto" wire:click="RessetPassowrd({{ $user_id }})"
                      wire:confirm="Reset password user ini? User akan diminta membuat password baru saat login.">
                <i class="bx bx-key me-1"></i>Reset password
              </button>
            @endif
            <button type="button" class="btn btn-label-secondary" wire:click="closeModal">Batal</button>
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="store">
              <span wire:loading.remove wire:target="store">{{ $user_id ? 'Simpan perubahan' : 'Tambah user' }}</span>
              <span wire:loading wire:target="store"><span class="spinner-border spinner-border-sm me-1"></span>Menyimpan…</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  @endif
</div>
