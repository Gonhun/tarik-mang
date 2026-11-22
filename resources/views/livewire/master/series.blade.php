<div>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <div class="d-flex justify-content-between">
                            <h5 class="mb-0">Manajemen Seri Aset</h5>
                            <button wire:click="create()" class="btn btn-primary btn-sm">
                                <i class="bx bx-plus me-1"></i> Buat Seri Aset Baru
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
                            <input wire:model.live.debounce.500ms="search" type="text" class="form-control" placeholder="Cari berdasarkan nama seri, spesifikasi, tipe, atau merek...">
                        </div>
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Tipe Aset</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Merek Aset</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Material Code</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Nama/Model Seri</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Spesifikasi (Singkat)</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Tgl Dibuat</th>
                                        <th class="text-secondary opacity-7"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($assetSeriesCollection as $series)
                                        <tr>
                                            <td>
                                                <p class="text-xs font-weight-bold mb-0">{{ $series->assetType ? $series->assetType->name : 'N/A' }}</p>
                                                {{-- <p class="text-xs text-secondary mb-0">{{ $series->assetType ? $series->assetType->code : '' }}</p> --}}
                                            </td>
                                            <td>
                                                <p class="text-xs font-weight-bold mb-0">{{ $series->assetBrand ? $series->assetBrand->name : 'N/A' }}</p>
                                                {{-- <p class="text-xs text-secondary mb-0">{{ $series->assetBrand ? $series->assetBrand->code : '' }}</p> --}}
                                            </td>
                                            <td>
                                                <p class="text-xs font-weight-bold mb-0">{{ $series->material_code ?? 'N/A' }}</p>
                                                {{-- <p class="text-xs text-secondary mb-0">{{ $series->materialCode ? $series->materialCode->name : '' }}</p> --}}
                                            </td>
                                            <td><p class="text-xs font-weight-bold mb-0">{{ !empty($series->name) ? $series->name : ($series->materialCode?->name ?? '-') }}</p></td>
                                            <td><p class="text-xs font-weight-bold mb-0" title="{{!empty($series->specifications) ? $series->specifications : ($series->materialCode?->description ?? '-')}}">{{ Str::limit(!empty($series->specifications) ? $series->specifications : ($series->materialCode?->description ?? '-'), 30) }}</p></td>
                                            <td class="align-middle text-center text-sm">
                                                <span class="text-secondary text-xs font-weight-bold">{{ $series->created_at->format('d M Y') }}</span>
                                            </td>
                                            <td class="align-middle">
                                                <button wire:click="edit({{ $series->id }})" class="btn btn-link text-secondary mb-0" data-bs-toggle="tooltip" title="Edit Seri Aset">
                                                    <i class="bx bx-edit"></i>
                                                </button>
                                                <button wire:click="delete({{ $series->id }})"
                                                        onclick="confirm('Apakah Anda yakin ingin menghapus seri aset ini: {{ $series->name }}?') || event.stopImmediatePropagation()"
                                                        class="btn btn-link text-danger text-gradient px-3 mb-0" data-bs-toggle="tooltip" title="Hapus Seri Aset">
                                                    <i class="bx bx-delete"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-4">
                                                <p class="mb-0">Tidak ada data seri aset ditemukan.</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="px-4 pt-3">
                            {{ $assetSeriesCollection->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal untuk Create dan Edit --}}
    @if ($isModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered modal-lg"> {{-- Modal lebih besar --}}
                <div class="modal-content">
                    <form wire:submit.prevent="store">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ $asset_series_id ? 'Edit Seri Aset' : 'Buat Seri Aset Baru' }}</h5>
                            <button type="button" class="btn-close" wire:click="closeModal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="asset_type_id_modal" class="form-label">Tipe Aset<span class="text-danger">*</span></label>
                                    <select wire:model="asset_type_id" wire:change="assetTypeChanged" id="asset_type_id_modal" class="form-select @error('asset_type_id') is-invalid @enderror">
                                        <option value="">-- Pilih Tipe Aset --</option>
                                        @foreach ($assetTypes as $type)
                                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('asset_type_id') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="asset_brand_id_modal" class="form-label">Merek Aset <span class="text-danger">*</span></label>
                                    <select wire:model.defer="asset_brand_id" id="asset_brand_id_modal" class="form-select @error('asset_brand_id') is-invalid @enderror" @if(count($assetBrands) == 0) disabled @endif>
                                        <option value="">-- Pilih Merek Aset --</option>
                                        @if(count($assetBrands) > 0)
                                            @foreach ($assetBrands as $brand)
                                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                            @endforeach
                                        @else
                                            <option value="" disabled>Pilih Tipe Aset terlebih dahulu</option>
                                        @endif
                                    </select>
                                    @error('asset_brand_id') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                           
                                <div class="mb-3">
                                    <label for="series_name_modal" class="form-label">Nama/Model Seri <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="series_name_modal" wire:model.defer="name" placeholder="Contoh: Latitude E7470, Asus ROG">
                                    @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="mb-3">
                                    <label for="material_code" class="form-label">Material Code</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="material_code" wire:model.defer="material_code" placeholder="material code SAP">
                                    @error('material_code') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="mb-3">
                                    <label for="specifications_modal" class="form-label">Spesifikasi<span class="text-danger">*</span></label>
                                    <textarea class="form-control @error('specifications') is-invalid @enderror" id="specifications_modal" wire:model.defer="specifications" rows="4" placeholder="Masukkan detail spesifikasi aset..." required></textarea>
                                    @error('specifications') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="closeModal">Batal</button>
                            <button type="submit" class="btn btn-primary">
                                <span wire:loading.remove wire:target="store">{{ $asset_series_id ? 'Simpan Perubahan' : 'Simpan' }}</span>
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
    // Skrip untuk tooltip Bootstrap (sama seperti sebelumnya)
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