<div>
    <div class="container-fluid py-4">
        <div class="card">
            <div class="card-header pb-0">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Aset Atas Nama Saya</h5>
                </div>
                <p class="text-sm">Berikut adalah daftar aset yang saat ini terdata atas nama Anda sebagai pemegang.</p>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <input wire:model.live.debounce.300ms="search" type="text" class="form-control" placeholder="Cari No Aset, Tipe, Merek...">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Nomor Aset</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Deskripsi Aset</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Serial Number</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Jobsite</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Tgl Diterima</th>
                                <th class="text-secondary opacity-7"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($myAssets as $asset)
                                <tr>
                                    <td>
                                        <p class="text-xs font-weight-bold mb-0 px-3">{{ $asset->asset_number }}</p>
                                    </td>
                                    <td>
                                        <p class="text-xs font-weight-bold mb-0">{{ $asset->status }}</p>
                                    </td>
                                    <td>
                                        <p class="text-xs font-weight-bold mb-0">{{ $asset->assetType->name ?? '' }} {{ $asset->assetBrand->name ?? '' }}</p>
                                        <p class="text-xs text-secondary mb-0">{{ $asset->assetSeries->name ?? '' }}</p>
                                    </td>
                                    <td>
                                        <p class="text-xs font-weight-bold mb-0">{{ $asset->serial_number ?? '-' }}</p>
                                    </td>
                                    <td>
                                        <p class="text-xs font-weight-bold mb-0">{{ $asset->jobsite->name ?? '-' }}</p>
                                    </td>
                                    <td>
                                        {{-- updated_at bisa menjadi perkiraan kapan aset terakhir ditransaksikan --}}
                                        <p class="text-xs font-weight-bold mb-0">{{ $asset->updated_at 
                                            ? $asset->updated_at->isoFormat('D MMM YYYY') 
                                            : '-' }}</p>
                                    </td>
                                    <td class="align-middle text-end">
                                        @if ($asset->status == 'Pre-Used')
                                            <button wire:click="confirmAccept({{ $asset->id }})" class="btn btn-sm btn-outline-info py-1 px-2 me-1" title="Edit Permission">
                                                Accept
                                            </button>
                                        @elseif($asset->status == 'Used')
                                            <button wire:click="openReturnModal({{ $asset->id }})" wire:loading.attr="disabled" wire:target="openReturnModal({{ $asset->id }})" class="btn btn-sm btn-warning mb-0">
                                                Kembalikan
                                            </button>
                                            <button wire:click="reportLost({{ $asset->id }})" wire:loading.attr="disabled" wire:target="reportLost({{ $asset->id }})" class="btn btn-sm btn-outline-danger mb-0 ms-1">
                                                Laporkan hilang
                                            </button>
                                           
                                        @endif
                                       
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <p class="text-muted mb-0">Tidak ada aset yang terdata atas nama Anda saat ini.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    {{ $myAssets->links() }}
                </div>
            </div>
        </div>
    </div>
    @livewire('modal.asset.pengembalian')
    @livewire('modal.asset.disposal')
</div>

@push('scripts')
<script>
    
     document.addEventListener('livewire:init', function () {
        
     });
    
</script>
@endpush