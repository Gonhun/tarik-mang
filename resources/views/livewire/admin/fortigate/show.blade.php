@php
    $fmt = fn ($b) => \App\Support\FortigateClient::formatBytes((int) $b);
    $statusMeta = ['online' => ['Online', 'success'], 'offline' => ['Offline', 'danger'], 'auth_error' => ['Token ditolak', 'warning']];
    [$stLabel, $stTone] = $statusMeta[$device->last_status] ?? ['Belum dicek', 'secondary'];
    $cols = $def['cols'] ?? [];

    // Warna badge aksi/status.
    $badgeTone = function ($v) {
        $v = strtolower((string) $v);
        return match (true) {
            in_array($v, ['up', 'alive', 'online', 'pass', 'passthrough', 'allowed', 'accept'], true) => 'success',
            in_array($v, ['blocked', 'block', 'deny', 'dropped', 'drop', 'down', 'reset', 'dead', 'detected', 'quarantine'], true) => 'danger',
            in_array($v, ['warning', 'monitored', 'sebagian', 'clear_session'], true) => 'warning',
            default => 'secondary',
        };
    };
    $severityTone = function ($v) {
        $v = strtolower((string) $v);
        return match (true) {
            in_array($v, ['critical', 'high', '5', '4'], true) => 'danger',
            in_array($v, ['medium', 'elevated', '3'], true) => 'warning',
            in_array($v, ['low', 'info', 'information', '1', '2'], true) => 'info',
            default => 'secondary',
        };
    };
    $groups = collect($tabs)->groupBy(fn ($t) => $t['group'] ?? '', true);
