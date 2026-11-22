@php
    $fmt = fn ($d) => $d ? \Carbon\Carbon::parse($d)->locale('id')->isoFormat('D MMM YYYY') : '-';
    $listTargets = 'setTab,search,statusFilter,gotoPage,nextPage,previousPage';
    $colorOf = fn ($s) => match ($s) { 'Completed', 'Close' => 'success', 'Rejected' => 'danger', 'Pending IT Item Validation' => 'info', 'Pending Approval' => 'warning', default => 'primary' };
@endphp

<div class="container-fluid py-4">
    {{-- Header --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="mb-1">Pengajuan aset</h4>
            <p class="text-muted mb-0">Ajukan aset, pantau prosesnya, dan setujui pengajuan yang menunggu Anda.</p>
        </div>
        <a href="{{ route('asset-requests.create') }}" class="btn btn-primary"><i class="bx bx-plus me-1"></i>Ajukan aset</a>
    </div>

    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible" role="alert">{{ session('message') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button></div>
    @endif

    {{-- Tab --}}
    <ul class="nav nav-pills flex-wrap gap-1 mb-3" role="tablist">
        @foreach (array_filter([
            'action' => ['Perlu tindakan saya', 'bx-bell', $actionRequests->count()],
            'mine' => ['Pengajuan saya', 'bx-user', null],
            'all' => $canSeeAll ? ['Semua pengajuan', 'bx-list-ul', null] : null,
        ]) as $key => [$label, $icon, $count])
            <li class="nav-item" role="presentation">
                <button type="button" wire:click="setTab('{{ $key }}')" class="nav-link {{ $tab === $key ? 'active' : '' }}" role="tab" aria-selected="{{ $tab === $key ? 'true' : 'false' }}">
                    <i class="bx {{ $icon }} me-1"></i>{{ $label }}
                    @if ($count)
                        <span class="badge rounded-pill {{ $tab === $key ? 'bg-white text-primary' : 'bg-danger' }} ms-1">{{ $count }}</span>
                    @endif
                </button>
            </li>
        @endforeach
        <li class="nav-item ms-auto align-self-center">
            <span wire:loading wire:target="{{ $listTargets }}" class="spinner-border spinner-border-sm text-primary" role="status" aria-label="Memuat"></span>
        </li>
    </ul>

    {{-- ========== Perlu tindakan saya ========== --}}
    @if ($tab === 'action')
        <div class="card">
            @if ($actionRequests->isEmpty())
                <div class="card-body text-center text-muted py-5">
                    <i class="bx bx-check-circle fs-1 text-success d-block mb-2"></i>
                    Tidak ada pengajuan yang menunggu tindakan Anda.
                </div>
            @else
                <div class="list-group list-group-flush">
                    @foreach ($actionRequests as $r)
                        @php
                            $isIt = $r->status === 'Pending IT Item Validation';
                            $mine = $r->approvals->where('status', 'Pending')->where('approver_user_id', auth()->id())->sortBy('step_order')->first();
                            $days = (int) \Carbon\Carbon::parse($r->request_date)->diffInDays(now());
                        @endphp
                        <a href="{{ route('asset-requests.show', $r->id) }}" class="list-group-item list-group-item-action py-3" wire:key="act-{{ $r->id }}">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                <div class="min-w-0">
                                    <span class="fw-semibold text-heading">{{ $r->request_number }}</span>
                                    <span class="badge bg-label-{{ $isIt ? 'info' : 'warning' }} ms-1">{{ $isIt ? 'Validasi item IT' : 'Persetujuan' . ($mine ? ' sebagai ' . $mine->approver_role_name : '') }}</span>
                                    <span class="d-block small text-muted">{{ $r->requester->name ?? '-' }} · {{ $r->jobsite->code ?? '-' }} · {{ $r->items_count }} item</span>
                                    <span class="d-block small text-truncate" style="max-width: 600px">{{ $r->purpose }}</span>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-label-{{ $days > 7 ? 'danger' : ($days > 3 ? 'warning' : 'secondary') }}">{{ $days }} hari</span>
                                    <span class="d-block small text-primary mt-1">Tinjau <i class="bx bx-chevron-right"></i></span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    @else
        {{-- ========== Pengajuan saya / Semua ========== --}}
        <div class="card">
            <div class="card-body border-bottom py-3">
                <div class="row g-2 align-items-center">
                    <div class="col-md-6">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="bx bx-search"></i></span>
                            <input type="search" wire:model.live.debounce.400ms="search" class="form-control" placeholder="No. pengajuan, keperluan{{ $tab === 'all' ? ', pemohon, NIK' : '' }}..." aria-label="Cari pengajuan">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <select wire:model.live="statusFilter" class="form-select form-select-sm" aria-label="Status">
                            <option value="active">Sedang berjalan ({{ $activeCount }})</option>
                            <option value="closed">Selesai / ditolak ({{ $closedCount }})</option>
                            <option value="">Semua status</option>
                            @foreach ($stages as $status => [$label])
                                @if (($statusCounts[$status] ?? 0) > 0)
                                    <option value="{{ $status }}">{{ $label }} ({{ $statusCounts[$status] }})</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="position-relative">
                <div wire:loading.flex wire:target="{{ $listTargets }}" class="position-absolute top-0 start-0 w-100 h-100 align-items-start justify-content-center pt-5" style="background: rgba(255,255,255,.65); z-index: 5;">
                    <span class="badge bg-white text-primary shadow-sm px-3 py-2"><span class="spinner-border spinner-border-sm me-2" role="status"></span>Memuat...</span>
                </div>
                <div class="list-group list-group-flush">
                    @forelse ($list as $r)
                        @php
                            [$stageLabel, $step] = $stages[$r->status] ?? [$r->status, 1];
                            $c = $colorOf($r->status);
                            $waiting = $r->status === 'Pending Approval' ? $r->approvals->where('status', 'Pending')->sortBy('step_order')->first() : null;
                            $approved = $r->approvals->where('status', 'Approved')->count();
                        @endphp
                        <a href="{{ route('asset-requests.show', $r->id) }}" class="list-group-item list-group-item-action py-3" wire:key="req-{{ $r->id }}">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                                <div class="min-w-0">
                                    <span class="fw-semibold text-heading">{{ $r->request_number }}</span>
                                    <span class="d-block small text-muted">
                                        {{ $fmt($r->request_date) }} · {{ $r->items_count }} item
                                        @if ($tab === 'all') · {{ $r->requester->name ?? '-' }} ({{ $r->jobsite->code ?? '-' }}) @endif
                                    </span>
                                    <span class="d-block small text-truncate" style="max-width: 600px">{{ $r->purpose }}</span>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-label-{{ $c }}">{{ $stageLabel }}</span>
                                    @if ($waiting)
                                        <span class="d-block small text-muted mt-1">Menunggu {{ $waiting->approver->name ?? $waiting->approver_role_name }} ({{ $approved + 1 }}/{{ $r->approvals->count() }})</span>
                                    @endif
                                </div>
                            </div>
                            @if ($r->status !== 'Rejected')
                                <div class="d-flex gap-1" aria-label="Tahap {{ $step }} dari 5">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <span class="flex-fill rounded-pill {{ $i <= $step ? 'bg-' . $c : 'bg-label-secondary' }}" style="height: 4px"></span>
                                    @endfor
                                </div>
                            @endif
                        </a>
                    @empty
                        <div class="list-group-item text-center text-muted py-5">
                            <i class="bx bx-clipboard fs-1 d-block mb-2"></i>
                            @if ($tab === 'mine' && !$search && $statusFilter === 'active')
                                Belum ada pengajuan yang berjalan. <a href="{{ route('asset-requests.create') }}">Ajukan aset</a>
                            @else
                                Tidak ada pengajuan yang cocok.
                            @endif
                        </div>
                    @endforelse
                </div>
            </div>
            @if ($list && $list->hasPages())
                <div class="card-body border-top">{{ $list->links() }}</div>
            @endif
        </div>
    @endif
</div>
