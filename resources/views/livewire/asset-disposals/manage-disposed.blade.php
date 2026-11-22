<div>
    <div class="container-fluid py-4">
        <div class="card">
            <div class="card-header pb-0">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Manajemen Pengajuan Pemusnahan Aset</h5>
                    {{-- Tombol untuk membuat pengajuan baru bisa diletakkan di halaman ManageAssets --}}
                </div>
                @if (session()->has('message'))
                    <div class="alert alert-success text-white mt-3" role="alert">{{ session('message') }}</div>
                @endif
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <input wire:model.live.debounce.300ms="search" type="text" class="form-control" placeholder="Cari No. Transaksi, No. Aset, atau Pemohon...">
                </div>
                <div class="table-responsive">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">No. Transaksi</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Aset</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Tgl. Pengajuan</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Diajukan Oleh</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Status</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Progres Approval</th>
                                <th class="text-end text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($disposalRequests as $request)
                                <tr>
                                    <td>
                                        <p class="text-sm font-weight-bold mb-0 px-3">{{ $request->transaction_number }}</p>
                                    </td>
                                    <td>
                                        @if($request->items->first() && $request->items->first()->asset)
                                            <p class="text-sm font-weight-bold mb-0">{{ $request->items->first()->asset->asset_number }}</p>
                                            <p class="text-xs text-secondary mb-0">SN: {{ $request->items->first()->asset->serial_number }}</p>
                                        @else
                                            <span class="text-danger text-xs">Aset Dihapus</span>
                                        @endif
                                    </td>
                                    <td>
                                        <p class="text-sm mb-0">{{ $request->transaction_date->isoFormat('D MMM YYYY') }}</p>
                                    </td>
                                    <td>
                                        <p class="text-sm mb-0">{{ $request->creator->name ?? 'N/A' }}</p>
                                    </td>
                                    <td>
                                        <span class="badge bg-gradient-{{ 
                                            match(strtolower($request->status)) {
                                                'completed' => 'success',
                                                'rejected', 'cancelled' => 'danger',
                                                default => 'info'
                                            }
                                        }}">{{ $request->status }}</span>
                                    </td>
                                    <td>
                                        {{-- Menampilkan progres approval --}}
                                        @php
                                            $approvedCount = $request->approvals->where('status', 'Approved')->count();
                                            $totalSteps = $request->approvals->count();
                                            $pendingStep = $request->approvals->where('status', 'Pending')->first();
                                        @endphp
                                        @if($request->status == 'Rejected')
                                            <span class="text-danger text-xs font-weight-bold">Ditolak</span>
                                        @elseif($request->status == 'Completed')
                                            <span class="text-success text-xs font-weight-bold">Selesai</span>
                                        @elseif($pendingStep)
                                            <span class="text-warning text-xs font-weight-bold">
                                                Menunggu: {{ $pendingStep->approver_role_name }}
                                            </span>
                                        @else
                                             <span class="text-secondary text-xs">Menunggu Proses</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        {{-- Arahkan ke halaman detail transaksi yang sudah ada --}}
                                        <a href="{{ route('asset.mutasi-status', $request->id) }}" class="btn btn-sm btn-outline-primary mb-0">
                                            Lihat Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center py-4">Tidak ada data pengajuan pemusnahan aset.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $disposalRequests->links() }}
                </div>
            </div>
        </div>
    </div>
</div>