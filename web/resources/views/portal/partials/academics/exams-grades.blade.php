@if ($academics['grades']->isNotEmpty())
    <section class="tich-dept-panel tich-mt-8">
        <div class="tich-dept-panel__head">
            <h2 class="tich-h2 tich-dept-panel__title">Final grades</h2>
        </div>
        <div class="tich-card tich-table-panel tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Unit</th>
                        <th>Semester</th>
                        <th>Score</th>
                        <th>Grade</th>
                        <th>Recorded</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($academics['grades'] as $grade)
                        <tr>
                            <td>{{ $grade->unit_code }} - {{ $grade->unit_name }}</td>
                            <td>{{ $grade->semester_label }}</td>
                            <td>{{ number_format((float) $grade->final_score, 1) }}</td>
                            <td>{{ $grade->grade_letter ?? '-' }}</td>
                            <td>{{ $grade->recorded_at ? \Illuminate\Support\Carbon::parse($grade->recorded_at)->format('d M Y') : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif

@if ($academics['cat_scores']->isNotEmpty())
    <section class="tich-dept-panel tich-mt-8">
        <div class="tich-dept-panel__head">
            <h2 class="tich-h2 tich-dept-panel__title">CAT &amp; continuous assessment</h2>
        </div>
        <div class="tich-card tich-table-panel tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Unit</th>
                        <th>Semester</th>
                        <th>Assessment</th>
                        <th>Score</th>
                        <th>Weight</th>
                        <th>Recorded</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($academics['cat_scores'] as $row)
                        <tr>
                            <td>{{ $row->unit_code }} - {{ $row->unit_name }}</td>
                            <td>{{ $row->semester_label }}</td>
                            <td>{{ $row->assessment_name ?: ucfirst($row->assessment_type ?? 'CAT') }}</td>
                            <td>
                                @if ($row->score_obtained !== null && $row->max_score !== null)
                                    {{ number_format((float) $row->score_obtained, 1) }}/{{ number_format((float) $row->max_score, 1) }}
                                @else
                                    {{ $row->percentage_score !== null ? number_format((float) $row->percentage_score, 1).'%' : '-' }}
                                @endif
                            </td>
                            <td>{{ $row->weight_in_final !== null ? number_format((float) $row->weight_in_final, 0).'%' : '-' }}</td>
                            <td>{{ $row->recorded_at ? \Illuminate\Support\Carbon::parse($row->recorded_at)->format('d M Y') : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif

@if ($academics['exam_results']->isNotEmpty())
    <section class="tich-dept-panel tich-mt-8">
        <div class="tich-dept-panel__head">
            <h2 class="tich-h2 tich-dept-panel__title">Exam results</h2>
        </div>
        <div class="tich-card tich-table-panel tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Unit</th>
                        <th>Semester</th>
                        <th>CAT</th>
                        <th>Exam</th>
                        <th>Total</th>
                        <th>Grade</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($academics['exam_results'] as $row)
                        <tr>
                            <td>{{ $row->unit_code }} - {{ $row->unit_name }}</td>
                            <td>{{ $row->semester_label }}</td>
                            <td>{{ $row->cat_total !== null ? number_format((float) $row->cat_total, 1) : '-' }}</td>
                            <td>{{ $row->final_exam_score !== null ? number_format((float) $row->final_exam_score, 1) : '-' }}</td>
                            <td>{{ $row->final_total_score !== null ? number_format((float) $row->final_total_score, 1) : '-' }}</td>
                            <td>{{ $row->grade_letter ?? '-' }}</td>
                            <td>
                                @if ($row->is_special_exam)
                                    Special exam
                                @elseif ($row->is_supplementary)
                                    Supplementary
                                @elseif ($row->is_published)
                                    Published
                                @else
                                    Pending
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif

@if (($portalData['transcript']['available'] ?? false))
    <section class="tich-dept-panel tich-mt-8">
        <div class="tich-dept-panel__head" style="display:flex; flex-wrap:wrap; justify-content:space-between; gap:1rem; align-items:start;">
            <div>
                <h2 class="tich-h2 tich-dept-panel__title">Official transcript</h2>
                <p class="tich-text">Download or print your academic transcript once grades have been recorded and approved.</p>
            </div>
            <div style="display:flex; flex-wrap:wrap; gap:0.5rem;">
                <a href="{{ route('portal.transcript.print') }}" target="_blank" class="tich-btn tich-btn-secondary">Print / preview</a>
                <a href="{{ route('portal.transcript.pdf') }}" class="tich-btn tich-btn-secondary">Download PDF</a>
            </div>
        </div>
    </section>
@endif

@if ($academics['grades']->isEmpty() && $academics['cat_scores']->isEmpty() && $academics['exam_results']->isEmpty())
    @include('partials.states.empty', [
        'title' => 'No exam or grade records yet',
        'description' => 'CAT marks, exam results, and final grades will appear here once your lecturers publish them.',
        'icon' => 'inbox',
    ])
@endif
