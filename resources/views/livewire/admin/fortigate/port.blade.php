<div wire:init="fetchGroupData">
    <div class="container-fluid py-4">
        <div class="card">
            <div class="card-header pb-0">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Fortigate HO</h5>
                    {{-- Tombol Refresh --}}
                    <button wire:click="fetchGroupData" class="btn btn-sm btn-outline-primary mb-3">
                        <span wire:loading.remove wire:target="fetchGroupData">
                            <i class="fas fa-sync-alt me-1"></i> Refresh Data
                        </span>
                        <span wire:loading wire:target="fetchGroupData">
                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                            Memuat...
                        </span>
                    </button>
                </div>
            </div>
            <div class="card-body">
                {{-- Tampilkan pesan error jika ada --}}
                @if ($errorMessage)
                    <div class="alert alert-danger text-white">{{ $errorMessage }}</div>
                @endif

                {{-- Tampilkan indikator loading saat data diambil --}}
                <div wire:loading wire:target="fetchGroupData" class="text-center p-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Mengambil data grup...</p>
                </div>

                {{-- Tampilkan tabel hanya jika loading selesai --}}
                <div wire:loading.remove wire:target="fetchGroupData">
                    <div class="table-responsive">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">No.</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Port</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Alias</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">RX Masuk</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">TX Keluar</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($groups as $index => $group)
                                    <tr>
                                        <td>
                                            <p class="text-xs font-weight-bold mb-0 px-3">{{  $index + 1  }}</p>
                                        </td>
                                        <td>
                                            <p class="text-xs font-weight-bold mb-0">{{ $group['name'] ?? 'N/A' }}</p>
                                        </td>
                                        <td>
                                            <p class="text-xs font-weight-bold mb-0">{{ $group['alias'] ?? 'N/A' }}</p>
                                        </td>
                                        <td>
                                            {{-- Mengakses data nested dengan aman --}}
                                            <p class="text-xs font-weight-bold mb-0">{{  $group['rx'] ?? '0' }}</p>
                                        </td>
                                        <td>
                                            {{-- Mengakses data nested dengan aman --}}
                                            <p class="text-xs font-weight-bold mb-0">{{  $group['tx'] ?? '0' }}</p>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-4">
                                            @if(!$errorMessage) {{-- Hanya tampilkan jika tidak ada error --}}
                                                <p class="text-muted mb-0">Tidak ada data grup yang ditemukan.</p>
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
</div>
