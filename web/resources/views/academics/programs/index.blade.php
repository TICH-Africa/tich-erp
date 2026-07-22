@extends('layouts.academics')

@section('academics-content')
    @php($hub = ['department' => $department->id])

    <div class="tich-section__intro" style="text-align:left;">
        <h1 class="tich-h1" style="font-size: 2rem;">Programme curriculum</h1>
        <p class="tich-text">Configure course length, terms per academic year, and map units to semesters or nursing blocks for each programme.</p>
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
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($programs as $program)
                    <tr>
                        <td>{{ $program->program_code }}</td>
                        <td>{{ $program->program_name }}</td>
                        <td>{{ $program->department?->dept_name ?? '—' }}</td>
                        <td>{{ $program->duration_months ? $program->duration_months.' months' : '—' }}</td>
                        <td>{{ $program->semester_count ?: $program->termsPerYear() }}</td>
                        <td>{{ $formats[$program->curriculum_format ?? 'trimester'] ?? ucfirst($program->curriculum_format ?? 'trimester') }}</td>
                        <td><a href="{{ route('departments.academics.programs.curriculum', array_merge($hub, ['program' => $program->id])) }}" class="tich-link">Open builder</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="padding:2rem;text-align:center;" class="tich-text">No programmes in this academics hub.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
