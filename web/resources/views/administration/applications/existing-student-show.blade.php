@extends('layouts.administration')

@section('title', 'Student Profile')

@section('administration-content')
    <x-page-toolbar :title="$student->registration_number . ' - ' . ($student->fullName())" meta="Student profile and basic information">
        <x-slot:actions>
            <a href="{{ route('administration.applications.existing-student.index') }}" class="tich-btn tich-btn-secondary">Back to Students</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-grid tich-grid--2 tich-mt-6">
        <article class="tich-card">
            <h3 class="tich-h4">Identity</h3>
            <dl class="tich-dl tich-mt-3">
                <dt class="tich-caption">Registration</dt>
                <dd>{{ $student->registration_number }}</dd>
                <dt class="tich-caption">Programme</dt>
                <dd>{{ $student->program?->program_name ?? '-' }}</dd>
                <dt class="tich-caption">Campus</dt>
                <dd>{{ $student->campus?->campus_name ?? '-' }}</dd>
                <dt class="tich-caption">Status</dt>
                <dd>{{ ucfirst($student->enrollment_status) }}</dd>
            </dl>
        </article>
        <article class="tich-card">
            <h3 class="tich-h4">Academic Summary</h3>
            <dl class="tich-dl tich-mt-3">
                <dt class="tich-caption">Active Records</dt>
                <dd>{{ $student->academicRecords()->where('status', 'active')->count() }}</dd>
                <dt class="tich-caption">Total GPA</dt>
                <dd>{{ $student->academicRecords()->whereNotNull('gpa')->avg('gpa') ?? 'N/A' }}</dd>
                <dt class="tich-caption">Program Type</dt>
                <dd>{{ $student->entry_pathway ?? '-' }}</dd>
            </dl>
        </article>
    </div>

    <div class="tich-card tich-mt-6">
        <h3 class="tich-h3">Student Information</h3>
        <div class="tich-table-wrap tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="tich-caption">Registration Number</td>
                        <td>{{ $student->registration_number }}</td>
                    </tr>
                    <tr>
                        <td class="tich-caption">Full Name</td>
                        <td>{{ $student->fullName() }}</td>
                    </tr>
                    <tr>
                        <td class="tich-caption">Email</td>
                        <td>{{ $student->applicant?->email ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="tich-caption">Programme</td>
                        <td>{{ $student->program?->program_name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="tich-caption">Campus</td>
                        <td>{{ $student->campus?->campus_name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="tich-caption">Enrollment Status</td>
                        <td>{{ ucfirst($student->enrollment_status) }}</td>
                    </tr>
                    <tr>
                        <td class="tich-caption">Entry Pathway</td>
                        <td>{{ $student->entry_pathway ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="tich-caption">Date of Admission</td>
                        <td>{{ $student->date_of_admission }}</td>
                    </tr>
                    <tr>
                        <td class="tich-caption">Overall Balance</td>
                        <td>KES {{ number_format($student->overall_balance ?? 0, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    @if(session('success'))
        <div class="tich-alert tich-alert--success tich-mt-6">{{ session('success') }}</div>
    @endif
@endsection
