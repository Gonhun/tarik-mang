@php
    $date = fn ($d) => $d ? \Carbon\Carbon::parse($d)->locale('id')->isoFormat('D MMM YYYY') : null;
    $statusMeta = [
        'Used' => ['Dipakai', 'success'], 'Pre-Used' => ['Perlu konfirmasi', 'warning'], 'Borrowed' => ['Dipinjam', 'info'],
        'Pending Mutation' => ['Proses mutasi', 'secondary'], 'Pending Inspection' => ['Menunggu inspeksi IT', 'secondary'],
        'Repair' => ['Perbaikan', 'danger'], 'Demage' => ['Rusak', 'danger'], 'Pre Disposed' => ['Proses disposal', 'secondary'],
        'Delivery' => ['Pengiriman', 'secondary'], 'Missing' => ['Hilang', 'danger'],
    ];
    $typeIcon = function ($type) {
        $t = strtolower((string) $type);
        return match (true) {
            str_contains($t, 'laptop') || str_contains($t, 'notebook') => 'bx-laptop',
            str_contains($t, 'pc') || str_contains($t, 'komputer') || str_contains($t, 'desktop') => 'bx-desktop',
            str_contains($t, 'monitor') => 'bx-tv',
            str_contains($t, 'printer') => 'bx-printer',
            str_contains($t, 'radio') || str_contains($t, 'ht') => 'bx-broadcast',
            str_contains($t, 'hp') || str_contains($t, 'phone') || str_contains($t, 'tablet') => 'bx-mobile-alt',
            default => 'bx-package',
        };
    };
