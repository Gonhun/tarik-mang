<div class="container-fluid py-4">
    @if ($isIt)
        <ul class="nav nav-pills mb-4" role="tablist">
            <li class="nav-item">
                <a href="{{ route('dashboard') }}" class="nav-link {{ $showIt ? 'active' : '' }}">
                    <i class="bx bx-buildings me-1"></i>Dashboard IT
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('dashboard', ['view' => 'personal']) }}" class="nav-link {{ $showIt ? '' : 'active' }}">
                    <i class="bx bx-user me-1"></i>Pribadi
                </a>
            </li>
        </ul>
    @endif

    @if ($showIt)
        <livewire:dashboard.it-dashboard />
    @else
        <livewire:dashboard.user-dashboard />
    @endif
</div>
