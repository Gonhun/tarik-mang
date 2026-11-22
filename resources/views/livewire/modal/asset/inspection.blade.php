<div>
    @if ($isModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form wire:submit.prevent="submitInspection">
                        <div class="modal-header">
                            <h5 class="modal-title">Formulir Inspeksi Aset</h5>
                            <button type="button" class="btn-close" wire:click="closeModal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                            @if($assetToInspect && $transactionToInspect)
                                <p>Anda sedang menginspeksi aset yang dikembalikan dari transaksi <strong>{{ $transactionToInspect->transaction_number }}</strong>.</p>
                                <div class="alert alert-light">
                                    <strong>Nomor Aset:</strong> {{ $assetToInspect->asset_number }} <br>
                                    <strong>Deskripsi:</strong> {{ $assetToInspect->assetType->name ?? '' }} {{ $assetToInspect->assetBrand->name ?? '' }} {{ $assetToInspect->assetSeries->name ?? '' }}<br>
                                    <strong>Catatan Pengembali:</strong> <em class="text-muted">{{ $transactionToInspect->items->first()->condition_notes ?? 'Tidak ada catatan.' }}</em>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Hasil Inspeksi <span class="text-danger">*</span></label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" value="good" id="outcome_good" wire:model="inspection_outcome">
                                        <label class="form-check-label" for="outcome_good">Kondisi Baik (Kembali ke Stok/Free)</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" value="repair" id="outcome_repair" wire:model="inspection_outcome">
                                        <label class="form-check-label" for="outcome_repair">Perlu Perbaikan (Masuk Status Repair)</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" value="disposed" id="outcome_disposed" wire:model="inspection_outcome">
                                        <label class="form-check-label" for="outcome_disposed">Rusak Berat (Akan di-Dispose)</label>
                                    </div>
                                    @error('inspection_outcome') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="inspection_notes" class="form-label">Catatan Hasil Inspeksi <span class="text-danger">*</span></label>
                                    <textarea wire:model.defer="inspection_notes" id="inspection_notes" class="form-control @error('inspection_notes') is-invalid @enderror" rows="3" placeholder="Jelaskan hasil pengecekan secara detail..."></textarea>
                                    @error('inspection_notes') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                            @else
                                <div class="text-center p-4">
                                    <span class="spinner-border" role="status" aria-hidden="true"></span>
                                    <p class="mt-2">Memuat data...</p>
                                </div>
                            @endif
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="closeModal">Batal</button>
                            <button type="submit" class="btn btn-primary" @if(!$assetToInspect) disabled @endif>
                                <span wire:loading.remove wire:target="submitInspection">Simpan Hasil Inspeksi</span>
                                <span wire:loading wire:target="submitInspection">Menyimpan...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>