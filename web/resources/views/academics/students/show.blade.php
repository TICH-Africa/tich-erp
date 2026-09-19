@extends('layouts.academics')

@section('title', 'Student Academic Journey')

@section('academics-content')
    <a href="{{ route('academics.students.index') }}" class="tich-link">&larr; Back to directory</a>

    <x-page-toolbar :title="$student->registration_number . ' - ' . ($student->fullName())" meta="{{ $student->program?->program_name ?? '-' }} · {{ ucfirst($student->enrollment_status) }}" />

    <div class="tich-grid tich-grid--3 tich-mt-6">
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
                <dt class="tich-caption">Entry Pathway</dt>
                <dd>{{ $student->entry_pathway ?? '-' }}</dd>
            </dl>
        </article>
        <article class="tich-card">
            <h3 class="tich-h4">Current Enrollment</h3>
            <dl class="tich-dl tich-mt-3">
                <dt class="tich-caption">Current Semester</dt>
                <dd>{{ $student->currentSemester?->semester_number ?? 'Not set' }}</dd>
                <dt class="tich-caption">Cohort</dt>
                <dd>{{ $student->cohort_intake ?? '-' }}</dd>
                <dt class="tich-caption">Active Records</dt>
                <dd>{{ $student->academicRecords()->where('status', 'active')->count() }}</dd>
            </dl>
        </article>
        <article class="tich-card">
            <h3 class="tich-h4">Transcript</h3>
            <div class="tich-mt-3">
                <a href="{{ route('sis.students.transcript.pdf', $student->id) }}" class="tich-btn tich-btn-primary tich-btn--sm" download>Download Transcript PDF</a>
                <a href="{{ route('sis.students.transcript', $student->id) }}" class="tich-btn tich-btn-secondary tich-btn--sm tich-mt-2">View Transcript</a>
            </div>
        </article>
    </div>

    <div class="tich-card tich-mt-6">
        <h3 class="tich-h3">Academic Journey</h3>
        <p class="tich-caption tich-mt-2">Milestones since enrollment — each record shows the programme, semester, and performance.</p>

        @if($groupedHistory->isNotEmpty())
            <div class="tich-mt-4" style="display:flex; flex-direction:column; gap:1rem;">
                @foreach ($groupedHistory as $yearLabel => $records)
                    @php $semesterId = $records->first()?->semester_id; @endphp
                    <div class="tich-inset-panel" style="border-left:3px solid #1e3a5f; padding-left:1rem;">
                        <div style="display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
                            <h4 class="tich-h4 tich-mt-0" style="color:#1e3a5f;">{{ $yearLabel }}</h4>
                            @if($semesterId)
                                <a href="{{ route('sis.students.transcript.term.pdf', [$student->id, $semesterId]) }}" class="tich-btn tich-btn-primary tich-btn--sm" download>Download Term Transcript</a>
                            @endif
                        </div>
                        @foreach ($records as $record)
                            <div class="tich-mt-3" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(140px,1fr)); gap:0.5rem 1rem; align-items:center;">
                                <div>
                                    <span class="tich-caption">Semester</span>
                                    <p style="margin:0.15rem 0 0;"><strong>{{ $record->semester?->semester_number ?? '-' }}</strong></p>
                                </div>
                                <div>
                                    <span class="tich-caption">Enrollment</span>
                                    <p style="margin:0.15rem 0 0;">{{ $record->enrollment_date }}</p>
                                </div>
                                <div>
                                    <span class="tich-caption">Completion</span>
                                    <p style="margin:0.15rem 0 0;">{{ $record->completion_date ?? '-' }}</p>
                                </div>
                                <div>
                                    <span class="tich-caption">GPA</span>
                                    <p style="margin:0.15rem 0 0;"><strong>{{ $record->gpa ?? '-' }}</strong></p>
                                </div>
                                <div>
                                    <span class="tich-caption">Units</span>
                                    <p style="margin:0.15rem 0 0;">{{ $record->units_registered }} / {{ $record->units_completed }}</p>
                                </div>
                                <div>
                                    <span class="tich-caption">Status</span>
                                    <p style="margin:0.15rem 0 0;">
                                        <span class="tich-status {{ $record->status === 'active' ? 'tich-status--success' : ($record->status === 'completed' ? 'tich-status--success' : 'tich-status--warning') }}">
                                            {{ $record->status }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <hr style="border:0; border-top:1px solid #e2e8f0; margin:0.75rem 0;">
                        @endforeach
                    </div>
                @endforeach
            </div>
        @else
            <p class="tich-caption tich-mt-4">No academic records yet.</p>
        @endif
    </div>

    @if(session('success'))
        <div class="tich-alert tich-alert--success tich-mt-6">{{ session('success') }}</div>
    @endif
@endsection
