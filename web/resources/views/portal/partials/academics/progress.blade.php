@php
    $grades = $academics['grades'] ?? collect();
    $units = $academics['curriculum_units'] ?? collect();
    $registered = $academics['registered_units'] ?? collect();
    $completed = $grades->count();
    $totalUnits = max($units->count(), $registered->count(), 1);
    $completionPct = min(100, round(($completed / $totalUnits) * 100));
    $gpa = $portalData['transcript']['gpa'] ?? null;
@endphp

<section class="tich-portal-panel tich-mt-6">
    <div class="tich-portal-panel__head">
        <div>
            <h2 class="tich-h3">Academic progress</h2>
            <p class="tich-caption tich-mt-1">Credit completion and published grades across your programme.</p>
        </div>
    </div>

    <div class="tich-grid tich-grid--3 tich-mt-4">
        <article class="tich-stat">
            <p class="tich-stat__label">Units with grades</p>
            <p class="tich-stat__value">{{ $completed }}</p>
        </article>
        <article class="tich-stat">
            <p class="tich-stat__label">Programme units</p>
            <p class="tich-stat__value">{{ $units->count() ?: $registered->count() }}</p>
        </article>
        <article class="tich-stat">
            <p class="tich-stat__label">Completion</p>
            <p class="tich-stat__value">{{ $completionPct }}%</p>
        </article>
    </div>

    <div class="tich-mt-4" style="height: 0.5rem; background: var(--tich-neutral-border); border-radius: 999px; overflow: hidden;">
        <div style="width: {{ $completionPct }}%; height: 100%; background: var(--tich-green);"></div>
    </div>

    @if ($gpa !== null)
        <p class="tich-text tich-mt-4">Cumulative GPA (from published grades): <strong>{{ number_format((float) $gpa, 2) }}</strong></p>
    @endif
</section>

@include('portal.partials.academics.exams-grades')
