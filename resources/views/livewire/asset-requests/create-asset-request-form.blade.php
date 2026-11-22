<div class="container-fluid py-4">
    @if (empty(Auth::user()->position->job_description_name) )
    <div class="container-fluid py-4">
        <div class="card-body">
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-danger text-center py-4">
                        <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                        <h5 class="mb-2">JOBDESK JABATAN <b>{{Auth::user()->position->name}}</b> BELUM DI UPLOAD!</h5>
                        {{-- <p class="mb-0">untuk upload SO atau Jobdesk <a href="{{ route('myprofil') }}"> Klik disini</a></p> --}}
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- @elseif($request_active)   
    <div class="container-fluid py-4">
        <div class="card-body">
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-danger text-center py-4">
                        <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                        <h5 class="mb-2">Masi ada pengajuan belum selesai validasi / On Progress</h5>
                    </div>
                </div>
            </div>
        </div>
    </div> --}}
    @else
        @if (!empty($items))
            {{-- @if (empty(Auth::user()->manager_id) && Auth::user()->department->code !='DIR')
                <div class="container-fluid py-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                <div class="alert alert-danger text-center py-4">
                                    <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                                    <h5 class="mb-2">Anda belum memilih atasan langsung</h5>
                                    <p class="mb-0"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>   
            @else --}}
                <form wire:submit.prevent="submitRequest" class="card" id="assetRequestForm">
                    <div class="card-header border-bottom">
                        <h5 class="card-title mb-0">Form Pengajuan Aset Baru</h5>
                        <p class="text-muted small mb-0">Item akan terisi otomatis sesuai standar jabatan Anda. Anda bisa menambahkan item non-standar jika diperlukan.</p>
                    </div>
                    <div class="card-body">
                        {{-- Notifikasi --}}
                        @if (session()->has('message'))
                            <div class="alert alert-success">{{ session('message') }}</div>
                        @endif
                        @if (session()->has('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif
            
                        {{-- Bagian 1: Informasi Umum Pengajuan --}}
                        <h6 class="text-primary">INFORMASI UMUM</h6>
                        <hr class="mt-0 mb-3">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Diajukan Oleh</label>
                                <input type="text" value="{{ Auth::user()->name }} ({{ Auth::user()->nik }})" class="form-control" readonly disabled>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Jobsite</label>
                                <input type="text" value="{{ Auth::user()->jobsite->name ?? 'Tidak Terdefinisi' }}" class="form-control" readonly disabled>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="purpose" class="form-label">Tujuan/Keperluan Pengajuan <span class="text-danger">*</span></label>
                                <textarea wire:model.defer="purpose" id="purpose" class="form-control @error('purpose') is-invalid @enderror" rows="3" placeholder="Contoh: Untuk karyawan baru, penggantian aset rusak, kebutuhan project..."></textarea>
                                @error('purpose') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="needed_date" class="form-label">Tanggal Dibutuhkan <span class="text-danger">Standart Pengadaan 14 Hari</span></label>
                                <input type="date" wire:model.defer="needed_date" id="needed_date" class="form-control @error('needed_date') is-invalid @enderror">
                                @error('needed_date') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <h5 class="mt-4 text-primary">BERITA ACARA <span class="text-danger">Jika ada</span></h5>
                        <hr class="mt-0 mb-3">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="mb-3">
                                    <label for="ba_budget_outside" class="form-label">Upload File BA Diluar Budget (PDF/DOC/DOCX/PNG/JPG - Maks 2MB)</label>
                                    <input type="file" wire:model="ba_budget_outside" id="ba_budget_outside" class="form-control @error('ba_budget_outside') is-invalid @enderror">
                                    <div wire:loading wire:target="ba_budget_outside" class="mt-1 small text-primary">Uploading...</div>
                                    @error('ba_budget_outside') <span class="text-danger small">{{ $message }}</span> @enderror
                                    
                                    @if($ba_budget_outside)
                                        <div class="mt-2 d-flex align-items-center">
                                            <small class="text-muted">Preview: {{ $ba_budget_outside->getClientOriginalName() }}</small>
                                            <button type="button" class="btn btn-link btn-sm p-0 ms-2" data-bs-toggle="modal" data-bs-target="#baBudgetOutsideModal">
                                                <i class="bx bx-show"></i> Lihat File
                                            </button>
                                            <button type="button" class="btn btn-link btn-sm text-danger p-0 ms-2" wire:click.prevent="$set('ba_budget_outside', null)">
                                                <i class="bx bx-trash"></i> Hapus
                                            </button>
                                        </div>

                                        <!-- Modal Preview BA Diluar Budget -->
                                        <div class="modal fade" id="baBudgetOutsideModal" tabindex="-1" aria-labelledby="baBudgetOutsideModalLabel" aria-hidden="true">
                                            <div class="modal-dialog modal-xl modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="baBudgetOutsideModalLabel">Preview File BA Diluar Budget</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body" style="min-height:60vh;">
                                                        @php
                                                            $mime = $ba_budget_outside->getMimeType();
                                                        @endphp
                                                        @if(Str::startsWith($mime, 'image/'))
                                                            <img src="{{ $ba_budget_outside->temporaryUrl() }}" alt="Preview" class="img-fluid w-100" style="max-height: 70vh; object-fit: contain;">
                                                        @elseif($mime === 'application/pdf')
                                                        <object data="{{ $ba_budget_outside->temporaryUrl() }}" type="application/pdf" width="100%" height="600px">
                                                            <p>Browser kamu tidak mendukung preview PDF. 
                                                                <a href="{{ $ba_budget_outside->temporaryUrl() }}">Klik di sini untuk download</a>.
                                                            </p>
                                                        </object>
                                                        @else
                                                            <p class="text-danger">Preview tidak tersedia untuk file ini.</p>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="mb-3">
                                    <label for="ba_damage" class="form-label">Upload File BA Kerusakan (PDF/DOC/DOCX/PNG/JPG - Maks 2MB)</label>
                                    <input type="file" wire:model="ba_damage" id="ba_damage" class="form-control @error('ba_damage') is-invalid @enderror">
                                    <div wire:loading wire:target="ba_damage" class="mt-1 small text-primary">Uploading...</div>
                                    @error('ba_damage') <span class="text-danger small">{{ $message }}</span> @enderror
                                    
                                    @if($ba_damage)
                                        <div class="mt-2 d-flex align-items-center">
                                            <small class="text-muted">Preview: {{ $ba_damage->getClientOriginalName() }}</small>
                                            <button type="button" class="btn btn-link btn-sm p-0 ms-2" data-bs-toggle="modal" data-bs-target="#baDamageModal">
                                                <i class="bx bx-show"></i> Lihat File
                                            </button>
                                            <button type="button" class="btn btn-link btn-sm text-danger p-0 ms-2" wire:click.prevent="$set('ba_damage', null)">
                                                <i class="bx bx-trash"></i> Hapus
                                            </button>
                                        </div>

                                        <!-- Modal Preview BA Kerusakan -->
                                        <div class="modal fade" id="baDamageModal" tabindex="-1" aria-labelledby="baDamageModalLabel" aria-hidden="true">
                                            <div class="modal-dialog modal-xl modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="baDamageModalLabel">Preview File BA Kerusakan</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body" style="min-height:60vh;">
                                                        @php
                                                            $mime = $ba_damage->getMimeType();
                                                        @endphp
                                                        @if(Str::startsWith($mime, 'image/'))
                                                            <img src="{{ $ba_damage->temporaryUrl() }}" alt="Preview" class="img-fluid w-100" style="max-height: 70vh; object-fit: contain;">
                                                        @elseif($mime === 'application/pdf')
                                                        <object data="{{ $ba_damage->temporaryUrl() }}" type="application/pdf" width="100%" height="600px">
                                                            <p>Browser kamu tidak mendukung preview PDF. 
                                                                <a href="{{ $ba_damage->temporaryUrl() }}">Klik di sini untuk download</a>.
                                                            </p>
                                                        </object>
                                                        @else
                                                            <p class="text-danger">Preview tidak tersedia untuk file ini.</p>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                   
                        {{-- Bagian 2: Item Aset yang Diajukan --}}
                        <h5 class="mt-4 text-primary">ITEM ASET YANG DIAJUKAN</h5>
                        <hr class="mt-0 mb-3">
                        {{-- @error('items') <div class="alert alert-danger text-white py-2 px-3 mb-2">{{ $message }}</div> @enderror --}}
                        @forelse($items as $index => $item)
                            <div class="card mb-3 shadow-sm" wire:key="request-item-{{ $index }}">
                                <div class="card-header d-flex justify-content-between align-items-center py-2 bg-light">
                                    <h6 class="mb-0">Item #{{ $index + 1 }}</h6>
                                    <div>
                                        @if($item['is_standard'])
                                            <span class="badge bg-success">Item Standar</span>
                                        @else
                                            <span class="badge bg-warning">Item Non-Standar</span>
                                        @endif
                                        @if(count($items)>1)
                                            <button type="button" wire:click="removeItem({{ $index }})" class="btn btn-sm btn-outline-danger p-1" title="Hapus Item Ini">
                                                <i class="bx bx-bxs-trash"></i> Hapus
                                            </button>
                                        @endif
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        {{-- Dropdown Tipe Aset --}}
                                        <div class="col-md-4 mb-3">
                                            <label for="items_{{ $index }}_type" class="form-label">Tipe Aset <span class="text-danger">*</span></label>
                                            <select wire:model.live="items.{{ $index }}.asset_type_id" class="form-select @error('items.'.$index.'.asset_type_id') is-invalid @enderror" @if($item['is_standard']) disabled @endif>
                                                <option value="">-- Pilih Tipe --</option>
                                                @foreach($allTypes as $type)
                                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('items.'.$index.'.asset_type_id') <span class="text-danger small d-block">{{ $message }}</span> @enderror
                                        </div>
            
                                        {{-- Dropdown Merek Aset (Dinamis) --}}
                                        <div class="col-md-4 mb-3">
                                            <label for="items_{{ $index }}_brand" class="form-label">Merek Aset <span class="text-danger">*</span></label>
                                            <select wire:model.live="items.{{ $index }}.asset_brand_id" class="form-select @error('items.'.$index.'.asset_brand_id') is-invalid @enderror" @if(empty($item['asset_type_id'])) disabled @endif @if($item['is_standard']) disabled @endif>
                                                <option value="">-- Pilih Merek --</option>
                                                @if(!empty($item['asset_type_id']))
                                                    @foreach($allBrands->where('asset_type_id', $item['asset_type_id']) as $brand)
                                                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            @error('items.'.$index.'.asset_brand_id') <span class="text-danger small d-block">{{ $message }}</span> @enderror
                                        </div>
            
                                        {{-- Dropdown Seri Aset (Dinamis) --}}
                                        <div class="col-md-4 mb-3">
                                            <label for="items_{{ $index }}_series" class="form-label">Seri Aset <span class="text-danger">*</span></label>
                                            <select wire:model.defer="items.{{ $index }}.asset_series_id" class="form-select @error('items.'.$index.'.asset_series_id') is-invalid @enderror" @if(empty($item['asset_brand_id'])) disabled @endif @if($item['is_standard']) disabled @endif>
                                                <option value="">-- Pilih Seri --</option>
                                                @if(!empty($item['asset_brand_id']))
                                                    @foreach($allSeries->where('asset_brand_id', $item['asset_brand_id']) as $series)
                                                        <option value="{{ $series->id }}">{{ $series->name }}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            @error('items.'.$index.'.asset_series_id') <span class="text-danger small d-block">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <label for="items_{{ $index }}_quantity" class="form-label">Jumlah <span class="text-danger">*</span></label>
                                            <input type="number" wire:model.defer="items.{{ $index }}.quantity" class="form-control @error('items.'.$index.'.quantity') is-invalid @enderror" min="1" @if($item['is_standard']) disabled @endif>
                                            @error('items.'.$index.'.quantity') <span class="text-danger small d-block">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="col-md-9 mb-3">
                                            <label for="items_{{ $index }}_item_description" class="form-label">Deskripsi/Spesifikasi Tambahan</label>
                                            <input type="text" wire:model.defer="items.{{ $index }}.item_description" class="form-control" @if($item['is_standard']) disabled @endif>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center p-3 border rounded">
                                <p class="text-muted mb-1">Tidak ada item dalam pengajuan.</p>
                            </div>
                        @endforelse
                        <button type="button" wire:click="addItem" class="btn btn-outline-secondary btn-sm mt-2">+ Tambah Item Non-Standar</button>
                        <hr class="my-4">
                        {{-- Bagian 3: Select IT --}}
                        {{-- <div class="mb-3">
                            <label for="it" class="form-label">IT yang Validasi<span class="text-danger">*</span></label>
                            <select wire:model.defer="itsite" class="form-select @error('itsite') is-invalid @enderror">
                                <option value="" selected>Pilih IT</option>
                                @forelse ($itne as $it)
                                    <option value="{{ $it->id }}">{{ $it->name }}</option>
                                @empty
                                    <option value="" disabled>Tidak ada IT Di Site ini</option>
                                @endforelse
                            </select>
                            @error('itsite') <span class="text-danger small d-block">{{ $message }}</span> @enderror
                        </div> --}}
                        <hr class="my-4">
                        {{-- Bagian 4: Catatan Umum --}}
                        <div class="mb-3">
                            <label for="remarks_overall_create" class="form-label">Catatan Umum Pengajuan</label>
                            <textarea wire:model.defer="remarks" id="remarks_overall_create" class="form-control @error('remarks') is-invalid @enderror" rows="2" placeholder="Catatan umum untuk seluruh pengajuan..."></textarea>
                            @error('remarks') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
            
                    </div>
                    <div class="card-footer text-end">
                        <button type="button" wire:click="resetFormAndLoadStandards" class="btn btn-warning">
                            <i class="fas fa-sync-alt me-1"></i> Reset ke Standar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <span wire:loading.remove wire:target="submitRequest">Ajukan Permintaan</span>
                            <span wire:loading wire:target="submitRequest">
                                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                Mengajukan...
                            </span>
                        </button>
                    </div>
                </form>
            {{-- @endif --}}
        @else
        <div class="container-fluid py-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        <div class="alert alert-danger text-center py-4">
                            <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                            <h5 class="mb-2">Anda tidak bisa mengjukan Asset</h5>
                            <p class="mb-0">Untuk jabatan Anda saat ini belum tersedia standarisasi asset.</p>
                            <p class="mb-0">Atau Standarisasi Asset sudah terpenuhi!</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>   
        @endif
    @endif
    
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:load', function () {
        // Untuk mereset file input secara manual jika diperlukan setelah submit berhasil
        window.Livewire.on('file-inputs-reset', () => {
            const fileInputs = ['strukturOrganisasiFile_create', 'otherSupportingDocumentFile_create'];
            fileInputs.forEach(id => {
                const inputElement = document.getElementById(id);
                if (inputElement) {
                    inputElement.value = null;
                }
            });
        });
    });
</script>
@endpush