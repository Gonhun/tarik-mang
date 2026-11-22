<li class="menu-item {{ $active ? 'active' : '' }}" @if($badgeType) wire:poll.60s.visible @endif>
    <a href="{{ route($routeName) }}" class="menu-link">
        @if($icon)
            <i class="menu-icon tf-icons {{ $icon }}"></i>
        @endif
        <div class="text-truncate">{{ $label }}</div>
        @if($badgeCount > 0)
            <span class="badge rounded-pill bg-danger ms-auto" title="{{ $badgeCount }} menunggu tindakan">{{ $badgeCount > 99 ? '99+' : $badgeCount }}</span>
        @endif
    </a>
</li>
