@php
    $extraClass = $extraClass ?? '';
    $headingTag = $headingTag ?? 'h2';
    $excerptLimit = $excerptLimit ?? 160;
    $imageUrl = $post->featured_image_path ?? null;
    $viewUrl = $post->url ?? route('blog.show', $post->slug);
    $searchBlob = strtolower(trim(implode(' ', array_filter([
        $post->title ?? null,
        $post->subtitle ?? null,
        $post->excerpt ?? null,
        $post->formatted_date ?? null,
    ]))));
@endphp
<article
    class="tich-course-card{{ $extraClass ? ' ' . $extraClass : '' }}"
    data-live-search-item
    data-search="{{ $searchBlob }}"
>
    <div class="tich-course-card__img">
        @if (! empty($imageUrl))
            <img src="{{ $imageUrl }}" alt="{{ $post->title }}" class="tich-course-card__image" loading="lazy">
        @else
            <div class="tich-course-card__placeholder" aria-hidden="true"></div>
        @endif
    </div>
    <div class="tich-course-card__body">
        <p class="tich-course-card__meta">
            {{ $post->formatted_date ?? '' }}
            @if (! empty($post->reading_time_minutes))
                · <span>{{ $post->reading_time_minutes }} min read</span>
            @endif
        </p>
        <{{ $headingTag }} class="tich-course-card__title">{{ $post->title }}</{{ $headingTag }}>
        @if (! empty($post->excerpt))
            <p class="tich-course-card__excerpt">{{ \Illuminate\Support\Str::limit(strip_tags($post->excerpt), $excerptLimit) }}</p>
        @endif
        <div class="tich-course-card__actions">
            <a href="{{ $viewUrl }}" class="tich-course-card__link">Read article →</a>
        </div>
    </div>
</article>
