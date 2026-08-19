@php
    $timetableData = $portalData['timetable'];
    $timetableTab = $timetableTab ?? 'lesson';

    $tabTitles = [
        'lesson' => 'Lesson Timetable',
        'exam' => 'Exam Timetable',
    ];

    $visibleTimetables = $timetableData['timetables']->filter(function ($timetable) use ($timetableTab) {
        if ($timetableTab === 'lesson') {
            return $timetable->timetable_kind === 'lesson';
        }

        return in_array($timetable->timetable_kind, ['exam', 'supplementary', 'special_exam'], true);
    })->values();
@endphp

<x-page-toolbar :title="$tabTitles[$timetableTab] ?? 'Timetable'" :meta="'Semester '.$timetableData['teaching_period']" />

@if ($timetableData['is_provisional'])
    <div class="tich-notice tich-notice--info tich-mt-4">
        <p class="tich-text" style="margin:0;">These timetables are still being finalised by the academic office.</p>
    </div>
@endif

@if ($visibleTimetables->isNotEmpty())
    @foreach ($visibleTimetables as $timetable)
        @php
            $template = $timetable->template?->load(['segments', 'days']);
            $activeDays = $template?->activeDayNumbers() ?? [1, 2, 3, 4, 5];
            $gridSegments = match ($timetable->timetable_kind) {
                'exam' => $template?->segments?->filter(fn ($segment) => $segment->segment_type === 'exam') ?? collect(),
                'supplementary', 'special_exam' => $template?->segments?->filter(fn ($segment) => $segment->segment_type === 'supplementary') ?? collect(),
                default => $template?->segments?->filter(
                    fn ($segment) => in_array($segment->segment_type, ['lesson', 'break'], true)
                ) ?? collect(),
            };
            if ($gridSegments->isEmpty() && in_array($timetable->timetable_kind, ['exam', 'supplementary', 'special_exam'], true)) {
                $gridSegments = collect($timetable->sessions ?? [])->map(fn ($session) => (object) [
                    'id' => $session->segment_id,
                    'label' => $session->timeLabel(),
                    'start_time' => $session->start_time,
                    'end_time' => $session->end_time,
                    'segment_type' => $session->session_type,
                ])->unique(fn ($row) => substr((string) $row->start_time, 0, 5).'-'.substr((string) $row->end_time, 0, 5))->sortBy('start_time')->values();
            }
        @endphp

        <article class="tich-card tich-mt-8">
            <div style="display:flex; flex-wrap:wrap; justify-content:space-between; gap:1rem; align-items:start;">
                <div>
                    <h2 class="tich-h3">{{ $timetable->displayTitle() }}</h2>
                    <p class="tich-caption tich-mt-2">{{ ucfirst($timetable->status) }}</p>
                </div>
                @if ($timetable->sessions->isNotEmpty())
                    <div style="display:flex; flex-wrap:wrap; gap:0.5rem;">
                        <a href="{{ route('portal.timetable.print', $timetable) }}" target="_blank" class="tich-btn tich-btn-secondary">Print / preview</a>
                        <a href="{{ route('portal.timetable.pdf', $timetable) }}" class="tich-btn tich-btn-secondary">Download PDF</a>
                    </div>
                @endif
            </div>

            @if ($timetable->sessions->isNotEmpty())
                @include('academics.programs.partials.timetable-grid', [
                    'sessions' => $timetable->sessions,
                    'dayLabels' => $timetableData['day_labels'],
                    'segmentTypes' => $timetableData['segment_types'],
                    'activeDays' => $activeDays,
                    'segments' => $gridSegments,
                ])
            @else
                @include('partials.states.empty', ['title' => 'No sessions scheduled yet', 'icon' => 'clock', 'inline' => true])
            @endif
        </article>
    @endforeach
@else
    @include('partials.states.empty', [
        'title' => $timetableTab === 'exam' ? 'No exam timetable published yet' : 'No lesson timetable published yet',
        'description' => $timetableTab === 'exam'
            ? 'Your exam schedule will appear here once the academic office publishes it for this semester.'
            : 'Your weekly lesson schedule will appear here once the academic office publishes the timetable for this semester.',
        'icon' => 'clock',
    ])
@endif
