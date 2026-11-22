<div>
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm bg-primary text-white">
                    <div class="card-body d-flex align-items-center">
                        <div class="bg-white bg-opacity-25 p-2 rounded me-3">
                            <i class="bx bx-key text-black fs-3"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 text-white-50 small">Total Licenses</h6>
                            <h4 class="mb-0 fw-bold">{{ $totalLicenses }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm bg-success text-white">
                    <div class="card-body d-flex align-items-center">
                        <div class="bg-white bg-opacity-25 p-2 rounded me-3">
                            <i class="bx bx-user-check text-black fs-3"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 text-white-50 small">Used Seats</h6>
                            <h4 class="mb-0 fw-bold">{{ $usedSeat }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-bottom py-3">
                <div class="d-flex justify-content-between">
                    <div class="d-flex">
                        <i class="bx bx-spreadsheet fs-4 text-primary me-2"></i>
                        <h5 class="mb-0 fw-bold">Manajemen Stok Lisensi</h5>
                    </div>
                    <div class="d-flex">
                        <button wire:click="exportExcel" class="btn btn-success btn-sm mb-0 me-2">
                            <span wire:loading.remove wire:target="exportExcel">
                                <i class="bx bx-bxs-file-export me-1"></i> Ekspor ke Excel
                            </span>
                            <span wire:loading wire:target="exportExcel">
                                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                Mengekspor...
                            </span>
                        </button>
                        {{-- <button wire:click="create()" class="btn btn-primary btn-sm mb-0">
                            <i class="bx bx-bxs-file-plus me-1"></i> Tambah Lisensi Baru
                        </button> --}}
                    </div>
                </div>
            </div>
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
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr class="small text-muted fw-bold">
                                <th class="ps-4">Software</th>
                                <th>Total Seats</th>
                                <th>Used</th>
                                <th>Available</th>
                                <th>Usage Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($softwareCollection as $software)
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-light p-2 rounded me-3 text-primary"><i
                                                    class="bx bx-desktop fs-4"></i></div>
                                            <div>
                                                <div class="fw-bold">{{ $software->name }}</div>
                                                <small class="text-muted">{{ $software->version }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="fw-bold">{{ $software->total_licenses_count }}</span></td>
                                    <td><span class="text-danger">{{ $software->active_checkout_count }}</span></td>
                                    <td><span class="text-success fw-bold">{{ $software->free_licenses_count }}</span></td>
                                    <td style="width: 200px;">
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar 
                                                        @if($software->usage_status_count >= 80)
                                                            bg-danger
                                                        @elseif($software->usage_status_count >= 50)
                                                            bg-warning
                                                        @else
                                                            bg-success
                                                        @endif
                                                        " role="progressbar"
                                                style="width: {{ $software->usage_status_count }}%"
                                                aria-valuenow="{{ $software->usage_status_count }}" aria-valuemin="0"
                                                aria-valuemax="100"></div>
                                        </div>
                                        {{-- <small class="text-muted mt-1 d-block">{{ $software->total_licenses_count > 0 ?
                                            round(($software->active_checkout_count / $software->total_licenses_count) *
                                            100) : 0 }}% Capacity Used</small> --}}
                                        <small
                                            class="text-muted mt-1 d-block">{{ $software->usage_status_count ? $software->usage_status_count : 0 }}%
                                            Capacity Used</small>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <button class="btn btn-light btn-sm rounded-circle" type="button"
                                                data-bs-toggle="dropdown">
                                                <i class="bx bx-dots-vertical-rounded"></i>
                                            </button>
                                            <ul class="dropdown-menu shadow-sm border-0">
                                                <li><a href="{{ route('licenses.detail', $software->id) }}"
                                                        class="dropdown-item" href="#"><i class="bx bx-list-ul me-2"></i>
                                                        List Pengguna</a></li>
                                                <li><a wire:click="create('{{ $software->id }}')" class="dropdown-item"
                                                        href="#"><i class="bx bx-plus-circle me-2"></i>
                                                        Tambah Seats</a></li>
                                                <li>
                                                <li>
                                                    <a class="dropdown-item">
                                                        {{-- href="{{ route('requests.license.renew', $sofware->id) }}" --}}
                                                        <i class="bx bx-calendar-edit me-2"></i>
                                                        <span>Renew</span>
                                                    </a>
                                                </li>
                                                <hr class="dropdown-divider">
                                                </li>
                                                <li><a class="dropdown-item text-danger" href="#"><i
                                                            class="bx bx-history me-2"></i> Log Riwayat</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @empty

                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <style>
        .btn-icon {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .btn-icon:hover {
            transform: translateY(-2px);
        }

        .progress {
            background-color: #f0f2f5;
            border-radius: 10px;
        }

        .table th {
            border-top: none !important;
        }
    </style>

    @if ($isModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content border-0 shadow-lg">
                    <form wire:submit.prevent="store">
                        <div class="modal-header bg-primary text-white py-3">
                            <h5 class="modal-title fw-bold text-white d-flex align-items-center">
                                <i class="bx bx-key me-2 fs-3"></i>
                                Registration New License
                            </h5>
                            <button type="button" class="btn-close btn-close-white" wire:click="closeModal"
                                aria-label="Close"></button>
                        </div>

                        <div class="modal-body p-4">
                            <div class="container-fluid">

                                <div class="d-flex align-items-center mb-3">
                                    <i class="bx bx-info-circle text-primary me-2"></i>
                                    <h6 class="mb-0 fw-bold text-uppercase small" style="letter-spacing: 1px;">General
                                        Information</h6>
                                </div>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-12">
                                        <label for="name" class="form-label fw-semibold small">Nama Software <span
                                                class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i
                                                    class="bx bx-rename"></i></span>
                                            <input type="text"
                                                class="form-control bg-light border-start-0 @error('name') is-invalid @enderror"
                                                id="name" wire:model.defer="name"
                                                placeholder="Contoh: Microsoft Office 365 Business" disabled>
                                        </div>
                                        @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold small">Category <span
                                                class="text-danger">*</span></label>
                                        <select wire:model="category_id"
                                            class="form-select bg-light @error('category_id') is-invalid @enderror"
                                            disabled>
                                            <option value="">-- Pilih Kategori --</option>
                                            @foreach ($licenseCategory as $category)
                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('category_id') <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold small">Manufacture <span
                                                class="text-danger">*</span></label>
                                        <select wire:model="manufacture_id"
                                            class="form-select bg-light @error('manufacture_id') is-invalid @enderror"
                                            disabled>
                                            <option value="">-- Pilih Manufacture --</option>
                                            @foreach ($licenseManufactur as $manufacture)
                                                <option value="{{ $manufacture->id }}">{{ $manufacture->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('manufacture_id') <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="d-flex align-items-center mb-3 mt-4">
                                    <i class="bx bx-key text-primary me-2"></i>
                                    <h6 class="mb-0 fw-bold text-uppercase small" style="letter-spacing: 1px;">Seat & Key
                                        Assignment</h6>
                                </div>

                                <div class="card border-0 bg-light-subtle shadow-sm mb-4">
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label fw-semibold small">Jumlah Seats yang Dibeli <span
                                                        class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-white border-end-0"><i
                                                            class="bx bx-list-ol"></i></span>
                                                    <input type="number" min="1" max="100"
                                                        class="form-control border-start-0 @error('seats') is-invalid @enderror"
                                                        wire:model.live="seats" placeholder="Contoh: 5">
                                                </div>
                                                <small class="text-muted mt-1 d-block" style="font-size: 0.7rem;">
                                                    Sistem akan membuatkan baris input sesuai angka ini.
                                                </small>
                                            </div>

                                            <div class="col-md-8">
                                                <label class="form-label fw-semibold small">Detail Per Seat / License
                                                    Key</label>
                                                <div class="bg-white rounded border p-3"
                                                    style="max-height: 300px; overflow-y: auto;">
                                                    @if($seats > 0 && $seats <= 100)
                                                        @for($i = 0; $i < $seats; $i++)
                                                            <div class="mb-3 last-child-mb-0">
                                                                <div class="input-group input-group-sm">
                                                                    <span class="input-group-text bg-light fw-bold"
                                                                        style="min-width: 80px;">Seat #{{ $i + 1 }}</span>
                                                                    <input type="text" wire:model.defer="license_keys.{{ $i }}"
                                                                        class="form-control"
                                                                        placeholder="Masukkan Serial Number / Product Key">
                                                                    <span class="input-group-text bg-white">
                                                                        <i class="bx bx-barcode-reader text-muted"></i>
                                                                    </span>
                                                                </div>
                                                                @error('license_keys.' . $i) <small
                                                                class="text-danger">{{ $message }}</small> @enderror
                                                            </div>
                                                        @endfor
                                                    @else
                                                        <div class="text-center py-4">
                                                            <i class="bx bx-info-circle fs-2 text-muted mb-2"></i>
                                                            <p class="text-muted small mb-0">Tentukan jumlah seat untuk mengisi
                                                                License Key</p>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center mb-3">
                                    <i class="bx bx-cart text-primary me-2"></i>
                                    <h6 class="mb-0 fw-bold text-uppercase small" style="letter-spacing: 1px;">Purchase
                                        Details</h6>
                                </div>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold small">Order Number</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i
                                                    class="bx bx-receipt"></i></span>
                                            <input type="text" class="form-control bg-light border-start-0"
                                                wire:model.defer="order_number" placeholder="PO-XXXXX">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold small">Purchase Cost</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">IDR</span>
                                            <input type="number" step="0.01" class="form-control bg-light border-start-0"
                                                wire:model.defer="purchase_cost" placeholder="0.00">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold small">No. PO</label>
                                        <input type="text" class="form-control bg-light" wire:model.defer="po_number"
                                            placeholder="Masukkan Nomor PO">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small">Purchase Date</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i
                                                    class="bx bx-calendar-event"></i></span>
                                            <input type="date" class="form-control bg-light border-start-0"
                                                wire:model.defer="purchase_date">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small text-danger">Expiration Date <span
                                                class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span
                                                class="input-group-text bg-danger-subtle border-danger border-end-0 text-danger"><i
                                                    class="bx bx-calendar-x"></i></span>
                                            <input type="date"
                                                class="form-control bg-light border-danger border-start-0 @error('expiration_date') is-invalid @enderror"
                                                wire:model.defer="expiration_date">
                                        </div>
                                        @error('expiration_date') <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label fw-semibold small">Notes</label>
                                    <textarea class="form-control bg-light" id="note" wire:model.defer="note" rows="2"
                                        placeholder="Tambahkan catatan jika diperlukan..."></textarea>
                                </div>

                            </div>
                        </div>

                        <div class="modal-footer bg-light border-top py-3">
                            <button type="button" class="btn btn-link text-secondary fw-semibold text-decoration-none px-4"
                                wire:click="closeModal">Batal</button>
                            <button type="submit" class="btn btn-primary px-5 shadow-sm d-flex align-items-center">
                                <span wire:loading.remove wire:target="store">
                                    <i class="bx bx-save me-2"></i> Simpan Data
                                </span>
                                <span wire:loading wire:target="store">
                                    <span class="spinner-border spinner-border-sm me-2" role="status"
                                        aria-hidden="true"></span>
                                    Menyimpan...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
    @livewire('licenses.modal.checkout')
</div>
@push('styles')
    <style>
        .sortable {
            cursor: pointer;
        }

        .sortable:hover {
            color: #0d6efd;
        }
    </style>
@endpush