<div>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="page-header">
            <div><h4 class="mb-1">Jobsite</h4></div>
            <div class="d-flex flex-wrap gap-2"><button wire:click="create()" class="btn btn-primary btn-sm">
                                <i class="bx bx-plus me-1"></i> Tambah Jobsite Baru
                            </button></div>
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
        <div class="card">
            <div class="card-body px-0 pt-0 pb-2">
                        {{-- Bagian untuk menampilkan daftar jobsite (opsional jika hanya fokus form tambah) --}}
                        {{-- Jika Anda ingin menampilkan daftar, tambahkan tabel di sini --}}
                        {{-- Contoh: --}}
                        <div class="px-4 pt-3"><div class="input-group input-group-merge" style="max-width: 380px"><span class="input-group-text"><i class="bx bx-search"></i></span><input wire:model.live.debounce.500ms="search" type="search" class="form-control" placeholder="Cari jobsite..."></div></div>
                        <div class="table-responsive p-0 mt-3">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Kode</th>
                                        <th>Nama Jobsite</th>
                                        <th>Lokasi</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($jobsites as $jobsite)
                                        <tr>
                                            <td>{{ $jobsite->code }}</td>
                                            <td>{{ $jobsite->name }}</td>
                                            <td>{{ Str::limit($jobsite->location, 70) }}</td>
                                            <td class="align-middle text-end">
                                                <button wire:click="edit({{ $jobsite->id }})" class="btn btn-link text-secondary mb-0 p-1" data-bs-toggle="tooltip" title="Edit Jobsite">
                                                    <i class="bx bx-edit text-sm"></i>
                                                </button>
                                                {{-- <button wire:click="delete({{ $jobsite->id }})"
                                                        wire:confirm="Apakah Anda yakin ingin menghapus jobsite ini: {{ $jobsite->code }} - {{ $jobsite->name }}?"
                                                        class="btn btn-link text-danger text-gradient p-1 mb-0" data-bs-toggle="tooltip" title="Hapus Jobsite">
                                                    <i class="bx bx-trash text-sm"></i>
                                                </button> --}}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center py-4">Tidak ada data jobsite ditemukan.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                            <div class="px-4 pt-3">
                                {{ $jobsites->links() }}
                            </div>
                        </div>
                        {{-- Akhir bagian daftar jobsite (opsional) --}}

                        @if($jobsites->isEmpty() && empty($search))
                            <div class="text-center p-4">
                                <p>Belum ada data jobsite. Silakan tambahkan jobsite baru.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal untuk Form Tambah dan Edit Jobsite --}}
    @if ($isModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered modal-lg"> {{-- Modal lebih besar --}}
                <div class="modal-content">
                    <form wire:submit.prevent="store">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ $jobsite_id ? 'Edit Jobsite' : 'Tambah Jobsite Baru' }}</h5>
                            <button type="button" class="btn-close" wire:click="closeModal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="jobsite_code" class="form-label">Kode Jobsite <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('code') is-invalid @enderror" id="jobsite_code" wire:model.defer="code" placeholder="Contoh: JKT, SBY, BPN" @if($isEdit) readonly @endif>
                                    @error('code') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="jobsite_name" class="form-label">Nama Jobsite <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="jobsite_name" wire:model.defer="name" placeholder="Contoh: Kantor Pusat Jakarta, Site Balikpapan">
                                    @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="jobsite_location" class="form-label">Lokasi/Alamat</label>
                                <textarea class="form-control @error('location') is-invalid @enderror" id="jobsite_location" wire:model.defer="location" rows="3" placeholder="Masukkan alamat lengkap atau detail lokasi jobsite..."></textarea>
                                @error('location') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="closeModal">Batal</button>
                            <button type="submit" class="btn btn-primary">
                                <span wire:loading.remove wire:target="store">{{ $jobsite_id ? 'Simpan Perubahan' : 'Simpan Jobsite' }}</span>
                                <span wire:loading wire:target="store">Menyimpan...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>

