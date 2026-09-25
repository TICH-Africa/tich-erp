@php
    $viewUrl = $program->url ?? route('programs.show', $program->program_code);
    $applyUrl = $program->apply_url ?? route('apply.index', ['program' => $program->program_code]);
    $excerptLimit = $excerptLimit ?? 160;
    $entryLimit = $entryLimit ?? 90;
    $extraClass = $extraClass ?? '';
    $headingTag = $headingTag ?? 'h3';
    $searchBlob = strtolower(trim(implode(' ', array_filter([
        $program->program_code ?? null,
        $program->program_name ?? null,
        $program->program_type ?? null,
        $program->homepage_tagline ?? null,
        $program->regulatory_body ?? null,
        $program->entry_requirements ?? null,
        $program->fee_display ?? null,
        $program->department_name ?? null,
        $program->department_code ?? null,
    ]))));
@endphp
<article
    class="tich-course-card{{ $extraClass ? ' ' . $extraClass : '' }}"
    data-live-search-item
    data-search="{{ $searchBlob }}"
    data-department="{{ strtolower($program->department_code ?? '') }}"
>
    <div class="tich-course-card__img">
        @include('programs.partials.cover-image', ['program' => $program])
    </div>
    <div class="tich-course-card__body">
        <p class="tich-course-card__meta">
            {{ strtoupper($program->program_code) }}
            ·
            <span>{{ strtoupper(str_replace('_', ' ', $program->program_type)) }}</span>
        </p>
        <{{ $headingTag }} class="tich-course-card__title">{{ $program->program_name }}</{{ $headingTag }}>
        @if (! empty($program->homepage_tagline))
            <p class="tich-course-card__excerpt">{{ \Illuminate\Support\Str::limit(strip_tags($program->homepage_tagline), $excerptLimit) }}</p>
        @endif

        @if (! empty($program->duration_months) || ! empty($program->regulatory_body) || ! empty($program->entry_requirements) || ! empty($program->fee_display))
            <dl class="tich-course-card__details">
                @if (! empty($program->duration_months))
                    <div>
                        <dt>Duration</dt>
                        <dd>{{ $program->duration_months }} months</dd>
                    </div>
                @endif
                @if (! empty($program->regulatory_body))
                    <div>
                        <dt>Accreditation</dt>
                        <dd>{{ $program->regulatory_body }}</dd>
                    </div>
                @endif
                @if (! empty($program->entry_requirements))
                    <div>
                        <dt>Entry</dt>
                        <dd>{{ \Illuminate\Support\Str::limit(strip_tags($program->entry_requirements), $entryLimit) }}</dd>
                    </div>
                @endif
                @if (! empty($program->fee_display))
                    <div>
                        <dt>Fees</dt>
                        <dd>{{ $program->fee_display }}</dd>
                    </div>
                @endif
            </dl>
        @endif

        <div class="tich-course-card__actions">
            <a href="{{ $viewUrl }}" class="tich-course-card__link">View programme →</a>
            <a href="{{ $applyUrl }}" class="tich-course-card__apply">Apply now</a>
        </div>
    </div>
</article>
