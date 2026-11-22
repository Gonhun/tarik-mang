@php
    $assetStatusClass = [
        'Used' => 'success', 'Pre-Used' => 'info', 'Repair' => 'warning', 'Demage' => 'danger',
        'Pending Mutation' => 'primary', 'Pending Inspection' => 'warning', 'Missing' => 'danger',
    ];
    $hour = now()->hour;
    $greeting = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 18 ? 'Selamat sore' : 'Selamat malam'));
@endphp

<div class="user-dashboard">
    {{-- Sapaan + aksi cepat --}}
    <div class="card mb-4">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h4 class="mb-1">{{ $greeting }}, {{ $user->name }}</h4>
                <p class="text-muted mb-0">
                    {{ $user->position->name ?? 'Karyawan' }}
                    @if ($user->department) · {{ $user->department->name }} @endif
                    @if ($user->jobsite) · {{ $user->jobsite->code }} @endif
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('asset-requests.create') }}" class="btn btn-primary btn-sm"><i class="bx bx-plus me-1"></i>Ajukan aset</a>
                <a href="{{ route('requests.license.add') }}" class="btn btn-outline-primary btn-sm"><i class="bx bx-key me-1"></i>Ajukan lisensi</a>
            </div>
        </div>
    </div>

    {{-- Approval menunggu (hanya muncul jika ada) --}}
    @if ($approvals['total'] > 0)
        <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4" role="alert">
            <div class="d-flex align-items-center gap-2">
                <i class="bx bx-bell fs-4"></i>
                <div>
                    <strong>{{ $approvals['total'] }} persetujuan menunggu Anda.</strong>
                    <span class="d-block small">
                        @if ($approvals['asset']) Pengajuan aset {{ $approvals['asset'] }} @endif
                        @if ($approvals['transaction']) · Mutasi/serah terima {{ $approvals['transaction'] }} @endif
                        @if ($approvals['license']) · Lisensi {{ $approvals['license'] }} @endif
                    </span>
                </div>
            </div>
            <a href="{{ route('asset-requests.dashboard') }}" class="btn btn-warning btn-sm">Lihat semua</a>
        </div>
    @endif

    {{-- Ringkasan --}}
    <div class="row g-4 mb-4">
        @foreach ([
            ['Aset yang saya pegang', $myAssets->count(), 'bx-laptop', 'primary', route('my.asset')],
            ['Pengajuan aset berjalan', $activeRequestCount, 'bx-clipboard', 'info', route('asset-requests.dashboard')],
            ['Pengajuan lisensi', $licenseRequestCount, 'bx-key', 'secondary', route('requests.license.main')],
        ] as [$label, $value, $icon, $color, $url])
            <div class="col-12 col-sm-4">
                <a href="{{ $url }}" class="card h-100 text-reset text-decoration-none">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="avatar flex-shrink-0">
                            <span class="avatar-initial rounded bg-label-{{ $color }}"><i class="bx {{ $icon }} fs-4"></i></span>
                        </span>
                        <div class="min-w-0">
                            <h4 class="mb-0 text-heading">{{ $value }}</h4>
                            <small class="text-muted">{{ $label }}</small>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    <div class="row g-4 mb-4">
        {{-- Pengajuan saya --}}
        <div class="{{ $itContacts->isNotEmpty() ? 'col-lg-7' : 'col-12' }}">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center pb-2">
                    <div>
                        <h5 class="card-title mb-0">Pengajuan aset saya</h5>
                        <small class="text-muted">Posisi terakhir tiap pengajuan</small>
                    </div>
                    <a href="{{ route('asset-requests.dashboard') }}" class="small">Semua <i class="bx bx-chevron-right"></i></a>
                </div>
                <ul class="list-group list-group-flush">
                    @forelse ($myRequests as $req)
                        @php
                            [$stageLabel, $stageColor, $step] = $stages[$req->status] ?? [$req->status, 'secondary', 1];
                            $waitingOn = $req->status === 'Pending Approval' ? $req->approvals->first() : null;
                        @endphp
                        <li class="list-group-item py-3">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <div class="min-w-0">
                                    <a href="{{ route('asset-requests.show', $req->id) }}" class="fw-medium text-heading">{{ $req->request_number }}</a>
                                    <small class="d-block text-muted text-truncate">
                                        {{ $req->items_count }} item · {{ \Carbon\Carbon::parse($req->request_date)->locale('id')->isoFormat('D MMM YYYY') }}
                                        @if ($waitingOn) · menunggu {{ $waitingOn->approver->name ?? $waitingOn->approver_role_name }} @endif
                                    </small>
                                </div>
                                <span class="badge bg-label-{{ $stageColor }} text-nowrap">{{ $stageLabel }}</span>
                            </div>
                            @if (!in_array($req->status, ['Rejected', 'Close']))
                                <div class="d-flex gap-1" aria-label="Tahap {{ $step }} dari 5">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <span class="flex-fill rounded-pill {{ $i <= $step ? 'bg-' . $stageColor : 'bg-label-secondary' }}" style="height: 4px"></span>
                                    @endfor
                                </div>
                            @endif
                        </li>
                    @empty
                        <li class="list-group-item text-center text-muted py-4">
                            Belum ada pengajuan. <a href="{{ route('asset-requests.create') }}">Ajukan aset baru</a>
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>

        {{-- Kontak IT --}}
        <div class="col-lg-5 d-flex flex-column gap-4">
            @if ($itContacts->isNotEmpty())
                <div class="card">
                    <div class="card-header pb-2">
                        <h5 class="card-title mb-0">Tim IT di {{ $user->jobsite->code ?? 'site Anda' }}</h5>
                    </div>
                    <ul class="list-group list-group-flush">
                        @foreach ($itContacts as $it)
                            @php $wa = PhoneFormat($it->whatsapp); @endphp
                            <li class="list-group-item d-flex align-items-center gap-3">
                                <span class="avatar avatar-sm flex-shrink-0">
                                    <span class="avatar-initial rounded-circle bg-label-primary">{{ strtoupper(mb_substr($it->name, 0, 1)) }}</span>
                                </span>
                                <div class="flex-grow-1 min-w-0">
                                    <span class="fw-medium text-heading d-block text-truncate">{{ $it->name }}</span>
                                    <small class="text-muted">{{ $it->position->name ?? 'IT' }}</small>
                                </div>
                                @if ($wa)
                                    <a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-success" aria-label="WhatsApp {{ $it->name }}">
                                        <i class="bx bx-bxl-whatsapp"></i>
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>

    {{-- Approval pengajuan aset (untuk atasan) --}}
    @if ($approvalList->isNotEmpty())
        <div class="card mb-4">
            <div class="card-header pb-2">
                <h5 class="card-title mb-0">Pengajuan aset yang perlu Anda setujui</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr><th>No. pengajuan</th><th>Pemohon</th><th>Site</th><th>Sebagai</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach ($approvalList as $approval)
                            <tr>
                                <td class="fw-medium">{{ $approval->assetRequest->request_number ?? '-' }}</td>
                                <td>{{ $approval->assetRequest->requester->name ?? '-' }}</td>
                                <td>{{ $approval->assetRequest->jobsite->code ?? '-' }}</td>
                                <td><small class="text-muted">{{ $approval->approver_role_name }}</small></td>
                                <td class="text-end">
                                    <a href="{{ route('asset-requests.show', $approval->asset_request_id) }}" class="btn btn-sm btn-primary">Tinjau</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Approval transaksi aset: disposal, kehilangan, mutasi --}}
    @if ($transactionApprovalList->isNotEmpty())
        @php $trxLabel = ['DISPOSED' => 'Disposal / kehilangan', 'MUTATION' => 'Mutasi', 'RETURN' => 'Pengembalian', 'HANDOVER' => 'Serah terima']; @endphp
        <div class="card mb-4">
            <div class="card-header pb-2">
                <h5 class="card-title mb-0">Transaksi aset yang perlu Anda setujui</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr><th>No. transaksi</th><th>Jenis</th><th>Aset</th><th class="d-none d-md-table-cell">Diajukan oleh</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach ($transactionApprovalList as $approval)
                            @php $trx = $approval->transaction; @endphp
                            <tr>
                                <td class="fw-medium">{{ $trx->transaction_number ?? '-' }}</td>
                                <td>
                                    {{ $trxLabel[$trx->transaction_type] ?? $trx->transaction_type }}
                                    @if ($trx->transaction_type === 'DISPOSED' && str_starts_with((string) $trx->notes, '[Kehilangan]'))
                                        <span class="badge bg-label-warning ms-1">Hilang</span>
                                    @endif
                                </td>
                                <td><small>{{ $trx->items->pluck('asset.asset_number')->filter()->implode(', ') ?: '-' }}</small></td>
                                <td class="d-none d-md-table-cell">{{ $trx->creator->name ?? '-' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('asset.mutasi-status', $trx->id) }}" class="btn btn-sm btn-primary">Tinjau</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Aset saya --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center pb-2">
            <div>
                <h5 class="card-title mb-0">Aset yang saya pegang</h5>
                <small class="text-muted">{{ $myAssets->count() }} aset tercatat atas nama Anda</small>
            </div>
            <a href="{{ route('my.asset') }}" class="small">Detail <i class="bx bx-chevron-right"></i></a>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr><th>No. aset</th><th>Jenis</th><th>Merek / seri</th><th class="d-none d-md-table-cell">Serial number</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @forelse ($myAssets as $asset)
                        <tr>
                            <td><a href="{{ route('assets.show', $asset->asset_number) }}" class="fw-medium">{{ $asset->asset_number }}</a></td>
                            <td>{{ $asset->assetType->name ?? '-' }}</td>
                            <td>{{ $asset->assetBrand->name ?? '-' }} {{ $asset->assetSeries->name ?? '' }}</td>
                            <td class="d-none d-md-table-cell"><small class="text-muted">{{ $asset->serial_number ?? '-' }}</small></td>
                            <td><span class="badge bg-label-{{ $assetStatusClass[$asset->status] ?? 'secondary' }}">{{ $asset->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Belum ada aset atas nama Anda.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
