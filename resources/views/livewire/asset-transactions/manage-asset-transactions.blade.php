<div>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <div class="d-flex flex-wrap justify-content-between align-items-center">
                            <h5 class="mb-2 mb-md-0">Manajemen Transaksi Aset</h5>
                            <div>
                                <button wire:click="create('HANDOVER')" class="btn btn-success btn-sm mb-1 mb-md-0 me-md-1">
                                    <i class="bx bx-plus me-1"></i> Buat BAST Serah Terima
                                </button>
                                <button wire:click="create('RETURN')" class="btn btn-info btn-sm mb-1 mb-md-0">
                                    <i class="bx bx-undo me-1"></i> Buat BA Pengembalian
                                </button>
                                {{-- Tambahkan tombol untuk jenis transaksi lain di sini --}}
                            </div>
                        </div>
                        @if (session()->has('message'))
                            <div class="alert alert-success text-white mt-3" role="alert">
                                {{ session('message') }}
                            </div>
                        @endif
                        @if (session()->has('error'))
                            <div class="alert alert-danger text-white mt-3" role="alert">
                                {{ session('error') }}
                            </div>
                        @endif
                    </div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="px-4 pt-3"><div class="input-group input-group-merge" style="max-width: 380px"><span class="input-group-text"><i class="bx bx-search"></i></span><input wire:model.live.debounce.500ms="search" type="search" class="form-control" placeholder="Cari No. Transaksi, User, Tipe..."></div></div>
                        <div class="table-responsive p-0">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>No. Transaksi</th>
                                        <th>Tgl Transaksi</th>
                                        <th>Jenis</th>
                                        <th>Dari User</th>
                                        <th>Kepada User</th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Item</th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Dokumen</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($transactions as $transaction) {{-- Asumsi var $transactions dari komponen --}}
                                        <tr>
                                            <td>{{ $transaction->transaction_number }}</td>
                                            <td>{{ \Carbon\Carbon::parse($transaction->transaction_date)->isoFormat('D MMM YYYY') }}</td>
                                            <td>
                                                <span class="badge badge-sm bg-gradient-primary">
                                                    @if($transaction->transaction_type == 'HANDOVER') Serah Terima
                                                    @elseif($transaction->transaction_type == 'RETURN') Pengembalian
                                                    @elseif($transaction->transaction_type == 'DAMAGE_REPORT') Lap. Kerusakan
                                                    @else {{ Str::title(str_replace('_', ' ', $transaction->transaction_type)) }}
                                                    @endif
                                                </span>
                                            </td>
                                            <td>{{ $transaction->fromUser->name ?? 'N/A' }}</td>
                                            <td>{{ $transaction->toUser->name ?? 'N/A' }}</td>
                                            <td class="text-center"><span class="badge bg-secondary">{{ $transaction->items_count ?? $transaction->items->count() }}</span></td>
                                            <td class="text-center">
                                                <span class="badge bg-gradient-{{ $transaction->status == 'Completed' || $transaction->status == 'Processed' ? 'success' : ($transaction->status == 'Draft' || $transaction->status == 'Pending Inspection' ? 'info' : 'warning') }}">
                                                    {{ $transaction->status }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                @if ($transaction->document_path)
                                                    <a href="{{ asset('storage/'.$transaction->document_path) }}" target="_blank" class="btn btn-link text-primary p-0 m-0" data-bs-toggle="tooltip" title="{{$transaction->document_name}}">
                                                        <i class="bx bx-bxs-show"></i> Lihat
                                                    </a>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="align-middle">
                                                <button wire:click="edit({{ $transaction->id }})" class="btn btn-link text-secondary mb-0 p-1" data-bs-toggle="tooltip" title="Edit Transaksi">
                                                    <i class="bx bx-edit text-sm"></i>
                                                </button>
                                                @if($transaction->status == 'Draft' || $transaction->status == 'Pending Inspection')
                                                <button wire:click="initiateFinalizeTransaction({{ $transaction->id }})"
                                                        class="btn btn-link text-success text-gradient p-1 mb-0" data-bs-toggle="tooltip" title="Selesaikan Transaksi">
                                                    <i class="bx bx-check-circle text-sm"></i>
                                                </button>
                                                @endif
                                                {{-- Ganti cancelHandover dengan cancelTransaction jika ada --}}
                                                @if ($transaction->status == 'Draft')
                                                <button wire:click="cancelTransaction({{ $transaction->id }})"
                                                        onclick="confirm('Apakah Anda yakin ingin membatalkan Transaksi No. {{ $transaction->transaction_number }}?') || event.stopImmediatePropagation()"
                                                        class="btn btn-link text-warning text-gradient p-1 mb-0" data-bs-toggle="tooltip" title="Batalkan Transaksi">
                                                    <i class="bx bx-band-aid text-sm"></i>
                                                </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="9" class="text-center py-4">Tidak ada data transaksi aset ditemukan.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="px-4 pt-3">
                            {{ $transactions->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Create/Edit Transaksi Aset --}}
    @if ($isModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content">
                    <form wire:submit.prevent="store">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                @if($asset_transaction_id)
                                    Edit Transaksi: {{ $transaction_number }} ({{ Str::title(str_replace('_',' ',$transaction_type)) }})
                                @else
                                    Buat Transaksi Baru: ({{ Str::title(str_replace('_',' ',$transaction_type)) }})
                                @endif
                            </h5>
                            <button type="button" class="btn-close" wire:click="closeModal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            {{-- Field transaction_type bisa disembunyikan jika sudah diset saat create --}}
                            {{-- <input type="hidden" wire:model="transaction_type"> --}}

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="transaction_number" class="form-label">No. Transaksi <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('transaction_number') is-invalid @enderror" id="transaction_number" wire:model.defer="transaction_number" readonly>
                                    @error('transaction_number') <span class="text-danger d-block">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="transaction_date" class="form-label">Tanggal Transaksi <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('transaction_date') is-invalid @enderror" id="transaction_date" wire:model.defer="transaction_date">
                                    @error('transaction_date') <span class="text-danger d-block">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="status_transaction" class="form-label">Status Transaksi <span class="text-danger">*</span></label>
                                    <select wire:model.defer="status" id="status_transaction" class="form-select @error('status') is-invalid @enderror">
                                        <option value="Draft">Draft</option>
                                        @if($transaction_type == 'RETURN')
                                        <option value="Pending Inspection">Pending Inspection</option>
                                        <option value="Completed" disabled>Completed</option>
                                        @else {{-- Untuk HANDOVER atau lainnya --}}
                                        <option value="Completed" disabled>Completed</option>
                                        @endif
                                        <option value="Cancelled">Cancelled</option>
                                    </select>
                                    @error('status') <span class="text-danger d-block">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="from_user_id" class="form-label">
                                        @if($transaction_type == 'HANDOVER') Pemberi
                                        @elseif($transaction_type == 'RETURN') Yang Mengembalikan
                                        @else Dari User @endif
                                        <span class="text-danger">*</span>
                                    </label>
                                    <select wire:model="from_user_id" wire:change="PengembalianLoadAsset" id="from_user_id" class="form-select @error('from_user_id') is-invalid @enderror">
                                        <option value="">-- Pilih User --</option>
                                        @foreach ($users as $user) {{-- Asumsi $users adalah semua user, mungkin perlu filter --}}
                                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->nik }})</option>
                                        @endforeach
                                    </select>
                                    @error('from_user_id') <span class="text-danger d-block">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="to_user_id" class="form-label">
                                        @if($transaction_type == 'HANDOVER') Penerima
                                        @elseif($transaction_type == 'RETURN') Diterima Oleh (Admin/Gudang)
                                        @else Kepada User @endif
                                        @if($transaction_type == 'HANDOVER') <span class="text-danger">*</span> @endif
                                    </label>
                                    <select wire:model.defer="to_user_id" id="to_user_id" class="form-select @error('to_user_id') is-invalid @enderror">
                                        <option value="">-- Pilih User --</option>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->nik }})</option>
                                        @endforeach
                                    </select>
                                    @error('to_user_id') <span class="text-danger d-block">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="transaction_notes" class="form-label">Catatan Tambahan Transaksi</label>
                                <textarea wire:model.defer="notes" id="transaction_notes" class="form-control @error('notes') is-invalid @enderror" rows="3"></textarea>
                                @error('notes') <span class="text-danger d-block">{{ $message }}</span> @enderror
                            </div>

                            <hr>
                            <h6>Pilih Aset yang Terlibat <span class="text-danger">*</span></h6>
                            @error('selected_assets') <div class="alert alert-danger text-white py-2 px-3 mb-2">{{ $message }}</div> @enderror

                            {{-- Filter Aset --}}
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="filterAssetTypeIdModalTrx" class="form-label">Filter Tipe Aset:</label>
                                    <select wire:model="filterAssetTypeId" id="filterAssetTypeIdModalTrx" class="form-select form-select-sm">
                                        <option value="">-- Semua Tipe --</option>
                                        @foreach ($assetTypesForFilter as $type)
                                            <option value="{{ $type->id }}">{{ $type->name }} ({{ $type->code }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="filterAssetBrandIdModalTrx" class="form-label">Filter Merek Aset:</label>
                                    <select wire:model="filterAssetBrandId" id="filterAssetBrandIdModalTrx" class="form-select form-select-sm" @if(count($assetBrandsForFilter) == 0 && !$filterAssetTypeId) disabled @endif>
                                        <option value="">-- Semua Merek --</option>
                                        @if(count($assetBrandsForFilter) > 0)
                                            @foreach ($assetBrandsForFilter as $brand)
                                                <option value="{{ $brand->id }}">{{ $brand->name }} ({{ $brand->code }})</option>
                                            @endforeach
                                        @elseif($filterAssetTypeId)
                                            <option value="" disabled>Tidak ada merek untuk tipe ini</option>
                                        @else
                                            <option value="" disabled>Pilih Tipe Aset dulu</option>
                                        @endif
                                    </select>
                                </div>
                            </div>

                            <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;" class="mb-3">
                                <strong>
                                    @if($transaction_type == 'HANDOVER') Aset Tersedia untuk Diserahkan (Status 'Free')
                                    @elseif($transaction_type == 'RETURN') Aset untuk Dikembalikan (Status 'Used' oleh "Dari User")
                                    @else Aset Tersedia @endif
                                </strong>
                                @forelse ($availableAssets as $asset)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="{{ $asset->id }}" wire:model.live="selected_assets" id="trx_asset_{{ $asset->id }}">
                                        <label class="form-check-label" for="trx_asset_{{ $asset->id }}">
                                            {{ $asset->asset_number }} - SN: {{ $asset->serial_number }}
                                            ({{ $asset->assetSeries->name ?? ($asset->assetBrand->name ?? $asset->assetType->name ?? 'Detail') }})
                                        </label>
                                    </div>
                                @empty
                                    <p class="text-muted small">Tidak ada aset yang cocok atau semua sudah dipilih.</p>
                                @endforelse
                            </div>
                            @if (!empty($selected_assets))
                                <hr>
                                <h6>Detail Kondisi Aset yang Dipilih:</h6>
                                <div style="max-height: 250px; overflow-y: auto; padding: 5px;">
                                    @foreach ($selected_assets as $selected_asset_id)
                                        @php
                                            $selectedAssetDetail = collect($availableAssets)->firstWhere('id', $selected_asset_id)
                                                                ?? \App\Models\Asset::with(['assetType:id,name,code', 'assetBrand:id,name,code', 'assetSeries:id,name'])->find($selected_asset_id);
                                        @endphp
                                        @if($selectedAssetDetail)
                                        <div class="border p-2 mb-2 rounded">
                                            <p class="mb-1"><strong>{{ $selectedAssetDetail->asset_number }}</strong>
                                               <br><small> ({{ $selectedAssetDetail->assetSeries->name ?? ($selectedAssetDetail->assetBrand->name ?? $selectedAssetDetail->assetType->name ?? 'Detail') }})</small>
                                            </p>
                                            <input type="text" wire:model.defer="asset_conditions.{{ $selected_asset_id }}" class="form-control form-control-sm mb-1" placeholder="Kondisi Aset (mis: Baik, Rusak Ringan)">
                                            <textarea wire:model.defer="condition_notes_per_asset.{{ $selected_asset_id }}" class="form-control form-control-sm" rows="1" placeholder="Catatan kondisi spesifik..."></textarea>
                                        </div>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                            <hr>

                            <div class="mb-3">
                                <label for="transaction_document" class="form-label">Upload Dokumen (BAST/BAPR - PDF, JPG, PNG - Maks 5MB)</label>
                                <input type="file" class="form-control @error('document') is-invalid @enderror" id="transaction_document" wire:model="document">
                                <div wire:loading wire:target="document" class="mt-1 text-primary">Uploading...</div>
                                @error('document') <span class="text-danger d-block">{{ $message }}</span> @enderror

                                @if ($document && method_exists($document, 'temporaryUrl'))
                                   {{-- Preview --}}
                                @elseif ($existing_document_path)
                                   {{-- Link ke dokumen lama --}}
                                @endif
                            </div>

                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="closeModal">Batal</button>
                            <button type="submit" class="btn btn-primary">
                                <span wire:loading.remove wire:target="store">{{ $asset_transaction_id ? 'Simpan Perubahan' : 'Simpan Transaksi' }}</span>
                                <span wire:loading wire:target="store">Menyimpan...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal Upload Dokumen untuk Penyelesaian --}}
    @if ($isUploadDocModalOpen && $transactionToCompleteId)
        {{-- ... (struktur modal ini mirip dengan modal upload BAST sebelumnya, sesuaikan variabel) ... --}}
        {{-- Ganti $handoverToCompleteId dengan $transactionToCompleteId --}}
        {{-- Ganti $bastDocumentForCompletion dengan $documentForCompletion --}}
        {{-- Ganti wire:click="uploadBastAndFinalize" dengan wire:click="uploadDocumentAndFinalize" --}}
         <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form wire:submit.prevent="uploadDocumentAndFinalize">
                        <div class="modal-header">
                            <h5 class="modal-title">Upload Dokumen untuk Penyelesaian Transaksi</h5>
                            <button type="button" class="btn-close" wire:click="closeUploadDocModal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                             @php $trxToComplete = \App\Models\AssetTransaction::find($transactionToCompleteId); @endphp
                            <p>Anda akan menyelesaikan Transaksi No. <strong>{{ $trxToComplete->transaction_number ?? $transactionToCompleteId }}</strong>.</p>
                            <p>Silakan upload dokumen pendukung untuk melanjutkan.</p>

                            <div class="mb-3">
                                <label for="documentForCompletionModal" class="form-label">File Dokumen (PDF, JPG, PNG - Maks 5MB) <span class="text-danger">*</span></label>
                                <input type="file" class="form-control @error('documentForCompletion') is-invalid @enderror" id="documentForCompletionModal" wire:model="documentForCompletion">
                                <div wire:loading wire:target="documentForCompletion" class="mt-1 text-primary">Uploading...</div>
                                @error('documentForCompletion') <span class="text-danger d-block">{{ $message }}</span> @enderror
                                {{-- Preview bisa ditambahkan di sini --}}
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="closeUploadDocModal">Batal</button>
                            <button type="submit" class="btn btn-success">
                                <span wire:loading.remove wire:target="uploadDocumentAndFinalize">Upload & Selesaikan</span>
                                <span wire:loading wire:target="uploadDocumentAndFinalize">Memproses...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

</div>

@push('scripts')
<script>
    // Skrip tooltip dan scroll sama seperti sebelumnya
    document.addEventListener('livewire:load', function () {
        // Tooltip init
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    });

    Livewire.hook('message.processed', (message, component) => {
        // Re-init tooltip
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        tooltipTriggerList.forEach(function (tooltipTriggerEl) {
            var existingTooltip = bootstrap.Tooltip.getInstance(tooltipTriggerEl);
            if (existingTooltip) {
                existingTooltip.dispose();
            }
            new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
    
    // Listener untuk konfirmasi JavaScript (jika masih dipakai untuk finalize)
    window.addEventListener('confirm-finalize-transaction', event => {
        if (confirm(event.detail.message)) {
            @this.call('doFinalizeTransaction', event.detail.transactionId);
        }
    });

    Livewire.on('fileInputReset', params => {
        const fileInput = document.getElementById(params.inputId || 'transaction_document');
        if(fileInput) {
            fileInput.value = null;
        }
        const fileInputComplete = document.getElementById(params.inputId || 'documentForCompletionModal');
         if(fileInputComplete) {
            fileInputComplete.value = null;
        }
    });
</script>
@endpush