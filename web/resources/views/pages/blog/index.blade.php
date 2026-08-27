@extends('layouts.app')

@section('title', 'Blog')
@section('meta_description', config('tich-seo.pages.blog.description'))

@section('content')
    <section class="tich-section" aria-labelledby="blog-heading">
        <div class="tich-container">
            <header class="tich-section__intro tich-mb-8">
                <h1 id="blog-heading" class="tich-h1">Blog</h1>
                <p class="tich-text tich-mt-2">News, student stories, and admissions updates from across TICH campuses.</p>
            </header>

            <div class="tich-grid tich-grid--3">
                @forelse ($blogPosts as $post)
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
                            <h2 class="tich-h3 tich-mt-2">{{ $post->title }}</h2>
                            @if (!empty($post->excerpt))
                                <p class="tich-text tich-mt-2">{{ $post->excerpt }}</p>
                            @endif
                            <a href="{{ $post->url }}" class="tich-link tich-mt-4" style="display: inline-block;">Read article</a>
                        </div>
                    </article>
                @empty
                    <p class="tich-text">No blog posts have been published yet.</p>
                @endforelse
            </div>
        </div>
    </section>
@endsection
