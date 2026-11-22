@php
    $pipelineLabels = [
        'Pending IT Item Validation' => 'Validasi IT',
        'Pending Approval' => 'Approval atasan',
        'Pending PR & Accounting Number' => 'PR & akunting',
        'Pending PO' => 'Menunggu PO',
        'Pending QC' => 'QC barang',
        'Pending Asset Mutation' => 'Mutasi ke user',
    ];
    $pipelineMax = max(1, $pipeline->max());
    $attention = $assets['groups']['Perlu perhatian'];
@endphp

<div class="it-dashboard">
    {{-- Header --}}
    <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
        <div>
            <h4 class="mb-1">Dashboard IT</h4>
            <p class="text-muted mb-0">{{ $today->locale('id')->isoFormat('dddd, D MMMM YYYY') }} · halo, {{ Auth::user()->name }}</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <label for="dashSite" class="form-label mb-0 text-muted small">Site</label>
            <select id="dashSite" class="form-select form-select-sm" style="min-width: 180px" wire:model.live="jobsiteId">
                <option value="">Semua site</option>
                @foreach ($jobsites as $site)
                    <option value="{{ $site->id }}">{{ $site->code }} · {{ $site->name }}</option>
                @endforeach
            </select>
            <span wire:loading wire:target="jobsiteId" class="spinner-border spinner-border-sm text-primary" role="status"></span>
        </div>
    </div>

    {{-- Notifikasi admin: workflow belum diatur / transaksi tertahan tanpa approver --}}
    @if ($workflowAlert)
        <div class="alert alert-danger d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4" role="alert">
            <div class="d-flex gap-2">
                <i class="bx bx-error-alt fs-4 flex-shrink-0"></i>
                <div>
                    <strong>Workflow approval perlu diperbaiki</strong>
                    <ul class="mb-0 ps-3 small">
                        @if ($workflowAlert['unconfigured'])
                            <li>Belum diatur: {{ implode(', ', $workflowAlert['unconfigured']) }}. Pengajuan jenis ini akan ditolak sampai workflow dibuat.</li>
                        @endif
                        @if ($workflowAlert['noApprovals'])
                            <li>{{ number_format($workflowAlert['noApprovals'], 0, ',', '.') }} transaksi mutasi/disposal berjalan tanpa tahapan approval (data lama).</li>
                        @endif
                        @if ($workflowAlert['orphanSteps'])
                            <li>{{ number_format($workflowAlert['orphanSteps'], 0, ',', '.') }} tahap approval menunggu tapi tidak punya approver.</li>
                        @endif
                    </ul>
                </div>
            </div>
            <a href="{{ route('admin.workflow') }}" class="btn btn-sm btn-danger">Atur workflow</a>
        </div>
    @endif

    {{-- Ringkasan aset --}}
    <div class="row g-4 mb-4">
        @foreach ([
            ['Total aset', $assets['total'], 'bx-package', 'primary', 'Semua status'],
            ['Siap pakai', $assets['groups']['Siap pakai'], 'bx-check-circle', 'success', 'Status Free'],
            ['Dipakai', $assets['groups']['Dipakai'], 'bx-user', 'info', 'Used & Pre-Used'],
            ['Perlu perhatian', $attention, 'bx-wrench', $attention > 0 ? 'warning' : 'secondary', 'Repair, rusak, inspeksi, hilang'],
        ] as [$label, $value, $icon, $color, $hint])
            <div class="col-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <span class="text-muted d-block mb-1">{{ $label }}</span>
                                <h3 class="mb-0 text-heading">{{ number_format($value, 0, ',', '.') }}</h3>
                            </div>
                            <div class="avatar flex-shrink-0">
                                <span class="avatar-initial rounded bg-label-{{ $color }}"><i class="bx {{ $icon }} fs-4"></i></span>
                            </div>
                        </div>
                        <small class="text-muted">{{ $hint }}</small>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4 mb-4">
        {{-- Perlu tindakan --}}
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header pb-2">
                    <h5 class="card-title mb-0">Perlu tindakan</h5>
                    <small class="text-muted">Pekerjaan yang menunggu tim IT</small>
                </div>
                <ul class="list-group list-group-flush">
                    @php
                        $actions = [
                            ['Validasi item IT', $itValidationCount, 'bx-clipboard', 'Pengajuan aset menunggu keputusan stok/baru', route('asset-requests.dashboard')],
                            ['Approval saya', $approvals['total'], 'bx-check-circle', "Aset {$approvals['asset']} · Mutasi {$approvals['transaction']} · Lisensi {$approvals['license']}", route('asset-requests.dashboard')],
                            ['Pengiriman & inspeksi', $pendingTransactions->sum(), 'bx-transfer', $pendingTransactions->map(fn ($n, $s) => "$s $n")->implode(' · ') ?: 'Tidak ada yang tertunda', auth()->user()->can('Mutasi') ? route('asset.mutasi') : null],
                        ];
                    @endphp
                    @foreach ($actions as [$label, $count, $icon, $hint, $url])
                        <li class="list-group-item d-flex align-items-center gap-3 py-3">
                            <span class="avatar avatar-sm flex-shrink-0">
                                <span class="avatar-initial rounded bg-label-{{ $count > 0 ? 'primary' : 'secondary' }}"><i class="bx {{ $icon }}"></i></span>
                            </span>
                            <div class="flex-grow-1 min-w-0">
                                @if ($url)
                                    <a href="{{ $url }}" class="fw-medium text-heading">{{ $label }}</a>
                                @else
                                    <span class="fw-medium text-heading">{{ $label }}</span>
                                @endif
                                <small class="d-block text-muted text-truncate">{{ $hint }}</small>
                            </div>
                            <span class="badge rounded-pill {{ $count > 0 ? 'bg-primary' : 'bg-label-secondary' }}">{{ $count }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- Status aset --}}
        <div class="col-lg-4 col-md-6">
            <div class="card h-100">
                <div class="card-header pb-0">
                    <h5 class="card-title mb-0">Kondisi aset</h5>
                    <small class="text-muted">Dikelompokkan dari status aset</small>
                </div>
                <div class="card-body">
                    <div wire:ignore id="itAssetStatusChart" style="min-height: 280px"></div>
                </div>
            </div>
        </div>

        {{-- Pipeline pengajuan --}}
        <div class="col-lg-4 col-md-6">
            <div class="card h-100">
                <div class="card-header pb-2">
                    <h5 class="card-title mb-0">Pengajuan aset berjalan</h5>
                    <small class="text-muted">{{ $pipeline->sum() }} pengajuan per tahap</small>
                </div>
                <div class="card-body">
                    @foreach ($pipeline as $status => $count)
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span>{{ $pipelineLabels[$status] ?? $status }}</span>
                                <span class="fw-medium text-heading">{{ $count }}</span>
                            </div>
                            <div class="progress" style="height: 6px" role="progressbar" aria-label="{{ $pipelineLabels[$status] ?? $status }}" aria-valuenow="{{ $count }}" aria-valuemin="0" aria-valuemax="{{ $pipelineMax }}">
                                <div class="progress-bar" style="width: {{ round($count / $pipelineMax * 100) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        {{-- Antrian validasi IT --}}
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center pb-2">
                    <div>
                        <h5 class="card-title mb-0">Antrian validasi IT</h5>
                        <small class="text-muted">Paling lama menunggu di atas</small>
                    </div>
                    <span class="badge bg-label-primary">{{ $itValidationCount }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>No. pengajuan</th>
                                <th>Pemohon</th>
                                <th class="text-center">Item</th>
                                <th class="text-end">Menunggu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($itValidationList as $req)
                                @php $days = (int) \Carbon\Carbon::parse($req->request_date)->diffInDays(now()); @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route('asset-requests.show', $req->id) }}" class="fw-medium">{{ $req->request_number }}</a>
                                        <small class="d-block text-muted">{{ $req->jobsite->code ?? '-' }}</small>
                                    </td>
                                    <td>{{ $req->requester->name ?? '-' }}</td>
                                    <td class="text-center">{{ $req->pending_items_count }}</td>
                                    <td class="text-end">
                                        <span class="badge bg-label-{{ $days > 7 ? 'danger' : ($days > 3 ? 'warning' : 'secondary') }}">{{ $days }} hari</span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">Tidak ada pengajuan yang menunggu validasi IT.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Tren pengajuan --}}
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header pb-0">
                    <h5 class="card-title mb-0">Pengajuan aset per bulan</h5>
                    <small class="text-muted">12 bulan terakhir</small>
                </div>
                <div class="card-body">
                    <div wire:ignore id="itRequestTrendChart" style="min-height: 260px"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Lisensi akan habis --}}
        <div class="col-12">
            <div class="card h-100">
                <div class="card-header pb-2">
                    <h5 class="card-title mb-0">Lisensi akan habis</h5>
                    <small class="text-muted">Dalam 60 hari ke depan, semua site</small>
                </div>
                <ul class="list-group list-group-flush">
                    @forelse ($expiringLicenses as $license)
                        @php $left = (int) now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($license->expiration_date)->startOfDay()); @endphp
                        <li class="list-group-item d-flex align-items-center gap-3">
                            <span class="avatar avatar-sm flex-shrink-0">
                                <span class="avatar-initial rounded bg-label-{{ $left <= 14 ? 'danger' : 'warning' }}"><i class="bx bx-key"></i></span>
                            </span>
                            <div class="flex-grow-1 min-w-0">
                                @can('Lisensi')
                                    <a href="{{ route('licenses.detail', $license->id) }}" class="fw-medium text-heading d-block text-truncate">{{ $license->name }}</a>
                                @else
                                    <span class="fw-medium text-heading d-block text-truncate">{{ $license->name }}</span>
                                @endcan
                                <small class="text-muted">Habis {{ \Carbon\Carbon::parse($license->expiration_date)->locale('id')->isoFormat('D MMM YYYY') }} · {{ $license->seats }} seat</small>
                            </div>
                            <span class="badge bg-label-{{ $left <= 14 ? 'danger' : 'warning' }}">{{ $left }} hari</span>
                        </li>
                    @empty
                        <li class="list-group-item text-center text-muted py-4">Tidak ada lisensi yang habis dalam 60 hari.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>

