<div>
    <div class="container-fluid py-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Penerimaan & QC Aset (dari PO)</h5>
                <p class="text-sm mb-0">Daftar item aset yang telah dipesan dan menunggu kedatangan serta pengecekan kualitas.</p>
            </div>
            <div class="card-body">
                @if (session()->has('message')) <div class="alert alert-success">{{ session('message') }}</div> @endif
                
                <div class="table-responsive">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th>No. Pengajuan</th>
                                <th>Item yang Dipesan</th>
                                <th>No. Asset</th>
                                <th>No. PO</th>
                                <th>No. GR</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($itemsWaitingForQc as $item)
                                <tr>
                                    <td>{{ $item->assetRequest->request_number ?? 'N/A' }}</td>
                                    <td>
                                        <p class="fw-bold mb-0">{{ $item->assetSeries->name ?? 'N/A' }}</p>
                                        <small class="text-muted">{{ $item->assetType->name ?? '' }} / {{ $item->assetBrand->name ?? '' }}</small>
                                    </td>
                                    <td>{{ $item->accounting_number }}</td>
                                    <td>{{ $item->po_number }}</td>
                                    <td>{{ $item->gr_number }}</td>
                                    <td class="text-end">
                                        <button wire:click="openQcModal({{ $item->id }})" class="btn btn-primary btn-sm mb-0">
                                            Proses QC
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center py-4">Tidak ada item yang menunggu proses QC saat ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $itemsWaitingForQc->links() }}</div>
            </div>
        </div>
    </div>

    {{-- Modal untuk Form QC --}}
    @if ($isModalOpen && $itemToQc)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Formulir QC & Pendaftaran Aset Baru</h5>
                        <button type="button" class="btn-close" wire:click="closeModal"></button>
                    </div>
                    <div class="modal-body">
                        @if (session()->has('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif
                        <div class="alert alert-secondary">
                            <strong>Permintaan Awal:</strong> {{ $itemToQc->assetType->name ?? '' }} - {{ $itemToQc->assetBrand->name ?? '' }} - {{ $itemToQc->assetSeries->name ?? '' }}
                        </div>

                        {{-- Bagian untuk Lulus QC --}}
                        <div class="p-3 border rounded mb-3">
                            <h6 class="text-success"><i class="bx bx-check-circle me-2"></i>Opsi 1: Aset Lulus QC</h6>
                            <p class="small text-muted">Isi form ini jika aset yang diterima sesuai dan siap didaftarkan.</p>
                            
                            <div class="row">
                                <div class="col-md-2 mb-3">
                                    <label class="form-label">Entitas</label>
                                    <select wire:model.live="qc_entity_id" class="form-select @error('qc_entity_id') is-invalid @enderror">
                                        @foreach($allEntities as $entity) <option value="{{ $entity->id }}">{{ $entity->name }}</option> @endforeach
                                    </select>
                                    @error('qc_entity_id') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label class="form-label">Tipe Aset</label>
                                    <select wire:model.live="qc_asset_type_id" class="form-select @error('qc_asset_type_id') is-invalid @enderror">
                                        @foreach($allTypes as $type) <option value="{{ $type->id }}">{{ $type->name }}</option> @endforeach
                                    </select>
                                    @error('qc_asset_type_id') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Merek Aset</label>
                                    <select wire:model.live="qc_asset_brand_id" class="form-select @error('qc_asset_brand_id') is-invalid @enderror" @if(count($allBrands) == 0) disabled @endif>
                                        <option value="">-- Pilih Merek --</option>
                                        @foreach($allBrands as $brand) <option value="{{ $brand->id }}">{{ $brand->name }}</option> @endforeach
                                    </select>
                                    @error('qc_asset_brand_id') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Seri Aset</label>
                                    <div class="input-group">
                                        <select wire:model.live="qc_asset_series_id" class="form-select @error('qc_asset_series_id') is-invalid @enderror" @if(count($allSeries) == 0) disabled @endif>
                                            <option value="">-- Pilih Seri --</option>
                                            @foreach($allSeries as $series) <option value="{{ $series->id }}">{{ $series->name }}</option> @endforeach
                                        </select>
                                        <button wire:click.prevent="toggleAddNewSeries" type="button" class="btn btn-outline-secondary" title="Tambah Seri Baru"><i class="fas fa-plus"></i></button>
                                    </div>
                                    @error('qc_asset_series_id') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            @if($isAddingNewSeries)
                            <div class="p-3 border rounded bg-light mb-3" wire:transition>
                                <input type="text" wire:model.defer="newSeriesName" class="form-control mb-2" placeholder="Nama Seri Baru *">
                                <button wire:click.prevent="saveNewSeries" class="btn btn-sm btn-success">Simpan Seri</button>
                            </div>
                            @endif
                            <div class="mb-3">
                                <label class="form-label">Serial Number Fisik <span class="text-danger">*</span></label>
                                <input type="text" wire:model.defer="qc_serial_number" class="form-control @error('qc_serial_number') is-invalid @enderror">
                            </div>
                            @if($itemToQc->asset_series_id != $qc_asset_series_id)
                            <div class="mb-3 p-3 border border-warning rounded" wire:transition>
                                <label class="form-label fw-bold text-warning">Alasan Perubahan Spesifikasi <span class="text-danger">*</span></label>
                                <textarea wire:model.defer="qc_change_reason" class="form-control @error('qc_change_reason') is-invalid @enderror" rows="2"></textarea>
                            </div>
                            @endif
                        </div>

                        {{-- Bagian untuk Gagal QC --}}
                        <div class="p-3 border rounded">
                             <h6 class="text-danger"><i class="fas fa-times-circle me-2"></i>Opsi 2: Aset Gagal QC (Return)</h6>
                             <p class="small text-muted">Isi form ini jika aset yang diterima tidak sesuai atau rusak.</p>
                             <div class="mb-3">
                                <label for="qc_failure_reason" class="form-label">Alasan Gagal QC / Return <span class="text-danger">*</span></label>
                                <textarea wire:model.defer="qc_failure_reason" class="form-control @error('qc_failure_reason') is-invalid @enderror" id="qc_failure_reason" rows="3" placeholder="Jelaskan mengapa aset ini tidak lolos QC..."></textarea>
                                @error('qc_failure_reason') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-danger" wire:click="failQcItem">
                            <i class="bx bx-bxs-time me-1"></i> Submit Gagal QC (Return)
                        </button>
                        <div>
                            <button type="button" class="btn btn-secondary" wire:click="closeModal">Batal</button>
                            <button type="button" class="btn btn-success" wire:click="passQcItem">
                                <i class="bx bx-check-circle me-1"></i> Submit Lulus QC & Daftarkan Aset
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($isModalSn)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                <div class="modal-content justify-content-center">
                    <div class="modal-header">
                        <h5 class="modal-title">Asset Tag</h5>
                        <button type="button" class="btn-close" wire:click="closeModalSn"></button>
                    </div>
                    <div class="modal-body">
                        <iframe src="{{ route('assets.print-single-label', ['asset' => $assetId]) }}" frameborder="1" width="750" height="250"></iframe>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <a href="{{ route('assets.print-single-label', ['asset' => $assetId]) }}" 
                                                    target="_blank"  class="btn btn-danger">
                            <i class="bx bx-bxs-printer me-1"></i> Print
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>