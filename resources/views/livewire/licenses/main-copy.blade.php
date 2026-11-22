<div class="container-fluid py-4">
        <div class="nav-wrapper position-relative end-0">
            <ul class="nav nav-pills nav-fill p-1" role="tablist">
                <li class="nav-item">
                </li>
                {{-- <li class="nav-item ">
                    <a class="nav-link {{ $tab == 2 ? 'active' : '' }} mb-0 px-0 py-1" data-bs-toggle="tab"
                        wire:click="$set('tab', 2)" role="tab" aria-selected="false">
                        <i class="bx bx-cart-add me-1"></i> QC From PO
                        @if(isset($waittingGRCount) && $waittingGRCount > 0)
                        <span class="badge badge-pill bg-danger ms-1">{{ $waittingGRCount }}</span>
                        @endif
                    </a>
                </li> --}}
                {{-- <li class="nav-item">
                    <a class="nav-link mb-0 px-0 py-1" data-bs-toggle="tab" href="#menu2" role="tab"
                        aria-selected="false">
                        <i class="fas fa-cog me-1"></i> Menu 2
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link mb-0 px-0 py-1" data-bs-toggle="tab" href="#menu3" role="tab"
                        aria-selected="false">
                        <i class="fas fa-chart-bar me-1"></i> Menu 3
                    </a>
                </li> --}}
            </ul>
        </div>
        <div class="row">
            <div class="tab-content col-12">

                <div class="tab-pane fade active show card ">
                    <div class="card-header pb-0">
                        <div class="d-flex justify-content-between">
                            <h5 class="mb-0">Manajemen Licenses</h5>
                            <div class="d-flex">
                                {{-- Tombol BARU untuk Ekspor --}}
                                <button wire:click="exportExcel" class="btn btn-success btn-sm mb-0 me-2">
                                    <span wire:loading.remove wire:target="exportExcel">
                                        <i class="bx bx-bxs-file-export me-1"></i> Ekspor ke Excel
                                    </span>
                                    <span wire:loading wire:target="exportExcel">
                                        <span class="spinner-border spinner-border-sm" role="status"
                                            aria-hidden="true"></span>
                                        Mengekspor...
                                    </span>
                                </button>

                                {{-- Tombol Tambah Aset --}}
                                <button wire:click="create()" class="btn btn-primary btn-sm mb-0">
                                    <i class="bx bx-bxs-file-plus me-1"></i> Tambah Licens Baru
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
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="row px-2 pt-2 gx-2 gy-2 mb-3">
                            <div class="col-md-4">
                                <input wire:model.live.debounce.300ms="search" type="text"
                                    class="form-control form-control-sm" placeholder="Cari No Aset, SN, Tipe...">
                            </div>
                            <div class="col-md-4">
                                <select wire:model.live="selectedJobsite" class="form-select form-select-sm">
                                    <option value="">Semua Jobsite</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <select wire:model.live="selectedStatus" class="form-select form-select-sm">
                                    <option value="">Semua Status</option>
                                </select>
                            </div>
                        </div>
                        <div class="table-responsive p-0">

                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7"
                                            wire:click="sortBy('name')" style="cursor:pointer">Name
                                            @if($sortField === 'name')
                                                {!! $sortDirection === 'asc' ? '▲' : '▼' !!}
                                            @endif
                                        </th>
                                        <th
                                            class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">
                                            Product Key</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2"
                                            wire:click="sortBy('expiration_date')" style="cursor:pointer">Expiration
                                            Date
                                            @if($sortField === 'expiration_date')
                                                {!! $sortDirection === 'asc' ? '▲' : '▼' !!}
                                            @endif
                                        </th>
                                        <th
                                            class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">
                                            Category</th>
                                        <th
                                            class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">
                                            Manufacturer</th>
                                        <th
                                            class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">
                                            Seats</th>
                                        <th
                                            class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">
                                            Used</th>
                                        <th
                                            class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">
                                            Free</th>
                                        <th
                                            class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">
                                            Checkout</th>
                                        <th
                                            class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">
                                            Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($softwareCollection as $sofware)
                                        <tr>
                                            <td>
                                                <p class="text-xs font-weight-bold mb-0">{{ $sofware->name ?? 'N/A' }}</p>
                                            </td>
                                            <td>
                                                <p class="text-xs font-weight-bold mb-0"
                                                    title="{{ $sofware->product_key ?? 'N/A' }}">
                                                    {{ \Illuminate\Support\Str::limit($sofware->product_key ?? 'N/A', 20, '...') }}
                                                </p>
                                            </td>
                                            <td>
                                                <p class="text-xs font-weight-bold mb-0">
                                                    {{ $sofware->expiration_date ? \Carbon\Carbon::parse($sofware->expiration_date)->format('d M Y') : '-' }}
                                                </p>
                                            </td>
                                            <td>
                                                <p class="text-xs font-weight-bold mb-0">
                                                    {{ $sofware->softwareCategory->name ?? '-' }}
                                                </p>
                                            </td>
                                            <td>
                                                <p class="text-xs font-weight-bold mb-0">
                                                    {{ $sofware->softwareManufacturer->name ?? '-' }}
                                                </p>
                                            </td>
                                            <td>
                                                <span class="badge bg-info text-dark">
                                                    {{ $sofware->seats ?? 0 }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-success text-white">
                                                    {{ $sofware->active_checkout_count ?? 0 }}
                                                </span>
                                            </td>
                                            <td>
                                                {{ ($sofware->seats ?? 0) - ($sofware->active_checkout_count ?? 0) }}
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-info shadow" type="button"
                                                    data-bs-toggle="dropdown" aria-expanded="false" title="Checkout"
                                                    @if(($sofware->seats ?? 0) - ($sofware->active_checkout_count ?? 0) === 0)
                                                    disabled @endif>
                                                    <a class="dropdown-item d-flex align-items-center"
                                                        wire:click="CheckoutSoftware({{ $sofware->id }})">
                                                        <span>Checkout</span>
                                                    </a>
                                                </button>
                                            </td>
                                            <td class="align-middle">
                                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-secondary shadow"
                                                            type="button" data-bs-toggle="dropdown" aria-expanded="false"
                                                            title="Aksi">
                                                            <i class="bx bx-dots-horizontal"></i>
                                                        </button>
                                                        <ul class="dropdown-menu animate__animated animate__fadeIn">
                                                            <li>
                                                                <a class="dropdown-item d-flex align-items-center"
                                                                    href="{{ route('licenses.detail', $sofware->id) }}">
                                                                    <i class="bx bxs-bullseye me-2 text-warning"></i>
                                                                    <span>View</span>
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item d-flex align-items-center"
                                                                    wire:click="edit({{ $sofware->id }})">
                                                                    <i class="bx bx-edit-alt me-2 text-warning"></i>
                                                                    <span>Edit</span>
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item d-flex align-items-center"
                                                                    wire:click="confirmDelete({{ $sofware->id }})">
                                                                    <i class="bx bx-trash me-2 text-danger"></i>
                                                                    <span>Hapus</span>
                                                                </a>
                                                            </li>
                                                            @if ($sofware->expiration_date && now()->diffInDays(\Carbon\Carbon::parse($sofware->expiration_date)) <= 30)
                                                                {{--
                                                                @dump(now()->diffInDays(\Carbon\Carbon::parse($sofware->expiration_date)))
                                                                --}}
                                                                <a class="dropdown-item d-flex align-items-center"
                                                                    href="{{ route('requests.license.renew', $sofware->id) }}">
                                                                    <i class="bx bx-calendar-edit me-2 text-danger"></i>
                                                                    <span>Renew</span>
                                                                </a>
                                                                </li>
                                                            @endif
                                                        </ul>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-4">Tidak ada License ditemukan.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="px-4 pt-3">
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
        @if ($isModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered modal-xl"> {{-- Modal lebih besar --}}
                <div class="modal-content">
                    <form wire:submit.prevent="store">
                        <div class="modal-header">
                            <h5 class="modal-title">Tambah License Baru</h5>
                            <button type="button" class="btn-close" wire:click="closeModal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="name" class="form-label">Nama License <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                                        wire:model.defer="name">
                                    @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="category_id_modal_asset" class="form-label">Category <span
                                            class="text-danger">*</span></label>
                                    <select wire:model="category_id" wire:change="changeCategoryId"
                                        id="category_id_modal_asset"
                                        class="form-select @error('category_id') is-invalid @enderror">
                                        <option value="">-- Pilih Tipe --</option>
                                        @foreach ($licenseCategory as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }} </option>
                                        @endforeach
                                    </select>
                                    @error('category_id') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="manufacture_id_modal_asset" class="form-label">Manufacture <span
                                            class="text-danger">*</span></label>
                                    <select wire:model="manufacture_id" id="manufacture_id_modal_asset"
                                        class="form-select @error('manufacture_id') is-invalid @enderror">
                                        <option value="">-- Pilih Tipe --</option>
                                        @foreach ($licenseManufactur as $manufacture)
                                            <option value="{{ $manufacture->id }}">{{ $manufacture->name }} </option>
                                        @endforeach
                                    </select>
                                    @error('manufacture_id') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="seats_modal" class="form-label">Seats <span
                                            class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('seats') is-invalid @enderror"
                                        id="seats_modal" wire:model.defer="seats">
                                    @error('seats') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                            </div>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="license_key" class="form-label">License Key <span
                                            class="text-danger">*</span></label>
                                    <textarea class="form-control @error('license_key') is-invalid @enderror"
                                        id="license_key" wire:model.defer="license_key" rows="3"></textarea>
                                    @error('license_key') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="order_number" class="form-label">Order Number</label>
                                    <input type="text" class="form-control @error('order_number') is-invalid @enderror"
                                        id="order_number" wire:model.defer="order_number">
                                    @error('order_number') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="purchase_cost" class="form-label">Purchase Cost</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" min="0"
                                            class="form-control @error('purchase_cost') is-invalid @enderror"
                                            id="purchase_cost" wire:model.defer="purchase_cost"
                                            placeholder="Masukkan harga pembelian">
                                        <span class="input-group-text">IDR</span>
                                    </div>
                                    @error('purchase_cost') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="purchase_date" class="form-label">Purchase Date</label>
                                    <input type="date" class="form-control @error('purchase_date') is-invalid @enderror"
                                        id="purchase_date" wire:model.defer="purchase_date">
                                    @error('purchase_date') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="expiration_date" class="form-label">Expiration Date <span
                                            class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('expiration_date') is-invalid @enderror"
                                        id="expiration_date" wire:model.defer="expiration_date">
                                    @error('expiration_date') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="po_number" class="form-label">No. PO</label>
                                    <input type="text" class="form-control @error('po_number') is-invalid @enderror"
                                        id="po_number" wire:model.defer="po_number">
                                    @error('po_number') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            {{-- <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="jobsite_id_modal_asset" class="form-label">Jobsite <span
                                            class="text-danger">*</span></label>
                                    <select wire:model.live="jobsite_id" wire:change="numberAssetTemporarry"
                                        id="jobsite_id_modal_asset"
                                        class="form-select @error('jobsite_id') is-invalid @enderror">
                                        @foreach ($jobsites as $jobsite)
                                        <option value="{{ $jobsite->id }}">{{ $jobsite->name }} ({{ $jobsite->code }})
                                        </option>
                                        @endforeach
                                    </select>
                                    @error('jobsite_id') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="status_modal_departement" class="form-label">Asset Departement <span
                                            class="text-danger">*</span></label>
                                    <select wire:model.live="departement_id" id="status_modal_departement"
                                        class="form-select @error('departement_id') is-invalid @enderror">
                                        @foreach ($departements as $departement)
                                        <option value="{{ $departement->id }}">{{ $departement->name }} ({{
                                            $departement->code }})</option>
                                        @endforeach
                                    </select>
                                    @error('departement_id') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="status_modal_cost_center" class="form-label">Cost Center</label>
                                    <input type="text" class="form-control @error('cost_center') is-invalid @enderror"
                                        id="status_modal_cost_center" wire:model.live="cost_center" readonly>
                                    @error('cost_center') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4 mb-3" hidden>
                                    <label for="status_modal_asset" class="form-label">Status <span
                                            class="text-danger">*</span></label>
                                    <select wire:model.defer="status" id="status_modal_asset"
                                        class="form-select @error('status') is-invalid @enderror" disabled>
                                        @foreach ($statuses as $stat)
                                        <option value="{{ $stat }}">{{ $stat }}</option>
                                        @endforeach
                                    </select>
                                    @error('status') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4 mb-3" hidden>
                                    <label for="holder_user_id_modal_asset" class="form-label">Pemegang Aset</label>
                                    <select wire:model.defer="holder_user_id" id="holder_user_id_modal_asset"
                                        class="form-select @error('holder_user_id') is-invalid @enderror" disabled>
                                        <option value="">-- Tidak Ada Pemegang --</option>
                                        @foreach ($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->nik }})</option>
                                        @endforeach
                                    </select>
                                    @error('holder_user_id') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div> --}}
                            <div class="mb-3">
                                <label for="note" class="form-label">Note :</label>
                                <textarea class="form-control @error('note') is-invalid @enderror" id="note"
                                    wire:model.defer="note" rows="2"></textarea>
                                @error('note') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="closeModal">Batal</button>
                            <button type="submit" class="btn btn-primary">
                                <span wire:loading.remove wire:target="store">Simpan</span>
                                <span wire:loading wire:target="store">Menyimpan...</span>
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