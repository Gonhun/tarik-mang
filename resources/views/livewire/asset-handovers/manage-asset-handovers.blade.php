<div>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Manajemen BAST Aset</h5>
                            <button wire:click="create()" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus me-1"></i> Buat BAST Baru
                            </button>
                        </div>
                        @if (session()->has('message'))
                            <div class="alert alert-success mt-3" role="alert">
                                {{ session('message') }}
                            </div>
                        @endif
                        @if (session()->has('error'))
                            <div class="alert alert-danger mt-3" role="alert">
                                {{ session('error') }}
                            </div>
                        @endif
                    </div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="px-4 pt-3">
                            <input wire:model.live.debounce.500ms="search" type="text" class="form-control" placeholder="Cari No. BAST, Pemberi, atau Penerima...">
                        </div>
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">No. BAST</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Tgl Serah Terima</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Pemberi</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Penerima</th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Item Aset</th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status BAST</th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Dokumen</th>
                                        <th class="text-secondary opacity-7"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($handovers as $handover)
                                        <tr>
                                            <td><p class="text-xs font-weight-bold mb-0 px-3">{{ $handover->handover_number }}</p></td>
                                            <td><p class="text-xs font-weight-bold mb-0">{{ \Carbon\Carbon::parse($handover->handover_date)->isoFormat('D MMM YYYY') }}</p></td>
                                            <td><p class="text-xs font-weight-bold mb-0">{{ $handover->giver->name ?? 'N/A' }}</p></td>
                                            <td><p class="text-xs font-weight-bold mb-0">{{ $handover->receiver->name ?? 'N/A' }}</p></td>
                                            <td class="text-center"><span class="badge bg-secondary">{{ $handover->items_count ?? $handover->items->count() }}</span></td>
                                            <td class="text-center">
                                                <span class="badge bg-gradient-{{ $handover->status == 'Completed' ? 'success' : ($handover->status == 'Draft' ? 'info' : 'warning') }}">
                                                    {{ $handover->status }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                @if ($handover->bast_document_path)
                                                    <a href="{{ $handover->bast_document_url }}" target="_blank" class="btn btn-link text-primary p-0 m-0" data-bs-toggle="tooltip" title="{{$handover->bast_document_name}}">
                                                        <i class="bx bx-file"></i> Lihat
                                                    </a>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="align-middle">
                                                @if ($handover->status !='Cancelled')
                                                    <button wire:click="edit({{ $handover->id }})" class="btn btn-link text-secondary mb-0 p-1" data-bs-toggle="tooltip" title="Edit BAST">
                                                        <i class="bx bx-edit"></i>
                                                    </button>
                                                    <a href="{{ route('bast.pdf', ['handoverId' => $handover->id]) }}" target="_blank"
                                                        class="btn btn-link text-info p-1 mb-0" data-bs-toggle="tooltip" title="Cetak/Simpan PDF BAST">
                                                         <i class="bx bx-bxs-printer text-sm"></i> {{-- atau fas fa-file-pdf --}}
                                                     </a>
                                                    @if($handover->status == 'Draft')
                                                        <button wire:click="initiateCompleteHandover({{ $handover->id }})"
                                                                class="btn btn-link text-success text-gradient p-1 mb-0" data-bs-toggle="tooltip" title="Selesaikan BAST">
                                                            <i class="bx bx-check-circle text-sm"></i>
                                                        </button>
                                                    @endif
                                                    
                                                    <button wire:click="delete({{ $handover->id }})"
                                                            onclick="confirm('Apakah Anda yakin ingin menghapus BAST No. {{ $handover->handover_number }}?') || event.stopImmediatePropagation()"
                                                            class="btn btn-link text-danger text-gradient p-1 mb-0" data-bs-toggle="tooltip" title="Hapus BAST">
                                                        <i class="bx bx-delete text-sm"></i>
                                                    </button>
                                                @endif
                                                
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="8" class="text-center py-4">Tidak ada data BAST Aset ditemukan.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="px-4 pt-3">
                            {{ $handovers->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Create/Edit BAST --}}
    @if ($isModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content">
                    <form wire:submit.prevent="store">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ $asset_handover_id ? 'Edit BAST Aset' : 'Buat BAST Aset Baru' }}</h5>
                            <button type="button" class="btn-close" wire:click="closeModal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="handover_number" class="form-label">No. BAST <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('handover_number') is-invalid @enderror" id="handover_number" wire:model.defer="handover_number" readonly>
                                    @error('handover_number') <span class="text-danger d-block">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="handover_date" class="form-label">Tanggal Serah Terima <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('handover_date') is-invalid @enderror" id="handover_date" wire:model.defer="handover_date">
                                    @error('handover_date') <span class="text-danger d-block">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="status_bast" class="form-label">Status BAST <span class="text-danger">*</span></label>
                                    <select wire:model.defer="status" id="status_bast" class="form-select @error('status') is-invalid @enderror" disabled>
                                        <option value="Draft">Draft</option>
                                        <option value="Completed">Completed</option>
                                        <option value="Cancelled">Cancelled</option>
                                    </select>
                                    @error('status') <span class="text-danger d-block">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="giver_user_id" class="form-label">Pemberi <span class="text-danger">*</span></label>
                                    <select wire:model.defer="giver_user_id" id="giver_user_id" class="form-select @error('giver_user_id') is-invalid @enderror">
                                        
                                        <option value="{{ Auth::id() }}" selected>{{ Auth::user()->name }} ({{ Auth::user()->nik }})</option>
                                    </select>
                                    @error('giver_user_id') <span class="text-danger d-block">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="receiver_user_id" class="form-label">Penerima <span class="text-danger">*</span></label>
                                    <select wire:model.defer="receiver_user_id" id="receiver_user_id" class="form-select @error('receiver_user_id') is-invalid @enderror">
                                        <option value="">-- Pilih Penerima --</option>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->nik }})</option>
                                        @endforeach
                                    </select>
                                    @error('receiver_user_id') <span class="text-danger d-block">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label">Catatan Tambahan</label>
                                <textarea wire:model.defer="notes" id="notes" class="form-control @error('notes') is-invalid @enderror" rows="3"></textarea>
                                @error('notes') <span class="text-danger d-block">{{ $message }}</span> @enderror
                            </div>

                            <hr>
                            <h6>Pilih Aset yang Diserahterimakan <span class="text-danger">*</span></h6>
                             @error('selected_assets') <div class="alert alert-danger text-white py-2 px-3 mb-2">{{ $message }}</div> @enderror
                            {{-- Filter Aset --}}
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="filterAssetTypeIdModal" class="form-label">Filter Tipe Aset:</label>
                                    <select wire:model="filterAssetTypeId" wire:change="changeFilterAssetTypeId" id="filterAssetTypeIdModal" class="form-select form-select-sm">
                                        <option value="">-- Semua Tipe --</option>
                                        @foreach ($assetTypesForFilter as $type)
                                            <option value="{{ $type->id }}">{{ $type->name }} ({{ $type->code }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="filterAssetBrandIdModal" class="form-label">Filter Merek Aset:</label>
                                    <select wire:model="filterAssetBrandId" wire:change="changeFilterAssetBrandId" id="filterAssetBrandIdModal" class="form-select form-select-sm" @if(count($assetBrandsForFilter) == 0 && !$filterAssetTypeId) disabled @endif>
                                        <option value="">-- Semua Merek (dari Tipe terpilih) --</option>
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
                            
                             {{-- Daftar Aset yang Tersedia untuk Dipilih (Hasil Filter) --}}
                            <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;" class="mb-3">
                                <strong>Aset Tersedia (Status 'Free', belum dipilih):</strong>
                                @forelse ($availableAssets as $asset)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="{{ $asset->id }}" wire:model="selected_assets" id="available_asset_{{ $asset->id }}">
                                        <label class="form-check-label" for="available_asset_{{ $asset->id }}">
                                            {{ $asset->asset_number }} - SN: {{ $asset->serial_number }}
                                            ({{ $asset->assetSeries->name ?? ($asset->assetBrand->name ?? $asset->assetType->name ?? 'Detail Aset') }})
                                        </label>
                                    </div>
                                @empty
                                    <p class="text-muted small">
                                        @if($filterAssetTypeId || $filterAssetBrandId)
                                            Tidak ada aset 'Free' yang cocok dengan filter Anda atau semua sudah dipilih.
                                        @else
                                            Tidak ada aset dengan status 'Free' yang tersedia atau semua sudah dipilih.
                                        @endif
                                    </p>
                                @endforelse
                            </div>

                            {{-- Daftar Aset yang SUDAH Dipilih (untuk input kondisi) --}}
                            @if (!empty($selected_assets))
                                <hr>
                                <h6>Detail Kondisi Aset yang Dipilih:</h6>
                                <div style="max-height: 250px; overflow-y: auto; padding: 5px;">
                                    @foreach ($selected_assets as $selected_asset_id)
                                        @php
                                            // Ambil detail aset yang dipilih dari koleksi yang sudah ada atau query jika perlu.
                                            // Lebih baik jika $availableAssets atau koleksi lain menyimpan detail lengkap.
                                            // Untuk contoh ini, kita cari manual (idealnya dioptimalkan di komponen PHP jika datanya besar)
                                            $selectedAssetDetail = collect($availableAssets)->firstWhere('id', $selected_asset_id)
                                                                ?? \App\Models\Asset::with(['assetType:id,name,code', 'assetBrand:id,name,code', 'assetSeries:id,name'])->find($selected_asset_id);
                                        @endphp
                                        @if($selectedAssetDetail)
                                        <div class="border p-2 mb-2 rounded">
                                            <p class="mb-1"><strong>{{ $selectedAssetDetail->asset_number }}</strong> - SN: {{ $selectedAssetDetail->serial_number }}
                                            <br><small> ({{ $selectedAssetDetail->assetSeries->name ?? ($selectedAssetDetail->assetBrand->name ?? $selectedAssetDetail->assetType->name ?? 'Detail Aset') }})</small>
                                            </p>
                                            <input type="text" wire:model.defer="asset_conditions.{{ $selected_asset_id }}" class="form-control form-control-sm mb-1" placeholder="Kondisi Aset (mis: Baik, Ada Goresan)">
                                            <textarea wire:model.defer="condition_notes_per_asset.{{ $selected_asset_id }}" class="form-control form-control-sm" rows="1" placeholder="Catatan kondisi spesifik..."></textarea>
                                        </div>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                            <hr>
                            <small class="form-text text-muted">Hanya aset dengan status 'Free' (atau status relevan lainnya) yang ditampilkan di sini.</small>
                            <hr>


                            <div class="mb-3">
                                <label for="bast_document" class="form-label">Upload Dokumen BAST (PDF, JPG, PNG - Maks 5MB)</label>
                                <input type="file" class="form-control @error('bast_document') is-invalid @enderror" id="bast_document" wire:model="bast_document">
                                <div wire:loading wire:target="bast_document" class="mt-1 text-primary">Uploading...</div>
                                @error('bast_document') <span class="text-danger d-block">{{ $message }}</span> @enderror

                                @if ($bast_document && method_exists($bast_document, 'temporaryUrl'))
                                    <div class="mt-2">
                                        <p class="mb-1">Preview Dokumen Baru:</p>
                                        @if(Str::startsWith($bast_document->getMimeType(), 'image/'))
                                            <img src="{{ $bast_document->temporaryUrl() }}" alt="Preview Dokumen Baru" class="img-thumbnail" style="max-width: 200px;">
                                        @else
                                            <span>{{ $bast_document->getClientOriginalName() }} (File akan diupload)</span>
                                        @endif
                                    </div>
                                @elseif ($existing_bast_document_path)
                                    <div class="mt-2">
                                        <p class="mb-1">Dokumen Saat Ini:
                                            <a href="{{ Storage::url($existing_bast_document_path) }}" target="_blank">{{ $existing_bast_document_name ?? 'Lihat Dokumen' }}</a>
                                        </p>
                                    </div>
                                @endif
                            </div>

                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="closeModal">Batal</button>
                            <button type="submit" class="btn btn-primary">
                                <span wire:loading.remove wire:target="store">{{ $asset_handover_id ? 'Simpan Perubahan BAST' : 'Simpan BAST' }}</span>
                                <span wire:loading wire:target="store">Menyimpan...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal untuk Upload Dokumen BAST Saat Penyelesaian --}}
