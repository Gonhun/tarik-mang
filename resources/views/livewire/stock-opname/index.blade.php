@php $fmt = fn ($d) => $d ? \Carbon\Carbon::parse($d)->locale('id')->isoFormat('D MMM YYYY HH:mm') : '-'; @endphp
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="mb-1">Stock opname</h4>
            <p class="text-muted mb-0">Periksa fisik aset per site dengan scan QR label atau barcode SN. Aset yang tidak terscan muncul sebagai "belum ditemukan".</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('asset.labels') }}" class="btn btn-outline-secondary btn-sm"><i class="bx bx-printer me-1"></i>Cetak label QR</a>
            <button wire:click="create" class="btn btn-primary btn-sm"><i class="bx bx-plus me-1"></i>Mulai stock opname</button>
        </div>
    </div>

    <div class="card">
        <div class="list-group list-group-flush">
            @forelse ($sessions as $s)
                @php
                    $pct = $s->expected_count ? min(100, round($s->found_count / $s->expected_count * 100)) : 0;
                @endphp
                <a href="{{ route('asset.opname.show', $s->id) }}" class="list-group-item list-group-item-action py-3" wire:key="so-{{ $s->id }}">
                    <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                        <div>
                            <span class="fw-semibold text-heading">{{ $s->code }}</span>
                            <span class="badge bg-label-{{ $s->isOpen() ? 'warning' : 'success' }} ms-1">{{ $s->isOpen() ? 'Berjalan' : 'Selesai' }}</span>
                            <span class="d-block small text-muted">{{ $s->title }} · {{ $s->jobsite->code ?? '-' }} · oleh {{ $s->starter->name ?? '-' }} · {{ $fmt($s->started_at) }}</span>
                        </div>
                        <div class="text-end small">
                            <span class="fw-semibold">{{ $s->found_count }}/{{ $s->expected_count }}</span> ditemukan
                            @if ($s->other_count)<span class="d-block text-warning">{{ $s->other_count }} aset site lain</span>@endif
                            @if ($s->unknown_count)<span class="d-block text-danger">{{ $s->unknown_count }} kode tidak terdaftar</span>@endif
                        </div>
                    </div>
                    <div class="progress" style="height: 6px" role="progressbar" aria-label="Progres {{ $s->code }}" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar bg-{{ $pct >= 100 ? 'success' : 'primary' }}" style="width: {{ $pct }}%"></div>
                    </div>
                </a>
            @empty
                <div class="list-group-item text-center text-muted py-5">
                    <i class="bx bx-qr-scan fs-1 d-block mb-2"></i>Belum ada stock opname. Klik <strong>Mulai stock opname</strong>.
                </div>
            @endforelse
        </div>
        @if ($sessions->hasPages())<div class="card-body border-top">{{ $sessions->links() }}</div>@endif
    </div>

    @if ($isModalOpen)
        <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="soTitle" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="soTitle">Mulai stock opname</h5>
                        <button type="button" class="btn-close" wire:click="$set('isModalOpen', false)" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <label for="so_site" class="form-label">Site yang diperiksa <span class="text-danger">*</span></label>
                        <select id="so_site" wire:model="jobsite_id" class="form-select mb-3 @error('jobsite_id') is-invalid @enderror">
                            <option value="">-- Pilih site --</option>
                            @foreach ($jobsites as $j)
                                <option value="{{ $j->id }}">{{ $j->code }} · {{ $j->name }}</option>
                            @endforeach
                        </select>
                        @error('jobsite_id') <div class="invalid-feedback d-block mb-2">{{ $message }}</div> @enderror
                        <label for="so_title" class="form-label">Judul <span class="text-danger">*</span></label>
                        <input id="so_title" type="text" wire:model="title" class="form-control @error('title') is-invalid @enderror">
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <p class="small text-muted mt-3 mb-0">Semua aset yang tercatat di site ini (kecuali Disposed, Scrap, Missing, Delivery) menjadi daftar yang harus ditemukan.</p>
                    </div>
                    <div class="modal-footer d-grid gap-2 d-sm-flex justify-content-sm-end">
                        <button type="button" class="btn btn-outline-secondary" wire:click="$set('isModalOpen', false)">Batal</button>
                        <button type="button" wire:click="store" wire:loading.attr="disabled" wire:target="store" class="btn btn-primary">
                            <span wire:loading.remove wire:target="store">Mulai & buka pemindai</span>
                            <span wire:loading wire:target="store"><span class="spinner-border spinner-border-sm me-1" role="status"></span>Menyiapkan...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
