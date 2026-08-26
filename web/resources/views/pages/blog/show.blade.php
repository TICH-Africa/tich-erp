@extends('layouts.app')

@section('title', $post->title)

@section('content')
    <article class="tich-section">
        <div class="tich-container" style="max-width: 46rem;">
            <p class="tich-caption">
                <a href="{{ route('blog') }}" class="tich-link">← Back to blog</a>
            </p>

            <header class="tich-mt-6">
                <p class="tich-caption">{{ $post->formatted_date ?? '' }}
                    @if (!empty($post->reading_time_minutes))
                        · {{ $post->reading_time_minutes }} min read
                    @endif
                </p>
                <h1 class="tich-h1 tich-mt-2">{{ $post->title }}</h1>
                @if (!empty($post->subtitle))
                    <p class="tich-text tich-mt-2">{{ $post->subtitle }}</p>
                @endif
            </header>

            @if (!empty($post->featured_image_path))
                <img
                    src="{{ $post->featured_image_path }}"
                    alt="{{ $post->title }}"
                    class="tich-mt-8"
                    style="width: 100%; height: auto; max-height: 26rem; object-fit: cover; border-radius: var(--radius-md, 0.5rem);"
                >
            @endif

            <div class="tich-text tich-mt-8" style="white-space: pre-wrap;">{{ $post->body }}</div>
        </div>
    </article>
@endsection
