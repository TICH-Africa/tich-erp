@php
    $students = $students ?? collect();
    $summaries = $summaries ?? collect();
    $detailRoute = $detailRoute ?? null;
    $sisRoute = $sisRoute ?? fn ($student) => route('sis.students.show', $student);
    $emptyMessage = $emptyMessage ?? 'No students found.';
@endphp

<div class="tich-card tich-table-panel tich-mt-4">
    <table class="tich-admin-table">
        <thead>
            <tr>
                <th>Reg. number</th>
                <th>Student</th>
                <th>Campus</th>
                <th>Status</th>
                <th>Units</th>
                <th>CAT</th>
                <th>Exams</th>
                <th>Grades</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($students as $student)
                @php($summary = $summaries->get($student->id, []))
                <tr>
                    <td>{{ $student->registration_number }}</td>
                    <td>
                        {{ $student->applicant?->fullName() ?? '-' }}<br>
                        <span class="tich-caption">{{ $student->applicant?->email }}</span>
                    </td>
                    <td>{{ $student->campus?->campus_name ?? '-' }}</td>
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
            @empty
                @if ($emptyMessage)
                    <tr>
                        <td colspan="9" class="tich-table-empty">{{ $emptyMessage }}</td>
                    </tr>
                @endif
            @endforelse
        </tbody>
    </table>
</div>
