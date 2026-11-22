<div class="container-fluid py-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="mb-1">Cetak label QR aset</h4>
            <p class="text-muted mb-0">Pilih aset, atur ukuran label, lalu cetak. QR berisi nomor aset dan bisa dipindai saat stock opname.</p>
        </div>
        <a href="{{ route('asset.opname') }}" class="btn btn-outline-secondary btn-sm"><i class="bx bx-qr-scan me-1"></i>Stock opname</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body border-bottom py-3">
                    <div class="row g-2">
                        <div class="col-12 col-md-4">
                            <input type="search" wire:model.live.debounce.400ms="search" class="form-control form-control-sm" placeholder="No. aset, no. lama, SN, pemegang" aria-label="Cari aset">
                        </div>
                        <div class="col-4 col-md-3">
                            <select wire:model.live="jobsiteId" class="form-select form-select-sm" aria-label="Site">
                                <option value="">Semua site</option>
                                @foreach ($jobsites as $j)<option value="{{ $j->id }}">{{ $j->code }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-4 col-md-3">
                            <select wire:model.live="typeId" class="form-select form-select-sm" aria-label="Tipe">
                                <option value="">Semua tipe</option>
                                @foreach ($types as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-4 col-md-2">
                            <select wire:model.live="status" class="form-select form-select-sm" aria-label="Status">
                                <option value="">Semua status</option>
                                @foreach ($statuses as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 align-items-center mt-2 small">
                        <span class="text-muted">{{ number_format($filteredCount, 0, ',', '.') }} aset cocok</span>
                        <button type="button" class="btn btn-sm btn-link px-0" wire:click="selectAllFiltered" wire:loading.attr="disabled">Pilih semua hasil filter{{ $filteredCount > \App\Livewire\Master\Asset\LabelPicker::MAX ? ' (maks. ' . \App\Livewire\Master\Asset\LabelPicker::MAX . ')' : '' }}</button>
                        <span wire:loading class="spinner-border spinner-border-sm text-primary" role="status"></span>
                    </div>
                </div>
                @php $pageIds = $assets->pluck('id')->map(fn ($i) => (string) $i)->all(); $allOnPage = $pageIds && !array_diff($pageIds, $selected); @endphp
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 36px">
                                    <input type="checkbox" class="form-check-input" aria-label="Pilih semua di halaman ini" @checked($allOnPage)
                                        wire:click="togglePage(@js($pageIds), {{ $allOnPage ? 'false' : 'true' }})">
                                </th>
                                <th>No. aset</th><th>Barang</th><th class="d-none d-md-table-cell">Site / pemegang</th><th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($assets as $a)
                                <tr wire:key="lbl-{{ $a->id }}">
                                    <td><input type="checkbox" class="form-check-input" value="{{ $a->id }}" wire:model.live="selected" aria-label="Pilih {{ $a->asset_number }}"></td>
                                    <td><span class="fw-medium">{{ $a->asset_number }}</span>@if ($a->old_asset_number)<small class="d-block text-muted">Lama: {{ $a->old_asset_number }}</small>@endif</td>
                                    <td><small>{{ $a->assetType->name ?? '-' }} · {{ $a->assetSeries->name ?? '-' }}</small><small class="d-block text-muted">SN {{ $a->serial_number ?: '-' }}</small></td>
                                    <td class="d-none d-md-table-cell"><small>{{ $a->jobsite->code ?? '-' }}</small><small class="d-block text-muted">{{ $a->holder->name ?? '-' }}</small></td>
                                    <td><span class="badge bg-label-secondary">{{ $a->status }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada aset yang cocok.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($assets->hasPages())<div class="card-body border-top">{{ $assets->links() }}</div>@endif
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card position-sticky" style="top: 1rem">
                <div class="card-header pb-2"><h6 class="mb-0">Pengaturan cetak</h6></div>
                <div class="card-body">
                    <span class="form-label d-block">Ukuran label</span>
                    @foreach (['small' => ['50 × 25 mm', 'Printer label kecil (mis. Zebra ZD230)'], 'medium' => ['70 × 35 mm', 'Label sedang'], 'large' => ['100 × 50 mm', 'Label besar, info lengkap']] as $key => [$label, $hint])
                        <div class="form-check mb-1">
                            <input class="form-check-input" type="radio" id="size-{{ $key }}" value="{{ $key }}" wire:model.live="size">
                            <label class="form-check-label" for="size-{{ $key }}">{{ $label }} <small class="text-muted d-block">{{ $hint }}</small></label>
                        </div>
                    @endforeach

                    <span class="form-label d-block mt-3">Kertas</span>
                    <div class="btn-group w-100 mb-3" role="group" aria-label="Jenis kertas">
                        <input type="radio" class="btn-check" id="layout-roll" value="roll" wire:model.live="layout">
                        <label class="btn btn-sm btn-outline-primary" for="layout-roll">Printer label</label>
                        <input type="radio" class="btn-check" id="layout-sheet" value="sheet" wire:model.live="layout">
                        <label class="btn btn-sm btn-outline-primary" for="layout-sheet">Kertas A4</label>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span><strong>{{ count($selected) }}</strong> aset dipilih</span>
                        @if (count($selected))<button type="button" class="btn btn-sm btn-link px-0 text-danger" wire:click="clearSelection">Kosongkan</button>@endif
                    </div>
                    @if ($printUrl)
                        <a href="{{ $printUrl }}" target="_blank" class="btn btn-primary w-100"><i class="bx bx-printer me-1"></i>Cetak {{ count($selected) }} label</a>
                    @else
                        <button type="button" class="btn btn-primary w-100" disabled>Pilih aset dulu</button>
                    @endif
                    <small class="text-muted d-block mt-2">Di jendela cetak, atur ukuran kertas sesuai label dan margin "None".</small>
                </div>
            </div>
        </div>
    </div>
</div>
