@php
    $extraClass = $extraClass ?? '';
    $headingTag = $headingTag ?? 'h3';
    $excerptLimit = $excerptLimit ?? 140;
    $imageUrl = $event->cover_image_url ?? $event->cover_image_path ?? null;
    $viewUrl = $event->url ?? route('events');
    $typeLabel = strtoupper(str_replace('_', ' ', $event->event_type ?? 'EVENT'));
    $searchBlob = strtolower(trim(implode(' ', array_filter([
        $event->title ?? null,
        $event->subtitle ?? null,
        $event->description ?? null,
        $event->venue ?? null,
        $typeLabel,
        $event->formatted_date ?? null,
    ]))));
@endphp
<article
    class="tich-course-card{{ $extraClass ? ' ' . $extraClass : '' }}"
    data-live-search-item
    data-search="{{ $searchBlob }}"
>
    <div class="tich-course-card__img">
        @if (! empty($imageUrl))
            <img src="{{ $imageUrl }}" alt="{{ $event->title }}" class="tich-course-card__image" loading="lazy">
        @else
            <div class="tich-course-card__placeholder" aria-hidden="true"></div>
        @endif
    </div>
    <div class="tich-course-card__body">
        <p class="tich-course-card__meta">
            {{ $typeLabel }}
            @if (! empty($event->formatted_date))
                · <span>{{ $event->formatted_date }}</span>
            @endif
        </p>
        <{{ $headingTag }} class="tich-course-card__title">{{ $event->title }}</{{ $headingTag }}>
        @if (! empty($event->subtitle))
            <p class="tich-course-card__excerpt">{{ \Illuminate\Support\Str::limit(strip_tags($event->subtitle), $excerptLimit) }}</p>
        @elseif (! empty($event->description))
            <p class="tich-course-card__excerpt">{{ \Illuminate\Support\Str::limit(strip_tags($event->description), $excerptLimit) }}</p>
        @endif

        @if (! empty($event->venue) || ! empty($event->formatted_time))
            <dl class="tich-course-card__details">
                @if (! empty($event->formatted_time))
                    <div>
                        <dt>Time</dt>
                        <dd>{{ $event->formatted_time }}</dd>
                    </div>
                @endif
                @if (! empty($event->venue))
                    <div>
                        <dt>Venue</dt>
                        <dd>{{ $event->venue }}</dd>
                    </div>
                @endif
            </dl>
        @endif

        <div class="tich-course-card__actions">
            <a href="{{ $viewUrl }}" class="tich-course-card__link">View event →</a>
            @if (! empty($event->registration_url_or_form))
                <a href="{{ $event->registration_url_or_form }}" class="tich-course-card__apply">Register</a>
            @endif
        </div>
    </div>
</article>
