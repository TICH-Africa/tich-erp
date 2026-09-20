@extends('layouts.app')

@section('title', 'Events')
@section('meta_description', config('tich-seo.pages.events.description'))

@section('content')
    <x-animated-section animation="top">
        <section class="tich-section tich-section--hero-plain" aria-labelledby="events-heading">
            <div class="tich-container">
                <h1 id="events-heading" class="tich-h1">Events</h1>
                <p class="tich-text tich-mt-4" style="max-width: 42rem;">
                    Symposiums, open days, outreach drives, and institutional calendar highlights.
                </p>
            </div>
        </section>
    </x-animated-section>

    <x-animated-section animation="bottom">
        <section class="tich-section" style="padding-top: 0; margin-top: 2rem;">
            <div class="tich-container">
                <div class="tich-grid tich-grid--3 tich-events-grid">
                    @forelse ($events as $event)
                        <x-animated-card animation="bottom" :delay="$loop->iteration * 140">
                            <article class="tich-event-card tich-event-card--overlay">
                                <a href="{{ $event->url }}" class="tich-event-card__hit">
                                    <div class="tich-event-card__media" aria-hidden="{{ empty($event->cover_image_url) && empty($event->cover_image_path) ? 'true' : 'false' }}">
                                        @if (!empty($event->cover_image_url) || !empty($event->cover_image_path))
                                            <img
                                                src="{{ $event->cover_image_url ?? $event->cover_image_path }}"
                                                alt=""
                                                class="tich-event-card__image"
                                            >
                                        @else
                                            <div class="tich-event-card__placeholder"></div>
                                        @endif
                                    </div>
                                    <div class="tich-event-card__body">
                                        <span class="tich-event-card__tag">{{ strtoupper(str_replace('_', ' ', $event->event_type ?? 'EVENT')) }}</span>
                                        <h2 class="tich-event-card__title">{{ $event->title }}</h2>
                                        @if (!empty($event->formatted_date) || !empty($event->venue))
                                            <p class="tich-event-card__meta">
                                                @if (!empty($event->formatted_date))
                                                    <span>{{ $event->formatted_date }}</span>
                                                @endif
                                                @if (!empty($event->formatted_date) && !empty($event->venue))
                                                    <span aria-hidden="true"> · </span>
                                                @endif
                                                @if (!empty($event->venue))
                                                    <span>{{ $event->venue }}</span>
                                                @endif
                                            </p>
                                        @endif
                                        @if (!empty($event->subtitle))
                                            <p class="tich-event-card__excerpt">{{ $event->subtitle }}</p>
                                        @elseif (!empty($event->description))
                                            <p class="tich-event-card__excerpt">{{ \Illuminate\Support\Str::limit(strip_tags($event->description), 140) }}</p>
                                        @endif
                                        <span class="tich-event-card__cta">View event →</span>
                                    </div>
                                </a>
                            </article>
                        </x-animated-card>
                    @empty
                        <x-animated-card animation="fade">
                            <p class="tich-text">No public events are listed yet. Check back soon.</p>
                        </x-animated-card>
                    @endforelse
                </div>
            </div>
        </section>
    </x-animated-section>
@endsection
