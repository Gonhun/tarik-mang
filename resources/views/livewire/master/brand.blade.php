<div>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <div class="d-flex justify-content-between">
                            <h5 class="mb-0">Manajemen Merek Aset</h5>
                            <button wire:click="create()" class="btn btn-primary btn-sm">
                                <i class="bx bx-plus me-1"></i> Buat Merek Aset Baru
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
                            <input wire:model.live.debounce.500ms="search" type="text" class="form-control" placeholder="Cari berdasarkan kode atau nama...">
                            <div wire:loading.delay wire:target="search" class="position-absolute top-50 end-0 translate-middle-y me-3">
                                <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                            </div>
                        </div>
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Tipe Aset</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Nama Merek</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Logo</th> {{-- Atau Foto --}}
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Tanggal Dibuat</th>
                                        <th class="text-secondary opacity-7"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($assetBrands as $brand)
                                        <tr>
                                            <td>
                                                <p class="text-xs font-weight-bold mb-0">{{ $brand->assetType ? $brand->assetType->name : 'N/A' }}</p>
                                               
                                            </td>
                                            <td>
                                                <p class="text-xs font-weight-bold mb-0">{{ $brand->name }}</p>
                                            </td>
                                            <td>
                                                @if ($brand->photo_path) {{-- Menggunakan accessor photo_url --}}
                                                    <img src="{{ asset('storage/'.$brand->photo_path) }}" alt="{{ $brand->name }}" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: contain;">
                                                @else
                                                    <span class="text-xs text-secondary">N/A</span>
                                                @endif
                                            </td>
                                            <td class="align-middle text-sm">
                                                <span class="text-secondary text-xs font-weight-bold">{{ $brand->created_at->format('d M Y H:i') }}</span>
                                            </td>
                                            
                                            <td class="align-middle">
                                                <button wire:click="edit({{ $brand->id }})" class="btn btn-link text-secondary mb-0" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit Merek Aset">
                                                    <i class="bx bx-edit"></i>
                                                </button>
                                                <button wire:click="delete({{ $brand->id }})"
                                                        onclick="confirm('Apakah Anda yakin ingin menghapus merek aset ini: {{ $brand->code }} - {{ $brand->name }}?') || event.stopImmediatePropagation()"
                                                        class="btn btn-link text-danger text-gradient px-3 mb-0" data-bs-toggle="tooltip" data-bs-placement="top" title="Hapus Merek Aset">
                                                    <i class="bx bx-delete"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4">
                                                <p class="mb-0">Tidak ada data merek aset ditemukan.</p>
                                                @if(empty($search))
                                                    <p class="mb-0">Anda bisa memulai dengan membuat merek aset baru.</p>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="px-4 pt-3">
                            {{ $assetBrands->links() }}
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
                            <h5 class="modal-title">{{ $asset_brand_id ? 'Edit Merek Aset' : 'Buat Merek Aset Baru' }}</h5>
                            <button type="button" class="btn-close" wire:click="closeModal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="asset_type_id" class="form-label">Tipe Aset <span class="text-danger">*</span></label>
                                <select wire:model.defer="asset_type_id" id="asset_type_id" class="form-select @error('asset_type_id') is-invalid @enderror">
                                    <option value="">-- Pilih Tipe Aset --</option>
                                    @foreach ($assetTypes as $type)
                                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                                    @endforeach
                                </select>
                                @error('asset_type_id') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="mb-3">
                                <label for="brand_name" class="form-label">Nama Merek Aset <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="brand_name" wire:model.defer="name" placeholder="Contoh: Dell Latitude, Honda CRV, Toyota Avanza">
                                @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="mb-3">
                                <label for="brand_photo" class="form-label">Foto/Logo Merek</label>
                                <input type="file" class="form-control @error('photo') is-invalid @enderror" id="brand_photo" wire:model="photo" accept="image/*">
                                <div wire:loading wire:target="photo" class="mt-1 text-primary">Uploading...</div>
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
                                        {{-- Anda bisa menambahkan tombol untuk menghapus foto yang sudah ada di sini jika perlu --}}
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="closeModal">Batal</button>
                            <button type="submit" class="btn btn-primary">
                                <span wire:loading.remove wire:target="store">{{ $asset_brand_id ? 'Simpan Perubahan' : 'Simpan' }}</span>
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
    document.addEventListener('livewire:load', function () {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    });
    Livewire.hook('message.processed', (message, component) => {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            var A = bootstrap.Tooltip.getInstance(tooltipTriggerEl);
            if(A) A.dispose();
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    });
</script>
@endpush