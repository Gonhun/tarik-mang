@php
    $listTargets = 'search,statusFilter,reasonFilter,resetFilters,gotoPage,nextPage,previousPage';
    $fmt = fn ($d) => $d ? \Carbon\Carbon::parse($d)->locale('id')->isoFormat('D MMM YYYY') : '-';
    $badge = fn ($s) => match ($s) { 'Completed' => 'success', 'Rejected', 'Cancelled' => 'danger', default => 'warning' };
@endphp
<div>
    <div class="container-fluid py-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <h4 class="mb-1">Disposal & kehilangan aset</h4>
                <p class="text-muted mb-0">Pengajuan penghapusan aset. Buat pengajuan baru dari menu aksi di halaman Aset (Ajukan disposal / Laporkan hilang).</p>
            </div>
            <a href="{{ route('asset.index') }}" class="btn btn-outline-primary btn-sm"><i class="bx bx-package me-1"></i>Ke halaman Aset</a>
        </div>

        {{-- Ringkasan --}}
        <div class="row g-3 mb-3">
            @foreach ([
                ['Menunggu approval', $openCount, 'bx-time-five', 'warning', 'open'],
                ['Kehilangan berjalan', $lostOpen, 'bx-error-circle', 'danger', null],
                ['Disetujui (Disposed)', $statusCounts['Completed'] ?? 0, 'bx-check-circle', 'success', 'Completed'],
                ['Ditolak', $statusCounts['Rejected'] ?? 0, 'bx-x-circle', 'secondary', 'Rejected'],
            ] as [$label, $value, $icon, $color, $filter])
                <div class="col-6 col-xl-3">
                    <button type="button" class="card h-100 w-100 text-start border-0 {{ $filter !== null && $statusFilter === $filter ? 'border border-' . $color : '' }}"
                        @if ($filter !== null) wire:click="$set('statusFilter', '{{ $statusFilter === $filter ? '' : $filter }}')" @else wire:click="$set('reasonFilter', '{{ $reasonFilter === 'LOST' ? '' : 'LOST' }}')" @endif>
                        <span class="card-body d-flex align-items-center gap-3">
                            <span class="avatar flex-shrink-0"><span class="avatar-initial rounded bg-label-{{ $color }}"><i class="bx {{ $icon }} fs-4"></i></span></span>
                            <span><span class="h4 mb-0 d-block text-heading">{{ $value }}</span><small class="text-muted">{{ $label }}</small></span>
                        </span>
                    </button>
                </div>
            @endforeach
        </div>

        <div class="card">
            <div class="card-body border-bottom py-3">
                <div class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="bx bx-search"></i></span>
                            <input type="search" wire:model.live.debounce.400ms="search" class="form-control" placeholder="No. transaksi, no. aset, SN, pemohon, catatan..." aria-label="Cari">
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <select wire:model.live="statusFilter" class="form-select form-select-sm" aria-label="Status">
                            <option value="">Semua status</option>
                            <option value="open">Menunggu approval</option>
                            @foreach ($statusCounts as $status => $total)
                                <option value="{{ $status }}">{{ $status }} ({{ $total }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <select wire:model.live="reasonFilter" class="form-select form-select-sm" aria-label="Jenis">
                            <option value="">Semua jenis</option>
                            <option value="LOST">Kehilangan</option>
                            <option value="OTHER">Rusak / lainnya</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2 justify-content-md-end align-items-center">
                        <span wire:loading wire:target="{{ $listTargets }}" class="spinner-border spinner-border-sm text-primary" role="status" aria-label="Memuat"></span>
                        @if ($search || $statusFilter || $reasonFilter)
                            <button wire:click="resetFilters" class="btn btn-sm btn-outline-secondary"><i class="bx bx-x me-1"></i>Reset</button>
                        @endif
                    </div>
                </div>
            </div>

            <div class="position-relative">
                <div wire:loading.flex wire:target="{{ $listTargets }}" class="position-absolute top-0 start-0 w-100 h-100 align-items-start justify-content-center pt-5" style="background: rgba(255,255,255,.65); z-index: 5;">
                    <span class="badge bg-white text-primary shadow-sm px-3 py-2"><span class="spinner-border spinner-border-sm me-2" role="status"></span>Memuat data...</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Pengajuan</th><th>Aset</th><th>Alasan</th><th>Status & approval</th><th class="text-end"></th></tr>
                        </thead>
                        <tbody>
                            @forelse ($disposalRequests as $r)
                                @php
                                    $asset = $r->items->first()?->asset;
                                    $isLost = str_starts_with((string) $r->notes, '[Kehilangan]');
                                    $reason = trim(preg_replace('/^\[[^\]]+\]\s*/', '', (string) $r->notes));
                                    $pending = $r->approvals->where('status', 'Pending')->sortBy('step_order')->first();
                                    $approved = $r->approvals->where('status', 'Approved')->count();
                                    $total = $r->approvals->count();
                                    $rejectedBy = $r->approvals->firstWhere('status', 'Rejected');
                                @endphp
                                <tr wire:key="dps-{{ $r->id }}">
                                    <td>
                                        <a href="{{ route('asset.mutasi-status', $r->id) }}" class="fw-semibold text-heading">{{ $r->transaction_number }}</a>
                                        <small class="d-block text-muted">{{ $fmt($r->transaction_date) }} · {{ $r->creator->name ?? '-' }}</small>
                                    </td>
                                    <td>
                                        @if ($asset)
                                            <a href="{{ route('assets.show', $asset->asset_number) }}" class="fw-medium">{{ $asset->asset_number }}</a>
                                            <small class="d-block text-muted">{{ $asset->assetType->name ?? '-' }} · {{ $asset->assetSeries->name ?? '-' }}</small>
                                            <small class="d-block text-muted">{{ $asset->jobsite->code ?? '-' }} · status {{ $asset->status }}</small>
                                        @else
                                            <span class="text-danger small">Aset tidak ditemukan</span>
                                        @endif
                                    </td>
                                    <td style="max-width: 300px">
                                        <span class="badge bg-label-{{ $isLost ? 'danger' : 'secondary' }} mb-1">{{ $isLost ? 'Kehilangan' : 'Rusak / lainnya' }}</span>
                                        <small class="d-block text-muted text-truncate" title="{{ $reason }}">{{ $reason ?: '-' }}</small>
                                        @if ($r->document_path)
                                            <a href="{{ Storage::url($r->document_path) }}" target="_blank" class="small"><i class="bx bx-paperclip"></i> {{ $r->document_name ?: 'Lampiran' }}</a>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-label-{{ $badge($r->status) }}">{{ $r->status }}</span>
                                        @if ($total > 0)
                                            <div class="progress mt-1" style="height: 4px; max-width: 140px" role="progressbar" aria-label="Progres approval" aria-valuenow="{{ $approved }}" aria-valuemin="0" aria-valuemax="{{ $total }}">
                                                <div class="progress-bar bg-{{ $r->status === 'Rejected' ? 'danger' : 'success' }}" style="width: {{ round($approved / $total * 100) }}%"></div>
                                            </div>
                                        @endif
                                        @if ($r->status === 'Rejected' && $rejectedBy)
                                            <small class="d-block text-muted">Ditolak {{ $rejectedBy->actionBy->name ?? $rejectedBy->approver->name ?? '-' }}</small>
                                        @elseif ($pending)
                                            <small class="d-block text-muted">Tahap {{ $approved + 1 }}/{{ $total }}: {{ $pending->approver->name ?? '' }}
                                                @if (!$pending->approver_user_id)<span class="text-danger">(approver kosong)</span>@endif
                                            </small>
                                        @elseif (in_array($r->status, \App\Livewire\AssetDisposals\ManageDisposed::OPEN_STATUSES) && $total === 0)
                                            <small class="d-block text-danger"><i class="bx bx-error"></i> Tanpa approver</small>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('asset.mutasi-status', $r->id) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-5"><i class="bx bx-trash fs-1 d-block mb-2"></i>Tidak ada pengajuan yang cocok.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-body border-top">{{ $disposalRequests->links() }}</div>
        </div>
    </div>
</div>
