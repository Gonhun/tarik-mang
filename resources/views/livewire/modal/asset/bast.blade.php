<div>
    {{-- Indikator saat modal sedang dimuat --}}
    <div wire:loading.flex wire:target="loadInitialData" class="position-fixed top-0 start-0 w-100 h-100 align-items-center justify-content-center" style="background: rgba(0,0,0,.25); z-index: 1060;">
        <div class="bg-white rounded shadow px-4 py-3 d-flex align-items-center gap-2">
            <span class="spinner-border spinner-border-sm text-primary" role="status"></span> Memuat...
        </div>
    </div>
    @if ($isModalOpen)
        <div id="bastModal" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <form wire:submit.prevent="submitBast">
                        <div class="modal-header">
                            <h5 class="modal-title">Formulir BAST (Berita Acara Serah Terima)</h5>
                            <button type="button" class="btn-close" wire:click="closeModal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Pemberi</label>
                                    <div class="form-control bg-lighter">{{ $giver->name ?? '-' }} <small class="text-muted">({{ $giver->nik ?? '-' }})</small></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="receiverSearch" class="form-label">Penerima <span class="text-danger">*</span></label>
                                    @if ($receiver && !$receiverSearch)
                                        <div class="input-group">
                                            <span class="form-control text-truncate">{{ $receiver->name }} <small class="text-muted">({{ $receiver->nik }})</small></span>
                                            <button type="button" class="btn btn-outline-secondary" wire:click="$set('receiver_user_id', null)">Ganti</button>
                                        </div>
                                    @else
                                        <input type="search" id="receiverSearch" wire:model.live.debounce.400ms="receiverSearch" class="form-control @error('receiver_user_id') is-invalid @enderror" placeholder="Ketik nama / NIK (min. 2 huruf)" autocomplete="off">
                                        <div wire:loading wire:target="receiverSearch" class="small text-muted mt-1"><span class="spinner-border spinner-border-sm me-1" role="status"></span>Mencari...</div>
                                        @if ($receiverOptions->isNotEmpty())
                                            <div class="list-group mt-1 shadow-sm">
                                                @foreach ($receiverOptions as $u)
                                                    <button type="button" class="list-group-item list-group-item-action py-1 small" wire:click="chooseReceiver({{ $u->id }})">{{ $u->name }} <span class="text-muted">· {{ $u->nik }} · {{ $u->jobsite->code ?? '-' }}</span></button>
                                                @endforeach
                                            </div>
                                        @endif
                                    @endif
                                    @error('receiver_user_id') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="transaction_date_bast" class="form-label">Tanggal Serah Terima <span
                                        class="text-danger">*</span></label>
                                <input type="date" wire:model.defer="transaction_date" id="transaction_date_bast"
                                    class="form-control @error('transaction_date') is-invalid @enderror" disabled>
                                @error('transaction_date')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>

                            <hr>
                            <h6>Pilih Aset yang Diserahkan <span class="text-danger">*</span></h6>
                            @error('selected_assets')
                                <div class="alert alert-danger py-2">{{ $message }}</div>
                            @enderror
                            <div style="max-height: 250px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;"
                                class="mb-3">
                                <div wire:loading wire:target="updatedGiverUserId">Memuat aset...</div>
                                @forelse ($availableAssets as $asset)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="{{ $asset->id }}"
                                            wire:model.defer="selected_assets" id="bast_asset_{{ $asset->id }}"
                                            disabled>
                                        <label class="form-check-label" for="bast_asset_{{ $asset->id }}">
                                            {{ $asset->asset_number }} - {{ $asset->assetSeries->name ?? 'N/A' }}
                                        </label>
                                    </div>
                                @empty
                                    <p class="text-muted small">
                                        @if ($giver_user_id)
                                            Tidak ada aset 'Free' atau yang dipegang oleh pemberi terpilih.
                                        @else
                                            Pilih Pemberi untuk melihat daftar aset.
                                        @endif
                                    </p>
                                @endforelse
                            </div>

                            <div class="mb-3">
                                <label for="notes_bast" class="form-label">Catatan Tambahan</label>
                                <textarea wire:model.defer="notes" id="notes_bast" class="form-control" rows="2"
                                    placeholder="Catatan mengenai serah terima..."></textarea>
                            </div>


                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="closeModal">Batal</button>
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="submitBast">
                                <span wire:loading.remove wire:target="submitBast">Buat BAST</span>
                                <span wire:loading wire:target="submitBast">Memproses...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    
</div>
