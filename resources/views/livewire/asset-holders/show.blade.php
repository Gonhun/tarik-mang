@php
    $rp = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    $date = fn ($d) => $d ? \Carbon\Carbon::parse($d)->locale('id')->isoFormat('D MMM YYYY') : '—';
    $statusTone = ['Used' => 'success', 'Pre-Used' => 'warning', 'Borrowed' => 'info', 'Repair' => 'danger', 'Demage' => 'danger', 'Missing' => 'danger'];
    $initials = collect(explode(' ', trim($user->name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
@endphp
<div>
  <div class="container-fluid py-4">
    <a href="{{ route('asset.holders') }}" class="small text-body-secondary"><i class="bx bx-chevron-left"></i>Pemegang Aset</a>

    {{-- Profil --}}
    <div class="card mt-2 mb-4">
      <div class="card-body">
        <div class="row g-4 align-items-center">
          <div class="col-lg-6 d-flex align-items-center gap-3">
            <div class="avatar avatar-xl flex-shrink-0">
              @if ($user->pp_path)
                <img src="{{ asset('storage/' . $user->pp_path) }}" alt="" class="rounded-circle object-fit-cover w-100 h-100">
              @else
                <span class="avatar-initial rounded-circle bg-label-primary fs-3">{{ $initials }}</span>
              @endif
            </div>
            <div class="min-w-0">
              <h4 class="mb-1">{{ $user->name }}
                @unless ($user->is_active)<span class="badge bg-label-danger align-middle">nonaktif</span>@endunless
              </h4>
              <div class="text-body-secondary small">
                NIK {{ $user->nik }} · {{ $user->position->name ?? '—' }}
              </div>
              <div class="text-body-secondary small">
                {{ $user->department->name ?? '—' }} · {{ $user->jobsite ? $user->jobsite->code . ' — ' . $user->jobsite->name : '—' }}
              </div>
              <div class="small mt-1 d-flex flex-wrap gap-3">
                @if ($user->whatsapp)<span><i class="bx bxl-whatsapp text-success"></i> {{ $user->whatsapp }}</span>@endif
                @if ($user->manager)<span class="text-body-secondary">Atasan: {{ $user->manager->name }}</span>@endif
              </div>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="row g-2 text-center">
              <div class="col-4"><div class="border rounded py-2"><div class="fs-4 fw-semibold">{{ $totalCount }}</div><small class="text-body-secondary">Aset dipegang</small></div></div>
              <div class="col-4"><div class="border rounded py-2"><div class="fs-6 fw-semibold text-truncate px-1" style="line-height: 2.1">{{ $totalValue > 0 ? $rp($totalValue) : '—' }}</div><small class="text-body-secondary">Nilai</small></div></div>
              <div class="col-4"><div class="border rounded py-2"><div class="fs-4 fw-semibold {{ $pendingCount ? 'text-warning' : '' }}">{{ $pendingCount }}</div><small class="text-body-secondary">Belum konfirmasi</small></div></div>
            </div>
          </div>
        </div>
        @if ($byType->isNotEmpty())
          <div class="d-flex flex-wrap gap-2 mt-4">
            <button type="button" class="btn btn-sm {{ $filterType === '' ? 'btn-primary' : 'btn-label-secondary' }}" wire:click="$set('filterType', '')">Semua <span class="badge bg-white text-body ms-1">{{ $totalCount }}</span></button>
            @foreach ($byType as $type => $count)
              @php $tid = (string) $typeIds->search($type); @endphp
              <button type="button" class="btn btn-sm {{ $filterType === $tid ? 'btn-primary' : 'btn-label-secondary' }}" wire:click="$set('filterType', '{{ $tid }}')">
                {{ $type }} <span class="badge bg-white text-body ms-1">{{ $count }}</span>
              </button>
            @endforeach
          </div>
        @endif
      </div>
    </div>

    <ul class="nav nav-pills gap-1 mb-3">
      <li class="nav-item"><button type="button" class="nav-link {{ $tab === 'aset' ? 'active' : '' }}" wire:click="setTab('aset')"><i class="bx bx-package me-1"></i>Aset dipegang</button></li>
      <li class="nav-item"><button type="button" class="nav-link {{ $tab === 'riwayat' ? 'active' : '' }}" wire:click="setTab('riwayat')"><i class="bx bx-history me-1"></i>Riwayat serah terima</button></li>
    </ul>

    @if ($tab === 'aset')
      <div class="card">
        <div class="card-header">
          <h5 class="card-title">{{ $assets->count() }} aset</h5>
          <input type="search" wire:model.live.debounce.300ms="search" class="form-control form-control-sm" style="max-width: 240px" placeholder="Cari no. aset / SN…">
        </div>
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr><th>No. aset</th><th>Aset</th><th>Serial number</th><th>Status</th><th>Lokasi</th><th>Diterima sejak</th><th class="text-end">Nilai</th><th class="text-end"></th></tr>
            </thead>
            <tbody wire:loading.class="opacity-50">
              @forelse ($assets as $a)
                <tr wire:key="a-{{ $a->id }}">
                  <td>
                    <a href="{{ route('assets.show', $a->asset_number) }}" class="fw-medium">{{ $a->asset_number }}</a>
                    @if ($a->old_asset_number)<small class="d-block text-body-secondary">Lama: {{ $a->old_asset_number }}</small>@endif
                  </td>
                  <td style="min-width: 180px">
                    <div class="fw-medium">{{ $a->assetType->name ?? '—' }}</div>
                    <small class="text-body-secondary">{{ trim(($a->assetBrand->name ?? '') . ' ' . ($a->assetSeries->name ?? '')) ?: '—' }}</small>
                  </td>
                  <td><code class="small">{{ $a->serial_number ?: '—' }}</code></td>
                  <td><span class="badge bg-label-{{ $statusTone[$a->status] ?? 'secondary' }}">{{ $statusLabels[$a->status] ?? $a->status }}</span></td>
                  <td>{{ $a->jobsite->code ?? '—' }}@if ($a->current_location_detail)<small class="d-block text-body-secondary text-truncate" style="max-width: 160px">{{ $a->current_location_detail }}</small>@endif</td>
                  <td class="small text-nowrap">
                    {{ $date($a->transaction?->transaction_date ?? $a->transaction?->created_at) }}
                    @if ($a->transaction)<span class="d-block text-body-secondary">{{ $a->transaction->transaction_number }}</span>@endif
                  </td>
                  <td class="text-end text-nowrap">{{ $a->price ? $rp($a->price) : '—' }}</td>
                  <td class="text-end text-nowrap">
                    @if ($a->transaction)
                      <a href="{{ route('asset.ba.print', [$a->transaction->id, $a->id]) }}" target="_blank" class="btn btn-sm btn-icon btn-text-secondary" title="Cetak BAST"><i class="bx bx-printer"></i></a>
                    @endif
                    <a href="{{ route('assets.show', $a->asset_number) }}" class="btn btn-sm btn-icon btn-text-primary" title="Detail aset"><i class="bx bx-show"></i></a>
                  </td>
                </tr>
              @empty
                <tr><td colspan="8"><div class="empty-state"><i class="bx bx-package"></i>{{ $search || $filterType ? 'Tidak ada aset yang cocok.' : 'User ini tidak sedang memegang aset.' }}</div></td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    @else
      <div class="card">
        <div class="card-header"><h5 class="card-title">Riwayat <small class="text-body-secondary fw-normal">100 transaksi terakhir</small></h5></div>
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead><tr><th>Tanggal</th><th>Jenis</th><th>No. transaksi</th><th>Aset</th><th>Dari → ke</th><th>Status</th></tr></thead>
            <tbody>
              @forelse ($history as $t)
                @php
                    $isLoan = str_starts_with((string) $t->notes, \App\Support\AssetLoan::TAG);
                    $isLoanReturn = str_starts_with((string) $t->notes, \App\Support\AssetLoan::RETURN_TAG);
                    [$label, $tone] = match (true) {
                        $isLoan => ['Pinjam', 'info'],
                        $isLoanReturn => ['Kembali pinjam', 'secondary'],
                        $t->transaction_type === 'HANDOVER' => ['Terima (BAST)', 'success'],
                        $t->transaction_type === 'RETURN' => ['Pengembalian', 'secondary'],
                        default => ['Mutasi', 'primary'],
                    };
                @endphp
                <tr>
                  <td class="small text-nowrap">{{ $date($t->transaction_date ?? $t->created_at) }}</td>
                  <td><span class="badge bg-label-{{ $tone }}">{{ $label }}</span></td>
                  <td class="small"><a href="{{ route('asset.mutasi-status', $t->id) }}">{{ $t->transaction_number }}</a></td>
                  <td class="small" style="min-width: 180px">
                    @foreach ($t->items->take(3) as $it)
                      <div>{{ $it->asset->asset_number ?? '—' }} <span class="text-body-secondary">{{ $it->asset->assetType->name ?? '' }}</span></div>
                    @endforeach
                    @if ($t->items->count() > 3)<span class="text-body-secondary">+{{ $t->items->count() - 3 }} lainnya</span>@endif
                  </td>
                  <td class="small">{{ $t->fromUser->name ?? '—' }} → {{ $t->toUser->name ?? '—' }}</td>
                  <td><span class="badge bg-label-{{ $t->status === 'Completed' ? 'success' : ($t->status === 'Rejected' ? 'danger' : 'warning') }}">{{ $t->status }}</span></td>
                </tr>
              @empty
                <tr><td colspan="6"><div class="empty-state"><i class="bx bx-history"></i>Belum ada riwayat serah terima.</div></td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    @endif
  </div>
</div>
