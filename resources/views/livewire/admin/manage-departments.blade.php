<div>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Manajemen Departemen</h5>
                            <button wire:click="create()" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus me-1"></i> Tambah Departemen Baru
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
                        {{-- Tambahkan input search di sini jika mau --}}
                        <div class="px-4 pt-3">
                            <input wire:model.live.debounce.500ms="search" type="text" class="form-control" placeholder="Cari departemen...">
                        </div>

                        {{-- Bagian untuk menampilkan daftar departemen (opsional jika hanya fokus form tambah) --}}
                        {{-- Jika Anda ingin menampilkan daftar, tambahkan tabel di sini. Contoh: --}}
                        <div class="table-responsive p-0 mt-3">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Kode</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Nama Departemen</th>
                                        <th class="text-secondary opacity-7"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($departments as $department)
                                        <tr>
                                            <td><p class="text-xs font-weight-bold mb-0 px-3">{{ $department->code }}</p></td>
                                            <td><p class="text-xs font-weight-bold mb-0">{{ $department->name }}</p></td>
                                            <td class="align-middle text-end">
                                                <button wire:click="edit({{ $department->id }})" class="btn btn-link text-secondary mb-0 p-1" data-bs-toggle="tooltip" title="Edit Departemen">
                                                    <i class="bx bx-edit text-sm"></i>
                                                </button>
                                                {{-- <button wire:click="delete({{ $department->id }})"
                                                        onclick="confirm('Apakah Anda yakin ingin menghapus departemen ini: {{ $department->code }} - {{ $department->name }}?') || event.stopImmediatePropagation()"
                                                        class="btn btn-link text-danger text-gradient p-1 mb-0" data-bs-toggle="tooltip" title="Hapus Departemen">
                                                    <i class="far fa-trash-alt text-sm"></i>
                                                </button> --}}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center py-4">Tidak ada data departemen ditemukan.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                             <div class="px-4 pt-3">
                                {{ $departments->links() }}
                            </div>
                        </div>
                         {{-- Akhir bagian daftar departemen (opsional) --}}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal untuk Form Tambah dan Edit Departemen --}}
    @if ($isModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <form wire:submit.prevent="store">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ $department_id ? 'Edit Departemen' : 'Tambah Departemen Baru' }}</h5>
                            <button type="button" class="btn-close" wire:click="closeModal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="department_code" class="form-label">Kode Departemen <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('code') is-invalid @enderror" id="department_code" wire:model.defer="code" placeholder="Contoh: IT, HRD, FIN">
                                    @error('code') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="department_name" class="form-label">Nama Departemen <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="department_name" wire:model.defer="name" placeholder="Contoh: Information Technology, Human Resources">
                                    @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="closeModal">Batal</button>
                            <button type="submit" class="btn btn-primary">
                                <span wire:loading.remove wire:target="store">{{ $department_id ? 'Simpan Perubahan' : 'Simpan Departemen' }}</span>
                                <span wire:loading wire:target="store">Menyimpan...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>

