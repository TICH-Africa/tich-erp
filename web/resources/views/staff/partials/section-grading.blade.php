<header class="tich-dept-header">
    <h1 class="tich-h1 tich-dept-header__title">Marks &amp; assessments</h1>
    <p class="tich-text">Enter CAT, assignment, practical, and exam marks for students registered in your unit. Marks compile into weighted final grades using the unit assessment profile.</p>
</header>

<article class="tich-card tich-mt-6">
    <form method="GET" action="{{ route('staff.dashboard') }}" class="tich-grid tich-grid--3" style="gap:1rem; align-items:end;">
        <input type="hidden" name="section" value="grading">
        <div class="tich-form-group" style="margin:0;">
            <label class="tich-label">Unit</label>
            <select name="allocation" class="tich-input" onchange="if (this.value) this.form.submit()">
                <option value="">Select a unit…</option>
                @foreach ($portalData['allocations'] as $allocation)
                    <option value="{{ $allocation->id }}" @selected(request('allocation') == $allocation->id)>
                        {{ $allocation->unit?->unit_code }} · {{ $allocation->intake_label ?? $allocation->semester?->semester_label }}
                    </option>
                @endforeach
            </select>
        </div>
    </form>
</article>

@if ($gradingTerminal)
    @php
        $allocation = $gradingTerminal['allocation'];
        $weights = $gradingTerminal['weights'];
    @endphp

    <div class="tich-grid tich-grid--4 tich-dept-stats tich-mt-6">
        <article class="tich-card tich-stat">
            <p class="tich-caption">CAT weight</p>
            <p class="tich-stat__value">{{ number_format($weights['cat'], 0) }}%</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Practical weight</p>
            <p class="tich-stat__value">{{ number_format($weights['practical'], 0) }}%</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Attendance weight</p>
            <p class="tich-stat__value">{{ number_format($weights['attendance'], 0) }}%</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Exam weight</p>
            <p class="tich-stat__value">{{ number_format($weights['exam'], 0) }}%</p>
        </article>
    </div>

    <div class="tich-tabs tich-mt-6" data-tabs>
        <div class="tich-tabs__nav">
            <button type="button" class="tich-tabs__btn is-active" data-tab="spreadsheet">CAT &amp; continuous assessment</button>
            <button type="button" class="tich-tabs__btn" data-tab="exams">Exam marks</button>
            <button type="button" class="tich-tabs__btn" data-tab="objective">Objective auto-grading</button>
            <button type="button" class="tich-tabs__btn" data-tab="cumulative">Final score sheet</button>
        </div>

        <div class="tich-tabs__panel is-active" data-panel="spreadsheet">
            <article class="tich-card">
                <h2 class="tich-h3">{{ $allocation->unit?->unit_code }} - data entry grid</h2>
                <p class="tich-caption">Enter scores out of each column maximum. Save to compile weighted cumulative marks.</p>

                <form method="POST" action="{{ route('staff.grading.grid') }}" class="tich-mt-4">
                    @csrf
                    <input type="hidden" name="allocation_id" value="{{ $allocation->id }}">
                    @foreach ($gradingTerminal['columns'] as $index => $column)
                        <input type="hidden" name="columns[{{ $index }}][key]" value="{{ $column['key'] }}">
                        <input type="hidden" name="columns[{{ $index }}][name]" value="{{ $column['name'] }}">
                        <input type="hidden" name="columns[{{ $index }}][type]" value="{{ $column['type'] }}">
                        <input type="hidden" name="columns[{{ $index }}][max]" value="{{ $column['max'] }}">
                    @endforeach

                    <div class="tich-competency-grid-wrap">
                        <table class="tich-competency-grid">
                            <thead>
                                <tr>
                                    <th class="tich-competency-grid__sticky">Reg. no.</th>
                                    <th class="tich-competency-grid__sticky">Student</th>
                                    @foreach ($gradingTerminal['columns'] as $column)
                                        <th>
                                            {{ $column['label'] }}
                                            <span class="tich-caption">/ {{ number_format($column['max'], 0) }}</span>
                                            <br><span class="tich-caption">{{ $gradingTerminal['assessmentTypes'][$column['type']] ?? $column['type'] }}</span>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($gradingTerminal['roster'] as $student)
                                    @php
                                        $studentScores = $gradingTerminal['scores'][$student->student_id] ?? [];
                                    @endphp
                                    <tr>
                                        <td class="tich-competency-grid__sticky">{{ $student->registration_number }}</td>
                                        <td class="tich-competency-grid__sticky">{{ trim($student->student_name) }}</td>
                                        @foreach ($gradingTerminal['columns'] as $column)
                                            @php
                                                $cell = $studentScores[$column['key']] ?? null;
                                                $value = $cell['score'] ?? '';
                                            @endphp
                                            <td>
                                                <input type="number"
                                                       step="0.01"
                                                       min="0"
                                                       max="{{ $column['max'] }}"
                                                       name="scores[{{ $student->student_id }}][{{ $column['key'] }}]"
                                                       value="{{ $value !== '' ? $value : '' }}"
                                                       class="tich-competency-grid__input"
                                                       placeholder="-">
                                            </td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr><td colspan="{{ count($gradingTerminal['columns']) + 2 }}">No students registered for this unit.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($gradingTerminal['roster']->isNotEmpty())
                        <button type="submit" class="tich-btn tich-btn-primary tich-mt-4">Save spreadsheet &amp; update cumulative scores</button>
                    @endif
                </form>
            </article>
        </div>

        @include('staff.partials.section-grading-objective')

        <div class="tich-tabs__panel" data-panel="exams">
            <article class="tich-card">
                <h2 class="tich-h3">{{ $allocation->unit?->unit_code }} - exam marks</h2>
                <p class="tich-caption">Enter final exam scores (out of {{ number_format($examMarksSheet['exam_max'] ?? 100, 0) }}). CAT, practical, and attendance averages are pulled from marks you have already entered. Final grade uses exam weight {{ number_format($weights['exam'], 0) }}%.</p>

                <form method="POST" action="{{ route('staff.grading.exams') }}" class="tich-mt-4">
                    @csrf
                    <input type="hidden" name="allocation_id" value="{{ $allocation->id }}">
                    <input type="hidden" name="exam_max" value="{{ $examMarksSheet['exam_max'] ?? 100 }}">

                    <div class="tich-table-wrap">
                        <table class="tich-admin-table">
                            <thead>
                                <tr>
                                    <th>Reg. no.</th>
                                    <th>Student</th>
                                    <th>CAT avg %</th>
                                    <th>Practical avg %</th>
                                    <th>Attendance %</th>
                                    <th>Continuous %</th>
                                    <th>Exam / {{ number_format($examMarksSheet['exam_max'] ?? 100, 0) }}</th>
                                    <th>Final %</th>
                                    <th>Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($examMarksSheet['rows'] ?? [] as $row)
                                    <tr>
                                        <td>{{ $row['registration_number'] }}</td>
                                        <td>{{ $row['student_name'] }}</td>
                                        <td>{{ number_format($row['cat_total'], 1) }}</td>
                                        <td>{{ number_format($row['practical_total'], 1) }}</td>
                                        <td>{{ number_format($row['attendance_pct'], 1) }}</td>
                                        <td>{{ number_format($row['continuous_total'], 1) }}</td>
                                        <td>
                                            <input type="number"
                                                   step="0.01"
                                                   min="0"
                                                   max="{{ $examMarksSheet['exam_max'] ?? 100 }}"
                                                   name="exam_scores[{{ $row['student_id'] }}]"
                                                   value="{{ $row['exam_score'] !== null ? $row['exam_score'] : '' }}"
                                                   class="tich-input"
                                                   style="width:5rem;"
                                                   placeholder="-">
                                        </td>
                                        <td>{{ $row['final_total'] !== null ? number_format($row['final_total'], 1) : '-' }}</td>
                                        <td>{{ $row['grade_letter'] ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="9">No students registered for this unit.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if (! empty($examMarksSheet['rows']))
                        <button type="submit" class="tich-btn tich-btn-primary tich-mt-4">Save exam marks</button>
                    @endif
                </form>
            </article>
        </div>

        <div class="tich-tabs__panel" data-panel="cumulative">
            <article class="tich-card">
                <h2 class="tich-h3">Automated cumulative score sheet</h2>
                <p class="tich-caption">Weighted from CAT/review/assignment averages, practical/skills lab averages, and attendance participation.</p>
                <div class="tich-table-wrap tich-mt-4">
                    <table class="tich-admin-table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>CAT avg</th>
                                <th>Practical avg</th>
                                <th>Attendance</th>
                                <th>Cumulative</th>
                                <th>Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($gradingTerminal['cumulative'] as $row)
                                <tr @class(['tich-competency-grid__fail' => $row['at_risk']])>
                                    <td>{{ $row['registration_number'] }} · {{ $row['student_name'] }}</td>
                                    <td>{{ number_format($row['breakdown']['cat_avg'], 1) }}%</td>
                                    <td>{{ number_format($row['breakdown']['practical_avg'], 1) }}%</td>
                                    <td>{{ number_format($row['breakdown']['attendance_pct'], 1) }}%</td>
                                    <td><strong>{{ number_format($row['cumulative'], 1) }}%</strong></td>
                                    <td>{{ $row['grade_letter'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </article>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-tabs]').forEach(function (tabs) {
            tabs.querySelectorAll('[data-tab]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var name = btn.getAttribute('data-tab');
                    tabs.querySelectorAll('[data-tab]').forEach(function (b) { b.classList.remove('is-active'); });
                    tabs.querySelectorAll('[data-panel]').forEach(function (p) { p.classList.remove('is-active'); });
                    btn.classList.add('is-active');
                    tabs.querySelector('[data-panel="' + name + '"]').classList.add('is-active');
                });
            });
        });
    });
    </script>
@elseif ($portalData['allocations']->isEmpty())
    <article class="tich-card tich-mt-6">
        <p class="tich-text">You need a unit allocation before entering marks.</p>
    </article>
@else
    <article class="tich-card tich-mt-6">
        <p class="tich-text">Select a unit above to enter CAT and exam marks for enrolled students.</p>
    </article>
@endif
