<div>
    <div wire:loading.flex wire:target="open" class="position-fixed top-0 start-0 w-100 h-100 align-items-center justify-content-center" style="background: rgba(0,0,0,.25); z-index: 1060;">
        <div class="bg-white rounded shadow px-4 py-3 d-flex align-items-center gap-2">
            <span class="spinner-border spinner-border-sm text-primary" role="status"></span> Memuat...
        </div>
    </div>

    @if ($isModalOpen && $asset)
        @php $isLost = $mode === 'LOST'; @endphp
        <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="disposalTitle" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg modal-fullscreen-sm-down">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="min-w-0">
                            <h5 class="modal-title mb-0" id="disposalTitle">{{ $isLost ? 'Laporkan aset hilang' : 'Ajukan penghapusan (disposal) aset' }}</h5>
                            <small class="text-muted">Perlu persetujuan sesuai alur approval</small>
                        </div>
                        <button type="button" class="btn-close" wire:click="closeModal" aria-label="Tutup"></button>
                    </div>

                    <div class="modal-body">
                        <div class="border rounded p-3 mb-4">
                            <div class="row g-2 small">
                                <div class="col-12 col-sm-6">
                                    <span class="text-muted d-block">No. aset</span>
                                    <span class="fw-semibold">{{ $asset->asset_number }}</span>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <span class="text-muted d-block">Status saat ini</span>
                                    <span class="fw-semibold">{{ $asset->status }}</span>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <span class="text-muted d-block">Barang</span>
                                    <span>{{ $asset->assetType->name ?? '-' }} · {{ $asset->assetBrand->name ?? '-' }} {{ $asset->assetSeries->name ?? '' }}</span>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <span class="text-muted d-block">Pemegang</span>
                                    <span>{{ $asset->holder->name ?? 'Tidak ada' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-{{ $isLost ? 'warning' : 'info' }} small d-flex gap-2" role="note">
                            <i class="bx bx-info-circle fs-5 flex-shrink-0"></i>
                            <span>
                                @if ($isLost)
                                    Setelah dikirim, status aset menjadi <strong>Missing</strong>. Jika disetujui, aset dihapusbukukan (<strong>Disposed</strong>).
                                    Jika ditolak, status dan pemegang aset dikembalikan seperti semula.
                                @else
                                    Setelah dikirim, status aset menjadi <strong>Pre Disposed</strong>. Jika disetujui, aset menjadi <strong>Disposed</strong>.
                                    Jika ditolak, status aset dikembalikan seperti semula.
                                @endif
                            </span>
                        </div>

                        @if (!$isLost)
                            <div class="mb-3">
                                <label for="disposal_reason" class="form-label">Alasan <span class="text-danger">*</span></label>
                                <select id="disposal_reason" wire:model="reason" class="form-select @error('reason') is-invalid @enderror">
                                    <option value="DAMAGED">Rusak berat / tidak ekonomis diperbaiki</option>
                                    <option value="OTHER">Lainnya (usang, tidak terpakai, dll.)</option>
                                </select>
                                @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        @else
                            <div class="row g-3 mb-3">
                                <div class="col-12 col-sm-5">
                                    <label for="incident_date" class="form-label">Tanggal kejadian <span class="text-danger">*</span></label>
                                    <input type="date" id="incident_date" wire:model="incident_date" max="{{ now()->format('Y-m-d') }}" class="form-control @error('incident_date') is-invalid @enderror">
                                    @error('incident_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-12 col-sm-7">
                                    <label for="incident_location" class="form-label">Lokasi terakhir / kejadian <span class="text-danger">*</span></label>
                                    <input type="text" id="incident_location" wire:model="incident_location" class="form-control @error('incident_location') is-invalid @enderror" placeholder="Mis. mess karyawan blok B, kendaraan operasional...">
                                    @error('incident_location') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        @endif

                        <div class="mb-3">
                            <label for="disposal_notes" class="form-label">{{ $isLost ? 'Kronologi' : 'Penjelasan' }} <span class="text-danger">*</span></label>
                            <textarea id="disposal_notes" wire:model="notes" rows="4" class="form-control @error('notes') is-invalid @enderror"
                                placeholder="{{ $isLost ? 'Ceritakan bagaimana aset hilang dan upaya pencarian yang sudah dilakukan...' : 'Jelaskan kondisi aset dan alasan penghapusan...' }}"></textarea>
                            @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div>
                            <label for="disposal_evidence" class="form-label">
                                Lampiran <span class="text-muted fw-normal">(opsional{{ $isLost ? ', mis. surat kehilangan kepolisian' : ', mis. foto atau BA kerusakan' }})</span>
                            </label>
                            <input type="file" id="disposal_evidence" wire:model="evidence" accept=".pdf,.jpg,.jpeg,.png" class="form-control @error('evidence') is-invalid @enderror">
                            <div wire:loading wire:target="evidence" class="small text-muted mt-1"><span class="spinner-border spinner-border-sm me-1" role="status"></span>Mengunggah...</div>
                            @error('evidence') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="modal-footer d-grid gap-2 d-sm-flex justify-content-sm-end">
                        <button type="button" class="btn btn-outline-secondary" wire:click="closeModal">Batal</button>
                        <button type="button" wire:click="submit" class="btn btn-{{ $isLost ? 'warning' : 'danger' }}" wire:loading.attr="disabled" wire:target="submit,evidence">
                            <span wire:loading.remove wire:target="submit"><i class="bx bx-send me-1"></i>{{ $isLost ? 'Kirim laporan kehilangan' : 'Kirim pengajuan disposal' }}</span>
                            <span wire:loading wire:target="submit"><span class="spinner-border spinner-border-sm me-1" role="status"></span>Mengirim...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
