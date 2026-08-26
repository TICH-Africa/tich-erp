@extends('layouts.app')

@section('title', $event->title)

@section('content')
    <article class="tich-event-show">
        <header class="tich-event-show__hero">
            @if (!empty($event->cover_image_url) || !empty($event->cover_image_path))
                <div class="tich-event-show__hero-media" aria-hidden="true">
                    <img
                        src="{{ $event->cover_image_url ?? $event->cover_image_path }}"
                        alt=""
                        class="tich-event-show__hero-image"
                    >
                    <div class="tich-event-show__hero-overlay"></div>
                </div>
            @else
                <div class="tich-event-show__hero-fallback" aria-hidden="true"></div>
            @endif

            <div class="tich-container tich-event-show__hero-content">
                <p class="tich-event-show__back">
                    <a href="{{ route('events') }}" class="tich-event-show__back-link">← All events</a>
                </p>
                <p class="tich-event-show__eyebrow">{{ strtoupper(str_replace('_', ' ', $event->event_type ?? 'EVENT')) }}</p>
                <h1 class="tich-event-show__title">{{ $event->title }}</h1>
                @if (!empty($event->subtitle))
                    <p class="tich-event-show__lead">{{ $event->subtitle }}</p>
                @endif
            </div>
        </header>

        <div class="tich-container tich-event-show__body">
            <div class="tich-event-show__layout">
                <div class="tich-event-show__main">
                    @if (!empty($event->description))
                        <h2 class="tich-h3">About this event</h2>
                        <div class="tich-event-show__description">{{ $event->description }}</div>
                    @else
                        <p class="tich-text">More details for this event will be published soon.</p>
                    @endif
                </div>

                <aside class="tich-event-show__aside">
                    <h2 class="tich-h3">Details</h2>
                    <dl class="tich-event-show__meta">
                        @if (!empty($event->formatted_date))
                            <div>
                                <dt>Starts</dt>
                                <dd>{{ $event->formatted_date }}@if (!empty($event->formatted_time)) · {{ $event->formatted_time }}@endif</dd>
                            </div>
                        @endif
                        @if (!empty($event->formatted_end))
                            <div>
                                <dt>Ends</dt>
                                <dd>{{ $event->formatted_end }}</dd>
                            </div>
                        @endif
                        @if (!empty($event->venue))
                            <div>
                                <dt>Venue</dt>
                                <dd>{{ $event->venue }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt>Type</dt>
                            <dd>{{ ucfirst(str_replace('_', ' ', $event->event_type ?? 'event')) }}</dd>
                        </div>
                    </dl>

                    <div class="tich-event-show__actions">
                        @if (!empty($event->registration_url_or_form))
                            <a href="{{ $event->registration_url_or_form }}" class="tich-btn tich-btn-primary">Register</a>
                        @endif
                        <a href="{{ route('events') }}" class="tich-btn tich-btn-secondary">Browse events</a>
                    </div>
                </aside>
            </div>
        </div>
    </article>
@endsection
