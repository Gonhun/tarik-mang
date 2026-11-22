<div class="container-fluid py-4">
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Pilih Jabatan untuk Kelola Standar Aset</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-5">
                    <select wire:model="selectedDepartementId" wire:change="SelectedDepartementId($event.target.value)" class="form-select">
                        <option value="">-- Silakan Pilih Jabatan --</option>
                        @foreach ($allDepartements as $pos)
                            <option value="{{ $pos->id }}">{{ $pos->name }} ({{ $pos->code ?? '' }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <select wire:model="selectedPositionId" wire:change="SelectedPositionId($event.target.value)" class="form-select">
                        <option value="">-- Silakan Pilih Jabatan --</option>
                        @foreach ($allPositions as $pos)
                            <option value="{{ $pos->id }}">{{ $pos->name }}</option>
                        @endforeach
                    </select>
                </div>
                 <div class="col-md-4 align-self-center">
                    <div wire:loading wire:target="selectedPositionId, loadPositionStandards,selectedDepartementId">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        Memuat data jabatan...
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($position && $position->id) {{-- Hanya tampilkan jika jabatan sudah dipilih --}}
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        Standar Aset Bawaan untuk Jabatan: 
                        <strong>{{ $position->name }} </strong>
                    </h5>
                    <button wire:click="create()" class="btn btn-primary btn-sm">
                        <i class="bx bx-plus me-1"></i> Tambah Standar Aset
                    </button>
                </div>
                @if (session()->has('message')) <div class="alert alert-success mt-2 py-2 px-3">{{ session('message') }}</div> @endif
                @if (session()->has('error')) <div class="alert alert-danger mt-2 py-2 px-3">{{ session('error') }}</div> @endif
            </div>

            <div class="card-body">
                @if($position->assetStandards->isEmpty())
                    <p class="text-muted text-center">Belum ada standar aset yang ditentukan untuk jabatan ini.</p>
                @else
                    <div class="table-responsive">
                        <table class="table align-items-center mb-0">
                            {{-- ... (struktur tabel standar aset seperti contoh Anda sebelumnya) ... --}}
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Tipe Aset</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Merek</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Seri</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Jumlah</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Catatan Spek</th>
                                    <th class="text-secondary opacity-7"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($position->assetStandards as $standard)
                                    <tr>
                                        <td>{{ $standard->assetType->name ?? 'N/A' }}</td>
                                        <td>{{ $standard->assetBrand->name ?? '-' }}</td>
                                        <td>{{ !empty($standard->assetSeries->name) ? $standard->assetSeries->name  : ($standard->assetSeries->materialCode?->name ?? '-') }}</td>
                                        <td class="text-center">{{ $standard->quantity }}</td>
                                        <td title="{{$standard->specifications_notes}}">{{ Str::limit(!empty($standard->specifications_notes) ? $standard->specifications_notes  : ($standard->assetSeries->materialCode?->description ?? '-'), 50) }}</td>
                                        <td class="text-end">
                                            <button wire:click="edit({{ $standard->id }})" class="btn btn-link text-secondary p-1 mb-0" title="Edit">
                                                <i class="bx bx-edit"></i>
                                            </button>
                                            <button wire:click="delete({{ $standard->id }})" 
                                                    onclick="confirm('Yakin hapus standar aset ini?') || event.stopImmediatePropagation()"
                                                    class="btn btn-link text-danger p-1 mb-0" title="Hapus">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        {{-- Pagination jika standar aset dipaginasi --}}
                        {{-- @if ($position->assetStandards() instanceof \Illuminate\Pagination\LengthAwarePaginator)
                             {{ $position->assetStandards()->links() }}
                        @endif --}}
                    </div>
                @endif
            </div>
        </div>

        {{-- Modal untuk Form Tambah/Edit Standar Aset (kode modal tetap sama seperti sebelumnya) --}}
        @if ($isModalOpen && $position && $position->id)
            <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <form wire:submit.prevent="store">
                            <div class="modal-header">
                                <h5 class="modal-title">{{ $standard_id ? 'Edit Standar Aset' : 'Tambah Standar Aset Baru' }} untuk Jabatan: {{ $position->name }}</h5>
                                <button type="button" class="btn-close" wire:click="closeModal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                {{-- Tipe Aset (Wajib) --}}
                                <div class="mb-3">
                                    <label for="asset_type_id_standard" class="form-label">Tipe Aset <span class="text-danger">*</span></label>
                                    <select wire:change="changeAssetTypeId($event.target.value)" id="asset_type_id_standard" class="form-select @error('asset_type_id') is-invalid @enderror">
                                        <option value="">-- Pilih Tipe Aset --</option>
                                        @foreach ($assetTypes as $type)
                                            <option value="{{ $type->id }}">{{ $type->name }} ({{ $type->code }})</option>
                                        @endforeach
                                    </select>
                                    @error('asset_type_id') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                {{-- Merek Aset (Opsional, tergantung Tipe Aset) --}}
                                <div class="mb-3">
                                    <label for="asset_brand_id_standard" class="form-label">Merek Aset <span class="text-danger">*</span></label>
                                    <select wire:change="changeAssetBrandId($event.target.value)" id="asset_brand_id_standard" class="form-select @error('asset_brand_id') is-invalid @enderror" @if(count($assetBrands) == 0 && !$asset_type_id) disabled @endif>
                                        <option value="">-- Pilih Merek Aset (Jika Ada) --</option>
                                        @if(count($assetBrands) > 0)
                                            @foreach ($assetBrands as $brand)
                                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                            @endforeach
                                        @elseif($asset_type_id)
                                            <option value="" disabled>Tidak ada merek untuk tipe aset ini</option>
                                        @else
                                            <option value="" disabled>Pilih Tipe Aset terlebih dahulu</option>
                                        @endif
                                    </select>
                                    @error('asset_brand_id') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                {{-- Seri Aset (Opsional, tergantung Merek Aset) --}}
                                <div class="mb-3">
                                    <label for="asset_series_id_standard" class="form-label">Seri Aset <span class="text-danger">*</span></label>
                                    <select wire:model.defer="asset_series_id" wire:change="changeAssetSeriesId($event.target.value)" id="asset_series_id_standard" class="form-select @error('asset_series_id') is-invalid @enderror" @if(count($assetSeries) == 0 && !$asset_brand_id) disabled @endif>
                                        <option value="">-- Pilih Seri Aset (Jika Ada) --</option>
                                        @if(count($assetSeries) > 0)
                                            @foreach ($assetSeries as $series)
                                                <option value="{{ $series->id }}">{{ !empty($series->name) ? $series->name : ($series->materialCode?->name ?? '-')}}</option>
                                            @endforeach
                                        @elseif($asset_brand_id)
                                            <option value="" disabled>Tidak ada seri untuk merek ini</option>
                                        @else
                                            <option value="" disabled>Pilih Merek Aset terlebih dahulu</option>
                                        @endif
                                    </select>
                                    @error('asset_series_id') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="quantity_standard" class="form-label">Jumlah Standar <span class="text-danger">*</span></label>
                                        <input type="number" wire:model.defer="quantity" id="quantity_standard" class="form-control @error('quantity') is-invalid @enderror" min="1" disabled>
                                        @error('quantity') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="specifications_notes_standard" class="form-label">Catatan Spesifikasi Tambahan (Opsional)</label>
                                    <textarea wire:model.defer="specifications_notes" id="specifications_notes_standard" class="form-control @error('specifications_notes') is-invalid @enderror" rows="3" placeholder="Contoh: Minimal RAM 16GB, Warna Hitam, dll."></textarea>
                                    @error('specifications_notes') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" wire:click="closeModal">Batal</button>
                                <button type="submit" class="btn btn-primary">
                                    <span wire:loading.remove wire:target="store">{{ $standard_id ? 'Simpan Perubahan' : 'Tambah Standar' }}</span>
                                    <span wire:loading wire:target="store">Menyimpan...</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>

@push('scripts')<script>
    // Skrip untuk tooltip Bootstrap (opsional, jika Anda menggunakan data-bs-toggle="tooltip")
    document.addEventListener('livewire:load', function () {
      var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
      var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
          return new bootstrap.Tooltip(tooltipTriggerEl)
      })
  });
  Livewire.hook('message.processed', (message, component) => { // Re-initialize after Livewire update
      var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
      tooltipTriggerList.forEach(function (tooltipTriggerEl) {
          var existingTooltip = bootstrap.Tooltip.getInstance(tooltipTriggerEl);
          if (existingTooltip) {
              existingTooltip.dispose();
          }
          new bootstrap.Tooltip(tooltipTriggerEl);
      });
  });
</script>{{-- ... (Skrip tooltip Bootstrap Anda) ... --}}
@endpush