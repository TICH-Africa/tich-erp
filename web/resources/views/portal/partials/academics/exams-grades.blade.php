@php
    $examPortal = $academics['exam_portal'] ?? [];
    $upcomingExams = $examPortal['upcoming_exams'] ?? collect();
    $canViewExamCard = (bool) ($examPortal['can_view_exam_card'] ?? false);
    $semesterId = $examPortal['semester_id'] ?? null;
    $examCard = $examPortal['exam_card'] ?? null;
@endphp

@if ($upcomingExams->isNotEmpty())
    <section class="tich-portal-panel tich-mt-6">
        <div class="tich-portal-panel__head">
            <div>
                <h2 class="tich-h3">Upcoming examinations</h2>
                <p class="tich-caption tich-mt-1">
                    {{ $examPortal['semester_label'] ?? 'Current semester' }} ·
                    {{ $upcomingExams->count() }} scheduled
                </p>
            </div>
            @if ($canViewExamCard && $semesterId)
                <div class="tich-portal-actions">
                    <a href="{{ route('portal.exam-card.print', ['semester' => $semesterId]) }}" target="_blank" rel="noopener" class="tich-btn tich-btn-secondary tich-btn--compact">
                        View exam card
                    </a>
                    <a href="{{ route('portal.exam-card.pdf', ['semester' => $semesterId]) }}" class="tich-btn tich-btn-primary tich-btn--compact">
                        Download exam card
                    </a>
                </div>
            @endif
        </div>

        @if ($examCard)
            <p class="tich-caption tich-mt-3">
                Exam card <strong>{{ $examCard->exam_card_number }}</strong>
                @if ($examCard->examination_number)
                    · Examination no. {{ $examCard->examination_number }}
                @endif
            </p>
        @endif

        <div class="tich-portal-card-grid tich-mt-4">
            @foreach ($upcomingExams as $exam)
                <article class="tich-portal-item-card{{ $exam->eligible_for_exams ? '' : ' tich-portal-item-card--muted' }}">
                    <div class="tich-portal-item-card__head">
                        <p class="tich-portal-item-card__code">{{ $exam->unit_code }}</p>
                        @if ($exam->eligible_for_exams)
                            <span class="tich-portal-badge tich-portal-badge--success">Eligible</span>
                        @else
                            <span class="tich-portal-badge tich-portal-badge--warning">Not cleared</span>
                        @endif
                    </div>
                    <h3 class="tich-portal-item-card__title">{{ $exam->unit_name }}</h3>
                    <dl class="tich-portal-item-card__meta">
                        <div>
                            <dt>Date</dt>
                            <dd>{{ \Illuminate\Support\Carbon::parse($exam->exam_date)->format('d M Y') }}</dd>
                        </div>
                        <div>
                            <dt>Time</dt>
                            <dd>
                                @if ($exam->start_time && $exam->end_time)
                                    {{ substr((string) $exam->start_time, 0, 5) }} – {{ substr((string) $exam->end_time, 0, 5) }}
                                @else
                                    -
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt>Venue</dt>
                            <dd>{{ $exam->venue ?: '-' }}</dd>
                        </div>
                        <div>
                            <dt>Type</dt>
                            <dd>{{ ucfirst(str_replace('_', ' ', (string) $exam->exam_type)) }}</dd>
                        </div>
                    </dl>
                    @if (! $exam->eligible_for_exams)
                        <p class="tich-caption tich-mt-3">Clear fees and meet attendance requirements to receive your exam card.</p>
                    @endif
                </article>
            @endforeach
        </div>
    </section>
@endif

@if ($academics['grades']->isNotEmpty())
    <section class="tich-portal-panel tich-mt-8">
        <div class="tich-portal-panel__head">
            <h2 class="tich-h3">Final grades</h2>
        </div>
        <div class="tich-card tich-table-panel tich-mt-4">
            <div class="tich-table-wrap">
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
        </div>
    </section>
@endif

@if ($academics['cat_scores']->isNotEmpty())
    <section class="tich-portal-panel tich-mt-8">
        <div class="tich-portal-panel__head">
            <h2 class="tich-h3">CAT &amp; continuous assessment</h2>
        </div>
        <div class="tich-card tich-table-panel tich-mt-4">
            <div class="tich-table-wrap">
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
        </div>
    </section>
@endif

@if ($academics['exam_results']->isNotEmpty())
    <section class="tich-portal-panel tich-mt-8">
        <div class="tich-portal-panel__head">
            <h2 class="tich-h3">Exam results</h2>
        </div>
        <div class="tich-card tich-table-panel tich-mt-4">
            <div class="tich-table-wrap">
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
        </div>
    </section>
@endif

@if (($portalData['transcript']['available'] ?? false))
    <section class="tich-portal-panel tich-mt-8">
        <div class="tich-portal-panel__head">
            <div>
                <h2 class="tich-h3">Official transcript</h2>
                <p class="tich-caption tich-mt-1">Download or print your academic transcript once grades have been recorded.</p>
            </div>
            <div class="tich-portal-actions">
                <a href="{{ route('portal.transcript.print') }}" target="_blank" rel="noopener" class="tich-btn tich-btn-secondary tich-btn--compact">Print / preview</a>
                <a href="{{ route('portal.transcript.pdf') }}" class="tich-btn tich-btn-secondary tich-btn--compact">Download PDF</a>
            </div>
        </div>
    </section>
@endif

@if ($upcomingExams->isEmpty() && $academics['grades']->isEmpty() && $academics['cat_scores']->isEmpty() && $academics['exam_results']->isEmpty())
    @include('partials.states.empty', [
        'title' => 'No exam or grade records yet',
        'description' => 'CAT marks, exam results, and final grades will appear here once your lecturers publish them.',
        'icon' => 'inbox',
    ])
@endif
