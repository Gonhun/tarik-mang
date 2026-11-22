<div>
    <div class="container-fluid py-4">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Manajemen Mutasi Aset</h5>
                   
                        <button wire:click="create()" class="btn btn-primary btn-sm">
                            <i class="fas fa-random me-1"></i> Buat Permintaan Mutasi Baru
                        </button>
                   
                </div>
                 @if (session()->has('message'))<div class="alert alert-success mt-2">{{ session('message') }}</div>@endif
                 @if (session()->has('error'))<div class="alert alert-danger mt-2">{{ session('error') }}</div>@endif
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th>No. Mutasi</th>
                                <th>Tgl. Permintaan</th>
                                <th>Dari Jobsite</th>
                                <th>Ke Jobsite</th>
                                <th>Jumlah Item</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($mutations as $mutation)
                                <tr>
                                    <td>{{ $mutation->transaction_number }}</td>
                                    <td>{{ $mutation->transaction_date->format('d M Y') }}</td>
                                    <td>{{ $mutation->fromJobsite->name ?? 'N/A' }}</td>
                                    <td>{{ $mutation->toJobsite->name ?? 'N/A' }}</td>
                                    <td class="text-center">{{ $mutation->items->count() }}</td>
                                    @if($mutation->status == 'Completed')
                                        <td><span class="badge bg-gradient-success">{{ $mutation->status }}</span></td>
                                    @elseif($mutation->status == 'Rejected')
                                        <td><span class="badge bg-gradient-danger">{{ $mutation->status }}</span></td>
                                    @elseif($mutation->status == 'Pending Release')
                                        <td><span class="badge bg-gradient-warning">{{ $mutation->status }}</span></td>
                                    @elseif($mutation->status == 'In Transit')
                                        <td><span class="badge bg-gradient-primary">{{ $mutation->status }}</span></td>
                                    @else
                                        <td><span class="badge bg-gradient-info">{{ $mutation->status }}</span></td>
                                    @endif
                                    <td>
                                        {{-- Tombol Detail selalu bisa dilihat --}}
                                        <div class="d-flex flex-column gap-2">
                                            {{-- <a href="{{ route('asset.mutasi-status', $mutation->id) }}" 
                                               class="btn btn-sm btn-outline-info mb-1" 
                                               title="Lihat Detail"
                                               style="font-weight: 600; letter-spacing: 0.5px;">
                                                <i class="bx bx-search me-1"></i> Detail
                                            </a> --}}
                                            
                                            {{-- Tombol Approve Release --}}
                                            @if($mutation->status == 'Pending Release' && Auth::user()->jobsite_id == $mutation->from_jobsite_id)
                                            {{-- Tombol ini sekarang membuka modal --}}
                                                <button wire:click="openReleaseModal({{ $mutation->id }})" class="btn btn-sm btn-success mb-0">Approve Release</button>
                                            @elseif($mutation->status == 'Delivery' && Auth::user()->jobsite_id == $mutation->to_jobsite_id)
                                                <button wire:click="confirmReceipt({{ $mutation->id }})" class="btn btn-sm btn-primary mb-0">Konfirmasi Terima</button>
                                            @endif
                                            <a href="{{ route('asset.mutasi-status', $mutation->id) }}" class="btn btn-sm btn-outline-secondary mb-0">Detail</a>
                                        </div>
                        
                                        {{-- Tombol Konfirmasi Terima
                                        @if($mutation->status == 'In Transit' && Auth::user()->jobsite_id == $mutation->to_jobsite_id)
                                            <button wire:click="confirmReceipt({{ $mutation->id }})" 
                                                    onclick="confirm('Konfirmasi bahwa Anda telah menerima aset untuk mutasi ini?') || event.stopImmediatePropagation()"
                                                    class="btn btn-sm btn-primary mb-0">
                                                Konfirmasi Terima
                                            </button>
                                        @endif --}}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center">Tidak ada data mutasi.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $mutations->links() }}
                </div>
            </div>
        </div>
    </div>

    {{-- Modal untuk Form Permintaan Mutasi --}}
    @if($isModalOpen)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form wire:submit.prevent="store">
                    <div class="modal-header">
                        <h5 class="modal-title">Form Permintaan Mutasi Aset</h5>
                        <button type="button" class="btn-close" wire:click="closeModal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="transaction_date_mutation" class="form-label">Tanggal Permintaan</label>
                                <input type="date" wire:model.defer="transaction_date" id="transaction_date_mutation" class="form-control @error('transaction_date') is-invalid @enderror">
                                @error('transaction_date') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="from_jobsite_id" class="form-label">Dari Jobsite</label>
                                <select wire:loading.remove wire:change="changeFromJobsiteId($event.target.value)" id="from_jobsite_id" class="form-select @error('from_jobsite_id') is-invalid @enderror">
                                    <option value="">-- Pilih Jobsite Asal --</option>
                                    @foreach($allJobsites as $jobsite)
                                        <option value="{{ $jobsite->id }}">{{ $jobsite->name }}</option>
                                    @endforeach
                                </select>
                                @error('from_jobsite_id') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                             <div class="col-md-4 mb-3">
                                <label for="to_jobsite_id" class="form-label">Ke Jobsite</label>
                                <select wire:model.defer="to_jobsite_id" id="to_jobsite_id" class="form-select @error('to_jobsite_id') is-invalid @enderror">
                                    <option value="">-- Pilih Jobsite Tujuan --</option>
                                     @foreach($allJobsites as $jobsite)
                                        @if($jobsite->id != $from_jobsite_id)
                                            <option value="{{ $jobsite->id }}">{{ $jobsite->name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                @error('to_jobsite_id') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="mb-3">
                             <label for="mutation_notes" class="form-label">Alasan/Catatan Mutasi</label>
                             <textarea wire:model.defer="notes" id="mutation_notes" class="form-control @error('notes') is-invalid @enderror" rows="3"></textarea>
                             @error('notes') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <hr>
                        <h6>Pilih Aset untuk Dimutasi</h6>
                        @error('selected_assets') <div class="alert alert-danger py-2">{{ $message }}</div> @enderror
                        <div style="max-height: 250px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">
                            <div wire:loading wire:target="changeFromJobsiteId">Memuat aset...</div>
                            @if($assetsFromOrigin)
                            <input type="text" wire:model.live="searchAsset" class="form-control mb-3" placeholder="Cari aset...">
                            @endif
                            
                            @forelse ($filteredAssets as $asset)
                                <div class="form-check mb-1">
                                    <input class="form-check-input"
                                        type="checkbox"
                                        value="{{ $asset['id'] }}"
                                        wire:model.defer="selected_assets"
                                        id="mutation_asset_{{ $asset['id'] }}"
                                        @checked(in_array($asset['id'], $selected_assets))>
                            
                                    <label class="form-check-label" for="mutation_asset_{{ $asset['id'] }}">
                                        {{ $asset['asset_number'] }} - SN: {{ $asset['serial_number'] }}
                                        ({{ $asset['asset_series']['name'] ?? $asset['asset_series']['material_code']['name'] ?? '-' }})
                                    </label>
                                </div>
                            @empty
                                <div class="text-muted fst-italic">Tidak ada aset ditemukan.</div>
                            @endforelse
                        </div>
                        
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeModal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <span wire:loading.remove wire:target="store">Buat Permintaan Mutasi</span>
                            <span wire:loading wire:target="store">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
    @if($isReleaseModalOpen && $mutationToRelease)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form wire:submit.prevent="submitReleaseApproval">
                    <div class="modal-header">
                        <h5 class="modal-title">Persetujuan Pelepasan Aset</h5>
                        <button type="button" class="btn-close" wire:click="closeReleaseModal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Anda akan menyetujui pelepasan aset untuk mutasi <strong>{{ $mutationToRelease->transaction_number }}</strong>.</p>
                        <p>Silakan upload bukti serah terima (surat jalan, resi, atau foto) untuk melanjutkan.</p>
                        
                        <div class="mb-3">
                            <label for="releaseDocument" class="form-label">Bukti Serah Terima <span class="text-danger">*</span></label>
                            <input type="file" wire:model="releaseDocument" id="releaseDocument" class="form-control @error('releaseDocument') is-invalid @enderror">
                            <div wire:loading wire:target="releaseDocument" class="mt-1 small text-primary">Uploading...</div>
                            @error('releaseDocument') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeReleaseModal">Batal</button>
                        <button type="submit" class="btn btn-success">
                            <span wire:loading.remove wire:target="submitReleaseApproval">Setujui & Lepas Aset</span>
                            <span wire:loading wire:target="submitReleaseApproval">Memproses...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>