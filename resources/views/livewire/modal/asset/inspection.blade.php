<div>
    {{-- Indikator saat modal sedang dimuat --}}
    <div wire:loading.flex wire:target="loadTransaction" class="position-fixed top-0 start-0 w-100 h-100 align-items-center justify-content-center" style="background: rgba(0,0,0,.25); z-index: 1060;">
        <div class="bg-white rounded shadow px-4 py-3 d-flex align-items-center gap-2">
            <span class="spinner-border spinner-border-sm text-primary" role="status"></span> Memuat...
        </div>
    </div>

    @if ($isModalOpen)
        @php
            $outcomes = [
                'good' => ['Kondisi baik', 'Kembali ke stok (Free)', 'bx-check-circle', 'success'],
                'repair' => ['Perlu perbaikan', 'Status Repair + laporan kerusakan', 'bx-wrench', 'warning'],
                'disposed' => ['Rusak berat', 'Diajukan disposal (perlu approval)', 'bx-trash', 'danger'],
            ];
            $notesRequired = in_array($inspection_outcome, ['repair', 'disposed'], true);
        @endphp
        <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="inspectionTitle" style="background-color: rgba(0,0,0,0.5);">
            {{-- Layar penuh di HP, lebar normal di desktop --}}
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg modal-fullscreen-sm-down">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="min-w-0">
                            <h5 class="modal-title mb-0" id="inspectionTitle">Inspeksi aset kembali</h5>
                            @if ($transactionToInspect)
                                <small class="text-muted">{{ $transactionToInspect->transaction_number }}</small>
                            @endif
                        </div>
                        <button type="button" class="btn-close" wire:click="closeModal" aria-label="Tutup"></button>
                    </div>

                    <div class="modal-body">
                        @if ($assetToInspect && $transactionToInspect)
                            {{-- Ringkasan aset --}}
                            <div class="border rounded p-3 mb-4">
                                <div class="row g-2 small">
                                    <div class="col-12 col-sm-6">
                                        <span class="text-muted d-block">No. aset</span>
                                        <span class="fw-semibold">{{ $assetToInspect->asset_number }}</span>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <span class="text-muted d-block">Serial number</span>
                                        <span class="fw-semibold text-break">{{ $assetToInspect->serial_number ?: '-' }}</span>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <span class="text-muted d-block">Barang</span>
                                        <span>{{ $assetToInspect->assetType->name ?? '-' }} · {{ $assetToInspect->assetBrand->name ?? '-' }} {{ $assetToInspect->assetSeries->name ?? '' }}</span>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <span class="text-muted d-block">Dikembalikan oleh</span>
                                        <span>{{ $transactionToInspect->fromUser->name ?? '-' }} · {{ \Carbon\Carbon::parse($transactionToInspect->transaction_date)->locale('id')->isoFormat('D MMM YYYY') }}</span>
                                    </div>
                                    <div class="col-12">
                                        <span class="text-muted d-block">Catatan pengembali</span>
                                        <em class="text-break">{{ $transactionToInspect->items->first()->condition_notes ?? 'Tidak ada catatan.' }}</em>
                                    </div>
                                </div>
                            </div>

                            {{-- Pilihan hasil: kartu besar yang mudah diketuk di HP --}}
                            <fieldset class="mb-4">
                                <legend class="form-label fs-6 mb-2">Hasil inspeksi <span class="text-danger">*</span></legend>
                                <div class="row g-2">
                                    @foreach ($outcomes as $value => [$label, $hint, $icon, $color])
                                        <div class="col-12 col-md-4">
                                            <input type="radio" class="btn-check" name="inspection_outcome" id="outcome_{{ $value }}" value="{{ $value }}" wire:model.live="inspection_outcome" autocomplete="off">
                                            <label for="outcome_{{ $value }}" class="btn btn-outline-{{ $color }} w-100 h-100 text-start d-flex align-items-start gap-2 p-3">
                                                <i class="bx {{ $icon }} fs-4 flex-shrink-0"></i>
                                                <span>
                                                    <span class="d-block fw-semibold">{{ $label }}</span>
                                                    <small class="d-block fw-normal">{{ $hint }}</small>
                                                </span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                @error('inspection_outcome') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
                                @if ($disposalWarning)
                                    <div class="alert alert-warning small d-flex gap-2 mt-3 mb-0" role="alert">
                                        <i class="bx bx-error fs-5 flex-shrink-0"></i>
                                        <span><strong>Pengajuan disposal tidak bisa dibuat.</strong> {{ $disposalWarning }} Pilih hasil lain atau atur workflow dulu.</span>
                                    </div>
                                @endif
                            </fieldset>

                            <div>
                                <label for="inspection_notes" class="form-label">
                                    Catatan hasil inspeksi
                                    @if ($notesRequired)
                                        <span class="text-danger">*</span>
                                    @else
                                        <span class="text-muted fw-normal">(opsional)</span>
                                    @endif
                                </label>
                                <textarea wire:model="inspection_notes" id="inspection_notes" rows="3"
                                    class="form-control @error('inspection_notes') is-invalid @enderror"
                                    placeholder="{{ $notesRequired ? 'Jelaskan kerusakan yang ditemukan (minimal 10 karakter)...' : 'Boleh dikosongkan jika aset dalam kondisi baik.' }}"></textarea>
                                @error('inspection_notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        @else
                            <div class="text-center p-4">
                                <span class="spinner-border" role="status" aria-hidden="true"></span>
                                <p class="mt-2 mb-0">Memuat data...</p>
                            </div>
                        @endif
                    </div>

                    <div class="modal-footer d-grid gap-2 d-sm-flex justify-content-sm-end">
                        <button type="button" class="btn btn-outline-secondary" wire:click="closeModal">Batal</button>
                        <button type="button" wire:click="submitInspection" class="btn btn-primary" wire:loading.attr="disabled" wire:target="submitInspection" @if (!$assetToInspect) disabled @endif>
                            <span wire:loading.remove wire:target="submitInspection"><i class="bx bx-save me-1"></i>Simpan hasil inspeksi</span>
                            <span wire:loading wire:target="submitInspection"><span class="spinner-border spinner-border-sm me-1" role="status"></span>Menyimpan...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