@endphp
<div>
  <div class="container-fluid py-4">
    <div class="page-header">
      <div class="min-w-0">
        <a href="{{ route('admin.fortigate') }}" class="small text-body-secondary"><i class="bx bx-chevron-left"></i>Daftar Fortigate</a>
        <h4 class="mb-1 mt-1 d-flex align-items-center gap-2 flex-wrap">
          {{ $device->name }}
          <span class="badge bg-label-{{ $stTone }}">{{ $stLabel }}</span>
        </h4>
        <div class="page-subtitle">
          <i class="bx bx-map-pin"></i> {{ $device->jobsite ? $device->jobsite->code . ' — ' . $device->jobsite->name : 'Tanpa jobsite' }}
          · {{ parse_url($device->base_url, PHP_URL_HOST) }}{{ parse_url($device->base_url, PHP_URL_PORT) ? ':' . parse_url($device->base_url, PHP_URL_PORT) : '' }}
          {{ $device->vdom ? '· VDOM ' . $device->vdom : '' }}
        </div>
      </div>
    </div>

    <div class="row g-4">
      {{-- Menu tab (kiri di desktop, atas di HP) --}}
      <div class="col-lg-3 col-xl-2">
        <div class="card">
          <div class="list-group list-group-flush fg-tabs">
            @foreach ($groups as $group => $items)
              @if ($group)
                <div class="list-group-item small text-uppercase text-body-secondary fw-semibold bg-body py-1 border-0 mt-1">{{ $group }}</div>
              @endif
              @foreach ($items as $key => $t)
                <button type="button" wire:click="setTab('{{ $key }}')"
                        class="list-group-item list-group-item-action d-flex align-items-center gap-2 border-0 {{ $tab === $key ? 'active' : '' }}">
                  <i class="bx {{ $t['icon'] }}"></i><span class="text-truncate">{{ $t['label'] }}</span>
                </button>
              @endforeach
            @endforeach
          </div>
        </div>
      </div>

      <div class="col-lg-9 col-xl-10">
        @if ($tab === 'api')
          <livewire:admin.fortigate.api :device-id="$device->id" :embedded="true" :key="'api-' . $device->id" />
        @else
          <div class="card">
            <div class="card-header">
              <h5 class="card-title">{{ $def['label'] }}
                @if ($loaded && is_array($rows) && $tab !== 'ringkasan')
                  <small class="text-body-secondary fw-normal">{{ count($rows) }} baris{{ $totalBytes ? ' · total ' . $fmt($totalBytes) : '' }}</small>
                @endif
              </h5>
              @if (!empty($def['period']))
                <div class="btn-group btn-group-sm" role="group" aria-label="Periode">
                  @foreach ($periods as $p => $plabel)
                    <button type="button" class="btn {{ $period === $p ? 'btn-primary' : 'btn-outline-primary' }}" wire:click="$set('period', '{{ $p }}')">{{ $plabel }}</button>
                  @endforeach
                </div>
              @endif
              @if ($loaded && $tab !== 'ringkasan')
                <input type="search" wire:model.live.debounce.300ms="search" class="form-control form-control-sm" style="max-width: 200px" placeholder="Cari…">
              @endif
              @if ($loaded)
                <small class="text-body-secondary">Diambil {{ $loadedTime }}</small>
                <button type="button" class="btn btn-sm btn-icon btn-outline-primary" title="Muat ulang" wire:click="load('{{ $tab }}')" wire:loading.attr="disabled" wire:target="load">
                  <i class="bx bx-refresh" wire:loading.class="bx-spin" wire:target="load"></i>
                </button>
              @endif
            </div>

            @if (!$loaded)
              {{-- Data baru diambil saat tab/periode ini dibuka --}}
              <div class="card-body" wire:init="load('{{ $tab }}')" wire:key="init-{{ $loadKey }}">
                <div class="empty-state">
                  <span class="spinner-border text-primary"></span>
                  <div class="mt-2">Mengambil data {{ strtolower($def['label']) }} dari {{ $device->name }}…</div>
                </div>
              </div>
            @elseif ($error)
              <div class="card-body">
                <div class="alert alert-danger d-flex gap-2 mb-0"><i class="bx bx-error-circle fs-5"></i><div class="text-break">{{ $error }}</div></div>
              </div>
            @elseif ($tab === 'ringkasan')
              @php $s = $rows; @endphp
              <div class="card-body">
                <div class="row g-3 mb-4">
                  @foreach ([['CPU', $s['cpu'], '%', 'bx-chip'], ['Memori', $s['mem'], '%', 'bx-memory-card'], ['Disk', $s['disk'], '%', 'bx-hdd'], ['Sesi aktif', $s['sessions'], '', 'bx-transfer-alt']] as [$k, $v, $unit, $icon])
                    @php $warn = $unit === '%' && $v !== null && $v >= 80; @endphp
                    <div class="col-6 col-xl-3">
                      <div class="border rounded p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                          <small class="text-body-secondary">{{ $k }}</small><i class="bx {{ $icon }} text-body-secondary"></i>
                        </div>
                        <div class="fs-4 fw-semibold {{ $warn ? 'text-danger' : '' }}">{{ $v === null ? '-' : number_format($v) . $unit }}</div>
                        @if ($unit === '%' && $v !== null)
                          <div class="progress mt-2" style="height: 5px"><div class="progress-bar bg-{{ $warn ? 'danger' : 'primary' }}" style="width: {{ min(100, $v) }}%"></div></div>
                        @endif
                      </div>
                    </div>
                  @endforeach
                </div>
                <dl class="row mb-0 small">
                  @foreach (['Hostname' => $s['hostname'], 'Model' => $s['model'], 'Serial' => $s['serial'], 'FortiOS' => trim(($s['version'] ?? '') . (!empty($s['build']) ? ' build ' . $s['build'] : '')), 'Sesi baru / detik' => $s['setup_rate']] as $k => $v)
                    <dt class="col-sm-3 text-body-secondary fw-normal">{{ $k }}</dt>
                    <dd class="col-sm-9 fw-medium">{{ filled($v) ? $v : '-' }}</dd>
                  @endforeach
                  @if ($device->notes)
                    <dt class="col-sm-3 text-body-secondary fw-normal">Catatan</dt>
                    <dd class="col-sm-9">{{ $device->notes }}</dd>
                  @endif
                </dl>
              </div>
            @elseif ($tab === 'interface')
              <div class="table-responsive">
                <table class="table table-hover mb-0">
                  <thead><tr><th>Port</th><th>Alias</th><th>IP</th><th>Link</th><th class="text-end">RX</th><th class="text-end">TX</th><th class="text-end">Error</th></tr></thead>
                  <tbody>
                    @forelse ($rows as $i)
                      <tr>
                        <td class="fw-medium">{{ $i['name'] }}</td>
                        <td>{{ $i['alias'] ?: '-' }}</td>
                        <td><code class="small">{{ $i['ip'] ?? '-' }}</code></td>
                        <td>
                          @if ($i['link'])<span class="badge bg-label-success">Up{{ $i['speed'] ? ' · ' . $i['speed'] . ' Mbps' : '' }}</span>
                          @else<span class="badge bg-label-secondary">Down</span>@endif
                        </td>
                        <td class="text-end">{{ $fmt($i['rx']) }}</td>
                        <td class="text-end">{{ $fmt($i['tx']) }}</td>
                        <td class="text-end {{ $i['rx_errors'] + $i['tx_errors'] ? 'text-danger' : 'text-body-secondary' }}">{{ number_format($i['rx_errors'] + $i['tx_errors']) }}</td>
                      </tr>
                    @empty
                      <tr><td colspan="7"><div class="empty-state"><i class="bx bx-search"></i>Tidak ada data.</div></td></tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            @else
              {{-- Tabel generik sesuai definisi kolom --}}
              <div class="table-responsive">
                <table class="table table-hover mb-0">
                  <thead>
                    <tr>
                      <th style="width: 40px">#</th>
                      @foreach ($cols as [$label, $type])
                        <th class="{{ in_array($type, ['bytes', 'num'], true) ? 'text-end' : '' }}">{{ $label }}</th>
                      @endforeach
                    </tr>
                  </thead>
                  <tbody>
                    @forelse ($rows as $r)
                      <tr>
                        <td class="text-body-secondary">{{ $loop->iteration }}</td>
                        @foreach ($cols as $c => [$label, $type])
                          @php $v = $r[$c] ?? ''; @endphp
                          @switch($type)
                            @case('bytes')
                              <td class="text-end text-nowrap">{{ $fmt($v) }}</td>
                              @break
                            @case('num')
                              <td class="text-end">{{ is_numeric($v) ? number_format($v) : ($v ?: '-') }}</td>
                              @break
                            @case('bar')
                              <td style="min-width: 150px">
                                <div class="d-flex align-items-center gap-2">
                                  <div class="progress flex-grow-1" style="height: 6px"><div class="progress-bar" style="width: {{ round(100 * (int) $v / ($barMax[$c] ?? 1)) }}%"></div></div>
                                  <span class="small text-nowrap fw-medium">{{ $fmt($v) }}</span>
                                </div>
                              </td>
                              @break
                            @case('code')
                              <td class="text-break" style="max-width: 320px"><code class="small">{{ $v !== '' ? $v : '-' }}</code></td>
                              @break
                            @case('badge')
                              <td>@if ($v !== '')<span class="badge bg-label-{{ $badgeTone($v) }}">{{ $v }}</span>@else - @endif</td>
                              @break
                            @case('severity')
                              <td>@if ($v !== '')<span class="badge bg-label-{{ $severityTone($v) }}">{{ $v }}</span>@else - @endif</td>
                              @break
                            @default
                              <td class="text-break" style="max-width: 280px">{{ $v !== '' ? $v : '-' }}</td>
                          @endswitch
                        @endforeach
                      </tr>
                    @empty
                      <tr><td colspan="{{ count($cols) + 1 }}">
                        <div class="empty-state"><i class="bx bx-search"></i>
                          {{ $search ? 'Tidak ada yang cocok dengan pencarian.' : (in_array($tab, ['virus', 'webfilter', 'ips'], true) ? 'Tidak ada log. Bagus, atau logging belum diaktifkan di Fortigate.' : 'Tidak ada data untuk periode ini.') }}
                        </div>
                      </td></tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
              @if (!empty($def['period']) && $period !== 'realtime')
                <div class="card-footer small text-body-secondary border-top">Data historis diambil dari log di disk Fortigate. Jika kosong, pastikan log disk aktif atau pilih "Saat ini".</div>
              @endif
            @endif
          </div>
        @endif
      </div>
    </div>
  </div>

  <style>
    .fg-tabs .list-group-item.active { background: rgba(var(--bs-primary-rgb), .1); color: var(--bs-primary); font-weight: 600; }
    @media (max-width: 991.98px) {
      .fg-tabs { flex-direction: row; overflow-x: auto; flex-wrap: nowrap; }
      .fg-tabs .list-group-item { white-space: nowrap; width: auto; }
      .fg-tabs .list-group-item.small { display: none; }
    }
  </style>
</div>