@endphp
<div>
  <div class="container-fluid py-4">
    <div class="page-header">
      <div>
        <h4 class="mb-1">Aset Saya</h4>
        <div class="page-subtitle">Aset yang tercatat atas nama Anda. Anda bertanggung jawab atas aset ini sampai dikembalikan ke IT.</div>
      </div>
      <a href="{{ route('asset-requests.create') }}" class="btn btn-sm btn-primary"><i class="bx bx-plus me-1"></i>Ajukan aset</a>
    </div>

    @if ($counts->pending > 0 && $filter !== 'pending')
      <div class="alert alert-warning d-flex flex-wrap gap-2 align-items-center">
        <i class="bx bx-bell fs-4"></i>
        <div class="flex-grow-1"><b>{{ $counts->pending }} aset</b> sudah diserahkan kepada Anda dan menunggu konfirmasi terima.</div>
        <button type="button" class="btn btn-sm btn-warning" wire:click="setFilter('pending')">Lihat &amp; konfirmasi</button>
      </div>
    @endif

    {{-- Tab status --}}
    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
      @foreach (['' => ['Semua', $counts->total], 'pending' => ['Perlu konfirmasi', $counts->pending], 'used' => ['Dipakai', $counts->used], 'borrowed' => ['Dipinjam', $counts->borrowed], 'other' => ['Lainnya', $counts->total - $counts->pending - $counts->used - $counts->borrowed]] as $key => [$label, $n])
        @if ($key === '' || $n > 0)
          <button type="button" class="btn btn-sm {{ $filter === $key ? 'btn-primary' : 'btn-label-secondary' }}" wire:click="setFilter('{{ $key }}')">
            {{ $label }} <span class="badge {{ $filter === $key ? 'bg-white text-primary' : 'bg-secondary' }} ms-1">{{ (int) $n }}</span>
          </button>
        @endif
      @endforeach
      <div class="ms-md-auto flex-grow-1 flex-md-grow-0" style="min-width: 220px">
        <input wire:model.live.debounce.300ms="search" type="search" class="form-control form-control-sm" placeholder="Cari no. aset, SN, tipe, merek…">
      </div>
    </div>

    <div class="row g-3" wire:loading.class="opacity-50">
      @forelse ($myAssets as $asset)
        @php
            [$stLabel, $stTone] = $statusMeta[$asset->status] ?? [$asset->status, 'secondary'];
            $received = $date($asset->transaction?->transaction_date ?? $asset->transaction?->created_at);
            $loanDue = null; $loanLate = false;
            if ($asset->status === 'Borrowed') {
                $loanTrx = \App\Support\AssetLoan::activeLoan($asset);
                $loanDue = $loanTrx ? (\App\Support\AssetLoan::meta($loanTrx, $asset->id)['due_date'] ?? null) : null;
                $loanLate = $loanDue && now()->startOfDay()->gt(\Carbon\Carbon::parse($loanDue));
            }
        @endphp
        <div class="col-md-6 col-xl-4" wire:key="ma-{{ $asset->id }}">
          <div class="card h-100 {{ $asset->status === 'Pre-Used' ? 'border border-warning' : '' }}">
            <div class="card-body">
              <div class="d-flex gap-3">
                <span class="avatar flex-shrink-0"><span class="avatar-initial rounded bg-label-{{ $stTone }}"><i class="bx {{ $typeIcon($asset->assetType->name ?? '') }} fs-4"></i></span></span>
                <div class="min-w-0 flex-grow-1">
                  <div class="d-flex justify-content-between gap-2">
                    <a href="{{ route('assets.show', $asset->asset_number) }}" class="fw-semibold text-heading text-truncate">{{ $asset->asset_number }}</a>
                    <span class="badge bg-label-{{ $stTone }} flex-shrink-0">{{ $stLabel }}</span>
                  </div>
                  <div class="text-truncate">{{ $asset->assetType->name ?? '—' }} · {{ trim(($asset->assetBrand->name ?? '') . ' ' . ($asset->assetSeries->name ?? '')) ?: '—' }}</div>
                  <small class="text-body-secondary d-block">SN <span class="font-monospace">{{ $asset->serial_number ?: '—' }}</span> · {{ $asset->jobsite->code ?? '—' }}</small>
                  @if ($received)
                    <small class="text-body-secondary d-block">Diterima {{ $received }}{{ $asset->transaction ? ' · ' . $asset->transaction->transaction_number : '' }}</small>
                  @endif
                  @if ($loanDue)
                    <small class="d-block {{ $loanLate ? 'text-danger fw-medium' : 'text-body-secondary' }}">
                      Kembalikan paling lambat {{ $date($loanDue) }}{{ $loanLate ? ' (terlambat)' : '' }}
                    </small>
                  @endif
                </div>
              </div>
            </div>
            <div class="card-footer border-top d-flex flex-wrap gap-2 py-2">
              @if ($asset->status === 'Pre-Used')
                <button type="button" wire:click="confirmAccept({{ $asset->id }})" class="btn btn-sm btn-warning flex-grow-1">
                  <i class="bx bx-check me-1"></i>Konfirmasi terima
                </button>
              @elseif ($asset->status === 'Used')
                <button type="button" wire:click="$dispatch('openReturnModal', { assetId: {{ $asset->id }} })" class="btn btn-sm btn-label-primary">
                  <i class="bx bx-undo me-1"></i>Kembalikan
                </button>
                <button type="button" wire:click="$dispatch('openDisposalModal', { data: { asset_id: {{ $asset->id }}, mode: 'LOST' } })" class="btn btn-sm btn-label-danger">
                  <i class="bx bx-error me-1"></i>Lapor hilang
                </button>
              @endif
              <div class="ms-auto d-flex gap-1">
                @if ($asset->transaction)
                  <a href="{{ route('asset.ba.print', [$asset->transaction->id, $asset->id]) }}" target="_blank" class="btn btn-sm btn-icon btn-text-secondary" title="Cetak BAST"><i class="bx bx-printer"></i></a>
                @endif
                <a href="{{ route('assets.show', $asset->asset_number) }}" class="btn btn-sm btn-icon btn-text-secondary" title="Detail &amp; riwayat"><i class="bx bx-show"></i></a>
              </div>
            </div>
          </div>
        </div>
      @empty
        <div class="col-12">
          <div class="card"><div class="card-body">
            <div class="empty-state">
              <i class="bx bx-package"></i>
              @if ($search || $filter)
                Tidak ada aset yang cocok.
              @else
                Belum ada aset atas nama Anda.
                <div class="mt-3"><a href="{{ route('asset-requests.create') }}" class="btn btn-sm btn-primary">Ajukan aset</a></div>
              @endif
            </div>
          </div></div>
        </div>
      @endforelse
    </div>

    @if ($myAssets->hasPages())
      <div class="mt-4">{{ $myAssets->links() }}</div>
    @endif
  </div>
  @livewire('modal.asset.pengembalian')
  @livewire('modal.asset.disposal')
</div>
