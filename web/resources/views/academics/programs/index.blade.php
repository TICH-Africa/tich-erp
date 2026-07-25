@extends('layouts.academics')

@section('academics-content')
    @php
        $hub = ['department' => $department->id];
        if (! empty($learningDepartment)) {
            $hub['learning_department'] = $learningDepartment->id;
        }
    @endphp

    @include('academics.partials.learning-department-context')

    <div class="tich-section__intro" style="text-align:left;">
        <h1 class="tich-h1" style="font-size: 2rem;">Programme curriculum</h1>
        <p class="tich-text">
            @if (! empty($learningDepartment))
                Programmes offered by {{ $learningDepartment->dept_name }}.
            @else
                Configure course length, terms per academic year, and map units to semesters or nursing blocks for each programme.
            @endif
        </p>
    </div>

    <div class="tich-card tich-mt-8" style="overflow-x:auto;">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Programme</th>
                    <th>Department</th>
                    <th>Duration</th>
                    <th>Terms / year</th>
                    <th>Format</th>
                    @if ($canViewApplications)
                        <th>Pending</th>
                    @endif
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($programs as $program)
                    @php($pendingApplications = $pendingApplicationsByProgram[$program->id] ?? 0)
                    <tr>
                        <td>{{ $program->program_code }}</td>
                        <td>{{ $program->program_name }}</td>
                        <td>{{ $program->department?->dept_name ?? '-' }}</td>
                        <td>{{ $program->duration_months ? $program->duration_months.' months' : '-' }}</td>
                        <td>{{ $program->semester_count ?: $program->termsPerYear() }}</td>
                        <td>{{ $formats[$program->curriculum_format ?? 'trimester'] ?? ucfirst($program->curriculum_format ?? 'trimester') }}</td>
                        @if ($canViewApplications)
                            <td>
                                @if ($pendingApplications > 0)
                                    <a href="{{ route('admissions.applications.index', ['department' => $program->department_id, 'program' => $program->id, 'status' => 'pending']) }}"
                                       class="tich-notification-badge"
                                       title="Review pending applications"
                                       aria-label="{{ $pendingApplications }} pending applications">{{ $pendingApplications }}</a>
                                @else
                                    <span class="tich-caption">-</span>
                                @endif
                            </td>
                        @endif
                        <td><a href="{{ route('departments.academics.programs.curriculum', array_merge($hub, ['program' => $program->id, 'section' => 'structure'])) }}" class="tich-link">Open builder</a></td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $canViewApplications ? 8 : 7 }}" style="padding:2rem;text-align:center;" class="tich-text">No programmes{{ ! empty($learningDepartment) ? ' for this department' : ' in this academics hub' }}.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
