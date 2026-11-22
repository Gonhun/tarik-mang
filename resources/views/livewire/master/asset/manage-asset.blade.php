<div>
    <div class="container-fluid py-4">
        <div class="nav-wrapper position-relative end-0">
            <ul class="nav nav-pills nav-fill p-1" role="tablist">
                <li class="nav-item">
                    <a class="nav-link {{ $tab == 1 ? 'active' : '' }} mb-0 px-0 py-1" data-bs-toggle="tab" wire:click="$set('tab', 1)" role="tab" aria-selected="true">
                        <i class="bx bx-home me-1"></i> Home
                    </a>
                    
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
               @if ($tab==1)
                <div class="tab-pane fade active show card ">
                    <div class="card-header pb-0">
                        <div class="d-flex justify-content-between">
                            <h5 class="mb-0">Manajemen Aset</h5>
                            <div class="d-flex">
                                {{-- Tombol BARU untuk Ekspor --}}
                                <button wire:click="exportExcel" class="btn btn-success btn-sm mb-0 me-2">
                                    <span wire:loading.remove wire:target="exportExcel">
                                        <i class="bx bx-bxs-file-export me-1"></i> Ekspor ke Excel
                                    </span>
                                    <span wire:loading wire:target="exportExcel">
                                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                        Mengekspor...
                                    </span>
                                </button>
                                
                                {{-- Tombol Tambah Aset --}}
                                <button wire:click="create()" class="btn btn-primary btn-sm mb-0">
                                    <i class="bx bx-bxs-file-plus me-1"></i> Tambah Aset Baru
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
                            <div class="col-md-3">
                                <input wire:model.live.debounce.300ms="search" type="text" class="form-control form-control-sm" placeholder="Cari No Aset, SN, Tipe...">
                            </div>
                            <div class="col-md-3">
                                <select wire:model.live="selectedJobsite" class="form-select form-select-sm">
                                    <option value="">Semua Jobsite</option>
                                    @foreach ($jobsites as $jobsite)
                                        <option value="{{ $jobsite->id }}">{{ $jobsite->code }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select wire:model.live="selectedStatus" class="form-select form-select-sm">
                                    <option value="">Semua Status</option>
                                    @foreach ($statuses as $stat)
                                        <option value="{{ $stat->status }}">{{ $stat->status }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                                
                            
                        </div>
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">No Aset</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Tipe</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Merek</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Seri</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">SN</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Status</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Pemegang</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Jobsite</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Entitas</th>
                                        <th class="text-secondary opacity-7"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($assetsCollection as $asset)
                                        <tr>
                                            <td><p class="text-xs font-weight-bold mb-0 px-3">{{ $asset->asset_number }}
                                                <a href="{{ route('assets.print-single-label', ['asset' => $asset->id]) }}" 
                                                    target="_blank" 
                                                    class="btn btn-link text-info text-gradient p-1 mb-0" 
                                                    data-bs-toggle="tooltip" 
                                                    title="Cetak Label Aset Ini">
                                                    <i class="bx bx-printer"></i>
                                                </a>
                                            </p>
                                                @if($asset->old_asset_number)<p class="text-xs text-secondary mb-0 px-3">Lama: {{ $asset->old_asset_number }}</p>@endif
                                            </td>
                                            <td>
                                                <p class="text-xs font-weight-bold mb-0">
                                                    {{ $asset->assetType->name ?? 'N/A' }}
                                                
                                                    {{-- {{ route('asset-request.print', $item->assetRequest->id) }} --}}
                                                    
                                                
                                                </p>
                                            </td>
                                            <td><p class="text-xs font-weight-bold mb-0">{{ $asset->assetBrand->name ?? 'N/A' }}</p></td>
                                            <td><p class="text-xs font-weight-bold mb-0">{{ $asset->assetSeries->name ?? 'N/A' }}</p><p class="text-xs text-secondary mb-0 px-3">{{ $asset->assetSeries->material_code}}</p></td>
                                            <td><p class="text-xs font-weight-bold mb-0">{{ $asset->serial_number }}</p></td>
                                            <td><span class="badge badge-sm bg-gradient-{{ $asset->status == 'Free' ? 'success' : ($asset->status == 'Used' ? 'info' : ($asset->status == 'Repair' ? 'warning' : 'secondary')) }}">{{ $asset->status }}</span></td>
                                            <td><p class="text-xs font-weight-bold mb-0">{{ $asset->holder->name ?? '-' }} </p></td>
                                            <td><p class="text-xs font-weight-bold mb-0">{{ $asset->jobsite->name ?? '-' }}</p></td>
                                            <td><p class="text-xs font-weight-bold mb-0">{{ $asset->entity->name ?? 'N/A' }}</p></td>
                                            <td class="align-middle">
                                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-secondary shadow" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Aksi">
                                                            <i class="bx bx-dots-horizontal"></i>
                                                        </button>
                                                        <ul class="dropdown-menu animate__animated animate__fadeIn">
                                                            <li>
                                                                <a class="dropdown-item d-flex align-items-center" href="{{ route('assets.show', $asset->asset_number) }}">
                                                                    <i class="bx bx-show me-2 text-info"></i>
                                                                    <span>Lihat Detail</span>
                                                                </a>
                                                            </li>
                                                            @can('Edit Inventaris')
                                                            <li>
                                                                <a wire:click="edit({{ $asset->id }})" class="dropdown-item d-flex align-items-center">
                                                                    <i class="bx bx-edit-alt me-2 text-warning"></i>
                                                                    <span>Edit Aset</span>
                                                                </a>
                                                            </li>
                                                            @endcan
                                                            @if ($asset->status == 'Pre-Used' || $asset->status == 'Used')
                                                            <li>
                                                                <a wire:click="openReturnModal({{ $asset->id }})"
                                                                    onclick="confirm('Apakah Anda yakin ingin mengembalikan aset ini: {{ $asset->asset_number }}?') || event.stopImmediatePropagation()"
                                                                    class="dropdown-item d-flex align-items-center" href="#">
                                                                    <i class="bx bx-arrow-back me-2"></i>
                                                                    <span>Pengembalian</span>
                                                                </a>
                                                            </li>
                                                                @if(!empty($asset->asset_transaction_id))
                                                                    <li>
                                                                        <a href="{{ route('asset.bast', $asset->id) }}"
                                                                            target="_blank" 
                                                                            class="dropdown-item d-flex align-items-center">
                                                                            <i class="bx bx-bxs-file-pdf me-2"></i>
                                                                            <span>Print BA</span>
                                                                        </a>
                                                                    </li>
                                                                @endif
                                                            @endif
                                                            
                                                        </ul>
                                                    </div>
                                                    @if ($asset->status == 'Free')
                                                        <button wire:click="createBast({{ $asset->id }})"
                                                            class="btn btn-sm btn-info d-flex align-items-center"
                                                            data-bs-toggle="tooltip" title="Serah Terima Aset">
                                                            <i class="bx bx-transfer me-1"></i>
                                                            <span>Serah Terima</span>
                                                        </button>
                                                    @endif
                                                   
                                                    @if (substr($asset->status, 0, 4) === 'Pre-' && $asset->holder_user_id != Auth::id())
                                                        <button wire:click="SendFUWhatApp({{ $asset->holder_user_id }})"
                                                            class="btn btn-sm btn-success d-flex align-items-center"
                                                            data-bs-toggle="tooltip" title="Follow Up User">
                                                            <i class="bx bxl-whatsapp me-1"></i>
                                                            <span>Follow Up</span>
                                                        </button>
                                                    @endif
                                                    @if ($asset->status == 'Pending Inspection')
                                                        <button wire:click="inspectAsset('{{ $asset->id }}')"
                                                            class="btn btn-sm btn-warning d-flex align-items-center"
                                                            data-bs-toggle="tooltip" title="Inspeksi Aset">
                                                            <i class="bx bx-search-alt-2 me-1"></i>
                                                            <span>Inspeksi</span>
                                                        </button>
                                                    @endif
                                                </div>
                                                
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Lokasi</td>
                                            <td colspan="9">{{$asset->current_location_detail}}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="9" class="text-center py-4">Tidak ada data aset ditemukan.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="px-4 pt-3">
                                <label for="perPage" class="form-label mb-0 me-2">Tampilkan</label>
                                <select wire:model.live="perPage" id="perPage" class="form-select form-select-sm w-auto d-inline-block">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                                <span class="ms-1">data per halaman</span>
                            </div>
                        
                            {{-- Optional: tampilkan total data --}}
                        </div>
                        <div class="px-4 pt-3">
                            {{ $assetsCollection->links() }}
                        </div>
                        
                    </div>
                </div>
                @elseif($tab==2)
               
               @endif
            </div>
        </div>
    </div>
   
    {{-- Modal Create/Edit Asset --}}
    @if ($isModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered modal-xl"> {{-- Modal lebih besar --}}
                <div class="modal-content">
                   
                    
                    @if ($isGR)
                        <form wire:submit.prevent="storeGR({{ $asset_request_id }})">
                    @else
                        <form wire:submit.prevent="store">
                    @endif
                    
                        <div class="modal-header">
                            @if ($isGR)
                                <h5 class="modal-title">GR Asset</h5>
                            @elseif ($asset_id)
                                <h5 class="modal-title">Edit Aset</h5>
                            @else
                                <h5 class="modal-title">Tambah Aset Baru</h5>
                            @endif
                            <button type="button" class="btn-close" wire:click="closeModal" aria-label="Close"></button>
                           
                        </div>
                        @if (session('error'))
                            <div class="alert alert-danger">
                                {{ session('error') }}
                            </div>
                        @endif
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <div class="modal-body">
                            @if(empty($cost_center_id))
                                <div class="alert alert-danger mt-3 d-flex align-items-center" role="alert" style="font-size:1.1em;">
                                    <i class="bx bx-error-circle me-2" style="font-size:1.5em;"></i>
                                    <div>
                                        <strong>Oops!</strong> Cost Center belum tersedia untuk kombinasi Departemen dan Jobsite yang dipilih.<br>
                                        <span class="text-muted">Silakan hubungi admin atau pastikan Anda memilih Departemen dan Jobsite yang benar.</span>
                                    </div>
                                </div>
                            @endif
                            <div class="row">
                                <div class="col-md-12 mb-3" {{ $isEdit ? '':'hidden' }}>
                                    <label for="asset_number_modal" class="form-label">Nomor Aset <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('asset_number') is-invalid @enderror" id="asset_number_modal" wire:model.defer="asset_number" readonly>
                                    @error('asset_number') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="asset_type_id_modal_asset" class="form-label">Tipe Aset <span class="text-danger">*</span></label>
                                    <select wire:model="asset_type_id" wire:change="changeAssetTypeId" id="asset_type_id_modal_asset" class="form-select @error('asset_type_id') is-invalid @enderror">
                                        <option value="">-- Pilih Tipe --</option>
                                        @foreach ($assetTypes as $type)
                                            <option value="{{ $type->id }}">{{ $type->name }} </option>
                                        @endforeach
                                    </select>
                                    @error('asset_type_id') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="asset_brand_id_modal_asset" class="form-label">Merek Aset <span class="text-danger">*</span></label>
                                    <select wire:model="asset_brand_id" wire:change="changeAssetBrandId" id="asset_brand_id_modal_asset" class="form-select @error('asset_brand_id') is-invalid @enderror" @if(count($assetBrands) == 0 && !$asset_type_id) disabled @endif>
                                        <option value="">-- Pilih Merek --</option>
                                        @if(count($assetBrands) > 0)
                                            @foreach ($assetBrands as $brand)
                                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                            @endforeach
                                        @else
                                            <option value="" disabled>Tidak ada Type Asset</option>
                                        @endif
                                    </select>
                                    @error('asset_brand_id') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="asset_series_id_modal_asset" class="form-label">Seri Aset <span class="text-danger">*</span></label>
                                    <select wire:model.defer="asset_series_id" id="asset_series_id_modal_asset" class="form-select @error('asset_series_id') is-invalid @enderror" @if(count($assetSeriesCollection) == 0 && !$asset_brand_id) disabled @endif>
                                        <option value="">-- Pilih Seri --</option>
                                         @if(count($assetSeriesCollection) > 0)
                                            @foreach ($assetSeriesCollection as $series)
                                                <option value="{{ $series->id }}">{{ empty($series->name) ? $series->materialCode->name :  $series->name}}</option>
                                            @endforeach
                                        @else
                                            <option value="" disabled>Tidak ada Merek Aset</option>
                                        @endif
                                    </select>
                                    @error('asset_series_id') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3" hidden>
                                    <label for="asset_number_modal" class="form-label">Nomor Aset <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('asset_number') is-invalid @enderror" id="asset_number_modal" wire:model.defer="asset_number" readonly>
                                    @error('asset_number') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="old_asset_number_modal" class="form-label">Nomor Aset Lama</label>
                                    <input type="text" class="form-control @error('old_asset_number') is-invalid @enderror" id="old_asset_number_modal" wire:model.defer="old_asset_number">
                                    @error('old_asset_number') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="serial_number_modal" class="form-label">Serial Number <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('serial_number') is-invalid @enderror" id="serial_number_modal" wire:model.defer="serial_number">
                                    @error('serial_number') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="entity_id_modal_asset" class="form-label">Entitas <span class="text-danger">*</span></label>
                                    <select wire:model="entity_id" wire:change="numberAssetTemporarry" id="entity_id_modal_asset" class="form-select @error('entity_id') is-invalid @enderror">
                                        <option value="">-- Pilih Entitas --</option>
                                        @foreach ($entities as $entity)
                                            <option value="{{ $entity->id }}">{{ $entity->name }} ({{ $entity->code }})</option>
                                        @endforeach
                                    </select>
                                    @error('entity_id') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="pr_number_modal" class="form-label">No. PR</label>
                                    <input type="text" class="form-control @error('pr_number') is-invalid @enderror" id="pr_number_modal" wire:model.defer="pr_number" @if($isGR) readonly @endif>
                                    @error('pr_number') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="po_number_modal" class="form-label">No. PO</label>
                                    <input type="text" class="form-control @error('po_number') is-invalid @enderror" id="po_number_modal" wire:model.defer="po_number" @if($isGR) readonly @endif>
                                    @error('po_number') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="sap_number_modal" class="form-label">No. SAP</label>
                                    <input type="text" class="form-control @error('sap_number') is-invalid @enderror" id="sap_number_modal" wire:model.defer="sap_number" @if($isGR) readonly @endif>
                                    @error('sap_number') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                               
                            </div>

                             <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="jobsite_id_modal_asset" class="form-label">Jobsite <span class="text-danger">*</span></label>
                                    <select wire:model.live="jobsite_id" wire:change="numberAssetTemporarry" id="jobsite_id_modal_asset" class="form-select @error('jobsite_id') is-invalid @enderror">
                                        @foreach ($jobsites as $jobsite)
                                            <option value="{{ $jobsite->id }}">{{ $jobsite->name }} ({{ $jobsite->code }})</option>
                                        @endforeach
                                    </select>
                                    @error('jobsite_id') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4 mb-3" >
                                    <label for="status_modal_departement" class="form-label">Asset Departement <span class="text-danger">*</span></label>
                                    <select wire:model.live="departement_id" id="status_modal_departement" class="form-select @error('departement_id') is-invalid @enderror">
                                        @foreach ($departements as $departement)
                                            <option value="{{ $departement->id }}">{{ $departement->name }} ({{ $departement->code }})</option>
                                        @endforeach
                                    </select>
                                    @error('departement_id') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4 mb-3" >
                                    <label for="status_modal_cost_center" class="form-label">Cost Center</label>
                                    <input type="text" class="form-control @error('cost_center') is-invalid @enderror" id="status_modal_cost_center" wire:model.live="cost_center" readonly>
                                    @error('cost_center') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4 mb-3" hidden>
                                    <label for="status_modal_asset" class="form-label">Status <span class="text-danger">*</span></label>
                                    <select wire:model.defer="status" id="status_modal_asset" class="form-select @error('status') is-invalid @enderror" disabled>
                                        @foreach ($statuses as $stat)
                                            <option value="{{ $stat }}">{{ $stat }}</option>
                                        @endforeach
                                    </select>
                                    @error('status') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                 <div class="col-md-4 mb-3" hidden>
                                    <label for="holder_user_id_modal_asset" class="form-label">Pemegang Aset</label>
                                    <select wire:model.defer="holder_user_id" id="holder_user_id_modal_asset" class="form-select @error('holder_user_id') is-invalid @enderror" disabled>
                                        <option value="">-- Tidak Ada Pemegang --</option>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->nik }})</option>
                                        @endforeach
                                    </select>
                                    @error('holder_user_id') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                             <div class="mb-3">
                                <label for="current_location_detail_modal" class="form-label">Detail Lokasi Saat Ini</label>
                                <textarea class="form-control @error('current_location_detail') is-invalid @enderror" id="current_location_detail_modal" wire:model.defer="current_location_detail" rows="2"></textarea>
                                @error('current_location_detail') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="closeModal">Batal</button>
                            @if(!empty($cost_center_id))
                            <button type="submit" class="btn btn-primary">
                                 <span wire:loading.remove wire:target="store">{{ $asset_id ? 'Simpan Perubahan' : 'Simpan Aset' }}</span>
                                 <span wire:loading wire:target="store">Menyimpan...</span>
                            </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
    @livewire('modal.asset.inspection')
    @livewire('modal.asset.bast')
    @livewire('modal.asset.pengembalian')
</div>

@push('scripts')
{{-- Skrip tooltip Bootstrap sama seperti sebelumnya --}}
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
            var A = bootstrap.Tooltip.getInstance(tooltipTriggerEl);
            if(A) A.dispose();
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    });
    Livewire.on('swal:success', event => {
                const detail = event[0] || event;
            Swal.fire({
                icon: 'success',
                title: detail.title,
                text: detail.text,
                timer: 2500,
                showConfirmButton: false,
            });
        });
</script>
@endpush