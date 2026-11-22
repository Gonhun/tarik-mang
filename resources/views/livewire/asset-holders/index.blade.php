@php
    $rp = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    $initials = fn ($n) => collect(explode(' ', trim($n)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    $tones = ['primary', 'success', 'info', 'warning', 'danger', 'secondary'];
@endphp
<div>
  <div class="container-fluid py-4">
    <div class="page-header">
      <div>
        <h4 class="mb-1">Pemegang Aset</h4>
        <div class="page-subtitle">Siapa memegang aset apa, berapa banyak, dan berapa nilainya.</div>
      </div>
      <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="export" wire:loading.attr="disabled" wire:target="export">
        <i class="bx bx-download me-1"></i> Ekspor CSV
      </button>
    </div>

    @if ($anomalies)
      <div class="alert alert-warning small d-flex gap-2 align-items-center">
        <i class="bx bx-error fs-5"></i>
        <div>{{ $anomalies }} aset berstatus Free / Disposed / Scrap masih tercatat punya pemegang. Aset ini tidak dihitung di sini; rapikan dari halaman Inventaris.</div>
      </div>
    @endif

    {{-- Ringkasan --}}
    <div class="row g-3 mb-4">
      @foreach ([
          ['Aset dipegang', number_format($summary->assets), 'bx-package', 'primary'],
          ['User pemegang', number_format($summary->users), 'bx-group', 'info'],
          ['Rata-rata per user', $summary->users ? number_format($summary->assets / $summary->users, 1, ',', '.') : '0', 'bx-bar-chart-alt-2', 'success'],
          ['Menunggu konfirmasi BAST', number_format($summary->pending), 'bx-time-five', 'warning'],
      ] as [$label, $value, $icon, $tone])
        <div class="col-6 col-xl-3">
          <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3 py-3">
              <span class="avatar flex-shrink-0"><span class="avatar-initial rounded bg-label-{{ $tone }}"><i class="bx {{ $icon }} fs-4"></i></span></span>
              <div>
                <div class="fs-4 fw-semibold lh-1">{{ $value }}</div>
                <small class="text-body-secondary">{{ $label }}</small>
              </div>
            </div>
          </div>
        </div>
      @endforeach
    </div>

    @if ($byType->isNotEmpty())
      <div class="card mb-4">
        <div class="card-body py-3">
          <div class="small text-body-secondary mb-2">Komposisi per tipe · total nilai {{ $rp($summary->value) }}</div>
          <div class="d-flex flex-wrap gap-2">
            @foreach ($byType as $type => $count)
              @php $t = $types->firstWhere('name', $type); @endphp
              <button type="button" class="btn btn-sm {{ $t && (string) $filterType === (string) $t->id ? 'btn-primary' : 'btn-label-secondary' }}"
                      wire:click="$set('filterType', '{{ $t && (string) $filterType !== (string) $t->id ? $t->id : '' }}')">
                {{ $type }} <span class="badge bg-white text-body ms-1">{{ number_format($count) }}</span>
              </button>
            @endforeach
          </div>
        </div>
      </div>
    @endif

    <div class="card">
      <div class="card-body border-bottom">
        <div class="row g-2">
          <div class="col-12 col-lg-3">
            <div class="input-group input-group-merge">
              <span class="input-group-text"><i class="bx bx-search"></i></span>
              <input type="search" wire:model.live.debounce.400ms="search" class="form-control" placeholder="Cari nama / NIK…">
            </div>
          </div>
          <div class="col-6 col-md-4 col-lg-2">
            <select wire:model.live="filterJobsite" class="form-select" title="Jobsite aset">
              <option value="">Semua jobsite</option>
              @foreach ($jobsites as $j)<option value="{{ $j->id }}">{{ $j->code }}</option>@endforeach
            </select>
          </div>
          <div class="col-6 col-md-4 col-lg-2">
            <select wire:model.live="filterDepartment" class="form-select">
              <option value="">Semua departemen</option>
              @foreach ($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
            </select>
          </div>
          <div class="col-6 col-md-4 col-lg-2">
            <select wire:model.live="filterType" class="form-select">
              <option value="">Semua tipe</option>
              @foreach ($types as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach
            </select>
          </div>
          <div class="col-6 col-md-4 col-lg-2">
            <select wire:model.live="filterStatus" class="form-select">
              <option value="">Semua status</option>
              @foreach ($statusLabels as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
            </select>
          </div>
          <div class="col-6 col-md-4 col-lg-1">
            <select wire:model.live="minAssets" class="form-select" title="Minimal jumlah aset">
              <option value="">≥ 1</option>
              @foreach ([2, 3, 5, 10] as $n)<option value="{{ $n }}">≥ {{ $n }}</option>@endforeach
            </select>
          </div>
          <div class="col-6 col-md-4 col-lg-2">
            <select wire:model.live="sort" class="form-select">
              @foreach ($sorts as $k => $label)<option value="{{ $k }}">Urut: {{ $label }}</option>@endforeach
            </select>
          </div>
          @if ($filtersActive)
            <div class="col-6 col-md-4 col-lg-2">
              <button type="button" class="btn btn-label-secondary w-100" wire:click="resetFilters"><i class="bx bx-x me-1"></i>Reset ({{ $filtersActive }})</button>
            </div>
          @endif
        </div>
      </div>

      <div class="d-flex justify-content-between align-items-center px-4 py-2 small text-body-secondary">
        <span>{{ number_format($holders->total()) }} user <span wire:loading.delay class="spinner-border spinner-border-sm ms-2"></span></span>
        <span class="d-flex align-items-center gap-2">Tampilkan
          <select wire:model.live="perPage" class="form-select form-select-sm" style="width:auto">
            @foreach ([20, 50, 100] as $n)<option value="{{ $n }}">{{ $n }}</option>@endforeach
          </select>
        </span>
      </div>

      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th>User</th>
              <th>Departemen / jabatan</th>
              <th style="min-width: 200px">Aset dipegang</th>
              <th class="text-end">Nilai</th>
              <th class="text-end"></th>
            </tr>
          </thead>
          <tbody wire:loading.class="opacity-50">
            @forelse ($holders as $u)
              <tr wire:key="h-{{ $u->id }}">
                <td style="min-width: 220px">
                  <div class="d-flex align-items-center gap-3">
                    <div class="avatar flex-shrink-0">
                      @if ($u->pp_path)
                        <img src="{{ asset('storage/' . $u->pp_path) }}" alt="" class="rounded-circle object-fit-cover w-100 h-100" loading="lazy">
                      @else
                        <span class="avatar-initial rounded-circle bg-label-{{ $tones[$u->id % count($tones)] }}">{{ $initials($u->name) }}</span>
                      @endif
                    </div>
                    <div class="min-w-0">
                      <a href="{{ route('asset.holders.show', $u->id) }}" class="fw-medium text-heading d-block text-truncate">{{ $u->name }}</a>
                      <small class="text-body-secondary">{{ $u->nik }} · {{ $u->jobsite->code ?? '—' }}
                        @unless ($u->is_active)<span class="badge bg-label-danger ms-1">nonaktif</span>@endunless
                      </small>
                    </div>
                  </div>
                </td>
                <td class="small" style="min-width: 180px">
                  <div class="text-truncate" style="max-width: 240px">{{ $u->department->name ?? '—' }}</div>
                  <span class="text-body-secondary text-truncate d-block" style="max-width: 240px">{{ $u->position->name ?? '—' }}</span>
                </td>
                <td>
                  <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="fs-5 fw-semibold">{{ $u->total }}</span>
                    <div class="progress flex-grow-1" style="height: 6px; max-width: 140px">
                      <div class="progress-bar" style="width: {{ round(100 * $u->total / max(1, $summary->max_total)) }}%"></div>
                    </div>
                  </div>
                  <div class="d-flex flex-wrap gap-1">
                    @if ($u->used)<span class="badge bg-label-success">{{ $u->used }} dipakai</span>@endif
                    @if ($u->pending)<span class="badge bg-label-warning">{{ $u->pending }} menunggu BAST</span>@endif
                    @if ($u->borrowed)<span class="badge bg-label-info">{{ $u->borrowed }} dipinjam</span>@endif
                    @if ($u->other)<span class="badge bg-label-secondary">{{ $u->other }} lainnya</span>@endif
                  </div>
                </td>
                <td class="text-end text-nowrap">{{ $u->value > 0 ? $rp($u->value) : '—' }}</td>
                <td class="text-end">
                  <a href="{{ route('asset.holders.show', $u->id) }}" class="btn btn-sm btn-label-primary text-nowrap">Detail <i class="bx bx-chevron-right"></i></a>
                </td>
              </tr>
            @empty
              <tr><td colspan="5"><div class="empty-state"><i class="bx bx-package"></i>Tidak ada pemegang aset yang cocok dengan filter.</div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      @if ($holders->hasPages())
        <div class="card-footer border-top">{{ $holders->links() }}</div>
      @endif
    </div>
  </div>
</div>
