@php
    $bySemester = $academics['provisional_results_by_semester'] ?? [];
@endphp

@if (! empty($bySemester))
    <section class="tich-portal-panel tich-mt-6">
        <div class="tich-portal-panel__head">
            <div>
                <h2 class="tich-h3">Provisional results by semester</h2>
                <p class="tich-caption tich-mt-1">
                    All your registered units with CAT marks and exam scores. These are unofficial until the Academic Registrar publishes final results.
                </p>
            </div>
        </div>

        @foreach ($bySemester as $semester)
            <article class="tich-card tich-mt-4">
                <h3 class="tich-h4">{{ $semester['semester_label'] ?? 'Semester' }}</h3>

                @forelse ($semester['units'] ?? [] as $unit)
                    <div class="tich-mt-4" style="border-top:1px solid var(--tich-border, #e5e7eb); padding-top:1rem;">
                        <p class="tich-text" style="margin:0; font-weight:600;">
                            {{ $unit['unit_code'] ?? 'Unit' }} · {{ $unit['unit_name'] ?? '' }}
                        </p>

                        @if (! empty($unit['cats']))
                            <p class="tich-caption tich-mt-2" style="margin-bottom:0.35rem;">Continuous assessment (CAT)</p>
                            <div class="tich-table-wrap">
                                <table class="tich-admin-table">
                                    <thead>
                                        <tr>
                                            <th>Assessment</th>
                                            <th>Score</th>
                                            <th>Weight</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($unit['cats'] as $cat)
                                            <tr>
                                                <td>{{ $cat['assessment_name'] ?: ucfirst(str_replace('_', ' ', $cat['assessment_type'] ?? 'CAT')) }}</td>
                                                <td>
                                                    @if ($cat['score_obtained'] !== null && $cat['max_score'] !== null)
                                                        {{ number_format((float) $cat['score_obtained'], 1) }}/{{ number_format((float) $cat['max_score'], 1) }}
                                                    @elseif ($cat['percentage_score'] !== null)
                                                        {{ number_format((float) $cat['percentage_score'], 1) }}%
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td>{{ $cat['weight_in_final'] !== null ? number_format((float) $cat['weight_in_final'], 0).'%' : '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        @if (! empty($unit['exam']))
                            @php $exam = $unit['exam']; @endphp
                            <p class="tich-caption tich-mt-3" style="margin-bottom:0.35rem;">Examination</p>
                            <dl class="tich-portal-item-card__meta tich-mt-1">
                                <div>
                                    <dt>CAT total</dt>
                                    <dd>{{ $exam['cat_total'] !== null ? number_format((float) $exam['cat_total'], 1) : '-' }}</dd>
                                </div>
                                <div>
                                    <dt>Exam mark</dt>
                                    <dd>{{ $exam['final_exam_score'] !== null ? number_format((float) $exam['final_exam_score'], 1) : '-' }}</dd>
                                </div>
                                <div>
                                    <dt>Total</dt>
                                    <dd>{{ $exam['final_total_score'] !== null ? number_format((float) $exam['final_total_score'], 1) : '-' }}</dd>
                                </div>
                                <div>
                                    <dt>Grade</dt>
                                    <dd>{{ $exam['grade_letter'] ?? '-' }}</dd>
                                </div>
                                <div>
                                    <dt>Status</dt>
                                    <dd>{{ $exam['status_label'] ?? 'Provisional' }}</dd>
                                </div>
                            </dl>
                        @endif

                        @if (! empty($unit['final_grade']))
                            @php $fg = $unit['final_grade']; @endphp
                            <p class="tich-caption tich-mt-3">
                                Recorded final grade:
                                <strong>{{ $fg['grade_letter'] ?? '-' }}</strong>
                                @if ($fg['final_score'] !== null)
                                    ({{ number_format((float) $fg['final_score'], 1) }})
                                @endif
                            </p>
                        @endif

                        @if (empty($unit['cats']) && empty($unit['exam']) && empty($unit['final_grade']))
                            <p class="tich-caption tich-mt-2">No marks recorded for this unit yet.</p>
                        @endif
                    </div>
                @empty
                    <p class="tich-caption tich-mt-2">No units in this semester.</p>
                @endforelse
            </article>
        @endforeach
    </section>
@endif
