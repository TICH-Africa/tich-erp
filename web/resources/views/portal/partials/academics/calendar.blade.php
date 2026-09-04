@php
    $periods = collect($academics['curriculum_by_semester'] ?? []);
    $current = $academics['current_period'] ?? null;
    $semester = $academics['current_semester'] ?? null;
@endphp

<section class="tich-portal-panel tich-mt-6">
    <div class="tich-portal-panel__head">
        <div>
            <h2 class="tich-h3">Academic calendar &amp; programme timeline</h2>
            <p class="tich-caption tich-mt-1">Semester and block training structures for your intake.</p>
        </div>
    </div>

    @if ($semester || $current)
        <article class="tich-card tich-mt-4">
            <h3 class="tich-h4">Current period</h3>
            <dl class="tich-mt-3" style="display:grid;grid-template-columns:9rem 1fr;gap:0.4rem 1rem;">
                @if ($semester)
                    <dt class="tich-caption">Semester</dt>
                    <dd>{{ $semester->semester_label ?? $semester->name ?? '—' }}</dd>
                @endif
                @if ($current)
                    <dt class="tich-caption">Curriculum term</dt>
                    <dd>Semester {{ $current->semester }}</dd>
                    <dt class="tich-caption">Starts</dt>
                    <dd>{{ $current->start_date?->format('d M Y') ?? '—' }}</dd>
                    <dt class="tich-caption">Ends</dt>
                    <dd>{{ $current->end_date?->format('d M Y') ?? '—' }}</dd>
                @endif
            </dl>
        </article>
    @endif

    <div class="tich-portal-card-grid tich-mt-4">
        @forelse ($periods as $semesterNumber => $periodUnits)
            @php
                $sample = collect($periodUnits)->first();
                $period = is_object($sample) && isset($sample->period) ? $sample->period : null;
            @endphp
            <article class="tich-portal-item-card">
                <p class="tich-portal-item-card__code">Semester {{ $semesterNumber }}</p>
                <h3 class="tich-portal-item-card__title">{{ collect($periodUnits)->count() }} unit(s)</h3>
                <dl class="tich-portal-item-card__meta">
                    <div>
                        <dt>Start</dt>
                        <dd>{{ $period?->start_date?->format('d M Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt>End</dt>
                        <dd>{{ $period?->end_date?->format('d M Y') ?? '—' }}</dd>
                    </div>
                </dl>
            </article>
        @empty
            @include('partials.states.empty', [
                'title' => 'No calendar periods published',
                'description' => 'Once Academics publishes your intake calendar, semester dates will appear here.',
                'icon' => 'calendar',
            ])
        @endforelse
    </div>
</section>
