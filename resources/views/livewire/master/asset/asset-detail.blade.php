<div>
    <div class="container-fluid py-4">
        <div class="row">
            {{-- Kolom Kiri: Detail Utama & QR Code --}}
            <div class="col-lg-4 mb-4">
                <div class="card position-sticky top-1">
                    <div class="card-header text-center">
                        <h5 class="mb-0">{{ $asset->asset_number }}</h5>
                        <p class="text-sm text-muted mb-0">{{ $asset->assetSeries->name ?? 'Detail Aset' }}</p>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-3">
                            {{-- PERBAIKAN: Gunakan $asset langsung, Laravel akan otomatis menggunakan route key yang benar --}}
                            {!! QrCode::size(180)->generate(route('assets.show', $asset)) !!}
                        </div>
                        <a href="{{ route('assets.print-single-label', $asset->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-print me-1"></i> Cetak Label Ini
                        </a>
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between"><strong>Status:</strong> <span class="badge bg-gradient-info">{{ $asset->status }}</span></li>
                        <li class="list-group-item d-flex justify-content-between"><strong>Pemegang:</strong> <span>{{ $asset->holder->name ?? '-' }}</span></li>
                        <li class="list-group-item d-flex justify-content-between"><strong>Jobsite:</strong> <span>{{ $asset->jobsite->name ?? '-' }}</span></li>
                        <li class="list-group-item d-flex justify-content-between"><strong>Tipe:</strong> <span>{{ $asset->assetType->name ?? '-' }}</span></li>
                        <li class="list-group-item d-flex justify-content-between"><strong>Merek:</strong> <span>{{ $asset->assetBrand->name ?? '-' }}</span></li>
                        <li class="list-group-item d-flex justify-content-between"><strong>Serial Number:</strong> <span>{{ $asset->serial_number }}</span></li>
                        <li class="list-group-item">
                            <div class="row">
                                <div class="col-6 d-flex flex-column">
                                    <span class="text-muted small mb-1"><i class="fas fa-file-alt me-1"></i> PR Number</span>
                                    <span class="fw-semibold">{{ $asset->pr_number ?: '-' }}</span>
                                </div>
                                <div class="col-6 d-flex flex-column">
                                    <span class="text-muted small mb-1"><i class="fas fa-file-invoice-dollar me-1"></i> PO Number</span>
                                    <span class="fw-semibold">{{ $asset->po_number ?: '-' }}</span>
                                </div>
                            </div>
                        </li>
                        <li class="list-group-item">
                            <div class="row">
                                <div class="col-6 d-flex flex-column">
                                    <span class="text-muted small mb-1"><i class="fas fa-calculator me-1"></i> Accounting Number</span>
                                    <span class="fw-semibold">{{ $asset->sap_number ?: '-' }}</span>
                                </div>
                                <div class="col-6 d-flex flex-column">
                                    <span class="text-muted small mb-1"><i class="fas fa-money-bill-wave me-1"></i> Harga</span>
                                    <span class="fw-semibold text-success">
                                        {{ $asset->AssetRequestItem && $asset->AssetRequestItem->price ? 'Rp ' . number_format($asset->AssetRequestItem->price, 0, ',', '.') : 'Rp 0' }}
                                    </span>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Kolom Kanan: Timeline History --}}
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Riwayat Aset (History Log)</h5>
                    </div>
                    <div class="card-body">
                        @forelse ($timelineEvents as $event)
                            <div class="timeline-item">
                                <div class="timeline-marker bg-{{ $event['color'] }} d-flex align-items-center justify-content-center mx-auto" style="display: flex; align-items: center; justify-content: center;">
                                    <i class="{{ $event['icon'] }} text-white" style="font-size: 1.5rem;"></i>
                                </div>
                                <div class="timeline-content">
                                    <p class="text-muted mb-0 small">{{ \Carbon\Carbon::parse($event['date'])->isoFormat('dddd, D MMMM YYYY') }}</p>
                                    <h6 class="mb-1">{{ $event['title'] }}</h6>
                                    <p class="mb-0">{{ $event['description'] }}</p>
                                    
                                    {{-- Tampilkan link ke dokumen BAST/Mutasi jika ada --}}
                                    @if(in_array($event['type'], ['HANDOVER', 'RETURN', 'MUTATION']) && $event['data']->document_path)
                                        <a href="{{ Storage::url($event['data']->document_path) }}" target="_blank" class="small">Lihat Dokumen</a>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-center text-muted">Belum ada riwayat untuk aset ini.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .timeline-item {
        position: relative;
        padding-left: 50px;
        padding-bottom: 2rem;
        border-left: 2px solid #e9ecef;
    }
    .timeline-item:last-child {
        border-left: 2px solid transparent;
        padding-bottom: 0;
    }
    .timeline-marker {
        position: absolute;
        left: -19px;
        top: 0;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content-center;
        border: 2px solid #fff;
        box-shadow: 0 0 0 3px #e9ecef;
    }
    .timeline-content h6 {
        font-weight: 600;
    }
</style>
@endpush