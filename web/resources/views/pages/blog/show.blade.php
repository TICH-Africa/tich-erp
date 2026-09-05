@extends('layouts.app')

@section('title', $post->seo_meta_title ?? $post->title)
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($post->seo_meta_description ?? $post->subtitle ?? $post->excerpt ?? $post->body ?? $post->title), 160, ''))

@php
    $seo = [
        'type' => 'article',
        'image' => $post->featured_image_path ?? null,
        'url' => route('blog.show', $post->slug),
        'published_time' => optional($post->published_at ?? $post->created_at ?? null)->toAtomString(),
        'modified_time' => optional($post->updated_at ?? null)->toAtomString(),
    ];
@endphp

@section('content')
    <x-animated-section animation="fade">
        <article class="tich-section tich-article" itemscope itemtype="https://schema.org/BlogPosting">
            <div class="tich-container" style="max-width: 48rem;">
                <p class="tich-caption tich-article-back">
                    <a href="{{ route('blog') }}" class="tich-link">← Back to blog</a>
                </p>

                <header class="tich-mt-6">
                    <p class="tich-caption">{{ $post->formatted_date ?? '' }}
                        @if (!empty($post->reading_time_minutes))
                            · {{ $post->reading_time_minutes }} min read
                        @endif
                    </p>
                    <h1 class="tich-h1 tich-mt-2" itemprop="headline">{{ $post->title }}</h1>
                    @if (!empty($post->subtitle))
                        <p class="tich-text tich-mt-2" itemprop="description">{{ $post->subtitle }}</p>
                    @endif

                    <div class="tich-article-actions">
                        <button type="button" class="tich-btn tich-btn-secondary" onclick="window.print()">Print</button>
                        <a href="{{ route('blog.pdf', $post->slug) }}" class="tich-btn tich-btn-primary">Download PDF</a>
                    </div>
                </header>

                @if (!empty($post->featured_image_path))
                    <figure class="tich-mt-8">
                        <img
                            src="{{ $post->featured_image_path }}"
                            alt="{{ $post->title }}"
                            itemprop="image"
                            style="width: 100%; height: auto; max-height: 26rem; object-fit: cover; border-radius: var(--radius-md, 0.5rem);"
                        >
                    </figure>
                @endif

                <div class="tich-prose-article tich-mt-8" itemprop="articleBody">
                    {!! $post->body !!}
                </div>
            </div>
        </article>
    </x-animated-section>
@endsection

@section('seo_jsonld')
    @include('partials.seo-jsonld-organization')
    @php
        $articleLd = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $post->title,
            'description' => $post->subtitle ?: \Illuminate\Support\Str::limit(strip_tags((string) $post->body), 160, ''),
            'image' => $post->featured_image_path ?? null,
            'datePublished' => optional($post->published_at ?? $post->created_at ?? null)->toAtomString(),
            'dateModified' => optional($post->updated_at ?? null)->toAtomString(),
            'author' => [
                '@type' => 'Organization',
                'name' => $siteMeta['institution_name'] ?? 'TICH in Africa',
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => $siteMeta['institution_name'] ?? 'TICH in Africa',
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $siteMeta['logo_url'] ?? \App\Support\PublicAsset::url('images/logo.png'),
                ],
            ],
            'mainEntityOfPage' => route('blog.show', $post->slug),
            'url' => route('blog.show', $post->slug),
        ], fn ($v) => $v !== null && $v !== '');
    @endphp
    <script type="application/ld+json">{!! json_encode($articleLd, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
@endsection
