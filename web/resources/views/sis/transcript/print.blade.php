@extends('layouts.print-document')

@section('document-content')
    @php
        $student = $transcript['student'];
        $program = $transcript['program'];
        $applicant = $student->applicant;
        $blocks = collect($transcript['semester_blocks']);
        $identity = [
            ['Student name', $applicant?->fullName() ?? $student->registration_number],
            ['Registration number', $student->registration_number],
            ['Programme', trim(($program?->program_code ?? '').' - '.($program?->program_name ?? '-'))],
            ['Department', $program?->department?->dept_name ?? '-'],
            ['Campus', $student->campus?->campus_name ?? '-'],
            ['Cohort intake', $student->cohort_intake ?: '-'],
            ['Year of study', $student->current_year ?: '-'],
            ['Date of admission', $student->date_of_admission ? \Illuminate\Support\Carbon::parse($student->date_of_admission)->format('d M Y') : '-'],
            ['Entry pathway', $student->entry_pathway ? str($student->entry_pathway)->title()->toString() : '-'],
            ['Enrollment status', ucfirst((string) $student->enrollment_status)],
        ];
    @endphp

    <section class="tich-transcript-panel">
        <h2 class="tich-transcript-panel__title">Student identification</h2>
        <table class="tich-transcript-identity">
            <tbody>
                @foreach (collect($identity)->chunk(2) as $pair)
                    <tr>
                        @foreach ($pair as $field)
                            <th>{{ $field[0] }}</th>
                            <td>{{ $field[1] }}</td>
                        @endforeach
                        @if ($pair->count() === 1)
                            <th></th><td></td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    <section class="tich-transcript-panel">
        <h2 class="tich-transcript-panel__title">Academic record</h2>

        @forelse ($blocks as $block)
            <p class="tich-transcript-term">{{ $block['semester_label'] }}@if (! empty($block['year_label'])) - {{ $block['year_label'] }} @endif</p>
            <table class="tich-doc-table tich-transcript-results">
                <thead>
                    <tr>
                        <th class="col-code">Code</th>
                        <th class="col-unit">Unit</th>
                        <th class="num col-credits">Credits</th>
                        <th class="num col-mark">Continuous</th>
                        <th class="num col-mark">Exam</th>
                        <th class="num col-mark">Final</th>
                        <th class="col-grade">Grade</th>
                        <th class="num col-grade">Points</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($block['rows'] as $row)
                        <tr>
                            <td class="mono">{{ $row->unit_code }}</td>
                            <td>{{ $row->unit_name }}</td>
                            <td class="num">{{ number_format((float) $row->credit_hours, 1) }}</td>
                            <td class="num">{{ $row->continuous_score !== null ? number_format((float) $row->continuous_score, 1) : '-' }}</td>
                            <td class="num">{{ $row->exam_score !== null ? number_format((float) $row->exam_score, 1) : '-' }}@if (! empty($row->is_supplementary))<sup>Supp</sup>@endif</td>
                            <td class="num"><strong>{{ $row->final_score !== null ? number_format((float) $row->final_score, 1) : '-' }}</strong></td>
                            <td class="grade">{{ $row->grade_letter ?: '-' }}</td>
                            <td class="num">{{ $row->grade_letter ? number_format((float) $row->grade_points, 1) : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2">Semester total</td>
                        <td class="num">{{ number_format((float) $block['credits'], 1) }}</td>
                        <td class="num" colspan="3">Semester GPA {{ number_format((float) $block['semester_gpa'], 2) }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        @empty
            <p class="tich-transcript-note">
                No graded units have been recorded for this student yet. Marks appear here once the
                lecturer submits and the exam office approves the assessment for each unit.
            </p>
        @endforelse

        @if (collect($blocks)->flatMap(fn (array $block) => $block['rows'])->contains(fn ($row) => ! empty($row->is_supplementary)))
            <p class="tich-transcript-note">
                Supp marks a unit carried by a supplementary examination that replaced the failed sitting.
                The unit counts once towards the credits and the GPA.
            </p>
        @endif
    </section>

    <section class="tich-transcript-panel">
        <h2 class="tich-transcript-panel__title">Cumulative position</h2>
        <table class="tich-transcript-identity">
            <tbody>
                <tr>
                    <th>Semesters recorded</th>
                    <td>{{ $stats['semesters'] }}</td>
                    <th>Units graded</th>
                    <td>{{ $stats['units_graded'] }}</td>
                </tr>
                <tr>
                    <th>Credit hours attempted</th>
                    <td>{{ number_format($stats['credits_attempted'], 1) }}</td>
                    <th>Credit hours earned</th>
                    <td>{{ number_format($stats['credits_earned'], 1) }}</td>
                </tr>
                <tr>
                    <th>Average score</th>
                    <td>{{ $stats['average_score'] === null ? '-' : number_format($stats['average_score'], 1).'%' }}</td>
                    <th>Cumulative GPA</th>
                    <td class="gpa">{{ number_format($stats['cumulative_gpa'], 2) }} / 4.00</td>
                </tr>
            </tbody>
        </table>
    </section>

    <section class="tich-transcript-panel">
        <h2 class="tich-transcript-panel__title">Grade scale</h2>
        <table class="tich-doc-table tich-transcript-scale">
            <thead>
                <tr>
                    <th class="col-grade">Grade</th>
                    <th>Score band</th>
                    <th class="num col-grade">Grade points</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($gradeScale as $step)
                    <tr>
                        <td class="grade">{{ $step['letter'] }}</td>
                        <td>{{ $step['range'] }}</td>
                        <td class="num">{{ $step['points'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="tich-transcript-note">
            GPA is the credit-weighted average of the grade points above. A unit carries no credit hours
            earned when it is graded F, and a repeated unit is recorded once at its latest mark.
        </p>
    </section>

    <p class="tich-transcript-cert">
        This transcript was produced from the student records system of
        {{ $institution['name'] ?? 'the Institute' }} on {{ ($generatedAt ?? now())->format('d F Y') }} and
        carries document reference {{ $documentRef ?? '-' }}. It is valid only with the signature and stamp of
        the Academic Registrar below.
    </p>
@endsection
