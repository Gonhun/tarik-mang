<div>
  <div class="{{ $embedded ? '' : 'container-fluid py-4' }}">
    @unless ($embedded)
    <div class="page-header">
      <div>
        <h4 class="mb-1">API Fortigate</h4>
        <div class="page-subtitle">
          <a href="{{ route('admin.fortigate') }}" class="text-body-secondary"><i class="bx bx-chevron-left"></i>Daftar perangkat</a>
          · hanya membaca data (GET)
        </div>
      </div>
      <select wire:model.live="deviceId" class="form-select form-select-sm" style="min-width: 240px; max-width: 320px">
        @forelse ($devices as $d)
          <option value="{{ $d->id }}">{{ $d->jobsite->code ?? '—' }} · {{ $d->name }}</option>
        @empty
          <option value="">Belum ada perangkat</option>
        @endforelse
      </select>
    </div>
    @endunless

    @if ($errorMessage)
      <div class="alert alert-danger d-flex gap-2"><i class="bx bx-error-circle fs-5"></i><div class="text-break">{{ $errorMessage }}</div></div>
    @endif

    @if (!$device)
      <div class="card"><div class="card-body"><div class="empty-state"><i class="bx bx-shield-quarter"></i>Belum ada Fortigate aktif.</div></div></div>
    @elseif ($category === '')
      <div class="row g-3">
        @foreach ($categories as $key => $label)
          <div class="col-sm-6 col-lg-4">
            <button type="button" class="card w-100 text-start border-0 h-100" wire:click="fetchGroupData('{{ $key }}')" wire:loading.attr="disabled">
              <div class="card-body d-flex align-items-center gap-3">
                <span class="avatar"><span class="avatar-initial rounded bg-label-primary"><i class="bx {{ $key === 'log' ? 'bx-list-ul' : 'bx-line-chart' }} fs-4"></i></span></span>
                <div class="flex-grow-1">
                  <h6 class="mb-0">{{ $label }}</h6>
                  <small class="text-body-secondary">Daftar endpoint /{{ $key }}</small>
                </div>
                <span wire:loading wire:target="fetchGroupData('{{ $key }}')" class="spinner-border spinner-border-sm text-primary"></span>
              </div>
            </button>
          </div>
        @endforeach
      </div>
    @else
      <div class="card">
        <div class="card-header">
          <button type="button" class="btn btn-sm btn-label-secondary" wire:click="back"><i class="bx bx-chevron-left"></i> Kategori</button>
          <h5 class="card-title">{{ $categories[$category] }} <small class="text-body-secondary fw-normal">{{ $rows->count() }} endpoint</small></h5>
          <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" id="onlySupported" wire:model.live="onlySupported">
            <label class="form-check-label small" for="onlySupported">Hanya yang didukung</label>
          </div>
          <input type="search" wire:model.live.debounce.300ms="search" class="form-control form-control-sm" style="max-width: 240px" placeholder="Cari path / nama…">
        </div>
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr><th>Endpoint</th><th>Keterangan</th><th>Parameter</th><th class="text-end">Aksi</th></tr>
            </thead>
            <tbody>
              @forelse ($rows as $e)
                <tr wire:key="ep-{{ $e['index'] }}">
                  <td style="min-width: 220px">
                    <code class="small">{{ $e['path'] }}/{{ $e['name'] }}</code>
                    <div><span class="badge bg-label-{{ $e['action'] === 'select' ? 'info' : 'secondary' }}">{{ $e['action'] }}</span>
                      @unless ($e['supported'])<span class="badge bg-label-danger">tidak didukung</span>@endunless
                    </div>
                  </td>
                  <td class="small" style="min-width: 220px">{{ $e['summary'] ?: '-' }}</td>
                  <td class="small">
                    @forelse ($e['params'] as $p)
                      <div><b>{{ $p['name'] }}</b> <span class="text-body-secondary">{{ $p['type'] }}</span></div>
                    @empty
                      <span class="text-body-secondary">-</span>
                    @endforelse
                  </td>
                  <td class="text-end">
                    @if ($e['action'] === 'select' && $e['supported'])
                      <button type="button" class="btn btn-sm btn-label-primary" wire:click="run({{ $e['index'] }})" wire:loading.attr="disabled" wire:target="run({{ $e['index'] }})">
                        <span wire:loading.remove wire:target="run({{ $e['index'] }})">Coba</span>
                        <span wire:loading wire:target="run({{ $e['index'] }})" class="spinner-border spinner-border-sm"></span>
                      </button>
                    @endif
                  </td>
                </tr>
              @empty
                <tr><td colspan="4"><div class="empty-state"><i class="bx bx-search"></i>Tidak ada endpoint yang cocok.</div></td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    @endif
  </div>

  @if ($resultJson !== null)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.45)" wire:keydown.escape.window="closeResult">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h6 class="modal-title"><code>GET /api/v2/{{ $resultPath }}</code></h6>
            <button type="button" class="btn-close" wire:click="closeResult" aria-label="Tutup"></button>
          </div>
          <div class="modal-body p-0">
            <pre class="m-0 p-3 small bg-body" style="max-height: 70vh; white-space: pre-wrap; word-break: break-all;">{{ $resultJson }}</pre>
          </div>
        </div>
      </div>
    </div>
  @endif
</div>
