@php
    $tone = match ($state) {
        'open' => 'success',
        'connecting' => 'warning',
        'loading' => 'secondary',
        default => 'danger',
    };
@endphp
<div wire:init="loadStatus">
  <div class="container-fluid py-4">
    <div class="page-header">
      <div>
        <h4 class="mb-1">WhatsApp</h4>
        <div class="page-subtitle">Nomor pengirim notifikasi AMS, terhubung lewat WhatsApp gateway.</div>
      </div>
      <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="loadStatus" wire:loading.attr="disabled" wire:target="loadStatus">
        <i class="bx bx-refresh me-1" wire:loading.class="bx-spin" wire:target="loadStatus"></i> Muat ulang status
      </button>
    </div>

    @if ($errorMessage)
      <div class="alert alert-danger d-flex gap-2 align-items-start">
        <i class="bx bx-error-circle fs-5"></i>
        <div class="text-break">{{ $errorMessage }}</div>
      </div>
    @endif

    <div class="row g-4">
      {{-- ================= Koneksi ================= --}}
      <div class="col-lg-7">
        <div class="card h-100">
          <div class="card-header">
            <h5 class="card-title">Koneksi</h5>
            <span class="badge bg-label-{{ $tone }}">
              @if ($state === 'loading')<span class="spinner-border spinner-border-sm me-1"></span>@endif
              {{ $stateLabel }}
            </span>
          </div>
          <div class="card-body">
            @if ($state === 'loading')
              <div class="empty-state"><span class="spinner-border text-primary"></span><div class="mt-2">Memeriksa koneksi…</div></div>

            @elseif ($state === 'unconfigured')
              <div class="empty-state">
                <i class="bx bx-cog"></i>
                Isi <code>WHATSAPP_API_URL</code>, <code>WHATSAPP_API_KEY</code>, dan <code>WHATSAPP_INSTANCE</code> di file <code>.env</code>, lalu jalankan <code>php artisan config:clear</code>.
              </div>

            @elseif ($state === 'error')
              <div class="empty-state">
                <i class="bx bx-wifi-off"></i>
                Server WhatsApp gateway tidak dapat dihubungi. Periksa URL dan API key, lalu muat ulang.
              </div>

            @elseif ($state === 'open')
              <div class="d-flex align-items-center gap-3 mb-4">
                <div class="avatar avatar-xl flex-shrink-0">
                  @if (!empty($profile['picture']))
                    <img src="{{ $profile['picture'] }}" alt="" class="rounded-circle object-fit-cover w-100 h-100" referrerpolicy="no-referrer">
                  @else
                    <span class="avatar-initial rounded-circle bg-label-success"><i class="bx bxl-whatsapp fs-2"></i></span>
                  @endif
                </div>
                <div class="min-w-0">
                  <h5 class="mb-0 text-truncate">{{ $profile['name'] ?? 'WhatsApp' }}</h5>
                  <div class="text-body-secondary">{{ $profile['number'] ?? '-' }}</div>
                  <small class="text-success"><i class="bx bxs-circle" style="font-size:.55rem"></i> Siap mengirim notifikasi</small>
                </div>
              </div>
              <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="restart" wire:loading.attr="disabled">
                  <i class="bx bx-revision me-1"></i> Restart sesi
                </button>
                <button type="button" class="btn btn-outline-danger btn-sm" wire:click="logout"
                        wire:confirm="Lepas nomor ini? Notifikasi WhatsApp akan berhenti sampai nomor dihubungkan lagi.">
                  <i class="bx bx-log-out me-1"></i> Lepas nomor
                </button>
              </div>

            @elseif ($state === 'connecting' && ($qr || $pairingCode))
              {{-- Menunggu scan: cek status tiap 4 detik, QR diperbarui otomatis --}}
              <div wire:poll.4s="pollConnection" class="row g-4 align-items-center">
                <div class="col-md-6 text-center">
                  @if ($pairingCode)
                    <div class="border rounded p-4">
                      <small class="text-body-secondary d-block mb-1">Kode pairing</small>
                      <div class="fs-2 fw-bold font-monospace" style="letter-spacing:.2em">{{ $pairingCode }}</div>
                    </div>
                  @else
                    <img src="{{ $qr }}" alt="QR WhatsApp" class="img-fluid border rounded p-2 bg-white" style="max-width: 260px; width: 100%;">
                    <small class="d-block text-body-secondary mt-2"><span class="spinner-grow spinner-grow-sm text-warning me-1"></span>QR diperbarui otomatis</small>
                  @endif
                </div>
                <div class="col-md-6">
                  <h6>Cara menghubungkan</h6>
                  <ol class="ps-3 mb-3 small">
                    <li>Buka WhatsApp di HP nomor pengirim.</li>
                    <li>Ketuk <b>⋮ / Setelan</b> → <b>Perangkat tertaut</b> → <b>Tautkan perangkat</b>.</li>
                    @if ($pairingCode)
                      <li>Pilih <b>Tautkan dengan nomor telepon</b>, lalu masukkan kode di samping.</li>
                    @else
                      <li>Arahkan kamera ke QR di samping.</li>
                    @endif
                    <li>Halaman ini otomatis berubah setelah terhubung.</li>
                  </ol>
                  <button type="button" class="btn btn-sm btn-label-secondary" wire:click="cancelConnect">Batal</button>
                </div>
              </div>

            @else
              {{-- close / missing --}}
              <div class="text-center py-3">
                <i class="bx bxl-whatsapp text-success" style="font-size: 3rem"></i>
                <p class="mb-3">Belum ada nomor yang terhubung. Notifikasi WhatsApp tidak akan terkirim sampai nomor dihubungkan.</p>
                <button type="button" class="btn btn-success" wire:click="startConnect" wire:loading.attr="disabled" wire:target="startConnect">
                  <span wire:loading.remove wire:target="startConnect"><i class="bx bx-qr-scan me-1"></i> Hubungkan dengan scan QR</span>
                  <span wire:loading wire:target="startConnect"><span class="spinner-border spinner-border-sm me-1"></span> Menyiapkan QR…</span>
                </button>
              </div>
              <hr>
              <form wire:submit="pairWithPhone" class="row g-2 align-items-start">
                <div class="col-12"><small class="text-body-secondary">Tidak bisa scan? Dapatkan kode pairing untuk dimasukkan di HP:</small></div>
                <div class="col-sm">
                  <input type="text" wire:model="pairPhone" inputmode="tel" class="form-control form-control-sm @error('pairPhone') is-invalid @enderror" placeholder="Nomor WA pengirim, mis. 0812xxxx">
                  @error('pairPhone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-sm-auto">
                  <button type="submit" class="btn btn-sm btn-outline-success w-100" wire:loading.attr="disabled" wire:target="pairWithPhone">Minta kode</button>
                </div>
              </form>
            @endif
          </div>
          <div class="card-footer border-top small text-body-secondary d-flex flex-wrap gap-3">
            <span><i class="bx bx-server me-1"></i>{{ parse_url((string) $apiUrl, PHP_URL_HOST) ?: '-' }}</span>
            <span><i class="bx bx-box me-1"></i>Instance: <b>{{ $instanceName }}</b></span>
            @if (!empty($server['version']))<span>Gateway v{{ $server['version'] }}</span>@endif
          </div>
        </div>
      </div>

      {{-- ================= Pesan uji ================= --}}
      <div class="col-lg-5">
        <div class="card h-100">
          <div class="card-header"><h5 class="card-title">Kirim pesan uji</h5></div>
          <div class="card-body">
            <form wire:submit="sendTest">
              <div class="mb-3">
                <label class="form-label" for="testNumber">Nomor tujuan</label>
                <input id="testNumber" type="text" wire:model="testNumber" inputmode="tel" class="form-control @error('testNumber') is-invalid @enderror" placeholder="0812xxxx atau 62812xxxx">
                @error('testNumber') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="mb-3">
                <label class="form-label" for="testMessage">Pesan</label>
                <textarea id="testMessage" wire:model="testMessage" rows="3" class="form-control @error('testMessage') is-invalid @enderror"></textarea>
                @error('testMessage') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <button type="submit" class="btn btn-primary w-100" @disabled($state !== 'open') wire:loading.attr="disabled" wire:target="sendTest">
                <span wire:loading.remove wire:target="sendTest"><i class="bx bx-send me-1"></i> Kirim</span>
                <span wire:loading wire:target="sendTest"><span class="spinner-border spinner-border-sm me-1"></span> Mengirim…</span>
              </button>
              @if ($state !== 'open')
                <small class="d-block text-body-secondary mt-2">Hubungkan nomor WhatsApp terlebih dahulu.</small>
              @endif
            </form>
          </div>
        </div>
      </div>

      {{-- ================= Grup ================= --}}
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h5 class="card-title">Grup WhatsApp</h5>
            @if ($groupsLoaded)
              <input type="search" wire:model.live.debounce.300ms="groupSearch" class="form-control form-control-sm" style="max-width: 240px" placeholder="Cari grup…">
            @endif
            <button type="button" class="btn btn-sm btn-outline-primary" wire:click="loadGroups" @disabled($state !== 'open') wire:loading.attr="disabled" wire:target="loadGroups">
              <span wire:loading.remove wire:target="loadGroups"><i class="bx bx-refresh me-1"></i>{{ $groupsLoaded ? 'Muat ulang' : 'Tampilkan grup' }}</span>
              <span wire:loading wire:target="loadGroups"><span class="spinner-border spinner-border-sm me-1"></span>Memuat…</span>
            </button>
          </div>
          @if (!$groupsLoaded)
            <div class="card-body">
              <div class="empty-state"><i class="bx bx-group"></i>{{ $state === 'open' ? 'Klik "Tampilkan grup" untuk memuat grup yang diikuti nomor ini.' : 'Grup tersedia setelah nomor terhubung.' }}</div>
            </div>
          @else
            <div class="table-responsive">
              <table class="table table-hover mb-0">
                <thead>
                  <tr>
                    <th style="width:48px">#</th>
                    <th>Nama grup</th>
                    <th>Anggota</th>
                    <th>ID grup</th>
                    <th class="text-end">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse ($filteredGroups as $g)
                    <tr wire:key="g-{{ $g['id'] }}">
                      <td class="text-body-secondary">{{ $loop->iteration }}</td>
                      <td class="fw-medium">{{ $g['subject'] }}</td>
                      <td>{{ $g['size'] ?? '-' }}</td>
                      <td><code class="small user-select-all">{{ $g['id'] }}</code></td>
                      <td class="text-end">
                        <button type="button" class="btn btn-sm btn-label-success" wire:click="openSendMessageModal(@js($g['id']))">
                          <i class="bx bx-send me-1"></i>Kirim pesan
                        </button>
                      </td>
                    </tr>
                  @empty
                    <tr><td colspan="5"><div class="empty-state"><i class="bx bx-search"></i>Tidak ada grup yang cocok.</div></td></tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>

  {{-- Modal kirim ke grup --}}
  @if ($isSendMessageModalOpen)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.45)" wire:keydown.escape.window="closeSendMessageModal">
      <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" wire:submit="sendMessage">
          <div class="modal-header">
            <h5 class="modal-title">Kirim ke {{ $targetGroupName }}</h5>
            <button type="button" class="btn-close" wire:click="closeSendMessageModal" aria-label="Tutup"></button>
          </div>
          <div class="modal-body">
            <textarea wire:model="messageBody" rows="6" class="form-control @error('messageBody') is-invalid @enderror" placeholder="Tulis pesan…" autofocus></textarea>
            @error('messageBody') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <small class="text-body-secondary d-block mt-2">Nama Anda ditambahkan di akhir pesan.</small>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-label-secondary" wire:click="closeSendMessageModal">Batal</button>
            <button type="submit" class="btn btn-success" wire:loading.attr="disabled" wire:target="sendMessage">
              <span wire:loading.remove wire:target="sendMessage"><i class="bx bx-send me-1"></i>Kirim</span>
              <span wire:loading wire:target="sendMessage"><span class="spinner-border spinner-border-sm me-1"></span>Mengirim…</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  @endif
</div>
