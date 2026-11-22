<div>
    <div class="container-fluid py-4">
        <div class="nav-wrapper position-relative end-0">
            <ul class="nav nav-pills nav-fill p-1" role="tablist">
                <li class="nav-item">
                </li>
            </ul>
        </div>
        <div class="row">
            <div class="tab-content col-12">
                <div class="tab-pane fade active show card ">
                    <div class="card-header pb-0">
                        <div class="d-flex justify-content-between">
                            <h5 class="mb-0">Detail Users</h5>
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
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Diajukan Oleh</label>
                                    <input type="text" value="{{ Auth::user()->name }} ({{ Auth::user()->nik }})"
                                        class="form-control" readonly disabled>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Jobsite</label>
                                    <input type="text" value="{{ Auth::user()->jobsite->name ?? 'Tidak Terdefinisi' }}"
                                        class="form-control" readonly disabled>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="purpose" class="form-label">Tujuan/Keperluan Pengajuan <span
                                            class="text-danger">*</span></label>
                                    <textarea wire:model.defer="purpose" id="purpose"
                                        class="form-control @error('purpose') is-invalid @enderror" rows="3"
                                        placeholder="Contoh: Untuk karyawan baru, penggantian aset rusak, kebutuhan project..."></textarea>
                                    @error('purpose') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="needed_date" class="form-label">Tanggal Dibutuhkan <span
                                            class="text-danger">Standart Pengadaan 14 Hari</span></label>
                                    <input type="date" wire:model.defer="needed_date" id="needed_date"
                                        class="form-control @error('needed_date') is-invalid @enderror">
                                    @error('needed_date') <span class="text-danger small">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <h5 class="mt-4 text-primary">BERITA ACARA <span class="text-danger">Jika ada</span></h5>
                            <hr class="mt-0 mb-3">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="mb-3">
                                        <label for="ba_file" class="form-label">Upload File BA (PDF/DOC/DOCX/PNG/JPG -
                                            Maks 2MB)</label>
                                        <input type="file" multiple wire:model="ba_file" id="ba_file"
                                            class="form-control @error('ba_file.*') is-invalid @enderror @error('ba_file') is-invalid @enderror">

                                        <div wire:loading wire:target="ba_file" class="mt-1 small text-primary">
                                            Uploading...</div>

                                        {{-- Error jika array-nya bermasalah --}}
                                        @error('ba_file') <span class="text-danger small d-block">{{ $message }}</span>
                                        @enderror

                                        {{-- Error jika salah satu file di dalamnya bermasalah --}}
                                        @error('ba_file.*') <span class="text-danger small d-block">Salah satu file
                                        tidak valid atau terlalu besar.</span> @enderror
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="tab-content col-12">
                <div class="tab-pane fade active show card ">
                    <div class="card-header pb-0">
                        <div class="d-flex justify-content-between">
                            <h5 class="mb-0">Request Licenses</h5>
                            <div class="d-flex">
                                <button wire:click="addItem" class="btn btn-primary btn-sm mb-0">
                                    <i class="bx bx-bxs-file-plus me-1"></i> Add Items
                                </button>
                            </div>
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
                    <hr>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="row px-2 pt-2 gx-2 gy-2 mb-3">
                        </div>
                        <div class="table-responsive p-0 position-relative">
                            <div wire:loading wire:target="searche" class="position-absolute top-50 start-50"
                                style="z-index: 10;">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                            </div>
                            @forelse ($items as $index => $item)
                                <div class="card mb-3 shadow-sm" wire:key="request-item-{{ $index }}">
                                    <div
                                        class="card-header d-flex justify-content-between align-items-center py-2 bg-light">
                                        <h6 class="mb-0">Item #{{ $index + 1 }}</h6>
                                        <div>
                                            {{-- @if($item['is_standard'])
                                            <span class="badge bg-success">Item Standar</span>
                                            @endif --}}
                                            <button type="button" wire:click="removeItem({{ $index }})"
                                                class="btn btn-sm btn-outline-danger p-1" title="Hapus Item Ini">
                                                <i class="bx bx-bxs-trash"></i> Hapus
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            {{-- Dropdown Tipe Aset --}}
                                            <div class="col-md-3 mb-3">
                                                <label for="items_{{ $index }}_type" class="form-label">Category
                                                    Licnese<span class="text-danger">*</span></label>
                                                <select wire:model.live="items.{{ $index }}.software_category_id"
                                                    class="form-select @error('items.' . $index . '.software_category_id') is-invalid @enderror">
                                                    <option value="">-- Pilih Tipe --</option>
                                                    @foreach($categorys as $type)
                                                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('items.' . $index . '.software_category_id') <span
                                                class="text-danger small d-block">{{ $message }}</span> @enderror
                                            </div>

                                            {{-- Dropdown Merek Aset (Dinamis) --}}
                                            <div class="col-md-3 mb-3">
                                                <label for="items_{{ $index }}_brand" class="form-label">Manufacture License
                                                    <span class="text-danger">*</span></label>
                                                <select wire:model.live="items.{{ $index }}.software_manufacture_id"
                                                    class="form-select @error('items.' . $index . '.software_manufacture_id') is-invalid @enderror">
                                                    <option value="">-- Pilih Merek --</option>
                                                    @foreach($manufactures as $type)
                                                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('items.' . $index . '.software_manufacture_id') <span
                                                class="text-danger small d-block">{{ $message }}</span> @enderror
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label for="items_{{ $index }}_software_name" class="form-label">Software
                                                    Name <span class="text-danger">*</span></label>
                                                <input type="text" wire:model.defer="items.{{ $index }}.software_name"
                                                    class="form-control @error('items.' . $index . '.software_name') is-invalid @enderror">
                                                @error('items.' . $index . '.software_name') <span
                                                class="text-danger small d-block">{{ $message }}</span> @enderror
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label for="items_{{ $index }}_quantity" class="form-label">Software Seats
                                                    <span class="text-danger">*</span></label>
                                                <input type="number" wire:model.defer="items.{{ $index }}.quantity"
                                                    class="form-control @error('items.' . $index . '.quantity') is-invalid @enderror"
                                                    min="1">
                                                @error('items.' . $index . '.quantity') <span
                                                class="text-danger small d-block">{{ $message }}</span> @enderror
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label for="items_{{ $index }}_quantity" class="form-label">Harga Per
                                                    Item<span class="text-danger">*</span></label>
                                                <input type="number" wire:model.defer="items.{{ $index }}.price"
                                                    class="form-control @error('items.' . $index . '.price') is-invalid @enderror"
                                                    min="1">
                                                @error('items.' . $index . '.price') <span
                                                class="text-danger small d-block">{{ $message }}</span> @enderror
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label for="items_{{ $index }}_quantity" class="form-label">Total Harga<span
                                                        class="text-danger">*</span></label>
                                                <input type="number" wire:model.defer="items.{{ $index }}.total_price"
                                                    class="form-control @error('items.' . $index . '.total_price') is-invalid @enderror"
                                                    min="1">
                                                @error('items.' . $index . '.total_price') <span
                                                class="text-danger small d-block">{{ $message }}</span> @enderror
                                            </div>
                                        </div>
                                        <div class="row">

                                            <div class="col-md-12 mb-3">
                                                <label for="items_{{ $index }}_item_remarks"
                                                    class="form-label">Deskripsi/Spesifikasi Tambahan</label>
                                                <input type="text" wire:model.defer="items.{{ $index }}.remarks"
                                                    class="form-control @error('items.' . $index . '.remarks') is-invalid @enderror"
                                                    min="20">
                                                @error('items.' . $index . '.remarks') <span
                                                class="text-danger small d-block">{{ $message }}</span> @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty

                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="d-flex justify-content-center">
            <button wire:click="submitRequest" class="btn btn-primary btn-sm mb-0">
                <i class="bx bx-bxs-save me-1"></i> Simpan
            </button>
        </div>
    </div>
    {{-- @livewire('licenses.modal.checkout') --}}
</div>