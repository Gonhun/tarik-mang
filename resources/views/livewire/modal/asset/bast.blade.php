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
                                    <label for="giver_user_id" class="form-label">Pemberi <span
                                            class="text-danger">*</span></label>
                                    <select wire:model.live="giver_user_id" id="giver_user_id"
                                        class="form-select @error('giver_user_id') is-invalid @enderror" disabled>
                                        <option value="">-- Pilih Pemberi --</option>
                                        @foreach ($allUsers as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}
                                                ({{ $user->nik }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('giver_user_id')
                                        <span class="text-danger small">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="receiver_user_id" class="form-label">Penerima <span
                                            class="text-danger">*</span></label>
                                    <select wire:model="receiver_user_id" id="receiver_user_id"
                                        class="form-select @error('receiver_user_id') is-invalid @enderror">
                                        <option value="">-- Pilih Penerima --</option>
                                        @foreach ($allUsers as $user)
                                            @if ($user->id != $giver_user_id)
                                                <option value="{{ $user->id }}">{{ $user->name }}
                                                    ({{ $user->nik }})
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                    @error('receiver_user_id')
                                        <span class="text-danger small">{{ $message }}</span>
                                    @enderror
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

    @push('scripts')
        <script>
            // function initSelect2() {
            //     $('#receiver_user_id').select2({
            //         dropdownParent: $('#bastModal')
            //     });
            //     $('#receiver_user_id').on('change', function() {
            //         @this.set('receiver_user_id', $(this).val());
            //     });
            // };

            // $(document).ready(function() {
            //     initSelect2();
            //     $('#receiver_user_id').on('change', function() {
            //         @this.set('receiver_user_id', $(this).val());
            //     });
            // });

            // Livewire.on('modal-open', function() {
            //         setTimeout(initSelect2, 50);
            //         console.log("Select 2 sudah di load");
            // });
            function initializeReceiverSelect2() {
                // Check if Select2 is already initialized to avoid errors
                if ($('#receiver_user_id').data('select2')) {
                    $('#receiver_user_id').select2('destroy');
                }

                $('#receiver_user_id').select2({
                    // Adjust options as needed
                    dropdownParent: $('#bastModal')
                });

                $('#receiver_user_id').off('change.livewire').on('change.livewire', function() {
                    @this.set('receiver_user_id', $(this).val());
                });
            }

            // Initialize Select2 on the first page load
            $(document).ready(function() {
                initializeReceiverSelect2();
            });

            // Re-initialize Select2 after every Livewire update
            Livewire.on('modal-open', () => {
                setTimeout(initializeReceiverSelect2, 50);
            });
        </script>
    @endpush
</div>
