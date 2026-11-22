<div>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Manajemen Jabatan</h5>
                            <button wire:click="create()" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus me-1"></i> Tambah Jabatan Baru
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
                        <div class="px-4 pt-3">
                            <input wire:model.live.debounce.500ms="search" type="text" class="form-control" placeholder="Cari Nama, Kode, atau Departemen Jabatan...">
                        </div>
                        <div class="px-4 pt-3 d-flex align-items-center justify-content-end">
                            <label for="perPage" class="me-2 mb-0 text-secondary small">Tampilkan</label>
                            <select id="perPage" wire:model.live="perPage" class="form-select form-select-sm w-auto d-inline-block me-2">
                                <option value="10">10</option>
                                <option value="20">20</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                            <span class="text-secondary small">data per halaman</span>
                        </div>
                        <div class="table-responsive p-0 mt-3">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Departemen</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Nama Jabatan</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">File Jobdesk</th>
                                        <th class="text-secondary opacity-7"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($positions as $position)
                                        <tr>
                                            <td><p class="text-xs font-weight-bold mb-0">{{ $position->department->name ?? 'Global/Tidak Spesifik' }}</p></td>
                                            <td><p class="text-xs font-weight-bold mb-0 px-3">{{ $position->name }}</p></td>
                                            
                                            <td>
                                                @if($position->job_description_path)
                                                    <button wire:click="openViewModal({{ $position->id }})" class="btn btn-sm btn-outline-info py-1 px-2 mb-0">
                                                        <i class="bx bxs-bullseye"></i> Lihat
                                                    </button>
                                                @else
                                                    <span class="text-muted small">- Tidak Ada -</span>
                                                @endif
                                            </td>
                                            
                                            <td class="align-middle text-end">
                                                <button wire:click="edit({{ $position->id }})" class="btn btn-link text-secondary mb-0 p-1" data-bs-toggle="tooltip" title="Edit Jabatan">
                                                    <i class="bx bx-edit text-sm"></i>
                                                </button>
                                                <button wire:click="delete({{ $position->id }})"
                                                        wire:confirm="Apakah Anda yakin ingin menghapus jabatan ini: {{ $position->name }}?"
                                                        class="btn btn-link text-danger text-gradient p-1 mb-0" data-bs-toggle="tooltip" title="Hapus Jabatan">
                                                    <i class="bx bx-delete text-sm"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center py-4">Tidak ada data jabatan ditemukan.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                         <div class="px-4 pt-3">
                            {{ $positions->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal untuk Form Tambah dan Edit Jabatan --}}
    @if ($isModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <form wire:submit.prevent="store">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ $position_id ? 'Edit Jabatan' : 'Tambah Jabatan Baru' }}</h5>
                            <button type="button" class="btn-close" wire:click="closeModal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="department_id_position" class="form-label">Departemen Terkait <span class="text-danger">*</span></label>
                                    <select wire:model.defer="department_id" id="department_id_position" class="form-select @error('department_id') is-invalid @enderror">
                                        <option value="">-- Tidak Terkait Departemen Spesifik --</option>
                                        @foreach ($departments as $department)
                                            <option value="{{ $department->id }}">{{ $department->name }} ({{ $department->code }})</option>
                                        @endforeach
                                    </select>
                                    @error('department_id') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="position_name" class="form-label">Nama Jabatan <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="position_name" wire:model.defer="name" placeholder="Contoh: Staff IT, Manager Keuangan">
                                    @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="mb-3">
                                    <label for="jobdesk_file" class="form-label">Upload File Job Description (PDF/DOC/DOCX - Maks 2MB)</label>
                                    <input type="file" wire:model="jobdesk_file" id="jobdesk_file" class="form-control @error('jobdesk_file') is-invalid @enderror">
                                    <div wire:loading wire:target="jobdesk_file" class="mt-1 small text-primary">Uploading...</div>
                                    @error('jobdesk_file') <span class="text-danger small">{{ $message }}</span> @enderror
                                    
                                    @if($jobdesk_file)
                                        <div class="mt-2"><small class="text-muted">Preview: {{ $jobdesk_file->getClientOriginalName() }}</small></div>
                                    @elseif($existing_jobdesk_path)
                                         <div class="mt-2"><small class="text-muted">File saat ini: <a href="{{ Storage::url($existing_jobdesk_path) }}" target="_blank">Lihat File</a></small></div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="closeModal">Batal</button>
                            <button type="submit" class="btn btn-primary">
                                <span wire:loading.remove wire:target="store">{{ $position_id ? 'Simpan Perubahan' : 'Simpan Jabatan' }}</span>
                                <span wire:loading wire:target="store">Menyimpan...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
    @if ($isViewModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered modal-xl"> {{-- Gunakan modal-xl agar lebih lega --}}
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Job Description: {{ $viewingPositionName }}</h5>
                        <button type="button" class="btn-close" wire:click="closeViewModal"></button>
                    </div>
                    
                    <div class="modal-body" style="height: 75vh;">
                        @if($viewingJobdeskUrl)
                            <iframe src="{{ $viewingJobdeskUrl }}" width="100%" height="100%" frameborder="0">
                                <p>Browser Anda tidak mendukung iframe. Silakan unduh file <a href="{{ $viewingJobdeskUrl }}">di sini</a>.</p>
                            </iframe>
                        @else
                            <p class="text-center">Tidak ada file untuk ditampilkan.</p>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeViewModal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
    
</div>

