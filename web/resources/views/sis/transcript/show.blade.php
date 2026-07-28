<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Academic Transcript — {{ $transcript['student']->registration_number }}</title>
    <link rel="stylesheet" href="{{ asset('css/tich-platform.css') }}">
    <style>
        body { background: #fff; color: #111; font-family: Georgia, 'Times New Roman', serif; margin: 0; }
        .tich-transcript { max-width: 900px; margin: 0 auto; padding: 2rem; }
        .tich-transcript__header { text-align: center; border-bottom: 2px solid #1e3a5f; padding-bottom: 1rem; margin-bottom: 1.5rem; }
        .tich-transcript__header h1 { margin: 0; font-size: 1.5rem; letter-spacing: 0.04em; text-transform: uppercase; }
        .tich-transcript__meta { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem 2rem; margin: 1.5rem 0; font-size: 0.95rem; }
        .tich-transcript__meta dt { font-weight: bold; }
        .tich-transcript table { width: 100%; border-collapse: collapse; font-size: 0.88rem; margin-bottom: 1.25rem; }
        .tich-transcript th, .tich-transcript td { border: 1px solid #cbd5e1; padding: 0.45rem 0.55rem; text-align: left; }
        .tich-transcript th { background: #f1f5f9; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.03em; }
        .tich-transcript .num { text-align: right; }
        .tich-transcript__semester { margin-top: 1.5rem; }
        .tich-transcript__semester h2 { font-size: 1rem; margin: 0 0 0.5rem; color: #1e3a5f; }
        .tich-transcript__summary { margin-top: 2rem; padding-top: 1rem; border-top: 2px solid #1e3a5f; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
        .tich-transcript__actions { max-width: 900px; margin: 1rem auto; padding: 0 2rem; }
        @media print {
            .tich-transcript__actions { display: none; }
            body { background: #fff; }
        }
    </style>
</head>
<body>
    <div class="tich-transcript__actions">
        <a href="{{ route('sis.students.show', $transcript['student']->id) }}#academic-record" class="tich-link">&larr; Back to student record</a>
        <button type="button" onclick="window.print()" class="tich-btn tich-btn-secondary" style="margin-left:1rem;">Print transcript</button>
    </div>

    <article class="tich-transcript">
        <header class="tich-transcript__header">
            <p style="margin:0; font-size:0.85rem;">The International College of Health Sciences</p>
            <h1>Official Academic Transcript</h1>
            <p style="margin:0.5rem 0 0; font-size:0.85rem;">Generated {{ $transcript['generated_at']->format('d F Y') }}</p>
        </header>

        @php
            $student = $transcript['student'];
            $program = $transcript['program'];
        @endphp

        <dl class="tich-transcript__meta">
            <div><dt>Student name</dt><dd>{{ $student->applicant?->fullName() ?? trim($student->registration_number) }}</dd></div>
            <div><dt>Registration number</dt><dd>{{ $student->registration_number }}</dd></div>
            <div><dt>Programme</dt><dd>{{ $program?->program_name ?? '—' }}</dd></div>
            <div><dt>Campus</dt><dd>{{ $student->campus?->campus_name ?? '—' }}</dd></div>
            <div><dt>Enrollment status</dt><dd>{{ ucfirst($student->enrollment_status) }}</dd></div>
            <div><dt>Department</dt><dd>{{ $program?->department?->dept_name ?? '—' }}</dd></div>
        </dl>

        @forelse ($transcript['semester_blocks'] as $block)
            <section class="tich-transcript__semester">
                <h2>{{ $block['semester_label'] }} @if($block['year_label']) · {{ $block['year_label'] }} @endif</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Unit</th>
                            <th class="num">Credits</th>
                            <th class="num">Continuous</th>
                            <th class="num">Exam</th>
                            <th class="num">Final</th>
                            <th>Grade</th>
                            <th class="num">GP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($block['rows'] as $row)
                            <tr>
                                <td>{{ $row->unit_code }}</td>
                                <td>{{ $row->unit_name }}</td>
                                <td class="num">{{ number_format($row->credit_hours, 1) }}</td>
                                <td class="num">{{ number_format($row->continuous_score, 1) }}%</td>
                                <td class="num">{{ $row->exam_score !== null ? number_format($row->exam_score, 1).'%' : '—' }}</td>
                                <td class="num"><strong>{{ number_format($row->final_score, 1) }}%</strong></td>
                                <td>{{ $row->grade_letter }}</td>
                                <td class="num">{{ number_format($row->grade_points, 1) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2"><strong>Semester summary</strong></td>
                            <td class="num"><strong>{{ number_format($block['credits'], 1) }}</strong></td>
                            <td colspan="4"></td>
                            <td colspan="2" class="num"><strong>GPA: {{ number_format($block['semester_gpa'], 2) }}</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </section>
        @empty
            <p>No cumulative grade records compiled yet. Transcript rows appear once tutors save assessment data in the evaluation terminal.</p>
        @endforelse

        <footer class="tich-transcript__summary">
            <div>
                <strong>Units recorded:</strong> {{ $transcript['units_completed'] }}<br>
                <strong>Total credit hours:</strong> {{ number_format($transcript['total_credits'], 1) }}
            </div>
            <div style="text-align:right;">
                <strong style="font-size:1.15rem;">Cumulative GPA: {{ number_format($transcript['cumulative_gpa'], 2) }}</strong><br>
                <span style="font-size:0.85rem;">Based on weighted grade points × credit hours</span>
            </div>
        </footer>
    </article>
</body>
</html>
