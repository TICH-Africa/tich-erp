@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
    <section class="tich-section tich-notif-page">
        <div class="tich-container tich-notif-page__wrap">
            <header class="tich-notif-header">
                <div class="tich-notif-header__title">
                    <h1>Notifications</h1>
                    <span class="tich-notif-header__count">
                        {{ $unreadCount > 0 ? $unreadCount.' unread' : 'All caught up' }}
                    </span>
                </div>
                @if ($unreadCount > 0)
                    <form method="POST" action="{{ route('notifications.read-all') }}">
                        @csrf
                        <button type="submit" class="tich-notif-mark-all">Mark all as read</button>
                    </form>
                @endif
            </header>

            <div class="tich-notif-list">
                @forelse ($notifications as $notification)
                    @php
                        $actionUrl = $notification->actionUrl(auth()->user());
                        $openUrl = route('notifications.open', $notification);
                        $isUnread = $notification->isUnread();
                    @endphp
                    <article class="tich-notif-card{{ $isUnread ? '' : ' is-read' }}"@if ($isUnread) data-unread @endif>
                        <div class="tich-notif-card__body">
                            <p class="tich-notif-meta">
                                <span>{{ ucfirst($notification->priority ?? 'normal') }}</span>
                                <span class="tich-notif-meta__sep" aria-hidden="true">•</span>
                                <span>{{ $notification->created_at?->diffForHumans() }}</span>
                                @if ($notification->created_at)
                                    <span class="tich-notif-meta__sep" aria-hidden="true">•</span>
                                    <span>{{ $notification->created_at->format('d M Y H:i') }}</span>
                                @endif
                                @if ($actionUrl)
                                    <span class="tich-notif-meta__sep" aria-hidden="true">•</span>
                                    <a href="{{ $openUrl }}">Open related page</a>
                                @endif
                            </p>
                            <h2 class="tich-notif-card__title">{{ $notification->title }}</h2>
                            @if ($notification->body)
                                <p class="tich-notif-card__desc">{{ $notification->body }}</p>
                            @endif
                        </div>
                        <div class="tich-notif-actions">
                            @if ($actionUrl)
                                <a href="{{ $openUrl }}" class="tich-notif-btn tich-notif-btn--open">Open</a>
                            @endif
                            @if ($isUnread)
                                <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                    @csrf
                                    <button type="submit" class="tich-notif-btn tich-notif-btn--ghost">Mark read</button>
                                </form>
                            @else
                                <span class="tich-notif-read-label">Read</span>
                            @endif
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
