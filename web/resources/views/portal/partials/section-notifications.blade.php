@php
    $notifications = $notifications ?? collect();
@endphp

<x-page-toolbar title="Notifications" meta="Alerts about fees, exams, and academics" />

<div class="tich-mt-8">
    @forelse ($notifications as $notification)
        <article class="tich-card tich-mb-4" style="{{ $notification->isUnread() ? 'border-left:4px solid var(--tich-primary, #0d6efd);' : '' }}">
            <div style="display:flex; justify-content:space-between; gap:1rem; align-items:start;">
                <div>
                    <p class="tich-caption">{{ ucfirst($notification->category) }} · {{ $notification->created_at?->format('d M Y H:i') }}</p>
                    <h2 class="tich-h3 tich-mt-1">{{ $notification->title }}</h2>
                    @if ($notification->body)
                        <p class="tich-text tich-mt-2">{{ $notification->body }}</p>
                    @endif
                </div>
                @if ($notification->isUnread())
                    <form method="POST" action="{{ route('portal.notifications.read', $notification) }}">
                        @csrf
                        <button type="submit" class="tich-btn tich-btn-ghost tich-btn--compact">Mark read</button>
                    </form>
                @endif
            </div>
        </article>
    @empty
        @include('partials.states.empty', [
            'title' => 'No notifications yet',
            'description' => 'Fee reminders, exam notices, and academic alerts will appear here.',
            'icon' => 'inbox',
        ])
    @endforelse
</div>
