@extends('layouts.administration')

@section('title', 'Existing Students')

@section('administration-content')
    <x-page-toolbar title="Existing Students" meta="All students registered directly">
        <x-slot:actions>
            <a href="{{ route('administration.applications.existing-student.create') }}" class="tich-btn tich-btn-primary">Add Existing Student</a>
        </x-slot:actions>
    </x-page-toolbar>

    <form method="GET" class="tich-mb-4 tich-grid tich-grid--3">
        @include('partials.search-field', ['placeholder' => 'Reg number, name...', 'value' => request('search') ?? ''])
        <select name="cohort_intake" class="tich-input">
            <option value="">All cohorts</option>
            @foreach ($cohorts as $cohort)
                <option value="{{ $cohort }}" @selected(request('cohort_intake') == $cohort)>{{ $cohort }}</option>
            @endforeach
        </select>
        <select name="program_id" class="tich-input">
            <option value="">All programmes</option>
            @foreach ($programs as $program)
                <option value="{{ $program->id }}" @selected(request('program_id') == $program->id)>{{ $program->program_code }}</option>
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
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($students as $student)
                    <tr>
                        <td>{{ $student->registration_number }}</td>
                        <td>{{ $student->fullName() }}</td>
                        <td>{{ $student->program?->program_name ?? '-' }}</td>
                        <td>{{ $student->cohort_intake }}</td>
                        <td>{{ $student->campus?->campus_name ?? '-' }}</td>
                        <td>{{ ucfirst($student->enrollment_status) }}</td>
                        <td>
                            <a href="{{ route('administration.applications.existing-student.show', $student->id) }}" class="tich-link">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="tich-caption">No students found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="tich-mt-4">{{ $students->links() }}</div>

    @if(session('success'))
        <div class="tich-alert tich-alert--success tich-mt-6">{{ session('success') }}</div>
    @endif
@endsection