<div class="container-fluid py-4">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
        <div>
            <a href="{{ route('admin.workflow') }}" class="small text-muted"><i class="bx bx-chevron-left"></i>Aturan validasi</a>
            <h4 class="mb-1 mt-1">Master jenis transaksi</h4>
            <p class="text-muted mb-0">Jenis transaksi yang bisa diberi aturan validasi (workflow approval).</p>
        </div>
        <a href="{{ route('admin.workflow') }}" class="btn btn-primary btn-sm"><i class="bx bx-plus me-1"></i>Tambah aturan validasi</a>
    </div>

    <div class="alert alert-info small d-flex gap-2" role="note">
        <i class="bx bx-info-circle fs-5 flex-shrink-0"></i>
        <span>Daftar ini disimpan di file <code>config/transaction_types.php</code> (bukan tabel). Perubahan nama, deskripsi, status aktif, atau fallback dilakukan oleh developer di file tersebut, lalu jalankan <code>php artisan config:clear</code>. Kode harus sama dengan yang dipakai aplikasi.</span>
    </div>

    <div class="row g-4">
        @foreach ($rows as $row)
            @php
                $aktif = $row['aktif'] ?? true;
                $fallbackOk = $row['fallback'] && ($rows[$row['fallback']]['valid_rules'] ?? 0) > 0;
                [$stColor, $stLabel] = match (true) {
                    !$aktif => ['secondary', 'Nonaktif'],
                    $row['has_default'] => ['success', 'Siap'],
                    $row['valid_rules'] > 0 => ['warning', 'Sebagian'],
                    $fallbackOk => ['info', 'Pakai ' . ($rows[$row['fallback']]['label'] ?? $row['fallback'])],
                    default => ['danger', 'Belum diatur'],
                };
            @endphp
            <div class="col-12 col-xl-6">
                <div class="card h-100 {{ $aktif ? '' : 'opacity-75' }}">
                    <div class="card-header d-flex justify-content-between align-items-start gap-2 pb-2">
                        <div>
                            <h5 class="card-title mb-0">{{ $row['label'] }}</h5>
                            <code class="small">{{ $row['code'] }}</code>
                            @if ($row['wajib'] ?? false)<span class="badge bg-label-primary ms-1">Wajib</span>@endif
                        </div>
                        <span class="badge bg-label-{{ $stColor }}">{{ $stLabel }}</span>
                    </div>
                    <div class="card-body small">
                        <p class="mb-2">{{ $row['deskripsi'] }}</p>
                        @if (!empty($row['dipakai_di']))
                            <p class="mb-2"><span class="text-muted">Dipakai di:</span> {{ implode(' · ', $row['dipakai_di']) }}</p>
                        @endif
                        @if ($row['fallback'])
                            <p class="mb-2"><span class="text-muted">Fallback:</span> {{ $rows[$row['fallback']]['label'] ?? $row['fallback'] }} ({{ $row['fallback'] }})</p>
                        @endif

                        <span class="text-muted d-block mb-1">Aturan validasi ({{ $row['rules']->count() }}):</span>
                        @forelse ($row['rules'] as $a)
                            <div class="d-flex justify-content-between border-top py-1">
                                <span>{{ $a->position->name ?? 'Semua jabatan' }} · {{ $a->jobsite->code ?? 'Semua site' }}</span>
                                <span class="text-muted">{{ $a->approvalWorkflow->name ?? '-' }} · prioritas {{ $a->priority }}</span>
                            </div>
                        @empty
                            <span class="text-danger">Belum ada aturan.</span>
                        @endforelse
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if ($unknown->isNotEmpty())
        <div class="alert alert-warning small mt-4" role="alert">
            <strong>Aturan dengan jenis yang tidak ada di master:</strong> {{ $unknown->implode(', ') }}.
            Aturan ini tidak dipakai aplikasi; hapus di halaman Aturan validasi atau tambahkan jenisnya ke master.
        </div>
    @endif
</div>
