@extends('layouts.academics')

@section('title', 'Alumnus profile')

@section('academics-content')
    @php
        $gpa = $student->academicRecords->whereNotNull('gpa')->avg('gpa');
    @endphp

    <x-page-toolbar :title="$student->displayName()" meta="Alumni file · registered by Admissions · read only here">
        <x-slot:actions>
            <a href="{{ route('academics.alumni.index') }}" class="tich-btn tich-btn-ghost">Back to Alumni</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-grid tich-grid--2 tich-mt-4">
        <article class="tich-card">
            <h3 class="tich-h4">Profile</h3>
            <div class="tich-flex-wrap tich-mt-3" style="gap:1rem; align-items:flex-start;">
                @if ($student->photo_path)
                    <img src="{{ $student->photoUrl() }}" alt="{{ $student->displayName() }}"
                         style="width:88px; height:88px; object-fit:cover; border-radius:50%; border:1px solid #e2e8f0;">
                @endif

                <dl class="tich-dl">
                    <dt class="tich-caption">Admission no.</dt>
                    <dd>{{ $student->registration_number ?: '-' }}</dd>
                    <dt class="tich-caption">Course they did</dt>
                    <dd>{{ $student->program?->program_name ?? '-' }}{{ $student->program?->program_code ? ' ('.$student->program->program_code.')' : '' }}</dd>
                    <dt class="tich-caption">Campus</dt>
                    <dd>{{ $student->campus?->campus_name ?? '-' }}</dd>
                </dl>
            </div>

            <dl class="tich-dl tich-mt-3">
                <dt class="tich-caption">Phone</dt>
                <dd>{{ $student->phone_number ?: '-' }}</dd>
                <dt class="tich-caption">Email</dt>
                <dd>{{ $student->email ?: '-' }}</dd>
            </dl>
        </article>

        <article class="tich-card">
            <h3 class="tich-h4">Graduation</h3>
            <dl class="tich-dl tich-mt-3">
                <dt class="tich-caption">Year joined</dt>
                <dd>{{ $student->cohort_intake ?: ($student->date_of_admission?->format('Y') ?? '-') }}</dd>
                <dt class="tich-caption">Year ended</dt>
                <dd>{{ $student->graduation_year ?: '-' }}</dd>
                <dt class="tich-caption">Graduation date</dt>
                <dd>{{ $student->graduation_date?->format('d M Y') ?? '-' }}</dd>
                <dt class="tich-caption">Award or class</dt>
                <dd>{{ $student->graduation_award ?: '-' }}</dd>
                <dt class="tich-caption">GPA</dt>
                <dd>{{ $gpa === null ? '-' : number_format((float) $gpa, 2) }}</dd>
                <dt class="tich-caption">Year of study ended on</dt>
                <dd>{{ $student->current_year ? 'Year '.$student->current_year : '-' }}</dd>
                <dt class="tich-caption">On the register as</dt>
                <dd>{{ ucfirst((string) $student->enrollment_status) }}</dd>
            </dl>
        </article>
    </div>

    <div class="tich-card tich-table-panel tich-mt-4">
        <h3 class="tich-h4 tich-mt-4">Study record on file</h3>

        @if ($student->academicRecords->isEmpty())
            <p class="tich-text tich-mt-3">No term records - Admissions registered this graduate from the school's own list.</p>
        @else
            <div class="tich-table-wrap">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Enrolled</th>
                            <th>Completed</th>
                            <th>Status</th>
                            <th>GPA</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($student->academicRecords as $academicRecord)
                            <tr>
                                <td>{{ $academicRecord->program?->program_name ?? ($student->program?->program_name ?? '-') }}</td>
                                <td class="tich-caption">{{ $academicRecord->enrollment_date?->format('d M Y') ?? '-' }}</td>
                                <td class="tich-caption">{{ $academicRecord->completion_date?->format('d M Y') ?? '-' }}</td>
                                <td class="tich-caption">{{ ucfirst((string) $academicRecord->status) }}</td>
                                <td>{{ $academicRecord->gpa === null ? '-' : number_format((float) $academicRecord->gpa, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="tich-caption tich-mt-3">
                Marks, transcripts and fee history stay on the student record itself, which Admissions and
                Finance keep. This page is the alumni file only.
            </p>
        @endif
    </div>
@endsection
