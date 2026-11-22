<div>
    <div class="container-fluid py-4">
        {{-- Pesan Sukses Global --}}
        @if (session()->has('message'))
            <div class="alert alert-success" role="alert">
                {{ session('message') }}
            </div>
        @endif
        
        <div class="card">
            {{-- Navigasi Tab --}}
            <div class="card-header p-0">
                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $tab == 0 ? 'active' : '' }}" id="roles-tab" data-bs-toggle="tab" data-bs-target="#roles-tab-pane" type="button" role="tab" aria-controls="roles-tab-pane" aria-selected="true" wire:click="tab = 0">Manajemen Roles</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $tab == 1 ? 'active' : '' }}" id="permissions-tab" data-bs-toggle="tab" data-bs-target="#permissions-tab-pane" type="button" role="tab" aria-controls="permissions-tab-pane" aria-selected="false" wire:click="tab = 1">Manajemen Permissions</button>
                    </li>
                </ul>
            </div>
            
            <div class="tab-content" id="myTabContent">
                {{-- KONTEN TAB ROLES --}}
                
                <div class="tab-pane fade {{ $tab == 0 ? 'show active' : '' }}" id="roles-tab-pane" role="tabpanel" aria-labelledby="roles-tab" tabindex="0">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0">Daftar Roles</h6>
                                <button wire:click="createRole()" class="btn btn-primary btn-sm">Tambah Role Baru</button>
                            </div>
                            <input wire:model.live.debounce.300ms="searchRole" type="text" class="form-control mb-3" placeholder="Cari role...">
                            <div class="table-responsive">
                                <table class="table table-hover align-items-center mb-0">
                                    <thead class="bg-gradient-primary text-white">
                                        <tr>
                                            <th><i class="bx bx-user me-1"></i> Nama Role</th>
                                            <th><i class="bx bx-key me-1"></i> Permissions</th>
                                            <th class="text-end"><i class="bx bx-cog me-1"></i> Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($roles as $role)
                                            <tr>
                                                <td class="font-weight-bold align-middle">
                                                    <span class="text-primary">
                                                        <i class="bx bx-shield-quarter me-1"></i>{{ $role->name }}
                                                    </span>
                                                </td>
                                                <td class="align-middle">
                                                    @if($role->permissions->count())
                                                        @foreach ($role->permissions as $permission)
                                                            <span class="badge bg-gradient-info text-white me-1 mb-1" style="font-size: 0.85em;">
                                                                <i class="bx bx-check-shield"></i> {{ $permission->name }}
                                                            </span>
                                                        @endforeach
                                                    @else
                                                        <span class="badge bg-light text-muted">Tidak ada</span>
                                                    @endif
                                                </td>
                                                <td class="text-center align-middle">
                                                    <button wire:click="editRole({{ $role->id }})" class="btn btn-sm btn-outline-primary py-1 px-2 me-1" title="Edit Role">
                                                        <i class="bx bx-edit-alt"></i>
                                                    </button>
                                                    <button wire:click="deleteRole({{ $role->id }})" class="btn btn-sm btn-outline-danger py-1 px-2 me-1" title="Hapus Role">
                                                        <i class="bx bx-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center text-muted py-4">
                                                    <i class="bx bx-info-circle fs-3 mb-2 d-block"></i>
                                                    <span>Tidak ada role ditemukan.</span>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3">{{ $roles->links() }}</div>
                        </div>
                    </div>
                

                {{-- KONTEN TAB PERMISSIONS --}}
                <div class="tab-pane fade {{ $tab == 1 ? 'show active' : '' }}" id="permissions-tab-pane" role="tabpanel" aria-labelledby="permissions-tab" tabindex="0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0 text-info">
                                <i class="bx bx-key me-2"></i> Daftar Permissions
                            </h5>
                            <button wire:click="createPermission()" class="btn btn-gradient-primary btn-sm shadow">
                                <i class="bx bx-plus me-1"></i> Tambah Permission Baru
                            </button>
                        </div>
                        <div class="input-group mb-3">
                            <span class="input-group-text bg-light"><i class="bx bx-search"></i></span>
                            <input wire:model.live.debounce.300ms="searchPermission" type="text" class="form-control" placeholder="Cari permission...">
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-items-center mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th><i class="bx bx-lock-alt me-1"></i> Nama Permission</th>
                                        <th class="text-end"><i class="bx bx-cog me-1"></i> Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                   
                                    @forelse ($permissions as $permission)
                                        <tr>
                                            <td class="font-weight-bold align-middle">
                                                <span class="badge bg-gradient-info text-white px-2 py-1" style="font-size: 1em;">
                                                    <i class="bx bx-check-shield me-1"></i> {{ $permission->name }}
                                                </span>
                                            </td>
                                            <td class="text-end align-middle">
                                                <button wire:click="editPermission({{ $permission->id }})" class="btn btn-sm btn-outline-info py-1 px-2 me-1" title="Edit Permission">
                                                    <i class="bx bx-edit-alt"></i>
                                                </button>
                                                <button wire:click="deletePermission({{ $permission->id }})" class="btn btn-sm btn-outline-danger py-1 px-2" title="Hapus Permission">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="text-center text-muted py-4">
                                                <i class="bx bx-info-circle fs-3 mb-2 d-block"></i>
                                                <span>Tidak ada permission ditemukan.</span>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">{{ $permissions->links() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL UNTUK ROLE --}}
    @if($isRoleModalOpen)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form wire:submit.prevent="storeRole">
                    <div class="modal-header"><h5 class="modal-title">{{ $role_id ? 'Edit Role' : 'Tambah Role Baru' }}</h5><button type="button" class="btn-close" wire:click="closeRoleModal"></button></div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="role_name" class="form-label">Nama Role</label>
                            <input type="text" wire:model.defer="role_name" class="form-control @error('role_name') is-invalid @enderror" id="role_name">
                            @error('role_name') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Permissions untuk Role ini:</label>
                            <div class="row" style="max-height: 250px; overflow-y: auto;">
                                @foreach ($allPermissions as $permission)
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="{{ $permission->id }}" wire:model.defer="role_permissions" id="perm_{{ $permission->id }}">
                                            <label class="form-check-label" for="perm_{{ $permission->id }}">{{ $permission->name }}</label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @error('role_permissions') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeRoleModal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- MODAL UNTUK PERMISSION --}}
    @if($isPermissionModalOpen)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form wire:submit.prevent="storePermission">
                    <div class="modal-header"><h5 class="modal-title">{{ $permission_id ? 'Edit Permission' : 'Tambah Permission Baru' }}</h5><button type="button" class="btn-close" wire:click="closePermissionModal"></button></div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="permission_name" class="form-label">Nama Permission</label>
                            <input type="text" wire:model.defer="permission_name" class="form-control @error('permission_name') is-invalid @enderror" id="permission_name" placeholder="Contoh: edit articles">
                            @error('permission_name') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closePermissionModal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
    
   document.addEventListener('livewire:init', function () {
        Livewire.on('swal:error', event => {
            const detail = event[0] || event;
            Swal.fire({
                title: detail.title,
                text: detail.text,
                icon: 'error'
            });
        });
        Livewire.on('swal:confirm', event => {
                // PASTIKAN BARIS INI ADA DI SINI
                const detail = event[0] || event;

                Swal.fire({
                    title: detail.title, // Menggunakan variabel 'detail'
                    text: detail.text,
                    icon: detail.type,
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, Lanjutkan!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Panggil method di komponen PHP jika dikonfirmasi
                        Livewire.dispatch(detail.method, { id: detail.id });
                    }
                });
            });

        Livewire.on('swal:alert', event => {
            // PASTIKAN BARIS INI JUGA ADA DI SINI
            const detail = event.detail[0] || event.detail;

            Swal.fire({
                title: detail.title, // Menggunakan variabel 'detail'
                text: detail.text,
                icon: detail.type
            });
        });
    });
</script>
@endpush