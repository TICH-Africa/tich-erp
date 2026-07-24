@php($timetableData = $portalData['timetable'])

<header class="tich-dept-header">
    <p class="tich-caption">Learning</p>
    <h1 class="tich-h1 tich-dept-header__title">Timetable</h1>
    <p class="tich-text tich-dept-header__meta">
        Semester {{ $timetableData['teaching_period'] }}
        @if ($timetableData['timetable'])
            · {{ ucfirst($timetableData['timetable']->status) }}
        @endif
    </p>
</header>

@if ($timetableData['is_provisional'])
    <div class="tich-notice tich-notice--info tich-mt-4">
        <p class="tich-text" style="margin:0;">This timetable is still being finalised by the academic office.</p>
    </div>
@endif

@if ($timetableData['timetable'] && $timetableData['sessions']->isNotEmpty())
    @include('academics.programs.partials.timetable-grid', [
        'sessions' => $timetableData['sessions'],
        'dayLabels' => $timetableData['day_labels'],
        'segmentTypes' => $timetableData['segment_types'],
        'activeDays' => $timetableData['active_days'],
        'segments' => $timetableData['template']?->segments ?? collect(),
    ])
@else
    <article class="tich-card tich-dept-empty tich-mt-8">
        <h2 class="tich-h3">No timetable published yet</h2>
        <p class="tich-text tich-mt-2">Your weekly schedule will appear here once the academic office publishes the timetable for this semester.</p>
    </article>
@endif
