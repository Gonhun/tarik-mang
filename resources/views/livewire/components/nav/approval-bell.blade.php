<li class="nav-item dropdown me-3 me-xl-2" wire:poll.60s.visible>
    <a class="nav-link dropdown-toggle hide-arrow position-relative p-1" href="javascript:void(0);" data-bs-toggle="dropdown" data-bs-auto-close="outside"
        aria-expanded="false" aria-label="{{ $total > 0 ? $total . ' menunggu persetujuan Anda' : 'Tidak ada yang perlu disetujui' }}">
        <i class="bx bx-bell fs-4 {{ $total > 0 ? 'bell-ring' : '' }}"></i>
        @if ($total > 0)
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: .65rem;">
                {{ $total > 99 ? '99+' : $total }}
            </span>
        @endif
    </a>

    <div class="dropdown-menu dropdown-menu-end p-0" style="width: min(360px, calc(100vw - 2rem));">
        <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
            <h6 class="mb-0">Perlu persetujuan</h6>
            <div class="d-flex align-items-center gap-2">
                <span wire:loading class="spinner-border spinner-border-sm text-primary" role="status" aria-label="Memuat"></span>
                @if ($total > 0)
                    <span class="badge bg-label-danger">{{ $total }} menunggu</span>
                @endif
            </div>
        </div>

        @if ($total > 0)
            <div class="d-flex flex-wrap gap-1 px-3 py-2 border-bottom small">
                @foreach ([
                    'asset' => 'Pengajuan aset',
                    'transaction' => 'Mutasi/disposal',
                    'license' => 'Lisensi',
                    'it' => 'Validasi IT',
                ] as $key => $label)
                    @if ($counts[$key] > 0)
                        <span class="badge bg-label-secondary">{{ $label }}: {{ $counts[$key] }}</span>
                    @endif
                @endforeach
            </div>
        @endif

        <ul class="list-group list-group-flush" style="max-height: 360px; overflow-y: auto;">
            @forelse ($items as $item)
                <li class="list-group-item list-group-item-action p-0">
                    <a href="{{ $item['url'] }}" class="d-flex align-items-start gap-2 px-3 py-2 text-reset text-decoration-none">
                        <span class="avatar avatar-sm flex-shrink-0">
                            <span class="avatar-initial rounded-circle bg-label-{{ $item['color'] }}"><i class="bx {{ $item['icon'] }}"></i></span>
                        </span>
                        <span class="flex-grow-1 min-w-0">
                            <span class="d-block fw-medium text-truncate">{{ $item['title'] }}</span>
                            <small class="d-block text-muted text-truncate">{{ $item['meta'] }}</small>
                            <small class="text-muted">{{ $item['at']?->locale('id')->diffForHumans() }}</small>
                        </span>
                    </a>
                </li>
            @empty
                <li class="list-group-item text-center text-muted py-4">
                    <i class="bx bx-check-circle fs-3 d-block mb-1 text-success"></i>
                    Tidak ada yang perlu Anda setujui.
                </li>
            @endforelse
        </ul>

        @if ($total > count($items))
            <div class="border-top px-3 py-2 text-center small">
                <a href="{{ route('dashboard', ['view' => 'personal']) }}">Lihat semua di dashboard ({{ $total }})</a>
            </div>
        @endif
    </div>

    <style>
        @keyframes bell-ring { 0%, 100% { transform: rotate(0); } 10%, 30% { transform: rotate(14deg); } 20%, 40% { transform: rotate(-14deg); } 50% { transform: rotate(0); } }
        .bell-ring { display: inline-block; transform-origin: top center; animation: bell-ring 2.4s ease-in-out 1s 2; }
        @media (prefers-reduced-motion: reduce) { .bell-ring { animation: none; } }
    </style>
</li>
