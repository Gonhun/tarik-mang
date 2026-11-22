<div >
    <div class="container-fluid py-4" >
        <div class="nav-wrapper position-relative end-0">
            <ul class="nav nav-pills nav-fill p-1" role="tablist">
                <li class="nav-item">
                </li>
                {{-- <li class="nav-item ">
                    <a class="nav-link {{ $tab == 2 ? 'active' : '' }} mb-0 px-0 py-1" data-bs-toggle="tab" wire:click="$set('tab', 2)" role="tab" aria-selected="false">
                        <i class="bx bx-cart-add me-1"></i> QC From PO
                        @if(isset($waittingGRCount) && $waittingGRCount > 0)
                            <span class="badge badge-pill bg-danger ms-1">{{ $waittingGRCount }}</span>
                        @endif
                    </a>
                </li> --}}
                {{-- <li class="nav-item">
                    <a class="nav-link mb-0 px-0 py-1" data-bs-toggle="tab" href="#menu2" role="tab" aria-selected="false">
                        <i class="fas fa-cog me-1"></i> Menu 2
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link mb-0 px-0 py-1" data-bs-toggle="tab" href="#menu3" role="tab" aria-selected="false">
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
                            <h5 class="mb-0">Manajemen Category Licenses</h5>
                            <div class="d-flex">
                                {{-- Tombol BARU untuk Ekspor --}}
                                {{-- <button wire:click="exportExcel" class="btn btn-success btn-sm mb-0 me-2">
                                    <span wire:loading.remove wire:target="exportExcel">
                                        <i class="bx bx-bxs-file-export me-1"></i> Ekspor ke Excel
                                    </span>
                                    <span wire:loading wire:target="exportExcel">
                                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                        Mengekspor...
                                    </span>
                                </button> --}}
                                
                                {{-- Tombol Tambah Aset --}}
                                <button wire:click="create()" class="btn btn-primary btn-sm mb-0">
                                    <i class="bx bx-bxs-file-plus me-1"></i> Tambah Category Baru
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
                            <div class="col-md-12">
                                <input wire:model.live.debounce.300ms="searche" type="text" class="form-control form-control-sm" placeholder="Cari No Aset, SN, Tipe...">
                            </div>
                            {{-- <div class="col-md-4">
                                <select wire:model.live="selectedJobsite" class="form-select form-select-sm">
                                    <option value="">Semua Jobsite</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <select wire:model.live="selectedStatus" class="form-select form-select-sm">
                                    <option value="">Semua Status</option>
                                </select>
                            </div> --}}
                        </div>
                        <div class="table-responsive p-0 position-relative" style="min-height:180px;" >
                            <div wire:loading wire:target="searche" class="position-absolute top-50 start-50" style="z-index: 10;">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                            </div>
                           
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ">#</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Name</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Enable</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($softwareCollection as $sofware)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td> 
                                            <td><p class="text-xs font-weight-bold mb-0">{{ $sofware->name ?? 'N/A' }}</p></td>
                                            <td>
                                                <input 
                                                    class="form-check-input" type="checkbox"
                                                    @checked($sofware->enable == 1) 
                                                    disabled
                                                >
                                            </td>
                                            <td class="align-middle">
                                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-secondary shadow" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Aksi">
                                                            <i class="bx bx-dots-horizontal"></i>
                                                        </button>
                                                        <ul class="dropdown-menu animate__animated animate__fadeIn">
                                                            <li>
                                                                <a class="dropdown-item d-flex align-items-center" wire:click="edit({{ $sofware->id }})">
                                                                    <i class="bx bx-edit-alt me-2 text-warning"></i>
                                                                    <span>Edit</span>
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item d-flex align-items-center" wire:click="confirmDelete({{ $sofware->id }})">
                                                                    <i class="bx bx-trash me-2 text-danger"></i>
                                                                    <span>Hapus</span>
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center py-4">Tidak ada License ditemukan.</td></tr>
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
            <div class="modal-dialog modal-dialog-centered modal-lg"> {{-- Modal lebih besar --}}
                <div class="modal-content">
                    <form wire:submit.prevent="store">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ $categoryId ? 'Edit  Category ' . $name : 'Tambah Category Baru'}}</h5>
                            <button type="button" class="btn-close" wire:click="closeModal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="name" class="form-label">Nama Category <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" wire:model="name">
                                    @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label for="name" class="form-label">Enbale <span class="text-danger">*</span></label>
                                    <input class="form-check-input" type="checkbox" value="" id="checkChecked" wire:model.live="checkChecked">
                                    @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
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
    {{-- @livewire('licenses.modal.checkout') --}}
</div>