@php
    $extraClass = $extraClass ?? '';
    $headingTag = $headingTag ?? 'h3';
    $excerptLimit = $excerptLimit ?? 180;
    $isModel = is_object($project) && method_exists($project, 'coverUrl');
    $imageUrl = $isModel ? $project->coverUrl() : ($project->cover_image_path ?? null);
    $status = $isModel ? $project->statusLabel() : ucfirst((string) ($project->status ?? 'Research'));
    $isFeatured = (bool) ($project->is_featured ?? false);
    $viewUrl = $isModel
        ? route('research.show', $project->slug)
        : ($project->url ?? route('research'));
    $summary = $project->summary ?? null;
    $subtitle = $project->subtitle ?? null;

    $durationLabel = null;
    if (! empty($project->duration_value) && ! empty($project->duration_unit)) {
        $durationLabel = $project->duration_value . ' ' . $project->duration_unit;
    }

    $startLabel = null;
    if (! empty($project->start_date)) {
        $startLabel = $project->start_date instanceof \Carbon\CarbonInterface
            ? $project->start_date->format('M Y')
            : \Illuminate\Support\Str::limit((string) $project->start_date, 20);
    }

    $leadName = null;
    if ($isModel && $project->relationLoaded('leadResearcher') && $project->leadResearcher) {
        $lead = $project->leadResearcher;
        $leadName = trim(implode(' ', array_filter([
            $lead->first_name ?? null,
            $lead->surname ?? null,
        ]))) ?: null;
    }

    $searchBlob = strtolower(trim(implode(' ', array_filter([
        $project->title ?? null,
        $summary,
        $subtitle,
        $status,
        $durationLabel,
        $startLabel,
        $leadName,
    ]))));
@endphp
<article
    class="tich-course-card{{ $extraClass ? ' ' . $extraClass : '' }}"
    data-live-search-item
    data-search="{{ $searchBlob }}"
>
    <div class="tich-course-card__img">
        @if (! empty($imageUrl))
            <img src="{{ $imageUrl }}" alt="{{ $project->title }}" class="tich-course-card__image" loading="lazy">
        @else
            <div class="tich-course-card__placeholder" aria-hidden="true"></div>
        @endif
    </div>
    <div class="tich-course-card__body">
        <p class="tich-course-card__meta">
            {{ $status }}
            @if ($isFeatured)
                · <span>Featured</span>
            @endif
        </p>
        <{{ $headingTag }} class="tich-course-card__title">{{ $project->title }}</{{ $headingTag }}>
        @if (! empty($summary))
            <p class="tich-course-card__excerpt">{{ \Illuminate\Support\Str::limit(strip_tags($summary), $excerptLimit) }}</p>
        @elseif (! empty($subtitle))
            <p class="tich-course-card__excerpt">{{ \Illuminate\Support\Str::limit(strip_tags($subtitle), $excerptLimit) }}</p>
        @endif

        @if ($durationLabel || $startLabel || $leadName)
            <dl class="tich-course-card__details">
                @if ($durationLabel)
                    <div>
                        <dt>Duration</dt>
                        <dd>{{ $durationLabel }}</dd>
                    </div>
                @endif
                @if ($startLabel)
                    <div>
                        <dt>Started</dt>
                        <dd>{{ $startLabel }}</dd>
                    </div>
                @endif
                @if ($leadName)
                    <div>
                        <dt>Lead</dt>
                        <dd>{{ $leadName }}</dd>
                    </div>
                @endif
            </dl>
        @endif

        <div class="tich-course-card__actions">
            <a href="{{ $viewUrl }}" class="tich-course-card__link">View activity →</a>
        </div>
    </div>
</article>
