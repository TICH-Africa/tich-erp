<header class="tich-dept-header">
    <p class="tich-caption">Teaching</p>
    <h1 class="tich-h1 tich-dept-header__title">My timetable</h1>
    <p class="tich-text tich-dept-header__meta">
        @if ($portalData['timetable']['teaching_period'])
            Semester {{ $portalData['timetable']['teaching_period'] }}
        @endif
        · Sessions where you are assigned as the lecturer
    </p>
</header>

@if ($portalData['timetable']['is_provisional'])
    <div class="tich-notice tich-notice--info tich-mt-4">
        <p class="tich-text" style="margin:0;">Some timetables below are still in draft and may change before publication.</p>
    </div>
@endif

@if ($portalData['timetable']['timetables']->isNotEmpty())
    @foreach ($portalData['timetable']['timetables'] as $row)
        <article class="tich-card tich-mt-8">
            <div style="display:flex; flex-wrap:wrap; justify-content:space-between; gap:1rem; align-items:start;">
                <div>
                    <h2 class="tich-h3">{{ $row['timetable']->displayTitle() }}</h2>
                    <p class="tich-caption tich-mt-2">
                        {{ $row['timetable']->program?->program_code }}
                        @if ($row['timetable']->curriculumVersion)
                            · {{ $row['timetable']->curriculumVersion->intakeLabel() }}
                        @endif
                        · Semester {{ $row['timetable']->teaching_period }}
                        · {{ ucfirst($row['timetable']->status) }}
                    </p>
                </div>
                @if ($row['timetable']->sessions->isNotEmpty())
                    <div style="display:flex; flex-wrap:wrap; gap:0.5rem;">
                        <a href="{{ route('staff.timetable.print', $row['timetable']) }}" target="_blank" class="tich-btn tich-btn-secondary">Print / preview</a>
                        <a href="{{ route('staff.timetable.pdf', $row['timetable']) }}" class="tich-btn tich-btn-secondary">Download PDF</a>
                    </div>
                @endif
            </div>

            @if ($row['timetable']->sessions->isNotEmpty())
                @include('academics.programs.partials.timetable-grid', [
                    'sessions' => $row['timetable']->sessions,
                    'dayLabels' => $portalData['timetable']['day_labels'],
                    'segmentTypes' => $portalData['timetable']['segment_types'],
                    'activeDays' => $row['activeDays'],
                    'segments' => $row['gridSegments'],
                ])
            @else
                <p class="tich-text tich-mt-4">No sessions scheduled yet.</p>
            @endif
        </article>
    @endforeach
@else
    <article class="tich-card tich-dept-empty tich-mt-8">
        <h2 class="tich-h3">No timetable assigned yet</h2>
        <p class="tich-text tich-mt-2">Your weekly schedule will appear here once the academic office assigns you on a programme timetable.</p>
    </article>
@endif
