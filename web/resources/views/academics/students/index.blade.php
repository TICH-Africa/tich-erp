@extends('layouts.academics')

@section('title', 'Student Directory')

@section('academics-content')
    <x-page-toolbar title="Student Directory" meta="All enrolled students with academic history">
        <x-slot:actions>
            <a href="{{ route('academics.students.create') }}" class="tich-btn tich-btn-secondary">Add Academic Record</a>
        </x-slot:actions>
    </x-page-toolbar>

    <form method="GET" class="tich-mb-4 tich-grid tich-grid--4">
        @include('partials.search-field', ['placeholder' => 'Reg number, name...', 'value' => request('search') ?? ''])
        <select name="program_id" class="tich-input">
            <option value="">All programmes</option>
            @foreach ($programs as $program)
                <option value="{{ $program->id }}" @selected(request('program_id') == $program->id)>
                    {{ $program->program_code }} - {{ $program->program_name }}
                </option>
            @endforeach
        </select>
        <select name="status" class="tich-input">
            <option value="">All statuses</option>
            @foreach ($statuses as $status)
                <option value="{{ $status }}" @selected(request('status') == $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        <select name="cohort" class="tich-input">
            <option value="">All cohorts</option>
            @foreach ($cohorts as $cohort)
                <option value="{{ $cohort }}" @selected(request('cohort') == $cohort)>{{ $cohort }}</option>
            @endforeach
        </select>
    </form>

    <div class="tich-card tich-table-panel tich-mt-6">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Reg. Number</th>
                    <th>Student</th>
                    <th>Programme</th>
                    <th>Cohort</th>
                    <th>Campus</th>
                    <th>Status</th>
                    <th>Current Semester</th>
                    <th>Units</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($students as $student)
                    <?php
                        $activeRecord = $student->academicRecords->firstWhere('status', 'active');
                        $unitsRegistered = $activeRecord?->units_registered ?? 0;
                        $unitsCompleted = $activeRecord?->units_completed ?? 0;
                    ?>
                    <tr>
                        <td>{{ $student->registration_number }}</td>
                        <td>
                            <strong>{{ $student->fullName() }}</strong>
                            @if($student->applicant?->email)
                                <br><span class="tich-caption">{{ $student->applicant->email }}</span>
                            @endif
                        </td>
                        <td>{{ $student->program?->program_name ?? '-' }}</td>
                        <td>{{ $student->cohort_intake ?? '-' }}</td>
                        <td>{{ $student->campus?->campus_name ?? '-' }}</td>
                        <td>
                            <span class="tich-status {{ $student->enrollment_status === 'active' ? 'tich-status--success' : ($student->enrollment_status === 'deferred' ? 'tich-status--warning' : 'tich-status--danger') }}">
                                {{ ucfirst($student->enrollment_status) }}
                            </span>
                        </td>
                        <td>{{ $student->currentSemester?->semester_number ?? '-' }}</td>
                        <td>{{ $unitsRegistered }} / {{ $unitsCompleted }}</td>
                        <td>
                            <a href="{{ route('academics.students.show', $student->id) }}" class="tich-link">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="tich-caption">No students found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="tich-mt-4">{{ $students->links() }}</div>

    @if(session('success'))
        <div class="tich-alert tich-alert--success tich-mt-6">{{ session('success') }}</div>
    @endif
@endsection