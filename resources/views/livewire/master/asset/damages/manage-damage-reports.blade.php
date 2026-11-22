<div>
    <div class="container-fluid py-4">
        <div class="card">
            <div class="card-header pb-0">
                <div class="d-flex justify-content-between align-items-center mb-0">
                    <h5 >Manajemen Laporan Kerusakan Aset</h5>
                    <button wire:click="create()" class="btn btn-danger btn-sm">
                        <i class="fas fa-plus me-1"></i> Buat Laporan Baru
                    </button>
                </div>
                @if (session()->has('message'))
                    <div class="alert alert-success mt-3" role="alert">{{ session('message') }}</div>
                @endif
                @if (session()->has('error'))
                    <div class="alert alert-danger mt-3" role="alert">{{ session('error') }}</div>
                @endif
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <input wire:model.live.debounce.300ms="search" type="text" class="form-control" placeholder="Cari deskripsi, no aset, atau pelapor...">
                </div>
                <div class="table-responsive">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                
                                <th>Aset (No/SN)</th>
                                <th>Deskripsi Kerusakan</th>
                                <th>Tgl Lapor</th>
                                <th>Pelapor</th>
                                <th>Status Laporan</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($damageReports as $report)
                                <tr>
                                    
                                    <td>
                                        <p class="text-sm font-weight-bold mb-0">{{ $report->asset->asset_number ?? 'N/A' }}</p>
                                        <p class="text-xs text-secondary mb-0">SN: {{ $report->asset->serial_number ?? 'N/A' }}</p>
                                    </td>
                                    <td title="{{ $report->damage_description }}">{{ Str::limit($report->damage_description, 40) }}</td>
                                    <td>{{ \Carbon\Carbon::parse($report->report_date)->isoFormat('D MMM YYYY') }}</td>
                                    <td>{{ $report->reporter->name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-gradient-warning">{{ $report->damage_status }}</span>
                                    </td>
                                    <td class="text-end">
                                        @if ($report->damage_status != 'Closed' && $report->damage_status != 'Beyond Economic Repair')
                                        <button wire:click="edit({{ $report->id }})" class="btn btn-sm btn-outline-secondary mb-0">Update</button>
                                        {{-- <button wire:click="delete({{ $report->id }})" onclick="confirm('Yakin hapus laporan ini?') || event.stopImmediatePropagation()" class="btn btn-sm btn-outline-danger mb-0">Hapus</button> --}}
                                        @endif
                                        
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center py-4">Tidak ada data laporan kerusakan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $damageReports->links() }}</div>
            </div>
        </div>
    </div>

    {{-- Modal Create/Edit Laporan Kerusakan --}}
    @if ($isModalOpen)
        <div class="modal fade show d-block" tabindex="-1" aria-labelledby="exampleModalScrollableTitle" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered modal-xl ">
                <div class="modal-content">
                    <form wire:submit.prevent="store">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ $damage_report_id ? 'Edit Laporan Kerusakan' : 'Buat Laporan Kerusakan Baru' }}</h5>
                            <button type="button" class="btn-close" wire:click="closeModal"></button>
                        </div>
                        <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                            {{-- Bagian Utama --}}
                            <div class="row">
                                
                                <div class="col-md-6 mb-3">
                                    <label for="asset_id" class="form-label">Jobsite <span class="text-danger">*</span></label>
                                    <select wire:model.live="from_jobsite_id" class="form-select select2" @if($isEdit===true) disabled @endif>
                                        @if($isEdit===true)
                                            <option value="">{{ optional($assetEdit->jobsite)->name }}</option>
                                        @else
                                            <option value="">-- Pilih Jobsite --</option>
                                            @foreach ($allJobsites as $jobsite)
                                                <option value="{{ $jobsite->id }}">{{ $jobsite->name }}</option>
                                            @endforeach
                                        @endif
                                       
                                    </select>
                                    @error('asset_id') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="reported_by_user_id" class="form-label">Dilaporkan Oleh <span class="text-danger">*</span></label>
                                    <select wire:model.defer="reported_by_user_id" id="select2-reported_by_user_id" class="form-select select2 @error('reported_by_user_id') is-invalid @enderror" {{ $isEdit ? 'disabled' : '' }}>
                                        <option value="">-- Pilih Pelapor --</option>
                                        @foreach ($usersForReporter as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('reported_by_user_id') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            @if($isEdit===true)
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="input-group mb-3">
                                        <span class="input-group-text" id="basic-addon1">No Asset</span>
                                        <input type="text" class="form-control" placeholder="asset_number" aria-label="asset_number" aria-describedby="basic-addon1" value="{{  optional($assetEdit)['asset_number'] ?? '' }}" disabled>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group mb-3">
                                        <span class="input-group-text" id="basic-addon2">Serial Number</span>
                                        <input type="text" class="form-control" placeholder="serial_number" aria-label="serial_number" aria-describedby="basic-addon2" value="{{ optional($assetEdit)['serial_number'] ?? '' }}" disabled>
                                    </div>
                                </div>
                            </div>
                            
                            @else
                                <div style="max-height: 250px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">
                                    <div wire:loading wire:target="changeFromJobsiteId">Memuat aset...</div>
                                    @if($assetsForSelection)
                                    <input type="text" wire:model.live="searchAsset" class="form-control mb-3" placeholder="Cari aset...">
                                    @endif
                                    @forelse ($filteredAssets as $asset)
                                        <div class="form-check">
                                            <input class="form-check-input"
                                                type="radio"
                                                name="selected_assets"
                                                value="{{ $asset['id'] }}"
                                                wire:model="asset_id"
                                                id="mutation_asset_{{ $asset['id'] }}" checked>
                                            <label class="form-check-label" for="mutation_asset_{{ $asset['id'] }}">
                                                {{ $asset['asset_number'] }} - SN: {{ $asset['serial_number'] }}
                                            </label>
                                        </div>
                                    @empty
                                        <div class="text-muted fst-italic">Tidak ada aset ditemukan.</div>
                                    @endforelse
                                </div>
                            @endif
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="report_date" class="form-label">Tanggal Lapor/Kejadian <span class="text-danger">*</span></label>
                                    <input type="date" wire:model.defer="report_date" class="form-control @error('report_date') is-invalid @enderror" {{ $isEdit ? 'disabled' : '' }}>
                                    @error('report_date') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="damage_status" class="form-label">Status Laporan <span class="text-danger">*</span></label>
                                    <select wire:model.defer="damage_status" class="form-select @error('damage_status') is-invalid @enderror">
                                        @foreach ($damageStatuses as $statusOption)
                                            <option value="{{ $statusOption }}">{{ $statusOption }}</option>
                                        @endforeach
                                    </select>
                                    @error('damage_status') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="damage_description" class="form-label">Deskripsi Kerusakan <span class="text-danger">*</span></label>
                                <textarea wire:model.defer="damage_description" class="form-control @error('damage_description') is-invalid @enderror" rows="3"></textarea>
                                @error('damage_description') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            {{-- Bagian Detail Penanganan --}}
                            <hr>
                            <p class="mb-2"><strong>Detail Penanganan (Diisi oleh Tim Terkait):</strong></p>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="assessment_notes" class="form-label">Catatan Penilaian</label>
                                    <textarea wire:model.defer="assessment_notes" class="form-control" rows="2"></textarea>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="repair_notes" class="form-label">Catatan Perbaikan</label>
                                    <textarea wire:model.defer="repair_notes" class="form-control" rows="2"></textarea>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="repair_cost" class="form-label">Biaya Perbaikan (Rp)</label>
                                    <input type="number" step="any" wire:model.defer="repair_cost" class="form-control">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="repaired_date" class="form-label">Tanggal Selesai Perbaikan</label>
                                    <input type="date" wire:model.defer="repaired_date" class="form-control">
                                </div>
                            </div>

                            {{-- Bagian Upload Bukti --}}
                            <hr>
                            <p class="mb-2"><strong>Upload Bukti (Opsional):</strong></p>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="photo1" class="form-label">Foto Kerusakan 1</label>
                                    <input type="file" wire:model="photo1" id="photo1" class="form-control">
                                    @if ($photo1) <img src="{{ $photo1->temporaryUrl() }}" class="img-thumbnail mt-2" style="max-height: 100px;">
                                    @elseif($existingPhoto1Path) <a href="{{ Storage::url($existingPhoto1Path) }}" target="_blank"><img src="{{ Storage::url($existingPhoto1Path) }}" class="img-thumbnail mt-2" style="max-height: 100px;"></a>
                                    @endif
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="photo2" class="form-label">Foto Kerusakan 2</label>
                                    <input type="file" wire:model="photo2" id="photo2" class="form-control">
                                    @if ($photo2) <img src="{{ $photo2->temporaryUrl() }}" class="img-thumbnail mt-2" style="max-height: 100px;">
                                    @elseif($existingPhoto2Path) <a href="{{ Storage::url($existingPhoto2Path) }}" target="_blank"><img src="{{ Storage::url($existingPhoto2Path) }}" class="img-thumbnail mt-2" style="max-height: 100px;"></a>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="closeModal">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan Laporan</button>
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
    
});
</script>
@endpush
@push('scripts')
<script>
document.addEventListener('livewire:load', function () {
    initSelect2();
    Livewire.hook('message.processed', (message, component) => {
        initSelect2();
    });
});

