@php
    $fmt = fn ($d) => $d ? \Carbon\Carbon::parse($d)->locale('id')->isoFormat('D MMM YYYY HH:mm') : '-';
    $found = $counts['found'] ?? 0;
    $pct = $expected ? min(100, round($found / $expected * 100)) : 0;
    $tabs = [
        'missing' => ['Belum ditemukan', $missingCount, 'secondary'],
        'found' => ['Ditemukan', $found, 'success'],
        'other_site' => ['Site lain', $counts['other_site'] ?? 0, 'warning'],
        'not_registered' => ['Tidak terdaftar', $counts['not_registered'] ?? 0, 'danger'],
    ];
@endphp

<div class="container-fluid py-4">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
        <div>
            <a href="{{ route('asset.opname') }}" class="small text-muted"><i class="bx bx-chevron-left"></i>Stock opname</a>
            <h4 class="mb-1 mt-1">{{ $opname->code }}</h4>
            <p class="text-muted mb-0">{{ $opname->title }} · {{ $opname->jobsite->code ?? '-' }} {{ $opname->jobsite->name ?? '' }} · mulai {{ $fmt($opname->started_at) }} oleh {{ $opname->starter->name ?? '-' }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="badge bg-label-{{ $opname->isOpen() ? 'warning' : 'success' }} fs-6">{{ $opname->isOpen() ? 'Berjalan' : 'Selesai ' . $fmt($opname->closed_at) }}</span>
            <button type="button" wire:click="export" class="btn btn-sm btn-outline-secondary"><i class="bx bx-download me-1"></i>Unduh hasil (Excel)</button>
            @if ($opname->isOpen())
                <button type="button" wire:click="close" wire:confirm="Tutup sesi {{ $opname->code }}? {{ $missingCount }} aset belum ditemukan akan tercatat sebagai hasil akhir." class="btn btn-sm btn-success"><i class="bx bx-check me-1"></i>Selesai & tutup sesi</button>
            @else
                <button type="button" wire:click="reopen" class="btn btn-sm btn-outline-primary">Buka lagi</button>
            @endif
        </div>
    </div>

    {{-- Progres --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-2">
                <div><span class="h3 mb-0">{{ $found }}</span><span class="text-muted"> / {{ $expected }} aset site ini ditemukan</span></div>
                <span class="fw-semibold">{{ $pct }}%</span>
            </div>
            <div class="progress" style="height: 10px" role="progressbar" aria-label="Progres stock opname" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar bg-{{ $pct >= 100 ? 'success' : 'primary' }}" style="width: {{ $pct }}%"></div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Pemindai --}}
        @if ($opname->isOpen())
            <div class="col-lg-5">
                <div class="card position-sticky" style="top: 1rem">
                    <div class="card-header pb-2">
                        <h6 class="mb-0"><i class="bx bx-qr-scan me-1"></i>Scan aset</h6>
                        <small class="text-muted">Bisa QR label aset atau barcode batang SN di perangkat.</small>
                    </div>
                    <div class="card-body">
                        {{-- Kamera --}}
                        <div wire:ignore>
                            <div id="opname-reader" class="rounded overflow-hidden bg-dark mb-2" style="width: 100%; min-height: 0;"></div>
                            <div class="d-flex gap-2 mb-3">
                                <button type="button" id="opname-cam-start" class="btn btn-primary btn-sm flex-fill"><i class="bx bx-camera me-1"></i>Buka kamera</button>
                                <button type="button" id="opname-cam-stop" class="btn btn-outline-secondary btn-sm flex-fill d-none"><i class="bx bx-stop me-1"></i>Tutup kamera</button>
                            </div>
                            <div id="opname-cam-error" class="alert alert-warning small py-2 d-none" role="alert"></div>
                        </div>

                        {{-- Input manual / scanner USB --}}
                        <form wire:submit="scan" class="mb-3">
                            <label for="opname-code" class="form-label small mb-1">Atau scanner USB / ketik No. aset atau SN</label>
                            <div class="input-group">
                                <input id="opname-code" type="text" wire:model="code" class="form-control" placeholder="QR label atau barcode SN, lalu Enter" autocomplete="off" autofocus>
                                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="scan">
                                    <span wire:loading.remove wire:target="scan">Cek</span>
                                    <span wire:loading wire:target="scan"><span class="spinner-border spinner-border-sm" role="status"></span></span>
                                </button>
                            </div>
                        </form>

                        {{-- Hasil terakhir + penilaian kondisi & lokasi (setelah aset ditemukan) --}}
                        @if ($last)
                            <div class="alert alert-{{ $last['type'] }} mb-3" role="status" aria-live="polite">
                                <strong class="d-block">{{ $last['title'] }}</strong>
                                <span class="small">{{ $last['text'] }}</span>

                                @if ($opname->isOpen() && $lastItem && $lastItem->asset)
                                    @php $a = $lastItem->asset; @endphp
                                    <div class="bg-white text-body rounded p-2 mt-2 small" wire:key="judge-{{ $lastItem->id }}">
                                        <span class="d-block text-muted">SN {{ $a->serial_number ?: '-' }} · Lokasi tercatat: <strong>{{ $lastItem->location_note ?: ($a->current_location_detail ?: 'belum ada') }}</strong></span>

                                        <span class="d-block mt-2 mb-1">Kondisi aset ini:</span>
                                        <div class="btn-group w-100" role="group" aria-label="Kondisi {{ $a->asset_number }}">
                                            <button type="button" wire:click="setCondition({{ $lastItem->id }}, 'baik')" class="btn btn-sm {{ $lastItem->condition !== 'rusak' ? 'btn-success' : 'btn-outline-success' }}" aria-pressed="{{ $lastItem->condition !== 'rusak' ? 'true' : 'false' }}"><i class="bx bx-check-circle me-1"></i>Baik</button>
                                            <button type="button" wire:click="setCondition({{ $lastItem->id }}, 'rusak')" class="btn btn-sm {{ $lastItem->condition === 'rusak' ? 'btn-danger' : 'btn-outline-danger' }}" aria-pressed="{{ $lastItem->condition === 'rusak' ? 'true' : 'false' }}"><i class="bx bx-wrench me-1"></i>Rusak</button>
                                        </div>

                                        <form wire:submit="saveLocation({{ $lastItem->id }})" class="mt-2">
                                            <label for="opname-loc" class="d-block mb-1">Lokasi sekarang <span class="text-muted">(kosongkan bila tetap)</span></label>
                                            <div class="input-group input-group-sm">
                                                <input id="opname-loc" type="text" wire:model="lastLocation" class="form-control" placeholder="{{ $a->current_location_detail ?: 'Mis. Ruang server lt. 2' }}" autocomplete="off">
                                                <button type="submit" class="btn btn-outline-primary" wire:loading.attr="disabled" wire:target="saveLocation">Simpan</button>
                                            </div>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        @endif

                        @if ($recent->isNotEmpty())
                            <span class="small text-muted d-block mb-1">Scan terakhir</span>
                            <ul class="list-unstyled small mb-0">
                                @foreach ($recent as $r)
                                    <li class="d-flex justify-content-between align-items-center py-1 border-top">
                                        <span><i class="bx {{ ['found' => 'bx-check text-success', 'other_site' => 'bx-error text-warning', 'not_registered' => 'bx-x text-danger'][$r->result] ?? 'bx-circle' }}"></i> {{ $r->asset->asset_number ?? $r->scanned_code }}</span>
                                        <button type="button" class="btn btn-sm btn-link text-danger p-0" wire:click="undo({{ $r->id }})" aria-label="Batalkan scan {{ $r->asset->asset_number ?? $r->scanned_code }}">Batal</button>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- Hasil --}}
        <div class="{{ $opname->isOpen() ? 'col-lg-7' : 'col-12' }}">
            <div class="card">
                <div class="card-body border-bottom py-3">
                    <ul class="nav nav-pills flex-wrap gap-1 mb-3" role="tablist">
                        @foreach ($tabs as $key => [$label, $n, $c])
                            <li class="nav-item" role="presentation">
                                <button type="button" wire:click="setTab('{{ $key }}')" class="nav-link py-1 px-3 {{ $tab === $key ? 'active' : '' }}" role="tab" aria-selected="{{ $tab === $key ? 'true' : 'false' }}">
                                    {{ $label }} <span class="badge rounded-pill {{ $tab === $key ? 'bg-white text-primary' : 'bg-label-' . $c }} ms-1">{{ $n }}</span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="search" wire:model.live.debounce.400ms="search" class="form-control" placeholder="Cari no. aset, SN, pemegang..." aria-label="Cari hasil">
                    </div>
                </div>

                <div class="list-group list-group-flush position-relative">
                    <div wire:loading.flex wire:target="setTab,search,gotoPage,nextPage,previousPage" class="position-absolute top-0 start-0 w-100 h-100 align-items-start justify-content-center pt-4" style="background: rgba(255,255,255,.7); z-index: 5;">
                        <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                    </div>
                    @forelse ($list as $row)
                        @if ($tab === 'missing')
                            <div class="list-group-item d-flex justify-content-between align-items-start gap-2" wire:key="m-{{ $row->id }}">
                                <div class="min-w-0">
                                    <a href="{{ route('assets.show', $row->asset_number) }}" target="_blank" class="fw-medium">{{ $row->asset_number }}</a>
                                    <span class="badge bg-label-secondary ms-1">{{ $row->status }}</span>
                                    <small class="d-block text-muted text-truncate">{{ $row->assetType->name ?? '-' }} · {{ $row->assetSeries->name ?? '-' }} · SN {{ $row->serial_number ?: '-' }}</small>
                                    <small class="d-block text-muted">{{ $row->holder ? 'Dipegang ' . $row->holder->name : 'Tanpa pemegang' }}{{ $row->current_location_detail ? ' · ' . $row->current_location_detail : '' }}</small>
                                </div>
                                @if (!$opname->isOpen() && in_array($row->status, \App\Livewire\Modal\Asset\Disposal::LOSABLE))
                                    <button type="button" class="btn btn-sm btn-outline-danger text-nowrap" wire:click="$dispatch('openDisposalModal', { data: { asset_id: {{ $row->id }}, mode: 'LOST' } })">Laporkan hilang</button>
                                @endif
                            </div>
                        @else
                            <div class="list-group-item d-flex justify-content-between align-items-start gap-2" wire:key="s-{{ $row->id }}">
                                <div class="min-w-0">
                                    @if ($row->asset)
                                        <a href="{{ route('assets.show', $row->asset->asset_number) }}" target="_blank" class="fw-medium">{{ $row->asset->asset_number }}</a>
                                        <small class="d-block text-muted text-truncate">{{ $row->asset->assetType->name ?? '-' }} · {{ $row->asset->assetSeries->name ?? '-' }}{{ $row->asset->holder ? ' · ' . $row->asset->holder->name : '' }}</small>
                                    @else
                                        <span class="fw-medium text-danger text-break">{{ $row->scanned_code }}</span>
                                        <small class="d-block text-muted">Kode tidak ada di data aset</small>
                                    @endif
                                    <small class="d-block text-muted">
                                        @if ($tab === 'other_site') Tercatat di {{ $row->jobsiteAtScan->code ?? '-' }} · @endif
                                        {{ $row->condition === 'rusak' ? 'Rusak' : 'Baik' }}{{ $row->location_note ? ' · ' . $row->location_note : '' }} · {{ $row->scanner->name ?? '-' }} · {{ $fmt($row->scanned_at) }}
                                    </small>
                                </div>
                                @if ($row->condition === 'rusak')<span class="badge bg-label-danger">Rusak</span>@endif
                            </div>
                        @endif
                    @empty
                        <div class="list-group-item text-center text-muted py-4">
                            {{ $tab === 'missing' ? 'Semua aset site ini sudah ditemukan.' : 'Belum ada data.' }}
                        </div>
                    @endforelse
                </div>
                @if ($list->hasPages())<div class="card-body border-top">{{ $list->links() }}</div>@endif
            </div>
            @if ($tab === 'missing' && $opname->isOpen() && $missingCount > 0)
                <p class="small text-muted mt-2"><i class="bx bx-info-circle"></i> Setelah sesi ditutup, aset yang belum ditemukan bisa langsung dilaporkan hilang dari daftar ini.</p>
            @endif
        </div>
    </div>

    @livewire('modal.asset.disposal')
</div>

@assets
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
@endassets

@script
<script>
    let scanner = null;
    let lastCode = null, lastAt = 0;
    const startBtn = document.getElementById('opname-cam-start');
    const stopBtn = document.getElementById('opname-cam-stop');
    const errBox = document.getElementById('opname-cam-error');

    // Bunyi & getar singkat sebagai umpan balik hasil scan.
    const beep = (type) => {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator(); const gain = ctx.createGain();
            osc.frequency.value = type === 'success' ? 880 : (type === 'info' ? 520 : 220);
            gain.gain.value = 0.15; osc.connect(gain); gain.connect(ctx.destination);
            osc.start(); setTimeout(() => { osc.stop(); ctx.close(); }, type === 'success' ? 120 : 300);
        } catch (e) {}
        if (navigator.vibrate) navigator.vibrate(type === 'success' ? 60 : [80, 60, 80]);
    };
    $wire.on('opname-feedback', ({ type }) => beep(type));

    const stop = async () => {
        if (scanner) { try { await scanner.stop(); } catch (e) {} try { scanner.clear(); } catch (e) {} scanner = null; }
        startBtn?.classList.remove('d-none'); stopBtn?.classList.add('d-none');
    };

    startBtn?.addEventListener('click', async () => {
        errBox.classList.add('d-none');
        if (typeof Html5Qrcode === 'undefined') { errBox.textContent = 'Pemindai belum termuat. Periksa koneksi lalu muat ulang halaman.'; errBox.classList.remove('d-none'); return; }
        try {
            // QR label + barcode batang SN (Code 128/39/93, EAN, UPC, ITF) + Data Matrix.
            const F = Html5QrcodeSupportedFormats;
            scanner = new Html5Qrcode('opname-reader', {
                formatsToSupport: [F.QR_CODE, F.DATA_MATRIX, F.CODE_128, F.CODE_39, F.CODE_93, F.EAN_13, F.EAN_8, F.UPC_A, F.UPC_E, F.ITF, F.CODABAR],
                experimentalFeatures: { useBarCodeDetectorIfSupported: true },
                verbose: false,
            });
            // Area bidik lebar agar barcode batang yang panjang ikut terbaca.
            const box = (w, h) => ({ width: Math.floor(Math.min(w * 0.9, 360)), height: Math.floor(Math.min(h * 0.55, 200)) });
            await scanner.start({ facingMode: 'environment' }, { fps: 12, qrbox: box }, (text) => {
                const now = Date.now();
                if (text === lastCode && now - lastAt < 3000) return; // abaikan QR yang sama berulang
                lastCode = text; lastAt = now;
                $wire.scan(text);
            });
            startBtn.classList.add('d-none'); stopBtn.classList.remove('d-none');
        } catch (e) {
            errBox.textContent = 'Kamera tidak bisa dibuka: ' + (e?.message || e) + '. Pastikan izin kamera diberikan dan halaman dibuka lewat HTTPS. Anda tetap bisa memakai scanner USB / ketik nomor.';
            errBox.classList.remove('d-none');
            scanner = null;
        }
    });
    stopBtn?.addEventListener('click', stop);
    document.addEventListener('livewire:navigating', stop, { once: true });

    // Setelah scan, fokus kembali ke input agar scanner USB (mengetik + Enter) bisa langsung dipakai lagi.
    $wire.on('opname-feedback', () => setTimeout(() => document.getElementById('opname-code')?.focus(), 50));
    $wire.on('opname-location-saved', () => setTimeout(() => document.getElementById('opname-code')?.focus(), 50));
</script>
@endscript
