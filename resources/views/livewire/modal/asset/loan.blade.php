<div>
    <div wire:loading.flex wire:target="open" class="position-fixed top-0 start-0 w-100 h-100 align-items-center justify-content-center" style="background: rgba(0,0,0,.25); z-index: 1060;">
        <div class="bg-white rounded shadow px-4 py-3 d-flex align-items-center gap-2"><span class="spinner-border spinner-border-sm text-primary" role="status"></span> Memuat...</div>
    </div>

    @if ($isModalOpen && $asset)
        @php
            $titles = ['lend' => 'Pinjamkan aset', 'return' => 'Terima pengembalian pinjaman', 'extend' => 'Perpanjang pinjaman'];
            $due = $loanMeta['due_date'] ?? null;
        @endphp
        <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="loanTitle" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="loanTitle">{{ $titles[$mode] }}</h5>
                        <button type="button" class="btn-close" wire:click="close" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="border rounded p-3 mb-3 small">
                            <span class="fw-semibold d-block">{{ $asset->asset_number }}</span>
                            <span class="text-muted">{{ $asset->assetType->name ?? '-' }} · {{ $asset->assetSeries->name ?? '-' }} · SN {{ $asset->serial_number ?: '-' }} · {{ $asset->jobsite->code ?? '-' }}</span>
                            @if ($mode !== 'lend')
                                <span class="d-block mt-1">Dipinjam <strong>{{ $asset->holder->name ?? '-' }}</strong> ({{ $loan->transaction_number ?? '-' }})
                                    · kembali {{ $due ? \Carbon\Carbon::parse($due)->locale('id')->isoFormat('D MMM YYYY') : '-' }}
                                    @if ($due && now()->startOfDay()->gt(\Carbon\Carbon::parse($due))) <span class="badge bg-label-danger">Terlambat</span> @endif
                                </span>
                                @if (!empty($loanMeta['purpose']))<span class="d-block text-muted">Keperluan: {{ $loanMeta['purpose'] }}</span>@endif
                            @endif
                        </div>

                        @if ($mode === 'lend')
                            <label for="loanBorrower" class="form-label">Peminjam <span class="text-danger">*</span></label>
                            @if ($borrower && !$borrowerSearch)
                                <div class="input-group mb-3">
                                    <span class="form-control">{{ $borrower->name }} <small class="text-muted">({{ $borrower->nik }})</small></span>
                                    <button type="button" class="btn btn-outline-secondary" wire:click="$set('borrowerId', null)">Ganti</button>
                                </div>
                            @else
                                <input id="loanBorrower" type="search" wire:model.live.debounce.400ms="borrowerSearch" class="form-control @error('borrowerId') is-invalid @enderror" placeholder="Ketik nama / NIK (min. 2 huruf)" autocomplete="off">
                                @if ($borrowerOptions->isNotEmpty())
                                    <div class="list-group mt-1 shadow-sm">
                                        @foreach ($borrowerOptions as $u)
                                            <button type="button" class="list-group-item list-group-item-action py-1 small" wire:click="chooseBorrower({{ $u->id }})">{{ $u->name }} <span class="text-muted">· {{ $u->nik }} · {{ $u->jobsite->code ?? '-' }}</span></button>
                                        @endforeach
                                    </div>
                                @endif
                                @error('borrowerId') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                <div class="mb-3"></div>
                            @endif

                            <div class="row g-3">
                                <div class="col-12 col-sm-5">
                                    <label for="loanDue" class="form-label">Kembali paling lambat <span class="text-danger">*</span></label>
                                    <input id="loanDue" type="date" wire:model="dueDate" min="{{ now()->format('Y-m-d') }}" max="{{ now()->addDays(\App\Support\AssetLoan::MAX_DAYS)->format('Y-m-d') }}" class="form-control @error('dueDate') is-invalid @enderror">
                                    @error('dueDate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-12 col-sm-7">
                                    <label for="loanPurpose" class="form-label">Keperluan <span class="text-danger">*</span></label>
                                    <input id="loanPurpose" type="text" wire:model="purpose" class="form-control @error('purpose') is-invalid @enderror" placeholder="Mis. presentasi klien, perjalanan dinas">
                                    @error('purpose') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <small class="text-muted d-block mt-2">Status aset menjadi <strong>Borrowed</strong>. Untuk pemakaian tetap gunakan Serah terima (BAST).</small>
                        @elseif ($mode === 'return')
                            <span class="form-label d-block">Kondisi saat kembali <span class="text-danger">*</span></span>
                            <div class="btn-group w-100 mb-3" role="group" aria-label="Kondisi">
                                <input type="radio" class="btn-check" id="ret-baik" value="baik" wire:model.live="condition">
                                <label class="btn btn-outline-success" for="ret-baik"><i class="bx bx-check-circle me-1"></i>Baik</label>
                                <input type="radio" class="btn-check" id="ret-rusak" value="rusak" wire:model.live="condition">
                                <label class="btn btn-outline-danger" for="ret-rusak"><i class="bx bx-wrench me-1"></i>Rusak</label>
                            </div>
                            <label for="retNotes" class="form-label">Catatan @if ($condition === 'rusak')<span class="text-danger">*</span>@else<span class="text-muted fw-normal">(opsional)</span>@endif</label>
                            <textarea id="retNotes" wire:model="returnNotes" rows="2" class="form-control @error('returnNotes') is-invalid @enderror" placeholder="{{ $condition === 'rusak' ? 'Jelaskan kerusakan...' : 'Mis. charger lengkap' }}"></textarea>
                            @error('returnNotes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <small class="text-muted d-block mt-2">{{ $condition === 'rusak' ? 'Aset menjadi Repair dan laporan kerusakan dibuat otomatis.' : 'Aset kembali Free dan siap dipakai.' }}</small>
                        @else
                            <label for="extDue" class="form-label">Tanggal kembali baru <span class="text-danger">*</span></label>
                            <input id="extDue" type="date" wire:model="dueDate" min="{{ now()->format('Y-m-d') }}" max="{{ now()->addDays(\App\Support\AssetLoan::MAX_DAYS)->format('Y-m-d') }}" class="form-control @error('dueDate') is-invalid @enderror">
                            @error('dueDate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        @endif
                    </div>
                    <div class="modal-footer d-grid gap-2 d-sm-flex justify-content-sm-end">
                        <button type="button" class="btn btn-outline-secondary" wire:click="close">Batal</button>
                        <button type="button" wire:click="submit" wire:loading.attr="disabled" wire:target="submit" class="btn btn-primary">
                            <span wire:loading.remove wire:target="submit">{{ ['lend' => 'Pinjamkan', 'return' => 'Terima pengembalian', 'extend' => 'Simpan'][$mode] }}</span>
                            <span wire:loading wire:target="submit"><span class="spinner-border spinner-border-sm me-1" role="status"></span>Menyimpan...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
