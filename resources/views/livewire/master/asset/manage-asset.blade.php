@php
    // Warna badge status aset, dipakai konsisten di seluruh halaman.
    $statusColor = [
        'Free' => 'success', 'Used' => 'primary', 'Pre-Used' => 'info',
        'Repair' => 'warning', 'Demage' => 'danger', 'Missing' => 'danger',
        'Pending Inspection' => 'warning', 'Pending Mutation' => 'info', 'Delivery' => 'info',
        'Pre Disposed' => 'secondary', 'Disposed' => 'dark', 'Scrap' => 'dark', 'Borrowed' => 'warning',
    ];
    $listTargets = 'search,selectedJobsite,selectedStatus,perPage,resetFilters,gotoPage,nextPage,previousPage';
@endphp
<div>
    <div class="container-fluid py-4">
        {{-- Header --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <h4 class="mb-1">Manajemen Aset</h4>
                <p class="text-muted mb-0">
                    {{ number_format($assetsCollection->total(), 0, ',', '.') }} aset
                    @if ($search || $selectedJobsite || $selectedStatus) sesuai filter @else terdaftar @endif
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @can('Peminjaman')<a href="{{ route('asset.loans') }}" class="btn btn-outline-secondary btn-sm"><i class="bx bx-time-five me-1"></i>Peminjaman</a>@endcan
                @can('Stock Opname')<a href="{{ route('asset.opname') }}" class="btn btn-outline-secondary btn-sm"><i class="bx bx-qr-scan me-1"></i>Stock opname</a>@endcan
                @can('Cetak Label')<a href="{{ route('asset.labels', array_filter(['site' => $selectedJobsite, 'status' => $selectedStatus, 'q' => $search])) }}" class="btn btn-outline-secondary btn-sm"><i class="bx bx-printer me-1"></i>Cetak label</a>@endcan
                <button wire:click="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel" class="btn btn-outline-success btn-sm">
                    <span wire:loading.remove wire:target="exportExcel"><i class="bx bx-bxs-file-export me-1"></i>Ekspor Excel</span>
                    <span wire:loading wire:target="exportExcel"><span class="spinner-border spinner-border-sm me-1" role="status"></span>Mengekspor...</span>
                </button>
                <button wire:click="create()" wire:loading.attr="disabled" wire:target="create" class="btn btn-primary btn-sm">
                    <span wire:loading.remove wire:target="create"><i class="bx bx-plus me-1"></i>Tambah Aset</span>
                    <span wire:loading wire:target="create"><span class="spinner-border spinner-border-sm me-1" role="status"></span>Membuka...</span>
                </button>
            </div>
        </div>

        @if (session()->has('message'))
            <div class="alert alert-success alert-dismissible" role="alert">
                {{ session('message') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
            </div>
        @endif
        @if (session()->has('error'))
            <div class="alert alert-danger alert-dismissible" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
            </div>
        @endif

        {{-- Ringkasan status (klik untuk filter) --}}
        <div class="d-flex flex-wrap gap-2 mb-3">
            <button type="button" wire:click="$set('selectedStatus', '')"
                class="btn btn-sm {{ $selectedStatus === '' ? 'btn-dark' : 'btn-outline-secondary' }}">
                Semua <span class="badge bg-label-secondary ms-1">{{ number_format($statusCounts->sum(), 0, ',', '.') }}</span>
            </button>
            @foreach ($statusCounts as $status => $total)
                @php $c = $statusColor[$status] ?? 'secondary'; @endphp
                <button type="button" wire:click="$set('selectedStatus', @js($status))"
                    class="btn btn-sm {{ $selectedStatus === $status ? 'btn-' . $c : 'btn-outline-' . $c }}">
                    {{ $status }} <span class="badge bg-label-{{ $c }} ms-1">{{ number_format($total, 0, ',', '.') }}</span>
                </button>
            @endforeach
        </div>

        <div class="card">
            {{-- Filter --}}
            <div class="card-body border-bottom pb-3">
                <div class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="bx bx-search"></i></span>
                            <input wire:model.live.debounce.400ms="search" type="search" class="form-control"
                                placeholder="No aset, no lama, SN, tipe, merek, seri, kode material, pemegang..." aria-label="Cari aset">
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <select wire:model.live="selectedJobsite" class="form-select form-select-sm" aria-label="Filter site">
                            <option value="">Semua site</option>
                            @foreach ($jobsites as $jobsite)
                                <option value="{{ $jobsite->id }}">{{ $jobsite->code }} · {{ $jobsite->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <select wire:model.live="selectedStatus" class="form-select form-select-sm" aria-label="Filter status">
                            <option value="">Semua status</option>
                            @foreach ($statusCounts as $status => $total)
                                <option value="{{ $status }}">{{ $status }} ({{ $total }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2 justify-content-md-end align-items-center">
                        <span wire:loading.delay wire:target="{{ $listTargets }}" class="spinner-border spinner-border-sm text-primary" role="status" aria-label="Memuat"></span>
                        @if ($search || $selectedJobsite || $selectedStatus)
                            <button wire:click="resetFilters" class="btn btn-sm btn-outline-secondary">
                                <i class="bx bx-x me-1"></i>Reset
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Tabel --}}
            <div class="position-relative">
                <div wire:loading.flex wire:target="{{ $listTargets }}"
                    class="position-absolute top-0 start-0 w-100 h-100 align-items-start justify-content-center pt-5"
                    style="background: rgba(255,255,255,.65); z-index: 5;">
                    <span class="badge bg-white text-primary shadow-sm px-3 py-2">
                        <span class="spinner-border spinner-border-sm me-2" role="status"></span>Memuat data...
                    </span>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Aset</th>
                                <th>Barang</th>
                                <th>Status</th>
                                <th>Pemegang & lokasi</th>
                                <th class="d-none d-xl-table-cell">Site / entitas</th>
                                <th class="d-none d-xxl-table-cell">Pembelian</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($assetsCollection as $asset)
                                @php $c = $statusColor[$asset->status] ?? 'secondary'; @endphp
                                <tr wire:key="asset-row-{{ $asset->id }}">
                                    <td>
                                        <a href="{{ route('assets.show', $asset->asset_number) }}" class="fw-semibold text-heading">{{ $asset->asset_number }}</a>
                                        <a href="{{ route('assets.print-single-label', ['asset' => $asset->id]) }}" target="_blank"
                                            class="text-muted ms-1" title="Cetak label" aria-label="Cetak label {{ $asset->asset_number }}"><i class="bx bx-printer"></i></a>
                                        @if ($asset->old_asset_number)
                                            <small class="d-block text-muted">Lama: {{ $asset->old_asset_number }}</small>
                                        @endif
                                        <small class="d-block text-muted">SN: {{ $asset->serial_number ?: '-' }}</small>
                                    </td>
                                    <td>
                                        <span class="d-block fw-medium">{{ $asset->assetType->name ?? '-' }} · {{ $asset->assetBrand->name ?? '-' }}</span>
                                        <small class="text-muted">{{ $asset->assetSeries->name ?? '-' }}@if ($asset->assetSeries?->material_code) · {{ $asset->assetSeries->material_code }}@endif</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-label-{{ $c }}">{{ $asset->status }}</span>
                                        <small class="d-block text-muted mt-1" title="{{ $asset->updated_at }}">{{ $asset->updated_at?->locale('id')->diffForHumans() }}</small>
                                    </td>
                                    <td style="max-width: 260px">
                                        @if ($asset->holder)
                                            <span class="d-block fw-medium text-truncate">{{ $asset->holder->name }}</span>
                                            <small class="text-muted">{{ $asset->holder->nik }}</small>
                                        @else
                                            <span class="text-muted">Tidak ada pemegang</span>
                                        @endif
                                        @if ($asset->current_location_detail)
                                            <small class="d-block text-muted text-truncate" title="{{ $asset->current_location_detail }}">
                                                <i class="bx bx-map-pin"></i> {{ $asset->current_location_detail }}
                                            </small>
                                        @endif
                                    </td>
                                    <td class="d-none d-xl-table-cell">
                                        <span class="d-block">{{ $asset->jobsite->code ?? '-' }}</span>
                                        <small class="text-muted">{{ $asset->entity->code ?? $asset->entity->name ?? '-' }}@if ($asset->departement) · {{ $asset->departement->code }}@endif</small>
                                    </td>
                                    <td class="d-none d-xxl-table-cell">
                                        <small class="d-block">PR {{ $asset->pr_number ?: '-' }}</small>
                                        <small class="d-block">PO {{ $asset->po_number ?: '-' }}</small>
                                        <small class="d-block text-muted">CC {{ $asset->costCenter->cost_center_number ?? '-' }}</small>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        {{-- Aksi utama sesuai status --}}
                                        @if ($asset->status == 'Free')
                                            <button type="button" wire:click="$dispatch('openBastModal', { initialData: { asset_id: {{ $asset->id }} } })" class="btn btn-sm btn-primary">
                                                <i class="bx bx-transfer me-1"></i>Serah terima
                                            </button>
                                        @elseif ($asset->status == 'Pending Inspection')
                                            <button type="button" wire:click="$dispatch('openInspectionForAsset', { assetId: {{ $asset->id }} })" class="btn btn-sm btn-warning">
                                                <i class="bx bx-search-alt-2 me-1"></i>Inspeksi
                                            </button>
                                        @elseif ($asset->status == 'Pre-Used' && $asset->holder_user_id && $asset->holder_user_id != Auth::id())
                                            <button wire:click="SendFUWhatApp({{ $asset->holder_user_id }})" wire:loading.attr="disabled" wire:target="SendFUWhatApp({{ $asset->holder_user_id }})" class="btn btn-sm btn-outline-success" title="Ingatkan penerima untuk konfirmasi">
                                                <span wire:loading.remove wire:target="SendFUWhatApp({{ $asset->holder_user_id }})"><i class="bx bx-bxl-whatsapp me-1"></i>Follow up</span>
                                                <span wire:loading wire:target="SendFUWhatApp({{ $asset->holder_user_id }})"><span class="spinner-border spinner-border-sm" role="status"></span></span>
                                            </button>
                                        @endif

                                        <div class="dropdown d-inline-block">
                                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Aksi lain {{ $asset->asset_number }}">
                                                <i class="bx bx-dots-horizontal-rounded"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('assets.show', $asset->asset_number) }}">
                                                        <i class="bx bx-show me-2"></i>Detail & riwayat
                                                    </a>
                                                </li>
                                                @can('Edit Inventaris')
                                                    <li>
                                                        <a class="dropdown-item" href="#" wire:click.prevent="edit({{ $asset->id }})">
                                                            <i class="bx bx-edit-alt me-2"></i>Edit aset
                                                        </a>
                                                    </li>
                                                @endcan
                                                @if (in_array($asset->status, ['Pre-Used', 'Used']))
                                                    <li>
                                                        <a class="dropdown-item" href="#" wire:click.prevent="$dispatch('openReturnModal', { assetId: {{ $asset->id }} })"
                                                            wire:confirm="Kembalikan aset {{ $asset->asset_number }} dari {{ $asset->holder->name ?? 'pemegang' }}?">
                                                            <i class="bx bx-undo me-2"></i>Pengembalian
                                                        </a>
                                                    </li>
                                                @endif
                                                @if ($asset->status === 'Free')
                                                    <li>
                                                        <a class="dropdown-item" href="#" wire:click.prevent="$dispatch('openLoanModal', { data: { asset_id: {{ $asset->id }}, mode: 'lend' } })">
                                                            <i class="bx bx-time-five me-2"></i>Pinjamkan sementara
                                                        </a>
                                                    </li>
                                                @elseif ($asset->status === 'Borrowed')
                                                    <li>
                                                        <a class="dropdown-item" href="#" wire:click.prevent="$dispatch('openLoanModal', { data: { asset_id: {{ $asset->id }}, mode: 'return' } })">
                                                            <i class="bx bx-undo me-2"></i>Terima pengembalian pinjaman
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="#" wire:click.prevent="$dispatch('openLoanModal', { data: { asset_id: {{ $asset->id }}, mode: 'extend' } })">
                                                            <i class="bx bx-calendar-plus me-2"></i>Perpanjang pinjaman
                                                        </a>
                                                    </li>
                                                @endif
                                                @if (in_array($asset->status, \App\Livewire\Modal\Asset\Disposal::LOSABLE))
                                                    <li>
                                                        <a class="dropdown-item text-warning" href="#" wire:click.prevent="$dispatch('openDisposalModal', { data: { asset_id: {{ $asset->id }}, mode: 'LOST' } })">
                                                            <i class="bx bx-error-circle me-2"></i>Laporkan hilang
                                                        </a>
                                                    </li>
                                                @endif
                                                @if (in_array($asset->status, \App\Livewire\Modal\Asset\Disposal::DISPOSABLE))
                                                    <li>
                                                        <a class="dropdown-item text-danger" href="#" wire:click.prevent="$dispatch('openDisposalModal', { data: { asset_id: {{ $asset->id }}, mode: 'DISPOSE' } })">
                                                            <i class="bx bx-trash me-2"></i>Ajukan disposal
                                                        </a>
                                                    </li>
                                                @endif
                                                @if ($asset->asset_transaction_id || $asset->asset_transaction_return_id)
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><h6 class="dropdown-header">Cetak berita acara</h6></li>
                                                @endif
                                                @if ($asset->asset_transaction_id)
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('asset.ba.print', ['transaction' => $asset->asset_transaction_id, 'asset' => $asset->id]) }}" target="_blank">
                                                            <i class="bx bx-bxs-file-pdf me-2"></i>BA serah terima terakhir
                                                        </a>
                                                    </li>
                                                @endif
                                                @if ($asset->asset_transaction_return_id)
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('asset.ba.print', ['transaction' => $asset->asset_transaction_return_id, 'asset' => $asset->id]) }}" target="_blank">
                                                            <i class="bx bx-bxs-file-pdf me-2"></i>BA pengembalian terakhir
                                                        </a>
                                                    </li>
                                                @endif
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('assets.show', ['asset' => $asset->asset_number, 'tab' => 'dokumen']) }}">
                                                        <i class="bx bx-history me-2"></i>Semua BA (cetak ulang)
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">
                                        <i class="bx bx-package fs-1 d-block mb-2"></i>
                                        Tidak ada aset yang cocok.
                                        @if ($search || $selectedJobsite || $selectedStatus)
                                            <a href="#" wire:click.prevent="resetFilters">Hapus filter</a>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Paginasi --}}
            <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3 border-top">
                <div class="d-flex align-items-center gap-2 small text-muted">
                    <label for="perPage" class="mb-0">Tampilkan</label>
                    <select wire:model.live="perPage" id="perPage" class="form-select form-select-sm w-auto">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span>
                        @if ($assetsCollection->total())
                            {{ $assetsCollection->firstItem() }}–{{ $assetsCollection->lastItem() }} dari {{ number_format($assetsCollection->total(), 0, ',', '.') }}
                        @endif
                    </span>
                </div>
                <div>{{ $assetsCollection->links() }}</div>
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
    @livewire('modal.asset.disposal')
    @livewire('modal.asset.loan')
</div>