@if ($isUploadBastModalOpen && $handoverToCompleteId)
@php
    // Ambil detail handover untuk ditampilkan di modal (opsional, bisa juga lewat properti)
    // Sebaiknya properti $handoverToCompleteModel di-set di komponen PHP saat membuka modal
    // agar tidak query di Blade. Untuk contoh ini, kita asumsikan $handoverToCompleteId sudah ada.
    // $currentHandover = \App\Models\AssetHandover::find($handoverToCompleteId);
@endphp
<div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form wire:submit.prevent="uploadBastAndFinalize">
                <div class="modal-header">
                    <h5 class="modal-title">Upload Bukti BAST untuk Penyelesaian</h5>
                    <button type="button" class="btn-close" wire:click="closeUploadBastModal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Anda akan menyelesaikan BAST No. <strong>{{ \App\Models\AssetHandover::find($handoverToCompleteId)->handover_number ?? $handoverToCompleteId }}</strong>.</p>
                    <p>Silakan upload dokumen BAST (bukti serah terima) untuk melanjutkan.</p>

                    <div class="mb-3">
                        <label for="bastDocumentForCompletion" class="form-label">File Dokumen BAST (PDF, JPG, PNG - Maks 5MB) <span class="text-danger">*</span></label>
                        <input type="file" class="form-control @error('bastDocumentForCompletion') is-invalid @enderror" id="bastDocumentForCompletion" wire:model="bastDocumentForCompletion">
                        <div wire:loading wire:target="bastDocumentForCompletion" class="mt-1 text-primary">Uploading...</div>
                        @error('bastDocumentForCompletion') <span class="text-danger d-block">{{ $message }}</span> @enderror

                        @if ($bastDocumentForCompletion && method_exists($bastDocumentForCompletion, 'temporaryUrl'))
                            <div class="mt-2">
                                <p class="mb-1">Preview Dokumen Baru:</p>
                                @if(Str::startsWith($bastDocumentForCompletion->getMimeType(), 'image/'))
                                    <img src="{{ $bastDocumentForCompletion->temporaryUrl() }}" alt="Preview Dokumen Baru" class="img-thumbnail" style="max-width: 200px;">
                                @else
                                    <span>{{ $bastDocumentForCompletion->getClientOriginalName() }} (File akan diupload)</span>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeUploadBastModal">Batal</button>
                    <button type="submit" class="btn btn-success">
                        <span wire:loading.remove wire:target="uploadBastAndFinalize">Upload & Selesaikan BAST</span>
                        <span wire:loading wire:target="uploadBastAndFinalize">
                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                            Memproses...
                        </span>
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
    document.addEventListener('livewire:load', function () {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    });
    Livewire.hook('message.processed', (message, component) => {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            var existingTooltip = bootstrap.Tooltip.getInstance(tooltipTriggerEl);
            if (existingTooltip) {
                existingTooltip.dispose();
            }
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    });

    // Reset file input jika ada event dari server (setelah berhasil upload/reset)
    Livewire.on('fileInputReset', inputId => {
        const fileInput = document.getElementById(inputId || 'bast_document'); // Default ke 'bast_document'
        if(fileInput) {
            fileInput.value = null;
        }
    });
    window.addEventListener('confirm-finalize-handover', event => {
        if (confirm(event.detail.message)) {
            // Panggil method Livewire jika dikonfirmasi
            @this.call('doFinalizeHandover', event.detail.handoverId);
        }
    });
    // Scroll ke bawah untuk chat (jika ini bagian dari halaman chat, bisa disesuaikan)
    function scrollToBottomChat() {
        const chatBox = document.getElementById('chat-box-messages');
        if (chatBox) {
            chatBox.scrollTop = chatBox.scrollHeight;
        }
    }
    Livewire.on('message-sent', () => { scrollToBottomChat(); });
    Livewire.on('chat-opened', () => { setTimeout(() => { scrollToBottomChat(); }, 50); });
</script>
@endpush