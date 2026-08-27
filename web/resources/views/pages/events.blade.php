@extends('layouts.app')

@section('title', 'Events')
@section('meta_description', config('tich-seo.pages.events.description'))

@section('content')
    <x-animated-section animation="fade">
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
                <div class="tich-grid tich-grid--3">
                    @forelse ($events as $event)
                        <x-animated-card animation="bottom" :delay="$loop->iteration * 140">
                            <article class="tich-card tich-program-card">
                                <div class="tich-program-card__media">
                                    @if (!empty($event->cover_image_url) || !empty($event->cover_image_path))
                                        <img
                                            src="{{ $event->cover_image_url ?? $event->cover_image_path }}"
                                            alt="{{ $event->title }}"
                                            class="tich-program-card__image"
                                        >
                                    @else
                                        <div class="tich-program-card__placeholder" aria-hidden="true"></div>
                                    @endif
                                </div>
                                <div class="tich-program-card__body">
                                    <p class="tich-caption">{{ strtoupper(str_replace('_', ' ', $event->event_type ?? 'EVENT')) }}</p>
                                    <p class="tich-event-card__date tich-mt-2">{{ $event->formatted_date ?? '' }}</p>
                                    <h2 class="tich-h3 tich-mt-2">{{ $event->title }}</h2>
                                    @if (!empty($event->subtitle))
                                        <p class="tich-text tich-mt-2">{{ $event->subtitle }}</p>
                                    @elseif (!empty($event->description))
                                        <p class="tich-text tich-mt-2">{{ \Illuminate\Support\Str::limit(strip_tags($event->description), 140) }}</p>
                                    @endif
                                    @if (!empty($event->venue))
                                        <p class="tich-caption tich-mt-4">{{ $event->venue }}</p>
                                    @endif
                                    <a href="{{ $event->url }}" class="tich-btn tich-btn-primary tich-mt-4">View event</a>
                                </div>
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
