@extends('layouts.app')

@section('title', 'Events')

@section('content')
    <section class="tich-section">
        <div class="tich-container">
            <header class="tich-section__intro tich-mb-8">
                <h1 class="tich-h1">Events</h1>
                <p class="tich-text tich-mt-2">Symposiums, open days, outreach drives, and institutional calendar highlights.</p>
            </header>

            <div class="tich-grid tich-grid--3">
                @forelse ($events as $event)
                    <article class="tich-card tich-event-card">
                        @if (!empty($event->cover_image_path))
                            <img src="{{ $event->cover_image_path }}" alt="{{ $event->title }}" class="tich-blog-card__image" style="margin-bottom: 1rem;">
                        @endif
                        <p class="tich-caption">{{ strtoupper(str_replace('_', ' ', $event->event_type ?? 'EVENT')) }}</p>
                        <p class="tich-event-card__date tich-mt-2">{{ $event->formatted_date ?? '' }}</p>
                        <h2 class="tich-h3 tich-mt-2">{{ $event->title }}</h2>
                        @if (!empty($event->subtitle))
                            <p class="tich-text tich-mt-2">{{ $event->subtitle }}</p>
                        @elseif (!empty($event->description))
                            <p class="tich-text tich-mt-2">{{ \Illuminate\Support\Str::limit(strip_tags($event->description), 160) }}</p>
                        @endif
                        @if (!empty($event->venue))
                            <p class="tich-caption tich-mt-4">{{ $event->venue }}</p>
                        @endif
                        @if (!empty($event->registration_url_or_form))
                            <a href="{{ $event->registration_url_or_form }}" class="tich-btn tich-btn-secondary tich-mt-4">Register</a>
                        @endif
                    </article>
                @empty
                    <p class="tich-text">No public events are listed yet. Check back soon.</p>
                @endforelse
            </div>
        </div>
    </section>
@endsection
