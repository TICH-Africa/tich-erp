@php($timetableData = $portalData['timetable'])

<header class="tich-dept-header">
    <p class="tich-caption">Learning</p>
    <h1 class="tich-h1 tich-dept-header__title">Timetable</h1>
    <p class="tich-text tich-dept-header__meta">
        Semester {{ $timetableData['teaching_period'] }}
    </p>
</header>

@if ($timetableData['is_provisional'])
    <div class="tich-notice tich-notice--info tich-mt-4">
        <p class="tich-text" style="margin:0;">These timetables are still being finalised by the academic office.</p>
    </div>
@endif

@if ($timetableData['timetables']->isNotEmpty())
    @foreach ($timetableData['timetables'] as $timetable)
        @php
            $template = $timetable->template?->load(['segments', 'days']);
            $activeDays = $template?->activeDayNumbers() ?? [1, 2, 3, 4, 5];
            $displaySegmentType = match ($timetable->timetable_kind) {
                'exam' => 'exam',
                'supplementary' => 'supplementary',
                'special_exam' => 'special_exam',
                default => 'lesson',
            };
            $gridSegments = $template?->segments?->filter(
                fn ($segment) => in_array($segment->segment_type, [$displaySegmentType, 'break'], true)
            ) ?? collect();
            if ($gridSegments->where('segment_type', $displaySegmentType)->isEmpty()) {
                $gridSegments = $template?->segments ?? collect();
            }
        @endphp

        <article class="tich-card tich-mt-8">
            <h2 class="tich-h3">{{ $timetable->displayTitle() }}</h2>
            <p class="tich-caption tich-mt-2">{{ ucfirst($timetable->status) }}</p>

            @if ($timetable->sessions->isNotEmpty())
                @include('academics.programs.partials.timetable-grid', [
                    'sessions' => $timetable->sessions,
                    'dayLabels' => $timetableData['day_labels'],
                    'segmentTypes' => $timetableData['segment_types'],
                    'activeDays' => $activeDays,
                    'segments' => $gridSegments,
                ])
            @else
                <p class="tich-text tich-mt-4">No sessions scheduled yet.</p>
            @endif
        </article>
    @endforeach
@else
    <article class="tich-card tich-dept-empty tich-mt-8">
        <h2 class="tich-h3">No timetable published yet</h2>
        <p class="tich-text tich-mt-2">Your weekly schedule will appear here once the academic office publishes the timetable for this semester.</p>
    </article>
@endif
