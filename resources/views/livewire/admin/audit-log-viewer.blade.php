<div>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Log Audit Aktivitas</h5>
                            {{-- Tambahkan tombol filter di sini jika diperlukan --}}
                        </div>
                         @if (session()->has('message'))
                            <div class="alert alert-success text-white mt-3" role="alert">
                                {{ session('message') }}
                            </div>
                        @endif
                    </div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="px-4 pt-3">
                            <input wire:model.live.debounce.500ms="search" type="text" class="form-control" placeholder="Cari event, tipe model, URL, IP, atau user...">
                        </div>
                        <div class="table-responsive p-0 mt-3">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Waktu</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">User</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Aksi</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Model Terdampak</th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">ID Model</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">URL</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">IP Address</th>
                                        <th class="text-secondary opacity-7"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($audits as $audit)
                                        <tr>
                                            <td>
                                                <p class="text-xs font-weight-bold mb-0 px-3" data-bs-toggle="tooltip" title="{{ $audit->created_at->format('d M Y H:i:s') }}">
                                                    {{ $audit->created_at->diffForHumans() }}
                                                </p>
                                            </td>
                                            <td>
                                                <p class="text-xs font-weight-bold mb-0">
                                                    {{ $audit->user->name ?? ($audit->user_id ?? 'Sistem/Tamu') }}
                                                </p>
                                                @if($audit->user)
                                                <p class="text-xs text-secondary mb-0">{{ $audit->user->email ?? '' }}</p>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge badge-sm bg-gradient-{{ 
                                                    $audit->event == 'created' ? 'success' : 
                                                    ($audit->event == 'updated' ? 'info' : 
                                                    ($audit->event == 'deleted' ? 'danger' : 
                                                    ($audit->event == 'restored' ? 'warning' : 'secondary'))) 
                                                    }}">
                                                    {{ ucfirst($audit->event) }}
                                                </span>
                                            </td>
                                            <td><p class="text-xs font-weight-bold mb-0">{{ $this->getCleanModelName($audit->auditable_type) }}</p></td>
                                            <td class="text-center"><p class="text-xs font-weight-bold mb-0">{{ $audit->auditable_id }}</p></td>
                                            <td><p class="text-xs font-weight-bold mb-0" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $audit->url }}">{{ $audit->url }}</p></td>
                                            <td><p class="text-xs font-weight-bold mb-0">{{ $audit->ip_address }}</p></td>
                                            <td class="align-middle">
                                                <button wire:click="viewDetails({{ $audit->id }})" class="btn btn-link text-primary p-1 mb-0" data-bs-toggle="tooltip" title="Lihat Detail Perubahan">
                                                    <i class="bx bx-bxs-bullseye text-sm"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="8" class="text-center py-4">Tidak ada data log audit ditemukan.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="px-4 pt-3">
                            {{ $audits->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Detail Perubahan Audit --}}
    @if ($isDetailModalOpen && $selectedAudit)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Detail Audit #{{ $selectedAudit->id }}</h5>
                        <button type="button" class="btn-close" wire:click="closeDetailModal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p><strong>Waktu:</strong> {{ $selectedAudit->created_at->format('d M Y, H:i:s') }} ({{ $selectedAudit->created_at->diffForHumans() }})</p>
                        <p><strong>User:</strong> {{ $selectedAudit->user->name ?? ($selectedAudit->user_id ?? 'Sistem/Tamu') }}</p>
                        <p><strong>Aksi:</strong> <span class="badge bg-gradient-info">{{ ucfirst($selectedAudit->event) }}</span></p>
                        <p><strong>Model Terdampak:</strong> {{ $this->getCleanModelName($selectedAudit->auditable_type) }} (ID: {{ $selectedAudit->auditable_id }})</p>
                        <p><strong>URL:</strong> {{ $selectedAudit->url }}</p>
                        <p><strong>IP Address:</strong> {{ $selectedAudit->ip_address }}</p>
                        <p><strong>User Agent:</strong> <small>{{ $selectedAudit->user_agent }}</small></p>

                        @if (!empty($selectedAudit->old_values) || !empty($selectedAudit->new_values))
                            <hr>
                            <h6>Perubahan Data:</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead>
                                        <tr>
                                            <th>Field</th>
                                            <th>Nilai Lama</th>
                                            <th>Nilai Baru</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $oldV = (array) ($selectedAudit->old_values ?? []);
                                            $newV = (array) ($selectedAudit->new_values ?? []);
                                            $allKeys = array_keys($oldV + $newV); // Gabungkan semua kunci untuk perbandingan
                                        @endphp
                                        @forelse ($allKeys as $key)
                                            @if(array_key_exists($key, $oldV) || array_key_exists($key, $newV))
                                                @if(isset($oldV[$key]) || isset($newV[$key])) {{-- Hanya tampilkan jika ada nilai lama atau baru --}}
                                                    @if (!isset($oldV[$key]) || !isset($newV[$key]) || $oldV[$key] !== $newV[$key] || $selectedAudit->event == 'created')
                                                    <tr>
                                                        <td><strong>{{ Str::title(str_replace('_', ' ', $key)) }}</strong></td>
                                                        <td>
                                                            @if(is_array($oldV[$key] ?? null)) {{ json_encode($oldV[$key] ?? '') }}
                                                            @else {{ $oldV[$key] ?? '' }}
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if(is_array($newV[$key] ?? null)) {{ json_encode($newV[$key] ?? '') }}
                                                            @else {{ $newV[$key] ?? '' }}
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    @endif
                                                @endif
                                            @endif
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center"><em>Tidak ada detail perubahan data yang tercatat atau data tidak berubah (untuk event 'updated').</em></td>
                                            </tr>
                                        @endforelse
                                         @if($selectedAudit->event == 'deleted' && !empty($oldV) && empty($allKeys))
                                            @foreach ($oldV as $key => $value)
                                            <tr>
                                                <td><strong>{{ Str::title(str_replace('_', ' ', $key)) }}</strong></td>
                                                <td>
                                                    @if(is_array($value)) {{ json_encode($value) }}
                                                    @else {{ $value }}
                                                    @endif
                                                </td>
                                                <td class="text-muted"><em>(dihapus)</em></td>
                                            </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p><em>Tidak ada detail perubahan nilai lama/baru yang tercatat untuk event ini.</em></p>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeDetailModal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
    // Skrip untuk tooltip Bootstrap (sama seperti sebelumnya)
    document.addEventListener('livewire:load', function () {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    });
    Livewire.hook('message.processed', (message, component) => {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        tooltipTriggerList.forEach(function (tooltipTriggerEl) {
            var existingTooltip = bootstrap.Tooltip.getInstance(tooltipTriggerEl);
            if (existingTooltip) {
                existingTooltip.dispose();
            }
            new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>
@endpush