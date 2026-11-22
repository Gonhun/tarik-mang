<div>
  <div class="container-fluid py-4">
    <div class="page-header">
      <div>
        <h4 class="mb-1">Role &amp; Permission</h4>
        <div class="page-subtitle">Atur menu dan fitur yang bisa diakses setiap role. Perubahan di matriks langsung tersimpan.</div>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-sm btn-outline-primary" wire:click="createPermission"><i class="bx bx-key me-1"></i>Tambah permission</button>
        <button type="button" class="btn btn-sm btn-primary" wire:click="createRole"><i class="bx bx-plus me-1"></i>Tambah role</button>
      </div>
    </div>

    <div class="d-flex flex-wrap align-items-center gap-2 mb-4">
      <ul class="nav nav-pills gap-1">
        @foreach (['matrix' => ['Matriks akses', 'bx-grid'], 'roles' => ['Role', 'bx-id-card'], 'permissions' => ['Permission', 'bx-key']] as $key => [$label, $icon])
          <li class="nav-item">
            <button type="button" class="nav-link {{ $tab === $key ? 'active' : '' }}" wire:click="setTab('{{ $key }}')"><i class="bx {{ $icon }} me-1"></i>{{ $label }}</button>
          </li>
        @endforeach
      </ul>
      @if ($tab !== 'roles')
        <input type="search" wire:model.live.debounce.300ms="search" class="form-control form-control-sm ms-md-auto" style="max-width: 260px" placeholder="Cari permission / keterangan…">
      @endif
    </div>

    {{-- ================= Matriks ================= --}}
    @if ($tab === 'matrix')
      <div class="card">
        <div class="table-responsive" style="max-height: 72vh">
          <table class="table table-sm table-hover mb-0 perm-matrix">
            <thead>
              <tr>
                <th class="perm-col">Permission</th>
                @foreach ($roles as $role)
                  <th class="text-center role-col">
                    <div class="text-heading text-truncate" title="{{ $role->name }}">{{ $role->name }}</div>
                    <small class="text-body-secondary fw-normal text-lowercase">{{ $role->users_count }} user</small>
                    @if ($locks[$role->id])<div><i class="bx bx-lock-alt text-body-secondary" title="{{ $locks[$role->id] }}"></i></div>@endif
                  </th>
                @endforeach
              </tr>
            </thead>
            <tbody wire:loading.class="opacity-75" wire:target="togglePermission,toggleGroup">
              @forelse ($groups as $group => $items)
                <tr class="group-row">
                  <td class="perm-col fw-semibold">{{ $group }} <small class="text-body-secondary fw-normal">({{ $items->count() }})</small></td>
                  @foreach ($roles as $role)
                    @php
                        $have = $items->filter(fn ($p) => isset($granted[$role->id][$p->id]))->count();
                        $state = $have === 0 ? 'none' : ($have === $items->count() ? 'all' : 'some');
                    @endphp
                    <td class="text-center">
                      <button type="button" class="btn btn-sm btn-icon btn-text-{{ $state === 'none' ? 'secondary' : 'primary' }}"
                              title="{{ $state === 'all' ? 'Cabut semua' : 'Beri semua' }} {{ $group }} untuk {{ $role->name }}"
                              wire:click="toggleGroup({{ $role->id }}, @js($group))"
                              @if ($state === 'all') wire:confirm="Cabut semua izin {{ $group }} dari {{ $role->name }}?" @endif
                              @disabled($locks[$role->id])>
                        <i class="bx {{ $state === 'all' ? 'bxs-checkbox-checked' : ($state === 'some' ? 'bxs-checkbox-minus' : 'bx-checkbox') }} fs-4"></i>
                      </button>
                    </td>
                  @endforeach
                </tr>
                @foreach ($items as $p)
                  <tr wire:key="p-{{ $p->id }}">
                    <td class="perm-col">
                      <div class="fw-medium">{{ $p->name }}
                        @unless ($p->is_system)<span class="badge bg-label-secondary ms-1">tidak dipakai</span>@endunless
                      </div>
                      <small class="text-body-secondary">{{ $p->description }}</small>
                    </td>
                    @foreach ($roles as $role)
                      @php
                          $on = isset($granted[$role->id][$p->id]);
                          $pinned = $on && in_array($role->name, $protectedRoles, true) && in_array($p->name, $lockedOnProtected, true);
                      @endphp
                      <td class="text-center">
                        <input type="checkbox" class="form-check-input" {{ $on ? 'checked' : '' }}
                               wire:click="togglePermission({{ $role->id }}, {{ $p->id }})"
                               @disabled($locks[$role->id] || $pinned)
                               title="{{ $pinned ? 'Wajib untuk ' . $role->name : ($locks[$role->id] ?? ($on ? 'Cabut' : 'Beri') . ' ' . $p->name) }}"
                               aria-label="{{ $p->name }} untuk {{ $role->name }}">
                      </td>
                    @endforeach
                  </tr>
                @endforeach
              @empty
                <tr><td colspan="{{ $roles->count() + 1 }}"><div class="empty-state"><i class="bx bx-search"></i>Tidak ada permission yang cocok.</div></td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="card-footer border-top small text-body-secondary d-flex flex-wrap gap-3">
          <span><i class="bx bxs-checkbox-checked text-primary"></i> semua</span>
          <span><i class="bx bxs-checkbox-minus text-primary"></i> sebagian</span>
          <span><i class="bx bx-lock-alt"></i> role tidak bisa Anda ubah</span>
          <span>Perubahan berlaku saat user memuat ulang halaman.</span>
        </div>
      </div>
    @endif

    {{-- ================= Role ================= --}}
    @if ($tab === 'roles')
      <div class="row g-3">
        @foreach ($roles as $role)
          <div class="col-md-6 col-xl-3" wire:key="r-{{ $role->id }}">
            <div class="card h-100">
              <div class="card-body">
                <div class="d-flex align-items-start justify-content-between mb-2">
                  <h5 class="mb-0">{{ $role->name }}</h5>
                  @if (in_array($role->name, $protectedRoles, true))<span class="badge bg-label-danger">Sistem</span>@endif
                </div>
                <div class="d-flex gap-4 my-3">
                  <a href="{{ route('admin.users', ['role' => $role->id]) }}" class="text-decoration-none">
                    <div class="fs-4 fw-semibold text-heading lh-1">{{ number_format($role->users_count) }}</div>
                    <small class="text-body-secondary">user <i class="bx bx-right-arrow-alt"></i></small>
                  </a>
                  <div>
                    <div class="fs-4 fw-semibold lh-1">{{ $role->permissions->count() }}</div>
                    <small class="text-body-secondary">permission</small>
                  </div>
                </div>
                <div class="small text-body-secondary" style="min-height: 2.5rem">
                  {{ $role->permissions->pluck('name')->take(6)->implode(', ') }}{{ $role->permissions->count() > 6 ? ', …' : '' }}
                  @if ($role->permissions->isEmpty())<span class="fst-italic">Belum ada permission.</span>@endif
                </div>
                @if ($locks[$role->id])
                  <div class="small text-warning mt-2"><i class="bx bx-lock-alt"></i> {{ $locks[$role->id] }}</div>
                @endif
              </div>
              <div class="card-footer border-top d-flex gap-1 py-2">
                <button type="button" class="btn btn-sm btn-label-primary" wire:click="$set('tab', 'matrix')"><i class="bx bx-grid me-1"></i>Atur izin</button>
                <div class="ms-auto d-flex gap-1">
                  <button type="button" class="btn btn-sm btn-icon btn-text-secondary" title="Ganti nama" wire:click="editRole({{ $role->id }})" @disabled($locks[$role->id])><i class="bx bx-edit"></i></button>
                  <button type="button" class="btn btn-sm btn-icon btn-text-danger" title="Hapus" wire:click="deleteRole({{ $role->id }})"
                          wire:confirm="Hapus role {{ $role->name }}?" @disabled($locks[$role->id] || in_array($role->name, $protectedRoles, true))><i class="bx bx-trash"></i></button>
                </div>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    @endif

    {{-- ================= Permission ================= --}}
    @if ($tab === 'permissions')
      <div class="alert alert-info small d-flex gap-2">
        <i class="bx bx-info-circle fs-5"></i>
        <div>Permission <b>sistem</b> dipakai langsung di kode untuk membuka menu dan halaman, jadi namanya terkunci. Permission baru hanya berguna setelah dipakai di kode oleh developer.
          @if ($unusedCount) Ada <b>{{ $unusedCount }}</b> permission yang tidak dipakai (kelompok "Lainnya") dan boleh dibersihkan.@endif
        </div>
      </div>
      <div class="card">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead><tr><th>Permission</th><th>Keterangan</th><th class="text-center">Role</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
              @forelse ($groups as $group => $items)
                <tr class="group-row"><td colspan="5" class="fw-semibold">{{ $group }}</td></tr>
                @foreach ($items as $p)
                  <tr wire:key="pl-{{ $p->id }}">
                    <td class="fw-medium">{{ $p->name }}</td>
                    <td class="small text-body-secondary" style="min-width: 260px">{{ $p->description }}</td>
                    <td class="text-center">{{ $p->roles_count }}</td>
                    <td>
                      @if ($p->is_system)
                        <span class="badge bg-label-primary"><i class="bx bx-lock-alt"></i> Sistem</span>
                      @else
                        <span class="badge bg-label-secondary">Tidak dipakai</span>
                      @endif
                    </td>
                    <td class="text-end text-nowrap">
                      @unless ($p->is_system)
                        <button type="button" class="btn btn-sm btn-icon btn-text-secondary" title="Ganti nama" wire:click="editPermission({{ $p->id }})"><i class="bx bx-edit"></i></button>
                        <button type="button" class="btn btn-sm btn-icon btn-text-danger" title="{{ $p->roles_count ? 'Cabut dari semua role dulu' : 'Hapus' }}"
                                wire:click="deletePermission({{ $p->id }})" wire:confirm="Hapus permission {{ $p->name }}?" @disabled($p->roles_count)><i class="bx bx-trash"></i></button>
                      @endunless
                    </td>
                  </tr>
                @endforeach
              @empty
                <tr><td colspan="5"><div class="empty-state"><i class="bx bx-search"></i>Tidak ada permission yang cocok.</div></td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    @endif
  </div>

  {{-- Modal role --}}
  @if ($isRoleModalOpen)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.45)" wire:keydown.escape.window="closeRoleModal">
      <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" wire:submit="storeRole">
          <div class="modal-header">
            <h5 class="modal-title">{{ $role_id ? 'Ganti nama role' : 'Tambah role' }}</h5>
            <button type="button" class="btn-close" wire:click="closeRoleModal" aria-label="Tutup"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label" for="role_name">Nama role</label>
              <input id="role_name" type="text" wire:model="role_name" class="form-control @error('role_name') is-invalid @enderror" placeholder="mis. Admin Jobsite" autofocus>
              @error('role_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            @unless ($role_id)
              <label class="form-label" for="copyFromRole">Salin izin dari <span class="text-body-secondary fw-normal">(opsional)</span></label>
              <select id="copyFromRole" wire:model="copyFromRole" class="form-select">
                <option value="">Mulai tanpa izin</option>
                @foreach ($roles as $r)<option value="{{ $r->id }}">{{ $r->name }} ({{ $r->permissions->count() }} izin)</option>@endforeach
              </select>
              <small class="text-body-secondary">Setelah dibuat, atur izinnya di Matriks akses.</small>
            @endunless
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-label-secondary" wire:click="closeRoleModal">Batal</button>
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="storeRole">Simpan</button>
          </div>
        </form>
      </div>
    </div>
  @endif

  {{-- Modal permission --}}
  @if ($isPermissionModalOpen)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.45)" wire:keydown.escape.window="closePermissionModal">
      <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" wire:submit="storePermission">
          <div class="modal-header">
            <h5 class="modal-title">{{ $permission_id ? 'Ganti nama permission' : 'Tambah permission' }}</h5>
            <button type="button" class="btn-close" wire:click="closePermissionModal" aria-label="Tutup"></button>
          </div>
          <div class="modal-body">
            <label class="form-label" for="permission_name">Nama permission</label>
            <input id="permission_name" type="text" wire:model="permission_name" class="form-control @error('permission_name') is-invalid @enderror" autofocus>
            @error('permission_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <small class="text-body-secondary d-block mt-2">Permission baru belum membuka menu apa pun sampai dipakai di kode (mis. <code>@@can('Nama')</code>) dan dicatat di <code>config/permissions.php</code>.</small>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-label-secondary" wire:click="closePermissionModal">Batal</button>
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="storePermission">Simpan</button>
          </div>
        </form>
      </div>
    </div>
  @endif

  <style>
    .perm-matrix thead th { position: sticky; top: 0; z-index: 2; background: var(--bs-paper-bg, #fff); text-transform: none; letter-spacing: 0; vertical-align: bottom; }
    .perm-matrix .perm-col { position: sticky; left: 0; z-index: 1; background: var(--bs-paper-bg, #fff); min-width: 280px; max-width: 380px; }
    .perm-matrix thead .perm-col { z-index: 3; }
    .perm-matrix .role-col { min-width: 110px; max-width: 140px; }
    .perm-matrix .group-row td, .group-row td { background: #f6f7fb !important; }
    .perm-matrix td { vertical-align: middle; }
  </style>
</div>
