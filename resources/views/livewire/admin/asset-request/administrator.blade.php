<div>
    <div class="container-fluid py-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Proses Administrasi - Input PR dan No Accounting</h5>
                <p class="text-sm mb-0">Halaman ini berisi daftar pengajuan yang siap untuk diinput Nomor PR-nya.</p>
            </div>
            <div class="card-body">
                @if (session()->has('global-message'))
                    <div class="alert alert-success" role="alert">
                        {{ session('global-message') }}
                    </div>
                @endif

                @forelse($requests as $request)
                <div class="card mb-4 shadow-sm" wire:key="request-procurement-{{ $request->id }}">
                    <div class="card-header bg-light d-flex justify-content-between">
                        <div>
                            <h6 class="mb-0">Pengajuan #{{ $request->request_number }}</h6>
                            <span class="text-sm text-muted">Pemohon: {{ $request->requester->name ?? 'N/A' }}</span>
                        </div>
                        <span class="badge bg-gradient-info align-self-center">{{ $request->status }}</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Item Pembelian Baru</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Input Data Pengadaan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($request->items as $item)
                                    {{-- Tampilkan form HANYA untuk item dengan tipe NEW_PURCHASE --}}
                                    @if ($item->fulfillment_type === 'NEW_PURCHASE')
                                        <tr wire:key="item-procurement-{{ $item->id }}">
                                            {{-- Kolom Info Item --}}
                                            <td class="w-40">
                                                <p class="text-sm font-weight-bold mb-0">{{ $item->assetSeries->name ?? 'N/A' }}</p>
                                                <p class="text-xs text-muted mb-0">Jumlah: {{ $item->quantity }}</p>
                                                @if($item->po_number)
                                                    <span class="badge bg-success mt-2">Data Lengkap</span>
                                                @endif
                                            </td>
                                            {{-- Kolom Form Input --}}
                                            <td>
                                                {{-- Jika No. PO sudah ada, tampilkan sebagai read-only --}}
                                                @if ($item->po_number)
                                                    <div class="small">
                                                        <p class="mb-1"><strong>No. PR:</strong> {{ $item->pr_number }}</p>
                                                        <p class="mb-1"><strong>No. Akunting:</strong> {{ $item->accounting_number }}</p>
                                                        <p class="mb-0"><strong>No. PO:</strong> {{ $item->po_number }}</p>
                                                    </div>
                                                @else
                                                    {{-- Form Input --}}
                                                    <div class="row g-2">
                                                        <div class="col-12">
                                                            <input type="number" class="form-control form-control-sm @error('procurementData.'.$item->id.'.pr_number') is-invalid @enderror" 
                                                                wire:model="procurementData.{{ $item->id }}.pr_number" placeholder="No. PR 1xxxxxxxxx" min="10" max="10">
                                                            @error('procurementData.'.$item->id.'.pr_number')
                                                                <span class="text-danger small mt-1 d-block">{{ $message }}</span>
                                                            @enderror
                                                        </div>
                                                        <div class="col-12">
                                                            <input type="text" class="form-control form-control-sm @error('procurementData.'.$item->id.'.accounting_number') is-invalid @enderror"
                                                                wire:model="procurementData.{{ $item->id }}.accounting_number" placeholder="No. Akunting 70000xxxxxxx" min="12" max="12">
                                                            @error('procurementData.'.$item->id.'.accounting_number')
                                                                <span class="text-danger small mt-1 d-block">{{ $message }}</span>
                                                            @enderror
                                                        </div>
                                                        <div class="col-12">
                                                            <button class="btn btn-primary btn-sm w-100 mb-0"
                                                                    wire:click="saveProcurementDetailsForItem({{ $item->id }})">
                                                                Simpan Data
                                                            </button>
                                                        </div>
                                                        @error('procurementData.'.$item->id.'.*') <span class="text-danger small mt-1 d-block">Error pada input di atas.</span> @enderror
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @empty
                    <div class="text-center p-4">
                        <p class="text-muted">Tidak ada pengajuan yang sedang menunggu input Nomor PO saat ini.</p>
                    </div>
                @endforelse

                <div class="mt-4">
                    {{ $requests->links() }}
                </div>
            </div>
        </div>
    </div>
</div>