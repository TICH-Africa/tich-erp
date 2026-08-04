@php
    $userId = auth()->id();
    $unreadNotifications = [];
    $unreadCount = 0;

    if ($userId) {
        $unreadNotifications = \Illuminate\Support\Facades\DB::table('notifications')
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'title', 'body', 'created_at', 'related_entity_type', 'related_entity_id']);

        $unreadCount = \Illuminate\Support\Facades\DB::table('notifications')
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->count();
    }
@endphp

<div class="tich-notification-bell" style="position: relative; display: inline-block;">
    <button class="tich-btn tich-btn-ghost" style="padding: 8px 12px; position: relative;" onclick="document.getElementById('notification-dropdown').classList.toggle('is-open')">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
        </svg>
        @if ($unreadCount > 0)
            <span class="tich-notification-badge" style="position: absolute; top: 2px; right: 2px; background: #e53e3e; color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 11px; display: flex; align-items: center; justify-content: center; font-weight: 600;">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div id="notification-dropdown" class="tich-notification-dropdown" style="display: none; position: absolute; top: 100%; right: 0; width: 360px; max-height: 480px; overflow-y: auto; background: white; border: 1px solid var(--tich-neutral-border); border-radius: var(--radius-md); box-shadow: 0 10px 25px rgba(0,0,0,0.1); z-index: 1000; margin-top: 8px;">
        <div class="tich-notification-dropdown__header" style="padding: 12px 16px; border-bottom: 1px solid var(--tich-neutral-border); display: flex; justify-content: space-between; align-items: center;">
            <strong>Notifications</strong>
            <span class="tich-caption">{{ $unreadCount }} unread</span>
        </div>

        <div class="tich-notification-dropdown__list">
            @forelse ($unreadNotifications as $notification)
                <a href="{{ $notification->related_entity_type && $notification->related_entity_id ? '#' : '#' }}" class="tich-notification-item" style="display: block; padding: 12px 16px; border-bottom: 1px solid var(--tich-neutral-border); text-decoration: none; color: inherit; transition: background 0.15s;">
                    <p class="tich-notification-item__title" style="font-weight: 600; margin-bottom: 4px; color: var(--tich-neutral-900);">{{ $notification->title }}</p>
                    <p class="tich-notification-item__body" style="font-size: 13px; color: var(--tich-neutral-600); margin-bottom: 4px;">{{ $notification->body }}</p>
                    <p class="tich-notification-item__time" style="font-size: 11px; color: var(--tich-neutral-400);">{{ \Carbon\Carbon::parse($notification->created_at)->diffForHumans() }}</p>
                </a>
            @empty
                <p class="tich-notification-empty" style="padding: 24px 16px; text-align: center; color: var(--tich-neutral-500);">No notifications</p>
            @endforelse
        </div>
    </div>
</div>

<script>
    document.addEventListener('click', function(e) {
        const bell = document.querySelector('.tich-notification-bell');
        const dropdown = document.getElementById('notification-dropdown');
        if (bell && dropdown && !bell.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
</script>
