<div>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Manajemen Pengguna</h5>
                            <button wire:click="create()" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus me-1"></i> Tambah Pengguna Baru
                            </button>
                        </div>
                        @if (session()->has('message'))
                            <div class="alert alert-success mt-3" role="alert">
                                {{ session('message') }}
                            </div>
                        @endif
                        @if (session()->has('error'))
                            <div class="alert alert-danger mt-3" role="alert">
                                {{ session('error') }}
                            </div>
                        @endif
                    </div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="px-4 pt-3 mb-3">
                            <form wire:submit.prevent="uploadExcel">
                                <div class="input-group">
                                    <input type="file" class="form-control" id="excelFile" wire:model="excelFile">
                                    <button type="submit" class="btn btn-primary" style="margin-left: 8px;">
                                        <span wire:loading.remove wire:target="uploadExcel">Upload</span>
                                        <span wire:loading wire:target="uploadExcel">Matching...</span>
                                    </button>
                                </div>
                                @error('excel_file') 
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </form>
                        </div>
                        <div class="px-4 pt-3">
                            <input wire:model.live.debounce.500ms="search" type="text" class="form-control" placeholder="Cari Nama, NIK, Email, atau Peran...">
                        </div>
                        <div class="table-responsive p-0 mt-3">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">NIK</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Nama</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Role</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Whatsapp</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Departemen</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Jabatan</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Level</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Atasan</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Jobsite</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Aktif</th>
                                        <th class="text-secondary opacity-7"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($usersCollection as $user)
                                        <tr>
                                            <td><p class="text-xs font-weight-bold mb-0">{{ $user->nik }}</p></td>
                                            <td>
                                                <div class="d-flex px-2 py-1">
                                                    {{-- Tambahkan foto profil jika ada --}}
                                                    <div class="d-flex flex-column justify-content-center">
                                                        <h6 class="mb-0 text-sm">{{ $user->name }}</h6>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                {{-- Loop untuk menampilkan semua role sebagai badge --}}
                                                @forelse ($user->roles as $role)
                                                    <span class="badge bg-gradient-info me-1">{{ $role->name }}</span>
                                                @empty
                                                    <span class="badge bg-gradient-secondary">Tanpa Role</span>
                                                @endforelse
                                            </td>
                                            
                                            <td><p class="text-xs font-weight-bold mb-0">{{ $user->whatsapp }}</p></td>
                                            <td><p class="text-xs font-weight-bold mb-0">{{ $user->department->name ?? 'N/A' }}</p></td>
                                            <td><p class="text-xs font-weight-bold mb-0">{{ $user->position->name ?? 'N/A' }}</p></td>
                                            <td><p class="text-xs font-weight-bold mb-0">{{ $user->positionLevel->name ?? 'N/A' }}</p></td>
                                            <td><p class="text-xs font-weight-bold mb-0">{{ $user->manager->name ?? 'N/A' }}</p></td>
                                            <td><p class="text-xs font-weight-bold mb-0">{{ $user->jobsite->name ?? 'N/A' }}</p></td>
                                            <td>
                                                <input type="checkbox" wire:click="toggleApproved({{ $user->id }})" {{ $user->is_active ? 'checked' : '' }}>
                                            </td>
                                            <td class="align-middle">
                                                <button wire:click="edit({{ $user->id }})" class="btn btn-link text-secondary mb-0 p-1" data-bs-toggle="tooltip" title="Edit Pengguna">
                                                    <i class="bx bx-edit text-sm"></i>
                                                </button>
                                                {{-- <button wire:click="delete({{ $user->id }})"
                                                        wire:confirm="Apakah Anda yakin ingin menghapus pengguna ini: {{ $user->name }}?"
                                                        class="btn btn-link text-danger text-gradient p-1 mb-0" data-bs-toggle="tooltip" title="Hapus Pengguna">
                                                    <i class="far fa-trash-alt text-sm"></i>
                                                </button> --}}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="text-center py-4">Tidak ada data pengguna ditemukan.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="px-4 pt-3">
                            {{ $usersCollection->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Create/Edit Pengguna --}}
    @if ($isModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    
                    <form>
                        <div class="modal-header">
                            <h5 class="modal-title">{{ $user_id ? 'Edit Pengguna' : 'Tambah Pengguna Baru' }}</h5>
                            <button type="button" class="btn-close" wire:click="closeModal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="name" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" wire:model.defer="name">
                                    @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="nik" class="form-label">NIK <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('nik') is-invalid @enderror" id="nik" wire:model.defer="nik">
                                    @error('nik') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="whatsapp_number" class="form-label">No. WhatsApp</label>
                                    <input type="text" class="form-control @error('whatsapp_number') is-invalid @enderror" id="whatsapp_number" wire:model.defer="whatsapp_number">
                                    @error('whatsapp_number') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                             <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="department_id" class="form-label">Departemen</label>
                                    <select wire:model.defer="department_id" wire:change="getPosisions($event.target.value)" id="department_id" class="form-select @error('department_id') is-invalid @enderror">
                                        <option value="">-- Pilih Departemen --</option>
                                        @foreach ($departments as $department)
                                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('department_id') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="jabatan_id" class="form-label">Jabatan</label>
                                    <select wire:model.defer="jabatan_id" id="jabatan_id" class="form-select @error('jabatan_id') is-invalid @enderror">
                                        <option value="">-- Pilih Jabatan --</option>
                                        @foreach ($jabatans as $jabatan)
                                            <option value="{{ $jabatan->id }}">{{ $jabatan->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('jabatan_id') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="position_level_id" class="form-label">Level Jabatan</label>
                                    <select wire:model.defer="position_level_id" id="position_level_id" class="form-select @error('position_level_id') is-invalid @enderror">
                                        <option value="">-- Pilih Level --</option>
                                        @foreach ($allLevels as $allLevel)
                                            <option value="{{ $allLevel->id }}">{{ $allLevel->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('position_level_id') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="manager_id" class="form-label">Atasan Langsung (Manager)</label>
                                    <select wire:model.defer="manager_id" id="manager_id" class="form-select @error('manager_id') is-invalid @enderror">
                                        <option value="">-- Tidak Ada Atasan Langsung --</option>
                                        {{-- Loop dari properti $usersForManagerSelection --}}
                                        @foreach ($usersForManagerSelection as $managerOption)
                                           
                                            <option value="{{ $managerOption->id }}">{{ $managerOption->name }} ['{{ $managerOption->nik }}','{{ $managerOption->positionLevel?->name }}','{{ $managerOption->department?->name }}']</option>
                                        @endforeach
                                    </select>
                                    @error('manager_id') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="jobsite_id" class="form-label">Jobsite</label>
                                    <select wire:model.defer="jobsite_id" id="jobsite_id" class="form-select @error('jobsite_id') is-invalid @enderror">
                                        <option value="">-- Pilih Jobsite --</option>
                                        @foreach ($jobsites as $jobsite)
                                            <option value="{{ $jobsite->id }}">{{ $jobsite->name }} ({{$jobsite->code}})</option>
                                        @endforeach
                                    </select>
                                    @error('jobsite_id') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <hr>
                            <div class="mb-3">
                                <label class="form-label">Peran (Roles) <span class="text-danger">*</span></label>
                                <div class="row" style="max-height: 200px; overflow-y: auto;">
                                    {{-- Loop semua role yang tersedia dari $allRoles --}}
                                    @foreach ($allRoles as $role)
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" value="{{ $role->id }}" 
                                                    wire:model.defer="user_roles" 
                                                    id="role_{{ $role->id }}">
                                                <label class="form-check-label" for="role_{{ $role->id }}">
                                                    {{ $role->name }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @error('user_roles') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            <hr>
                            <p class="text-muted">
                                {{-- @if($mode == 'create') Isi password untuk pengguna baru.
                                @else Biarkan kosong jika tidak ingin mengubah password.
                                @endif --}}
                            </p>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    @if ($user_id)
                                        <button type="button" wire:click= "RessetPassowrd({{$user_id}})" class="btn btn-warning">
                                            <span wire:loading.remove wire:target="RessetPassowrd({{$user_id}})">Reset Password</span>
                                            <span wire:loading wire:target="RessetPassowrd({{$user_id}})">Menyimpan...</span>
                                        </button>
                                    @endif
                                    
                                    
                                </div>
                                <div class="row">
                                    <div class="alert alert-info d-flex align-items-center mt-2 col-md-12" role="alert" style="font-size: 0.95rem;">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <div>
                                            Pengguna akan membuat atau mengganti password secara mandiri melalui halaman login. <br>
                                            <span class="text-muted">Catatan: Admin tidak perlu menginput atau mengatur password di sini.</span>
                                        </div>
                                    </div>
                                </div>
                                {{-- <div class="col-md-6 mb-3">
                                    <label for="password_confirmation" class="form-label">Konfirmasi Password @if($mode == 'create' || !empty($this->password) ) <span class="text-danger">*</span> @endif</label>
                                    <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror" id="password_confirmation" wire:model.defer="password_confirmation">
                                     @error('password_confirmation') <span class="text-danger">{{ $message }}</span> @enderror
                                </div> --}}
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="closeModal">Batal</button>
                            <button type="button" wire:click= "store" class="btn btn-primary">
                                <span wire:loading.remove wire:target="store">{{ $user_id ? 'Simpan Perubahan' : 'Simpan Pengguna' }}</span>
                                <span wire:loading wire:target="store">Menyimpan...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>

