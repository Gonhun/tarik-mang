<div>
    {{-- @dump($this->data_license) --}}
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom py-3">
                        <div class="d-flex align-items-center">
                            <i class="bx bx-file-blank fs-4 text-primary me-2"></i>
                            <h5 class="mb-0 fw-bold">Pengajuan Renew License</h5>
                        </div>
                    </div>

                    <div class="card-body">
                        @if (session()->has('success'))
                            <div class="alert alert-success text-black alert-dismissible fade show" role="alert">
                                <i class="bx bx-check-circle me-2"></i> {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif
                        @if (session()->has('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bx bx-error-circle me-2"></i> {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-muted">
                                    <i class="bx bx-user me-1"></i> Diajukan Oleh
                                </label>
                                <input type="text" value="{{ Auth::user()->name }} ({{ Auth::user()->nik }})"
                                    class="form-control bg-light border-0 shadow-none" readonly disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-muted">
                                    <i class="bx bx-map-pin me-1"></i> Jobsite
                                </label>
                                <input type="text" value="{{ Auth::user()->jobsite->name ?? 'Tidak Terdefinisi' }}"
                                    class="form-control bg-light border-0 shadow-none" readonly disabled>
                            </div>
                        </div>

                        <div class="mt-4 pt-2">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bx bx-upload fs-5 text-primary me-2"></i>
                                <h6 class="mb-0 fw-bold">BERITA ACARA <span
                                        class="badge bg-danger-subtle text-danger ms-2" style="font-size: 0.65rem;">Jika
                                        ada</span></h6>
                            </div>
                            <hr class="mt-0 mb-3 opacity-10">

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="upload-wrapper p-3 border border-dashed rounded bg-light-subtle">
                                        <label for="ba_file" class="form-label fw-semibold small">Upload File BA</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white border-end-0"><i
                                                    class="bx bx-cloud-upload"></i></span>
                                            <input type="file" multiple wire:model="ba_file" id="ba_file"
                                                class="form-control border-start-0 @error('ba_file.*') is-invalid @enderror @error('ba_file') is-invalid @enderror">
                                        </div>
                                        <div class="form-text mt-2" style="font-size: 0.75rem;">
                                            Maksimal 2MB per file (PDF, DOC, PNG, JPG).
                                        </div>

                                        <div wire:loading wire:target="ba_file" class="mt-2">
                                            <div class="spinner-border spinner-border-sm text-primary me-1"
                                                role="status"></div>
                                            <span class="small text-primary">Uploading...</span>
                                        </div>

                                        @error('ba_file') <span class="text-danger small d-block mt-1"><i
                                        class="bx bx-error me-1"></i>{{ $message }}</span> @enderror
                                        @error('ba_file.*') <span class="text-danger small d-block mt-1"><i
                                            class="bx bx-error me-1"></i>Salah satu file tidak valid.</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom py-3">
                        <div class="d-flex align-items-center">
                            <i class="bx bx-key fs-4 text-primary me-2"></i>
                            <h5 class="mb-0 fw-bold">Licenses Detail</h5>
                        </div>
                    </div>

                    <div class="card-body p-4">
                        <div class="item-card border rounded p-3 bg-white shadow-sm mb-3">
                            <div class="d-flex align-items-center mb-3 pb-2 border-bottom border-light">
                                <span class="badge bg-primary me-2">#Item 1</span>
                                <small class="text-muted fw-bold text-uppercase">Informasi Perangkat Lunak</small>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold small">Category License</label>
                                    <input type="text" wire:model="software_category"
                                        class="form-control bg-light border-0" disabled>
                                    @error('software_category') <span class="text-danger small">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold small">Manufacture License</label>
                                    <input type="text" wire:model="software_manufacture"
                                        class="form-control bg-light border-0" disabled>
                                    @error('software_manufacture') <span class="text-danger small">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold small">Software Name</label>
                                    <input type="text" wire:model="software_name" class="form-control bg-light border-0"
                                        disabled>
                                    @error('software_name') <span class="text-danger small">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold small">Seats / Quantity</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-0"><i
                                                class="bx bx-hash"></i></span>
                                        <input type="number" wire:model="software_seat"
                                            class="form-control bg-light border-0" disabled>
                                    </div>
                                    @error('software_seat') <span class="text-danger small">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold small">Harga Per Item</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-0">Rp</span>
                                        <input type="number" wire:model="software_cost"
                                            class="form-control bg-light border-0" disabled>
                                    </div>
                                    @error('software_cost') <span class="text-danger small">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-md-9">
                                    <label class="form-label fw-semibold small text-primary">Deskripsi/Spesifikasi
                                        Tambahan</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white"><i class="bx bx-detail"></i></span>
                                        <input type="text" wire:model="renew_desc" class="form-control"
                                            placeholder="Tambahkan deskripsi jika ada...">
                                    </div>
                                    @error('renew_desc') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer bg-light border-top text-end py-3">
                        <button class="btn btn-secondary btn-sm px-4 me-2">
                            Batal
                        </button>
                        <button wire:click="save" class="btn btn-primary btn-sm px-4">
                            <i class="bx bx-save me-1"></i> Simpan Pengajuan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .upload-wrapper {
            border-style: dashed !important;
            border-width: 2px !important;
            border-color: #dee2e6 !important;
        }

        .form-control:focus {
            border-color: #5e72e4;
            box-shadow: 0 0 0 0.2rem rgba(94, 114, 228, 0.25);
        }

        .badge {
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>
</div>