
    <li class="menu-item {{ request()->routeIs($routeName) ? 'active' : '' }}" wire:poll.5s>
        <a href="{{ route($routeName) }}" class="menu-link {{ !empty($icon) ? 'd-flex align-items-center' : '' }}">
            @if($icon)
                <i class="menu-icon tf-icons {{ $icon }}"></i>
            @endif
            <div class="text-truncate" data-i18n="{{ $label }}">{{ $label }}</div>
           
            {{-- Tampilkan badge hanya jika hitungannya lebih dari 0 --}}
            @if($badgeCount > 0)
                <span class="badge bg-danger rounded-pill ms-2">{{ $badgeCount }}</span>
            @endif
        </a>
    </li>
    {{-- <li class="menu-item {{ request()->routeIs('asset-requests.*') ? 'active' : '' }}">
        <a
          href="{{ route('asset-requests.dashboard') }}"
          class="menu-link">
          <i class="menu-icon tf-icons bx bx-file"></i>
          <div class="text-truncate" data-i18n="Documentation">Request Asset</div>
        </a>
      </li> --}}
      