function initSelect2() {
    if (typeof $ === 'undefined' || !$.fn.select2) {
        console.warn('Select2/jQuery belum siap, menunggu...');
        setTimeout(initSelect2, 200);
        return;
    }

    // Perbaiki: destroy hanya jika sudah init select2
    $('.select2').each(function() {
        if ($(this).hasClass("select2-hidden-accessible")) {
            $(this).select2('destroy');
        }
    });

    // Inisialisasi select2 pada semua elemen .select2 di MODAL yang terbuka saja agar tidak bocor ke page lain
    $('.modal.show .select2').select2({
        dropdownParent: $('.modal.show'),
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: function(){
            return $(this).attr('placeholder') || 'Pilih...';
        },
        allowClear: true
    });

    // Sinkronisasi Select2 <-> Livewire wire:model (.select2 id: select2-asset_id, select2-reported_by_user_id)
    $('#select2-asset_id').off('change').on('change', function (e) {
        @this.set('asset_id', $(this).val());
    });
    $('#select2-reported_by_user_id').off('change').on('change', function (e) {
        @this.set('reported_by_user_id', $(this).val());
    });

    // Pastikan value select2 sama dengan livewire (biar ga null saat update modal)
    // Di-trigger setiap initSelect2
    if(window.Livewire){
        let $assetSelect = $('#select2-asset_id');
        if($assetSelect.length) {
            let v = @this.get('asset_id');
            if (v !== $assetSelect.val()) $assetSelect.val(v).trigger('change.select2');
        }
        let $reporterSelect = $('#select2-reported_by_user_id');
        if($reporterSelect.length) {
            let v = @this.get('reported_by_user_id');
            if (v !== $reporterSelect.val()) $reporterSelect.val(v).trigger('change.select2');
        }
    }
}
</script>
@endpush

