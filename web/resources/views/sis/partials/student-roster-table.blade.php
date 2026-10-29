@php
    $students = $students ?? collect();
    $summaries = $summaries ?? collect();
    $detailRoute = $detailRoute ?? null;
    $sisRoute = $sisRoute ?? fn ($student) => route('sis.students.show', $student);
    $emptyMessage = $emptyMessage ?? 'No students found.';
    $groupByCampus = $groupByCampus ?? false;
    // Academics hands over the year each student is actually sitting so the roster groups by
    // enrolment year first and campus second - a mixed intake reads as one list per year.
    $yearsByStudent = $yearsByStudent ?? collect();
    $programLabel = $students->first()?->program?->program_code;

    $segments = [];
    $byYear = $yearsByStudent->isNotEmpty();

    if ($byYear) {
        $buckets = $students
            ->groupBy(fn ($student) => (int) ($yearsByStudent[$student->id]['year'] ?? 0))
            ->sortKeys(SORT_NUMERIC)
            ->reverse();
    } else {
        $buckets = collect([0 => $students]);
    }

    foreach ($buckets as $yearNumber => $yearStudents) {
        if ($byYear) {
            $known = $yearStudents->map(fn ($student) => $yearsByStudent[$student->id] ?? null)->filter()->first();

            $segments[] = [
                'kind' => 'year',
                'label' => $yearNumber > 0 ? 'Year '.$yearNumber : 'Year of study not derived',
                'note' => $known ? $known['period_label'].' · '.$known['basis'] : 'admission year is missing on these students',
                'count' => $yearStudents->count(),
            ];
        }

        if (! $groupByCampus) {
            $segments[] = ['kind' => 'rows', 'students' => $yearStudents];

            continue;
        }

        foreach ($yearStudents->groupBy(fn ($student) => $student->campus?->campus_name ?: 'Campus not set') as $campusName => $campusStudents) {
            $segments[] = ['kind' => 'campus', 'label' => $campusName, 'count' => $campusStudents->count()];
            $segments[] = ['kind' => 'rows', 'students' => $campusStudents];
        }
    }
@endphp

<div class="tich-card tich-table-panel tich-mt-4">
    <table class="tich-admin-table">
        <thead>
            <tr>
                <th>Reg. number</th>
                <th>Student</th>
                <th>Campus</th>
                <th>Mode</th>
                <th>Status</th>
                <th>Units</th>
                <th>CAT</th>
                <th>Exams</th>
                <th>Grades</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($segments as $segment)
                @if ($segment['kind'] === 'year')
                    <tr style="background:var(--tich-surface-hover, #f1f5f9);">
                        <td colspan="10">
                            <strong>{{ $segment['label'] }}</strong>
                            <span class="tich-caption"> · {{ $segment['note'] }} ({{ $segment['count'] }} {{ str('student')->plural($segment['count']) }})</span>
                        </td>
                    </tr>
                @endif

                @if ($segment['kind'] === 'campus')
                    <tr>
                        <td colspan="10">
                            <strong>{{ $segment['label'] }}</strong>
                            @if ($programLabel)
                                · {{ $programLabel }}
                            @endif
                            ({{ $segment['count'] }})
                        </td>
                    </tr>
                @endif

                @if ($segment['kind'] === 'rows')
                    @foreach ($segment['students'] as $student)
                        @php($summary = $summaries->get($student->id, []))
                        <tr>
                            <td>{{ $student->registration_number }}</td>
                            <td>
                                {{ $student->displayName() }}<br>
                                <span class="tich-caption">{{ $student->applicant?->email ?: ($student->email ?: $student->user?->email) }}</span>
                            </td>
                            <td>{{ $student->campus?->campus_name ?? '-' }}</td>
                            <td>{{ $student->studyModeLabel() }}</td>
                            <td>{{ ucfirst($student->enrollment_status) }}</td>
                            <td>{{ $summary['registered_units'] ?? 0 }}</td>
                            <td>{{ $summary['cat_scores'] ?? 0 }}</td>
                            <td>{{ $summary['exam_results'] ?? 0 }}</td>
                            <td>{{ $summary['grades'] ?? 0 }}</td>
                            <td style="white-space:nowrap;">
                                @if ($detailRoute)
                                    <a href="{{ $detailRoute($student) }}#student-detail" class="tich-link">Academic record</a>
                                    ·
                                @endif
                                <a href="{{ $sisRoute($student) }}" class="tich-link">SIS</a>
                            </td>
                        </tr>
                    @endforeach
                @endif
            @endforeach

            @if ($students->isEmpty() && $emptyMessage)
                @include('partials.states.table-empty', ['colspan' => 10, 'title' => $emptyMessage, 'icon' => 'inbox'])
            @endif
        </tbody>
    </table>
</div>
