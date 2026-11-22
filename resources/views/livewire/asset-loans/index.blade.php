@php $fmt = fn ($d) => $d ? \Carbon\Carbon::parse($d)->locale('id')->isoFormat('D MMM YYYY') : '-'; @endphp
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
        <div>
            <h4 class="mb-1">Peminjaman aset</h4>
            <p class="text-muted mb-0">Pinjam sementara (maks. {{ \App\Support\AssetLoan::MAX_DAYS }} hari). Aset dipinjam berstatus <strong>Borrowed</strong>.</p>
        </div>
        <form wire:submit="startLend" class="d-flex gap-2 align-items-start" style="min-width: 280px">
            <div class="flex-fill">
                <input type="text" wire:model="lendCode" class="form-control form-control-sm @error('lendCode') is-invalid @enderror" placeholder="No. aset / SN (scan atau ketik)" aria-label="Nomor aset yang akan dipinjamkan">
                @error('lendCode') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <button type="submit" class="btn btn-primary btn-sm text-nowrap" wire:loading.attr="disabled" wire:target="startLend"><i class="bx bx-plus me-1"></i>Pinjamkan</button>
        </form>
    </div>

    <ul class="nav nav-pills flex-wrap gap-1 mb-3" role="tablist">
        @foreach (['active' => ['Sedang dipinjam', $activeCount, 'primary'], 'overdue' => ['Terlambat', $overdueCount, 'danger'], 'history' => ['Riwayat', null, 'secondary']] as $key => [$label, $n, $c])
            <li class="nav-item" role="presentation">
                <button type="button" wire:click="setTab('{{ $key }}')" class="nav-link {{ $tab === $key ? 'active' : '' }}" role="tab" aria-selected="{{ $tab === $key ? 'true' : 'false' }}">
                    {{ $label }} @if ($n !== null)<span class="badge rounded-pill {{ $tab === $key ? 'bg-white text-primary' : 'bg-label-' . $c }} ms-1">{{ $n }}</span>@endif
                </button>
            </li>
        @endforeach
        <li class="nav-item ms-auto"><input type="search" wire:model.live.debounce.400ms="search" class="form-control form-control-sm" placeholder="Cari no. aset / peminjam" aria-label="Cari"></li>
    </ul>

    <div class="card position-relative">
        <div wire:loading.flex wire:target="setTab,search,gotoPage,nextPage,previousPage" class="position-absolute top-0 start-0 w-100 h-100 align-items-start justify-content-center pt-4" style="background: rgba(255,255,255,.7); z-index: 5;">
            <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
        </div>
        <div class="list-group list-group-flush">
            @if ($tab === 'history')
                @forelse ($list as $trx)
                    @php $a = $trx->items->first()?->asset; $meta = is_array($trx->items->first()?->metadata) ? $trx->items->first()->metadata : []; $stillOut = $a && $a->status === 'Borrowed' && (int) $a->asset_transaction_id === $trx->id; @endphp
                    <div class="list-group-item d-flex flex-wrap justify-content-between gap-2" wire:key="h-{{ $trx->id }}">
                        <div>
                            <span class="fw-medium">{{ $a->asset_number ?? '-' }}</span> <small class="text-muted">{{ $trx->transaction_number }}</small>
                            <small class="d-block text-muted">{{ $trx->toUser->name ?? '-' }} · {{ $fmt($trx->transaction_date) }} → {{ $fmt($meta['due_date'] ?? null) }} · {{ $meta['purpose'] ?? '' }}</small>
                        </div>
                        <span class="badge bg-label-{{ $stillOut ? 'warning' : 'success' }} align-self-start">{{ $stillOut ? 'Masih dipinjam' : 'Sudah kembali' }}</span>
                    </div>
                @empty
                    <div class="list-group-item text-center text-muted py-5">Belum ada riwayat peminjaman.</div>
                @endforelse
            @else
                @forelse ($list as $a)
                    @php $late = $a->loan_days_left !== null && $a->loan_days_left < 0; @endphp
                    <div class="list-group-item py-3" wire:key="l-{{ $a->id }}">
                        <div class="d-flex flex-wrap justify-content-between gap-2">
                            <div class="min-w-0">
                                <a href="{{ route('assets.show', $a->asset_number) }}" class="fw-semibold">{{ $a->asset_number }}</a>
                                <small class="text-muted">{{ $a->assetType->name ?? '-' }} · {{ $a->assetSeries->name ?? '-' }} · {{ $a->jobsite->code ?? '-' }}</small>
                                <span class="d-block small">Dipinjam <strong>{{ $a->holder->name ?? '-' }}</strong> ({{ $a->holder->nik ?? '-' }}) · {{ $a->loan_purpose }}</span>
                                <small class="text-muted">Sejak {{ $fmt($a->transaction->transaction_date ?? null) }} · kembali {{ $fmt($a->loan_due) }}</small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-label-{{ $late ? 'danger' : ($a->loan_days_left <= 1 ? 'warning' : 'success') }}">
                                    {{ $late ? 'Terlambat ' . abs($a->loan_days_left) . ' hari' : ($a->loan_days_left === 0 ? 'Kembali hari ini' : $a->loan_days_left . ' hari lagi') }}
                                </span>
                                <div class="d-flex flex-wrap gap-1 justify-content-end mt-2">
                                    <button type="button" class="btn btn-sm btn-primary" wire:click="$dispatch('openLoanModal', { data: { asset_id: {{ $a->id }}, mode: 'return' } })"><i class="bx bx-undo me-1"></i>Terima kembali</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="$dispatch('openLoanModal', { data: { asset_id: {{ $a->id }}, mode: 'extend' } })">Perpanjang</button>
                                    @if ($a->holder?->whatsapp)
                                        <button type="button" class="btn btn-sm btn-outline-success" wire:click="remind({{ $a->id }})" wire:loading.attr="disabled" wire:target="remind({{ $a->id }})"><i class="bx bx-bell"></i> Ingatkan</button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="list-group-item text-center text-muted py-5">
                        <i class="bx bx-check-circle fs-1 text-success d-block mb-2"></i>
                        {{ $tab === 'overdue' ? 'Tidak ada pinjaman yang terlambat.' : 'Tidak ada aset yang sedang dipinjam.' }}
                    </div>
                @endforelse
            @endif
        </div>
        @if ($tab === 'history' && $list->hasPages())<div class="card-body border-top">{{ $list->links() }}</div>@endif
    </div>

    @livewire('modal.asset.loan')
</div>
