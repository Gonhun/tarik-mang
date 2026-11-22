@php
    $fmt = fn ($d) => $d ? \Carbon\Carbon::parse($d)->locale('id')->isoFormat('D MMM YYYY') : '-';
    $totalUnits = $queue->sum('units');
@endphp

<div class="container-fluid py-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="mb-1">Input No. PR & akunting</h4>
            <p class="text-muted mb-0">Pengajuan aset yang sudah disetujui dan perlu dibeli baru. {{ $queue->count() }} pengajuan · {{ $totalUnits }} unit menunggu.</p>
        </div>
        <a href="{{ route('request.asset-po') }}" class="btn btn-sm btn-outline-primary"><i class="bx bx-right-arrow-alt me-1"></i>Tahap berikutnya: Input PO</a>
    </div>

    <div class="row g-4">
        {{-- Antrian --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body border-bottom py-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="search" wire:model.live.debounce.400ms="search" class="form-control" placeholder="No. pengajuan, pemohon, site..." aria-label="Cari pengajuan">
                    </div>
                </div>
                <div class="list-group list-group-flush" style="max-height: 70vh; overflow-y: auto;">
                    @forelse ($queue as $r)
                        @php
                            $days = (int) \Carbon\Carbon::parse($r->request_date)->diffInDays(now());
                            $active = (int) $selectedId === $r->id;
                        @endphp
                        <button type="button" wire:click="select({{ $r->id }})" wire:key="q-{{ $r->id }}"
                            class="list-group-item list-group-item-action text-start py-3 {{ $active ? 'active' : '' }}" @if ($active) aria-current="true" @endif>
                            <div class="d-flex justify-content-between gap-2">
                                <span class="fw-semibold text-truncate">{{ $r->request_number }}</span>
                                <span class="badge {{ $active ? 'bg-white text-primary' : 'bg-label-' . ($days > 14 ? 'danger' : ($days > 7 ? 'warning' : 'secondary')) }}">{{ $days }} hr</span>
                            </div>
                            <small class="d-block {{ $active ? '' : 'text-muted' }}">{{ $r->requester->name ?? '-' }} · {{ $r->jobsite->code ?? '-' }}</small>
                            <small class="d-block {{ $active ? '' : 'text-muted' }}">{{ $r->units }} unit beli baru{{ $r->filled ? ' · ' . $r->filled . ' sudah terisi' : '' }}</small>
                        </button>
                    @empty
                        <div class="list-group-item text-center text-muted py-5">
                            <i class="bx bx-check-circle fs-1 text-success d-block mb-2"></i>
                            {{ $search ? 'Tidak ada yang cocok.' : 'Semua pengajuan sudah diisi PR & akunting.' }}
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Form pengajuan terpilih --}}
        <div class="col-lg-8">
            <div class="position-relative">
                <div wire:loading.flex wire:target="select" class="position-absolute top-0 start-0 w-100 h-100 align-items-start justify-content-center pt-5" style="background: rgba(255,255,255,.7); z-index: 5;">
                    <span class="badge bg-white text-primary shadow-sm px-3 py-2"><span class="spinner-border spinner-border-sm me-2" role="status"></span>Memuat...</span>
                </div>

                @if (!$selected)
                    <div class="card"><div class="card-body text-center text-muted py-5">
                        <i class="bx bx-left-arrow-alt fs-1 d-block mb-2"></i>Pilih pengajuan di sebelah kiri untuk mengisi No. PR & akunting.
                    </div></div>
                @else
                    <div class="card mb-4">
                        <div class="card-body d-flex flex-wrap justify-content-between gap-3">
                            <div>
                                <h5 class="mb-1">{{ $selected->request_number }}</h5>
                                <span class="text-muted small">{{ $selected->requester->name ?? '-' }} ({{ $selected->requester->nik ?? '-' }}) · {{ $selected->jobsite->name ?? '-' }}</span>
                                <span class="d-block small text-muted">Diajukan {{ $fmt($selected->request_date) }} · disetujui {{ $fmt($approvedAt) }} · dibutuhkan {{ $fmt($selected->needed_date) }}</span>
                            </div>
                            <a href="{{ route('asset-requests.show', $selected->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary align-self-start">Detail pengajuan <i class="bx bx-link-external"></i></a>
                        </div>
                    </div>

                    {{-- Langkah cepat --}}
                    <div class="card mb-4">
                        <div class="card-header pb-2"><h6 class="mb-0">Isi cepat untuk semua {{ count($rows) }} unit</h6></div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label for="prNumber" class="form-label">1. No. PR (10 digit)</label>
                                    <div class="input-group">
                                        <input id="prNumber" type="text" inputmode="numeric" maxlength="10" wire:model="prNumber" class="form-control @error('prNumber') is-invalid @enderror" placeholder="2100xxxxxx">
                                        <button type="button" class="btn btn-outline-primary" wire:click="applyPrToAll" wire:loading.attr="disabled" wire:target="applyPrToAll">Terapkan ke semua</button>
                                    </div>
                                    @error('prNumber') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="startAccounting" class="form-label">2. No. akunting pertama (12 digit)</label>
                                    <div class="input-group">
                                        <input id="startAccounting" type="text" inputmode="numeric" maxlength="12" wire:model="startAccounting" class="form-control @error('startAccounting') is-invalid @enderror" placeholder="7000000xxxxx">
                                        <button type="button" class="btn btn-outline-primary" wire:click="fillSequential" wire:loading.attr="disabled" wire:target="fillSequential">Isi berurutan</button>
                                    </div>
                                    @error('startAccounting') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    <small class="text-muted">Unit berikutnya otomatis +1. Bisa dikoreksi per unit di bawah.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Per unit --}}
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center pb-2">
                            <h6 class="mb-0">3. Periksa per unit</h6>
                            <small class="text-muted">{{ collect($rows)->filter(fn ($r) => strlen($r['pr']) === 10 && strlen($r['acc']) === 12)->count() }}/{{ count($rows) }} lengkap</small>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr><th style="width: 40px">#</th><th>Item</th><th style="min-width: 150px">No. PR</th><th style="min-width: 170px">No. akunting</th></tr>
                                </thead>
                                <tbody>
                                    @foreach ($items as $item)
                                        <tr wire:key="row-{{ $item->id }}">
                                            <td class="text-muted">{{ $loop->iteration }}</td>
                                            <td>
                                                <span class="fw-medium d-block">{{ $item->assetSeries->name ?? '-' }}</span>
                                                <small class="text-muted">{{ $item->assetType->name ?? '-' }} · {{ $item->assetBrand->name ?? '-' }}{{ $item->item_description ? ' · ' . $item->item_description : '' }}</small>
                                            </td>
                                            <td>
                                                <input type="text" inputmode="numeric" maxlength="10" wire:model="rows.{{ $item->id }}.pr" aria-label="No. PR unit {{ $loop->iteration }}"
                                                    class="form-control form-control-sm @error('rows.'.$item->id.'.pr') is-invalid @enderror">
                                                @error('rows.'.$item->id.'.pr') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </td>
                                            <td>
                                                <input type="text" inputmode="numeric" maxlength="12" wire:model="rows.{{ $item->id }}.acc" aria-label="No. akunting unit {{ $loop->iteration }}"
                                                    class="form-control form-control-sm @error('rows.'.$item->id.'.acc') is-invalid @enderror">
                                                @error('rows.'.$item->id.'.acc') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="card-body border-top d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <small class="text-muted">Setelah semua unit lengkap, pengajuan otomatis lanjut ke tahap <strong>PO</strong>.</small>
                            <button type="button" wire:click="saveAll" wire:loading.attr="disabled" wire:target="saveAll" class="btn btn-primary">
                                <span wire:loading.remove wire:target="saveAll"><i class="bx bx-save me-1"></i>Simpan semua</span>
                                <span wire:loading wire:target="saveAll"><span class="spinner-border spinner-border-sm me-1" role="status"></span>Menyimpan...</span>
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
