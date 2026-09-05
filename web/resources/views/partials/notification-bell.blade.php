@auth
    @php
        $navUnreadCount = \App\Models\InAppNotification::unreadCountForUser((int) auth()->id());
        $navUnreadLabel = $navUnreadCount > 99 ? '99+' : (string) $navUnreadCount;
    @endphp

    <a
        href="{{ route('notifications.index') }}"
        class="tich-nav-notification{{ request()->routeIs('notifications.*') ? ' is-active' : '' }}"
        aria-label="{{ $navUnreadCount > 0 ? 'Notifications, '.$navUnreadCount.' unread' : 'Notifications' }}"
        title="Notifications"
    >
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
        </svg>
        @if ($navUnreadCount > 0)
            <span class="tich-nav-notification__badge" aria-hidden="true">{{ $navUnreadLabel }}</span>
        @endif
    </a>
@endauth
