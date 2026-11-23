<div>
    {{-- Indikator saat modal sedang dimuat --}}
    <div wire:loading.flex wire:target="loadAssetToReturn" class="position-fixed top-0 start-0 w-100 h-100 align-items-center justify-content-center" style="background: rgba(0,0,0,.25); z-index: 1060;">
        <div class="bg-white rounded shadow px-4 py-3 d-flex align-items-center gap-2">
            <span class="spinner-border spinner-border-sm text-primary" role="status"></span> Memuat...
        </div>
    </div>
    @if ($isModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form wire:submit.prevent="submitReturn">
                        <div class="modal-header">
                            <h5 class="modal-title">Form Pengembalian Aset</h5>
                            <button type="button" class="btn-close" wire:click="closeModal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            @if($assetToReturn)
                                <p>Anda akan mengajukan pengembalian untuk aset berikut:</p>
                                <div class="alert alert-light">
                                    <strong>Nomor Aset:</strong> {{ $assetToReturn->asset_number }} <br>
                                    <strong>Deskripsi:</strong> {{ $assetToReturn->assetType->name ?? '' }} {{ $assetToReturn->assetBrand->name ?? '' }} {{ $assetToReturn->assetSeries->name ?? '' }}<br>
                                    <strong>Pemegang:</strong> {{ $assetToReturn->holder->name ?? '-' }}<br>
                                    <strong>Serial number:</strong> {{ $assetToReturn->serial_number ?: '-' }}
                                </div>
                                @if ($this->withoutHolder)
                                    <div class="alert alert-warning small d-flex gap-2" role="alert">
                                        <i class="bx bx-error fs-5 flex-shrink-0"></i>
                                        <div>Aset ini berstatus <strong>{{ $assetToReturn->status }}</strong> tapi <strong>tidak punya pemegang tercatat</strong> (data lama). Pengembalian akan mengembalikannya ke stok lewat inspeksi IT.</div>
                                    </div>
                                @endif
                                
                                <div class="mb-3">
                                    <label for="returnCondition" class="form-label">Kondisi Aset Saat Dikembalikan <span class="text-danger">*</span></label>
                                    <select wire:model.defer="returnCondition" id="returnCondition" class="form-select @error('returnCondition') is-invalid @enderror">
                                        <option value="">-- Pilih Kondisi --</option>
                                        <option value="Baik">Baik</option>
                                        <option value="Rusak Ringan">Rusak Ringan</option>
                                        <option value="Rusak Berat">Rusak Berat</option>
                                        <option value="Perlu Perbaikan">Perlu Perbaikan</option>
                                    </select>
                                    @error('returnCondition') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="returnNotes" class="form-label">{{ $this->withoutHolder ? 'Alasan pengembalian (opsional)' : 'Catatan Tambahan' }}</label>
                                    <textarea wire:model.defer="returnNotes" id="returnNotes" class="form-control @error('returnNotes') is-invalid @enderror" rows="3" placeholder="Jelaskan detail kondisi atau alasan pengembalian..."></textarea>
                                    @error('returnNotes') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                            @else
                                <div class="text-center p-4">
                                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                    Memuat data aset...
                                </div>
                            @endif
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="closeModal">Batal</button>
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="submitReturn" @if(!$assetToReturn) disabled @endif>
                                <span wire:loading.remove wire:target="submitReturn">Ajukan Pengembalian</span>
                                <span wire:loading wire:target="submitReturn">Memproses...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>