@script
<script>
    const colors = (window.config && window.config.colors) || {};
    const initial = @js($chartData);

    const statusChart = new ApexCharts(document.querySelector('#itAssetStatusChart'), {
        chart: { type: 'donut', height: 280 },
        labels: initial.status.labels,
        series: initial.status.series,
        colors: [colors.success, colors.info, colors.warning, colors.primary, colors.secondary].filter(Boolean),
        legend: { position: 'bottom' },
        dataLabels: { enabled: false },
        stroke: { width: 2 },
        plotOptions: { pie: { donut: { size: '70%', labels: { show: true, total: { show: true, label: 'Total',
            formatter: (w) => w.globals.seriesTotals.reduce((a, b) => a + b, 0).toLocaleString('id-ID') } } } } },
        tooltip: { y: { formatter: (v) => v.toLocaleString('id-ID') + ' aset' } },
        noData: { text: 'Belum ada data' },
    });
    statusChart.render();

    const trendChart = new ApexCharts(document.querySelector('#itRequestTrendChart'), {
        chart: { type: 'bar', height: 260, toolbar: { show: false } },
        series: [{ name: 'Pengajuan', data: initial.trend.series }],
        xaxis: { categories: initial.trend.labels },
        yaxis: { labels: { formatter: (v) => Math.round(v) } },
        colors: [colors.primary].filter(Boolean),
        plotOptions: { bar: { borderRadius: 4, columnWidth: '50%' } },
        dataLabels: { enabled: false },
        grid: { strokeDashArray: 4 },
        noData: { text: 'Belum ada data' },
    });
    trendChart.render();

    $wire.on('it-dashboard-charts', ({ charts }) => {
        statusChart.updateOptions({ labels: charts.status.labels, series: charts.status.series });
        trendChart.updateOptions({ xaxis: { categories: charts.trend.labels }, series: [{ name: 'Pengajuan', data: charts.trend.series }] });
    });
</script>
@endscript
