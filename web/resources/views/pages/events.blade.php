@extends('layouts.app')

@section('title', 'Events & Blog')

@section('content')
    <section class="tich-section">
        <div class="tich-container">
            <div class="tich-section__intro">
                <h2 class="tich-h2">Events &amp; conferences</h2>
                <p class="tich-text">Symposiums, open days, outreach drives, and institutional calendar highlights.</p>
                @if (!empty($usingFallback['events']))
                    <p class="tich-caption">Showing sample events until the events registry is populated.</p>
                @endif
            </div>

            <div class="tich-grid tich-grid--3">
                @foreach ($events as $event)
                    <article class="tich-card tich-event-card">
                        <p class="tich-caption">{{ strtoupper(str_replace('_', ' ', $event->event_type ?? 'EVENT')) }}</p>
                        <p class="tich-event-card__date tich-mt-2">{{ $event->formatted_date ?? '' }}</p>
                        <h3 class="tich-h3 tich-mt-2">{{ $event->title }}</h3>
                        @if (!empty($event->subtitle))
                            <p class="tich-text tich-mt-2">{{ $event->subtitle }}</p>
                        @endif
                        @if (!empty($event->venue))
                            <p class="tich-caption tich-mt-4">{{ $event->venue }}</p>
                        @endif
                        @if (!empty($event->registration_url_or_form))
                            <a href="{{ $event->registration_url_or_form }}" class="tich-btn tich-btn-secondary tich-mt-4">Register</a>
                        @endif
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="tich-section tich-section--white">
        <div class="tich-container">
            <div class="tich-section__intro">
                <h2 class="tich-h2">Latest from the blog</h2>
                <p class="tich-text">News, student stories, and admissions updates from across TICH campuses.</p>
                @if (!empty($usingFallback['blogPosts']))
                    <p class="tich-caption">Showing default articles until blog posts are published.</p>
                @endif
            </div>

            <div class="tich-grid tich-grid--3">
                @foreach ($blogPosts as $post)
                    <article class="tich-card tich-blog-card">
                        @if (!empty($post->featured_image_path))
                            <img src="{{ $post->featured_image_path }}" alt="{{ $post->title }}" class="tich-blog-card__image">
                        @else
                            <div class="tich-blog-card__placeholder" aria-hidden="true"></div>
                        @endif
                        <div class="tich-blog-card__body">
                            <p class="tich-caption">{{ $post->formatted_date ?? '' }}
                                @if (!empty($post->reading_time_minutes))
                                    · {{ $post->reading_time_minutes }} min read
                                @endif
                            </p>
                            <h3 class="tich-h3 tich-mt-2">{{ $post->title }}</h3>
                            @if (!empty($post->excerpt))
                                <p class="tich-text tich-mt-2">{{ $post->excerpt }}</p>
                            @endif
                            <a href="{{ $post->url ?? '#blog' }}" class="tich-link tich-mt-4" style="display: inline-block;">Read article</a>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endsection
