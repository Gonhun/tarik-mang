<div>
    <div id="home" class="tab-pane fade active show card ">
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
                <div class="col-md-4">
                    <input wire:model.live.debounce.300ms="search" type="text" class="form-control form-control-sm" placeholder="Cari No Aset, SN, Tipe...">
                </div>
                <div class="col-md-4">
                    <select wire:model.live="selectedJobsite" class="form-select form-select-sm">
                        <option value="">Semua Jobsite</option>
                        @foreach ($jobsites as $jobsite)
                            <option value="{{ $jobsite->id }}">{{ $jobsite->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <select wire:model.live="selectedStatus" class="form-select form-select-sm">
                        <option value="">Semua Status</option>
                        @foreach ($statuses as $stat)
                            <option value="{{ $stat }}">{{ $stat }}</option>
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
                                <td><p class="text-xs font-weight-bold mb-0">{{ $asset->assetSeries->name ?? 'N/A' }}</p></td>
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
                                                    <a class="dropdown-item d-flex align-items-center" href="#">
                                                        <i class="bx bx-show me-2 text-info"></i>
                                                        <span>Lihat Detail</span>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a wire:click="edit({{ $asset->id }})" class="dropdown-item d-flex align-items-center">
                                                        <i class="bx bx-edit-alt me-2 text-warning"></i>
                                                        <span>Edit Aset</span>
                                                    </a>
                                                </li>
                                                {{-- <li>
                                                    <a wire:click="delete({{ $asset->id }})"
                                                        onclick="confirm('Apakah Anda yakin ingin menghapus aset ini: {{ $asset->asset_number }}?') || event.stopImmediatePropagation()"
                                                        class="dropdown-item d-flex align-items-center text-danger" href="#">
                                                        <i class="bx bx-trash me-2"></i>
                                                        <span>Hapus</span>
                                                    </a>
                                                </li> --}}
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
                                            <button wire:click="inspectAsset({{ $asset->id }})"
                                                class="btn btn-sm btn-warning d-flex align-items-center"
                                                data-bs-toggle="tooltip" title="Inspeksi Aset">
                                                <i class="bx bx-search-alt-2 me-1"></i>
                                                <span>Inspeksi</span>
                                            </button>
                                        @endif
                                    </div>
                                    

                                    {{-- Tambah tombol untuk ubah status jika perlu --}}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center py-4">Tidak ada data aset ditemukan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 pt-3">
                {{ $assetsCollection->links() }}
            </div>
            
        </div>
    </div>
</div>
