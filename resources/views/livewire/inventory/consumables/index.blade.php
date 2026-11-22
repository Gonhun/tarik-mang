<div class="container">
    <h4 class="mb-4">Stok Part Per Jobsite</h4>
    
    <div class="row mb-4">
        <div class="col-md-4">
            <label for="jobsiteFilter" class="form-label">Pilih Jobsite:</label>
            <select id="jobsiteFilter" wire:model.live="selectedJobsite" class="form-select">
                @foreach ($this->jobsites as $jobsite)
                    <option value="{{ $jobsite->id }}" @selected($jobsite->id == Auth::user()->jobsite_id)>{{ $jobsite->code }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label for="search" class="form-label">Cari Part:</label>
            <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Part Number / Nama">
        </div>
        <div class="col-md-4 d-flex align-items-end justify-content-end">
             {{-- Tombol Transaksi Masuk/Keluar bisa ditambahkan di sini --}}
             <button class="btn btn-success me-2">Stok Masuk</button>
             <button class="btn btn-warning">Stok Keluar</button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="table-primary">
                <tr>
                    <th>Part Number</th>
                    <th>Nama Part</th>
                    <th>Satuan</th>
                    <th>Stok Saat Ini ({{ $this->selectedJobsite ? $this->jobsites->where('id', $this->selectedJobsite)->first()->location_name : 'N/A' }})</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($parts as $part)
                    <tr>
                        <td>{{ $part->part_number }}</td>
                        <td>{{ $part->name }}</td>
                        <td>{{ $part->unit }}</td>
                        <td>
                            {{-- Hitung stok secara dinamis --}}
                            <span class="{{ $this->getStock($part->id, $selectedJobsite) <= 5 ? 'text-danger fw-bold' : '' }}">
                                {{ $this->getStock($part->id, $selectedJobsite) }}
                            </span>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-success me-1" wire:click="openTransactionModal({{ $part->id }}, 'IN')">
                                <i class="fas fa-arrow-up"></i> Masuk
                            </button>
                            <button class="btn btn-sm btn-warning me-1" wire:click="openTransactionModal({{ $part->id }}, 'OUT')">
                                <i class="fas fa-arrow-down"></i> Keluar
                            </button>
                            <button class="btn btn-sm btn-info" wire:click="showMutation({{ $part->id }})">
                                <i class="fas fa-history"></i> Mutasi
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center">Tidak ada Master Part yang ditemukan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $parts->links() }}
    </div>
    @if($showTransactionModal)
<div class="modal fade {{ $showTransactionModal ? 'show d-block' : '' }}" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form wire:submit="processTransaction">
                <div class="modal-header bg-{{ $transactionType === 'IN' ? 'success' : 'warning' }} text-white">
                    <h5 class="modal-title">
                        {{ $transactionType === 'IN' ? 'Stok Masuk' : 'Stok Keluar' }}
                        : {{ $partDetails->part_number ?? '' }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="$set('showTransactionModal', false)"></button>
                </div>
                <div class="modal-body">
                    <p>Nama Part: <strong>{{ $partDetails->name ?? '' }}</strong></p>
                    <p>Lokasi: <strong>{{ $jobsites->where('id', $selectedJobsite)->first()->code ?? 'N/A' }}</strong></p>
                    <p>Stok Saat Ini: <strong>{{ $this->getStock($selectedPartId, $selectedJobsite) }} {{ $partDetails->unit ?? '' }}</strong></p>
                    
                    @if (session()->has('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <div class="mb-3">
                        <label for="quantity" class="form-label required">Kuantitas ({{ $partDetails->unit ?? 'Unit' }})</label>
                        <input type="number" id="quantity" class="form-control" wire:model="quantity">
                        @error('quantity') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="transactionDate" class="form-label">Tanggal Transaksi</label>
                        <input type="date" id="transactionDate" class="form-control" wire:model="transactionDate">
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Catatan</label>
                        <textarea id="notes" class="form-control" wire:model="notes"></textarea>
                        @error('notes') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="$set('showTransactionModal', false)">Batal</button>
                    <button type="submit" class="btn btn-{{ $transactionType === 'IN' ? 'success' : 'warning' }}">
                        Proses {{ $transactionType === 'IN' ? 'Masuk' : 'Keluar' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@if($showMutationModal)
<div class="modal fade {{ $showMutationModal ? 'show d-block' : '' }}" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">Riwayat Mutasi Stok</h5>
                <button type="button" class="btn-close" wire:click="$set('showMutationModal', false)"></button>
            </div>
            <div class="modal-body">
                <h6>Part: {{ $partDetails->part_number ?? '' }} - {{ $partDetails->name ?? '' }}</h6>
                <p>Lokasi: <strong>{{ $jobsites->where('id', $selectedJobsite)->first()->code ?? 'N/A' }}</strong></p>
                <p>Stok Akhir: <strong>{{ $this->getStock($selectedPartId, $selectedJobsite) }}</strong></p>
                
                <hr>

                @if($mutationHistory && $mutationHistory->isNotEmpty())
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Tgl</th>
                                    <th>Tipe</th>
                                    <th class="text-end">Qty</th>
                                    <th>Dibuat Oleh</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($mutationHistory as $mutasi)
                                    <tr class="table-{{ $mutasi->type === 'IN' ? 'success' : 'warning' }} bg-opacity-25">
                                        <td>{{ $mutasi->transaction_date->format('Y-m-d H:i') }}</td>
                                        <td>{{ $mutasi->type }}</td>
                                        <td class="text-end">{{ number_format($mutasi->quantity) }}</td>
                                        <td>{{ $mutasi->user->name ?? 'System' }}</td>
                                        <td>{{ $mutasi->notes }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-secondary">Belum ada riwayat transaksi untuk part ini di lokasi ini.</div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" wire:click="$set('showMutationModal', false)">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endif
@script
    <script>
        // Event listener untuk menutup modal jika menggunakan Alpine/Bootstrap JS
        Livewire.on('close-modal', () => {
            // Logika penutupan modal jika diperlukan
        });
        // Event untuk refresh data setelah transaksi berhasil
        Livewire.on('refreshComponent', () => {
            Livewire.dispatch('refresh');
        });
    </script>
@endscript
</div>