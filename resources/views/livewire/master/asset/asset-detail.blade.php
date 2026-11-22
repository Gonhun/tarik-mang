@php
    $statusColor = [
        'Free' => 'success', 'Used' => 'primary', 'Pre-Used' => 'info',
        'Repair' => 'warning', 'Demage' => 'danger', 'Missing' => 'danger',
        'Pending Inspection' => 'warning', 'Pending Mutation' => 'info', 'Delivery' => 'info',
        'Pre Disposed' => 'secondary', 'Disposed' => 'dark', 'Scrap' => 'dark',
    ];
    $c = $statusColor[$asset->status] ?? 'secondary';
    $price = $asset->price ?: ($asset->assetRequestItem->price ?? null);
    $fmtDate = fn ($d, $f = 'D MMM YYYY') => $d ? \Carbon\Carbon::parse($d)->locale('id')->isoFormat($f) : '-';
@endphp

<div class="container-fluid py-4">
    {{-- Header --}}
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
        <div>
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('my.asset') }}" class="small text-muted"><i class="bx bx-chevron-left"></i>Kembali</a>
            <h4 class="mb-1 mt-1">{{ $asset->asset_number }}</h4>
            <p class="text-muted mb-0">
                {{ $asset->assetType->name ?? '-' }} · {{ $asset->assetBrand->name ?? '-' }} {{ $asset->assetSeries->name ?? '' }}
            </p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="badge bg-label-{{ $c }} fs-6">{{ $asset->status }}</span>
            @can('print-asset-label', $asset)
                <a href="{{ route('assets.print-single-label', $asset->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                    <i class="bx bx-printer me-1"></i>Cetak label
                </a>
            @endcan
        </div>
    </div>

    <div class="row g-4">
        {{-- Kiri: data aset --}}
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="d-inline-block p-2 border rounded bg-white">
                        {!! QrCode::size(150)->generate(route('assets.show', $asset)) !!}
                    </div>
                    <small class="d-block text-muted mt-2">Pindai untuk membuka halaman ini</small>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header pb-2"><h6 class="mb-0">Pemegang & lokasi</h6></div>
                <div class="card-body pt-2">
                    @if ($asset->holder)
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <span class="avatar flex-shrink-0">
                                <span class="avatar-initial rounded-circle bg-label-primary">{{ strtoupper(mb_substr($asset->holder->name, 0, 1)) }}</span>
                            </span>
                            <div class="min-w-0">
                                <span class="fw-medium d-block text-truncate">{{ $asset->holder->name }}</span>
                                <small class="text-muted">{{ $asset->holder->nik }} · {{ $asset->holder->position->name ?? $asset->holder->department->name ?? '-' }}</small>
                            </div>
                        </div>
                    @else
                        <p class="text-muted mb-3">Tidak sedang dipegang siapa pun.</p>
                    @endif
                    <dl class="row small mb-0">
                        <dt class="col-5 text-muted fw-normal">Site</dt><dd class="col-7">{{ $asset->jobsite ? $asset->jobsite->code . ' · ' . $asset->jobsite->name : '-' }}</dd>
                        <dt class="col-5 text-muted fw-normal">Lokasi detail</dt><dd class="col-7">{{ $asset->current_location_detail ?: '-' }}</dd>
                        <dt class="col-5 text-muted fw-normal">Department</dt><dd class="col-7">{{ $asset->departement->name ?? '-' }}</dd>
                        <dt class="col-5 text-muted fw-normal">Entitas</dt><dd class="col-7 mb-0">{{ $asset->entity->name ?? '-' }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card-header pb-2"><h6 class="mb-0">Data aset</h6></div>
                <div class="card-body pt-2">
                    <dl class="row small mb-0">
                        <dt class="col-5 text-muted fw-normal">No. aset lama</dt><dd class="col-7">{{ $asset->old_asset_number ?: '-' }}</dd>
                        <dt class="col-5 text-muted fw-normal">Serial number</dt><dd class="col-7 text-break">{{ $asset->serial_number ?: '-' }}</dd>
                        <dt class="col-5 text-muted fw-normal">Kode material</dt><dd class="col-7">{{ $asset->assetSeries->material_code ?? '-' }}</dd>
                        <dt class="col-5 text-muted fw-normal">Cost center</dt><dd class="col-7">{{ $asset->costCenter->cost_center_number ?? '-' }}</dd>
                        <dt class="col-5 text-muted fw-normal">No. PR</dt><dd class="col-7">{{ $asset->pr_number ?: '-' }}</dd>
                        <dt class="col-5 text-muted fw-normal">No. PO</dt><dd class="col-7">{{ $asset->po_number ?: '-' }}</dd>
                        <dt class="col-5 text-muted fw-normal">No. SAP</dt><dd class="col-7">{{ $asset->sap_number ?: '-' }}</dd>
                        @if ($canManage)
                            <dt class="col-5 text-muted fw-normal">Harga</dt><dd class="col-7">{{ $price ? 'Rp ' . number_format($price, 0, ',', '.') : '-' }}</dd>
                        @endif
                        <dt class="col-5 text-muted fw-normal">Asal</dt>
                        <dd class="col-7">
                            @if ($asset->assetRequestItem?->assetRequest)
                                @can('view-asset-request', $asset->assetRequestItem->assetRequest)
                                    <a href="{{ route('asset-requests.show', $asset->assetRequestItem->assetRequest->id) }}">{{ $asset->assetRequestItem->assetRequest->request_number }}</a>
                                @else
                                    {{ $asset->assetRequestItem->assetRequest->request_number }}
                                @endcan
                            @else
                                Input manual
                            @endif
                        </dd>
                        <dt class="col-5 text-muted fw-normal">Didaftarkan</dt><dd class="col-7">{{ $fmtDate($asset->created_at) }}{{ $asset->creator ? ' · ' . $asset->creator->name : '' }}</dd>
                        <dt class="col-5 text-muted fw-normal">Terakhir diubah</dt><dd class="col-7 mb-0">{{ $fmtDate($asset->updated_at, 'D MMM YYYY HH:mm') }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        {{-- Kanan: tab --}}
        <div class="col-lg-8">
            <div class="card">
                {{-- Tab navigasi: nav-pills biasa (card-header-tabs di tema Sneat memberi margin negatif sehingga menimpa isi) --}}
                <div class="card-body border-bottom py-3">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <ul class="nav nav-pills flex-wrap gap-1 mb-0" role="tablist">
                            @foreach ([
                                'riwayat' => ['Riwayat', 'bx-time-five', $timeline->count()],
                                'dokumen' => ['Berita acara', 'bx-file', $printable->count()],
                            ] + ($canManage ? ['log' => ['Log perubahan', 'bx-history', $auditCount]] : []) as $key => [$label, $icon, $count])
                                <li class="nav-item" role="presentation">
                                    <button type="button" wire:click="setTab('{{ $key }}')"
                                        class="nav-link py-1 px-3 {{ $tab === $key ? 'active' : '' }}"
                                        role="tab" aria-selected="{{ $tab === $key ? 'true' : 'false' }}">
                                        <i class="bx {{ $icon }} me-1"></i>{{ $label }}
                                        <span class="badge rounded-pill {{ $tab === $key ? 'bg-white text-primary' : 'bg-label-secondary' }} ms-1">{{ $count }}</span>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                        <span wire:loading wire:target="setTab" class="spinner-border spinner-border-sm text-primary ms-auto" role="status" aria-label="Memuat"></span>
                    </div>
                </div>

                <div class="card-body" wire:loading.class="opacity-50" wire:target="setTab">
                    {{-- Tab: Riwayat --}}
                    @if ($tab === 'riwayat')
                        <ul class="asset-timeline list-unstyled mb-0">
                            @forelse ($timeline as $event)
                                <li class="asset-timeline-item">
                                    <span class="asset-timeline-dot bg-label-{{ $event['color'] }}"><i class="bx {{ $event['icon'] }}"></i></span>
                                    <div class="d-flex flex-wrap justify-content-between gap-2">
                                        <h6 class="mb-0">{{ $event['title'] }}</h6>
                                        <small class="text-muted">{{ $fmtDate($event['date'], 'dddd, D MMM YYYY') }}</small>
                                    </div>
                                    <p class="mb-1">{{ $event['description'] }}</p>
                                    @if (!empty($event['meta']))
                                        <div class="small text-muted d-flex flex-wrap column-gap-3">
                                            @foreach ($event['meta'] as $k => $v)
                                                <span><span class="fw-medium">{{ $k }}:</span> {{ \Illuminate\Support\Str::limit($v, 160) }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                    @if (!empty($event['transaction']))
                                        @php $trx = $event['transaction']; @endphp
                                        <div class="d-flex flex-wrap gap-2 mt-2">
                                            @if (in_array($trx->transaction_type, ['HANDOVER', 'RETURN', 'MUTATION']) && ($canManage || auth()->user()->can('view-asset-transaction', $trx)))
                                                <a href="{{ route('asset.ba.print', ['transaction' => $trx->id, 'asset' => $asset->id]) }}" target="_blank" class="btn btn-sm btn-outline-primary py-0">
                                                    <i class="bx bx-printer me-1"></i>Cetak BA
                                                </a>
                                            @endif
                                            @if ($trx->document_path)
                                                <a href="{{ Storage::url($trx->document_path) }}" target="_blank" class="btn btn-sm btn-outline-secondary py-0">
                                                    <i class="bx bx-paperclip me-1"></i>{{ $trx->document_name ?: 'Dokumen' }}
                                                </a>
                                            @endif
                                        </div>
                                    @endif
                                </li>
                            @empty
                                <li class="text-center text-muted py-4">Belum ada riwayat.</li>
                            @endforelse
                        </ul>
                    @endif

                    {{-- Tab: Berita acara --}}
                    @if ($tab === 'dokumen')
                        <p class="text-muted small">Semua berita acara aset ini, termasuk yang lama, bisa dicetak ulang.</p>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr><th>Jenis</th><th>No. transaksi</th><th>Tanggal</th><th>Pihak</th><th>Status</th><th class="text-end"></th></tr>
                                </thead>
                                <tbody>
                                    @forelse ($printable as $trx)
                                        @php [$label] = $transactionMeta[$trx->transaction_type] ?? [$trx->transaction_type]; @endphp
                                        <tr>
                                            <td>{{ $label }}</td>
                                            <td class="fw-medium">{{ $trx->transaction_number }}</td>
                                            <td>{{ $fmtDate($trx->transaction_date ?? $trx->created_at) }}</td>
                                            <td>
                                                <small>
                                                    @if ($trx->transaction_type === 'MUTATION')
                                                        {{ $trx->fromJobsite->code ?? '-' }} → {{ $trx->toJobsite->code ?? '-' }}
                                                    @else
                                                        {{ $trx->fromUser->name ?? 'Gudang' }} → {{ $trx->toUser->name ?? '-' }}
                                                    @endif
                                                </small>
                                            </td>
                                            <td><span class="badge bg-label-{{ $trx->status === 'Completed' ? 'success' : ($trx->status === 'Rejected' ? 'danger' : 'warning') }}">{{ $trx->status }}</span></td>
                                            <td class="text-end text-nowrap">
                                                @if ($trx->can_print)
                                                    <a href="{{ route('asset.ba.print', ['transaction' => $trx->id, 'asset' => $asset->id]) }}" target="_blank" class="btn btn-sm btn-primary">
                                                        <i class="bx bx-printer me-1"></i>Cetak
                                                    </a>
                                                @else
                                                    <span class="text-muted small">Tidak ada akses</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-center text-muted py-4">Belum ada berita acara untuk aset ini.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @endif

                    {{-- Tab: Log perubahan (audit) --}}
                    @if ($tab === 'log' && $canManage)
                        <p class="text-muted small">Setiap perubahan data aset tercatat otomatis: siapa, kapan, dan nilai sebelum → sesudah.</p>
                        @forelse ($auditLog as $row)
                            <div class="border rounded p-3 mb-3">
                                <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                                    <div>
                                        <span class="badge bg-label-{{ $row['event_raw'] === 'created' ? 'success' : ($row['event_raw'] === 'deleted' ? 'danger' : 'primary') }}">{{ $row['event'] }}</span>
                                        <span class="fw-medium ms-1">{{ $row['user'] }}</span>
                                    </div>
                                    <small class="text-muted" title="{{ $row['date'] }}">{{ $fmtDate($row['date'], 'D MMM YYYY HH:mm') }}</small>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-borderless mb-0 small">
                                        <tbody>
                                            @foreach ($row['changes'] as $change)
                                                <tr>
                                                    <td class="text-muted ps-0" style="width: 30%">{{ $change['label'] }}</td>
                                                    <td class="text-decoration-line-through text-muted text-break" style="width: 33%">{{ $change['old'] }}</td>
                                                    <td style="width: 4%" aria-hidden="true">→</td>
                                                    <td class="fw-medium text-break">{{ $change['new'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @empty
                            <p class="text-center text-muted py-4 mb-0">Belum ada log perubahan.</p>
                        @endforelse
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .asset-timeline-item { position: relative; padding: 0 0 1.5rem 2.75rem; }
    .asset-timeline-item::before { content: ''; position: absolute; left: 1rem; top: 2.1rem; bottom: .25rem; width: 2px; background: var(--bs-border-color, #e9ecef); }
    .asset-timeline-item:last-child { padding-bottom: 0; }
    .asset-timeline-item:last-child::before { display: none; }
    .asset-timeline-dot { position: absolute; left: 0; top: 0; width: 2rem; height: 2rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1rem; }
</style>
@endpush
