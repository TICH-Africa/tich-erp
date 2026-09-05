@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
    <section class="tich-section">
        <div class="tich-container" style="max-width: 48rem;">
            <x-page-toolbar title="Notifications" :meta="$unreadCount > 0 ? $unreadCount.' unread' : 'All caught up'">
                <x-slot:actions>
                    @if ($unreadCount > 0)
                        <form method="POST" action="{{ route('notifications.read-all') }}">
                            @csrf
                            <button type="submit" class="tich-btn tich-btn-secondary">Mark all as read</button>
                        </form>
                    @endif
                </x-slot:actions>
            </x-page-toolbar>

            <div class="tich-mt-6">
                @forelse ($notifications as $notification)
                    @php
                        $actionUrl = $notification->actionUrl(auth()->user());
                        $openUrl = route('notifications.open', $notification);
                    @endphp
                    <article class="tich-card tich-mb-4 tich-notification-card{{ $notification->isUnread() ? ' tich-notification-card--unread' : '' }}{{ $actionUrl ? ' tich-notification-card--linked' : '' }}">
                        <div class="tich-notification-card__row">
                            <a href="{{ $openUrl }}" class="tich-notification-card__link">
                                <p class="tich-caption">
                                    {{ ucfirst($notification->priority ?? 'normal') }}
                                    · {{ $notification->created_at?->diffForHumans() }}
                                    @if ($notification->created_at)
                                        · {{ $notification->created_at->format('d M Y H:i') }}
                                    @endif
                                    @if ($actionUrl)
                                        · Open related page
                                    @endif
                                </p>
                                <h2 class="tich-h3 tich-mt-1">{{ $notification->title }}</h2>
                                @if ($notification->body)
                                    <p class="tich-text tich-mt-2">{{ $notification->body }}</p>
                                @endif
                            </a>
                            <div class="tich-notification-card__actions">
                                @if ($actionUrl)
                                    <a href="{{ $openUrl }}" class="tich-btn tich-btn-primary tich-btn--compact">Open</a>
                                @endif
                                @if ($notification->isUnread())
                                    <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                        @csrf
                                        <button type="submit" class="tich-btn tich-btn-ghost tich-btn--compact">Mark read</button>
                                    </form>
                                @else
                                    <span class="tich-caption">Read</span>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    @include('partials.states.empty', [
                        'title' => 'No notifications yet',
                        'description' => 'Alerts about leave, profile updates, contracts, and other activity will appear here.',
                        'icon' => 'inbox',
                    ])
                @endforelse
            </div>
        </div>
    </section>
@endsection
