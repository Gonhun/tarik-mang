<div>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <div class="d-flex justify-content-between">
                            <h5 class="mb-0">Manajemen Tipe Aset</h5>
                            <button wire:click="create()" class="btn btn-primary btn-sm">
                                <i class="bx bx-plus me-1"></i> Buat Tipe Aset Baru
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
                        <div class="px-4 pt-3 position-relative">
                            <input wire:model.live.debounce.500ms="search" type="text" class="form-control" placeholder="Cari berdasarkan kode atau nama...">
                            <div wire:loading.delay wire:target="search" class="position-absolute top-50 end-0 translate-middle-y me-3">
                                <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                            </div>
                        </div>
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Kode</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Nama</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">BAST</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Foto</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Tanggal Dibuat</th>
                                        <th class="text-secondary opacity-7"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($assetTypes as $assetType)
                                        <tr>
                                           <td>
                                                <p class="text-xs font-weight-bold mb-0">{{ $assetType->code }}</p>
                                            </td>
                                            <td>
                                                <p class="text-xs font-weight-bold mb-0">{{ $assetType->name }}</p>
                                            </td>
                                            <td>
                                                <input class="form-check-input" type="checkbox" disabled {{ $assetType->requires_bast ? 'checked' : '' }}>
                                               
                                            </td>
                                            <td>
                                                @if ($assetType->photo_path)
                                                    <img src="{{ Storage::url($assetType->photo_path) }}" alt="{{ $assetType->name }}" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                                @else
                                                    <span class="text-xs text-secondary">N/A</span>
                                                @endif
                                            </td>
                                            <td class="align-middle text-sm">
                                                <span class="text-secondary text-xs font-weight-bold">{{ $assetType->created_at->format('d M Y H:i') }}</span>
                                            </td>
                                            <td class="align-middle">
                                                <button wire:click="edit({{ $assetType->id }})" class="btn btn-link text-secondary mb-0" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit Tipe Aset">
                                                    <i class="bx bx-edit"></i>
                                                </button>
                                                <button wire:click="delete({{ $assetType->id }})"
                                                        onclick="confirm('Apakah Anda yakin ingin menghapus tipe aset ini: {{ $assetType->code }} - {{ $assetType->name }}?') || event.stopImmediatePropagation()"
                                                        class="btn btn-link text-danger text-gradient px-3 mb-0" data-bs-toggle="tooltip" data-bs-placement="top" title="Hapus Tipe Aset">
                                                    <i class="bx bx-delete"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4">
                                                <p class="mb-0">Tidak ada data tipe aset ditemukan.</p>
                                                @if(empty($search))
                                                    <p class="mb-0">Anda bisa memulai dengan membuat tipe aset baru.</p>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="px-4 pt-3">
                            {{ $assetTypes->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal untuk Create dan Edit --}}
    @if ($isModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form wire:submit.prevent="store">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ $asset_type_id ? 'Edit Tipe Aset' : 'Buat Tipe Aset Baru' }}</h5>
                            <button type="button" class="btn-close" wire:click="closeModal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="col-md-6 mb-3">
                                <label for="asset_type_code" class="form-label">Kode Tipe Aset <span class="text-danger">*</span></label>
                                <input type="text" wire:model.defer="code" class="form-control @error('code') is-invalid @enderror" id="asset_type_code" placeholder="Contoh: LP, PC, VH">
                                @error('code') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                            <div class="mb-3">
                                <label for="name" class="form-label">Nama Tipe Aset</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" wire:model.defer="name" placeholder="Contoh: Elektronik, Kendaraan, Bangunan">
                                @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input @error('requires_bast') is-invalid @enderror" type="checkbox" id="requires_bast" wire:model.defer="requires_bast" value="1" {{ old('requires_bast', $requires_bast) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="requires_bast">
                                        Perlu BAST
                                    </label>
                                </div>
                                @error('requires_bast') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="mb-3">
                                <label for="photo" class="form-label">Foto Tipe Aset</label>
                                <input type="file" class="form-control @error('photo') is-invalid @enderror" id="photo" wire:model="photo" accept="image/*">
                                <div wire:loading wire:target="photo" class="mt-1">Uploading...</div>
                                @error('photo') <span class="text-danger">{{ $message }}</span> @enderror
                            
                                @if ($photo) {{-- Preview untuk foto baru yang akan diupload --}}
                                @if ($photo && method_exists($photo, 'getMimeType'))
                                    @if (Str::startsWith($photo->getMimeType(), 'image/'))
                                        <div class="mb-3">
                                            <p class="mb-1">Preview Foto Baru:</p>
                                            <img src="{{ $photo->temporaryUrl() }}" alt="Preview Foto Baru" class="img-thumbnail" style="max-width: 150px; max-height: 150px;">
                                        </div>
                                    @endif
                                @endif
                                @elseif ($existing_photo_path) {{-- Tampilkan foto yang sudah ada saat edit --}}
                                    <div class="mt-2">
                                        <p class="mb-1">Foto Saat Ini:</p>
                                        <img src="{{ Storage::url($existing_photo_path) }}" alt="Foto Saat Ini" class="img-thumbnail" style="max-width: 150px; max-height: 150px;">
                                        {{-- Tambahkan tombol untuk hapus foto jika diinginkan --}}
                                        {{-- <button type="button" wire:click="removePhoto" class="btn btn-sm btn-outline-danger mt-1">Hapus Foto</button> --}}
                                    </div>
                                @endif
                            </div>
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="closeModal">Batal</button>
                            <button type="submit" class="btn btn-primary">
                                <span wire:loading.remove wire:target="store">{{ $asset_type_id ? 'Simpan Perubahan' : 'Simpan' }}</span>
                                <span wire:loading wire:target="store">Menyimpan...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
    // Untuk inisialisasi tooltip Bootstrap jika Anda menggunakannya
    document.addEventListener('livewire:load', function () {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    });
    // Re-initialize tooltips after Livewire updates the DOM
    Livewire.hook('message.processed', (message, component) => {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            // Hapus tooltip lama jika ada untuk menghindari duplikasi
            var A = bootstrap.Tooltip.getInstance(tooltipTriggerEl);
            if(A) A.dispose();
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    });
</script>
@endpush