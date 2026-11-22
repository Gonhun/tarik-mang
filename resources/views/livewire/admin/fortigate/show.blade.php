@php
    $fmt = fn ($b) => \App\Support\FortigateClient::formatBytes($b);
    $statusMeta = ['online' => ['Online', 'success'], 'offline' => ['Offline', 'danger'], 'auth_error' => ['Token ditolak', 'warning']];
    [$stLabel, $stTone] = $statusMeta[$device->last_status] ?? ['Belum dicek', 'secondary'];
    $cols = $tabs[$tab][3];
@endphp
<div>
  <div class="container-fluid py-4">
    <div class="page-header">
      <div class="min-w-0">
        <a href="{{ route('admin.fortigate') }}" class="small text-body-secondary"><i class="bx bx-chevron-left"></i>Daftar Fortigate</a>
        <h4 class="mb-1 mt-1 d-flex align-items-center gap-2 flex-wrap">
          {{ $device->name }}
          <span class="badge bg-label-{{ $stTone }} fs-tiny">{{ $stLabel }}</span>
        </h4>
        <div class="page-subtitle">
          <i class="bx bx-map-pin"></i> {{ $device->jobsite ? $device->jobsite->code . ' — ' . $device->jobsite->name : 'Tanpa jobsite' }}
          · {{ parse_url($device->base_url, PHP_URL_HOST) }}{{ parse_url($device->base_url, PHP_URL_PORT) ? ':' . parse_url($device->base_url, PHP_URL_PORT) : '' }}
          {{ $device->vdom ? '· VDOM ' . $device->vdom : '' }}
        </div>
      </div>
    </div>

    {{-- Tab --}}
    <ul class="nav nav-pills flex-nowrap overflow-auto mb-4 pb-1 gap-1" role="tablist">
      @foreach ($tabs as $key => [$label, $icon])
        <li class="nav-item">
          <button type="button" class="nav-link text-nowrap {{ $tab === $key ? 'active' : '' }}" wire:click="setTab('{{ $key }}')">
            <i class="bx {{ $icon }} me-1"></i>{{ $label }}
          </button>
        </li>
      @endforeach
    </ul>

    @if ($tab === 'api')
      {{-- Penjelajah API untuk perangkat ini --}}
      <livewire:admin.fortigate.api :device-id="$device->id" :embedded="true" :key="'api-' . $device->id" />
    @else
      <div class="card">
        <div class="card-header">
          <h5 class="card-title">{{ $tabs[$tab][0] }}
            @if ($loaded && is_array($rows) && $tab !== 'ringkasan')
              <small class="text-body-secondary fw-normal">{{ count($rows) }} baris</small>
            @endif
          </h5>
          @if ($loaded && $tab !== 'ringkasan')
            <input type="search" wire:model.live.debounce.300ms="search" class="form-control form-control-sm" style="max-width: 220px" placeholder="Cari…">
          @endif
          @if ($loaded)
            <small class="text-body-secondary">Diambil {{ $loadedAt[$tab] ?? '' }}</small>
            <button type="button" class="btn btn-sm btn-outline-primary" wire:click="load('{{ $tab }}')" wire:loading.attr="disabled" wire:target="load">
              <i class="bx bx-refresh me-1" wire:loading.class="bx-spin" wire:target="load"></i>Muat ulang
            </button>
          @endif
        </div>

        @if (!$loaded)
          {{-- Data baru diambil saat tab ini dibuka --}}
          <div class="card-body" wire:init="load('{{ $tab }}')" wire:key="init-{{ $tab }}">
            <div class="empty-state">
              <span class="spinner-border text-primary"></span>
              <div class="mt-2">Mengambil data {{ strtolower($tabs[$tab][0]) }} dari {{ $device->name }}…</div>
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
                <div class="col-6 col-lg-3">
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
              @foreach (['Hostname' => $s['hostname'], 'Model' => $s['model'], 'Serial' => $s['serial'], 'FortiOS' => trim(($s['version'] ?? '') . ($s['build'] ? ' build ' . $s['build'] : '')), 'Sesi baru / detik' => $s['setup_rate']] as $k => $v)
                <dt class="col-sm-3 text-body-secondary fw-normal">{{ $k }}</dt>
                <dd class="col-sm-9 fw-medium">{{ filled($v) ? $v : '-' }}</dd>
              @endforeach
              @if ($device->notes)
                <dt class="col-sm-3 text-body-secondary fw-normal">Catatan</dt>
                <dd class="col-sm-9">{{ $device->notes }}</dd>
              @endif
            </dl>
          </div>
        @else
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              @if ($tab === 'interface')
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
              @elseif ($tab === 'vpn')
                <thead><tr><th>Tunnel</th><th>Remote gateway</th><th>Status</th><th>Phase 2 up</th><th class="text-end">RX</th><th class="text-end">TX</th></tr></thead>
                <tbody>
                  @forelse ($rows as $t)
                    <tr>
                      <td class="fw-medium">{{ $t['name'] }}</td>
                      <td><code class="small">{{ $t['remote'] }}</code></td>
                      <td><span class="badge bg-label-{{ $t['status'] === 'up' ? 'success' : ($t['status'] === 'sebagian' ? 'warning' : 'danger') }}">{{ ucfirst($t['status']) }}</span></td>
                      <td>{{ $t['phase2'] }}</td>
                      <td class="text-end">{{ $fmt($t['rx']) }}</td>
                      <td class="text-end">{{ $fmt($t['tx']) }}</td>
                    </tr>
                  @empty
                    <tr><td colspan="6"><div class="empty-state"><i class="bx bx-lock-open-alt"></i>Tidak ada tunnel IPsec.</div></td></tr>
                  @endforelse
                </tbody>
              @else
                <thead><tr>@foreach ($cols as $label)<th>{{ $label }}</th>@endforeach</tr></thead>
                <tbody>
                  @forelse ($rows as $r)
                    <tr>
                      @foreach (array_keys($cols) as $c)
                        <td>
                          @if ($c === 'status')
                            <span class="badge bg-label-{{ in_array(strtolower($r[$c]), ['up', 'alive', 'online'], true) ? 'success' : 'danger' }}">{{ $r[$c] }}</span>
                          @elseif (in_array($c, ['ip', 'mac', 'destination', 'gateway'], true))
                            <code class="small">{{ $r[$c] }}</code>
                          @else
                            {{ $r[$c] !== '' ? $r[$c] : '-' }}
                          @endif
                        </td>
                      @endforeach
                    </tr>
                  @empty
                    <tr><td colspan="{{ count($cols) }}"><div class="empty-state"><i class="bx bx-search"></i>Tidak ada data.</div></td></tr>
                  @endforelse
                </tbody>
              @endif
            </table>
          </div>
          @if (is_array($rows) && count($rows) >= \App\Support\FortigateClient::MAX_ROWS)
            <div class="card-footer small text-body-secondary">Menampilkan {{ \App\Support\FortigateClient::MAX_ROWS }} baris pertama.</div>
          @endif
        @endif
      </div>
    @endif
  </div>
</div>
