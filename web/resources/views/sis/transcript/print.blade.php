@extends('layouts.print-document')

@section('document-content')
    @php
        $student = $transcript['student'];
        $program = $transcript['program'];
    @endphp

    @forelse ($transcript['semester_blocks'] as $block)
        <section class="tich-doc-section">
            <h2>{{ $block['semester_label'] }} @if($block['year_label']) · {{ $block['year_label'] }} @endif</h2>
            <table class="tich-doc-table">
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
                        <td colspan="2">Semester summary</td>
                        <td class="num">{{ number_format($block['credits'], 1) }}</td>
                        <td colspan="4"></td>
                        <td colspan="2" class="num">GPA: {{ number_format($block['semester_gpa'], 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </section>
    @empty
        <p>No cumulative grade records compiled yet. Transcript rows appear once tutors save assessment data in the evaluation terminal.</p>
    @endforelse

    <section class="tich-doc-section">
        <table class="tich-doc-table">
            <tbody>
                <tr>
                    <td><strong>Units recorded</strong></td>
                    <td class="num">{{ $transcript['units_completed'] }}</td>
                    <td><strong>Total credit hours</strong></td>
                    <td class="num">{{ number_format($transcript['total_credits'], 1) }}</td>
                    <td><strong>Cumulative GPA</strong></td>
                    <td class="num"><strong>{{ number_format($transcript['cumulative_gpa'], 2) }}</strong></td>
                </tr>
            </tbody>
        </table>
    </section>
@endsection
