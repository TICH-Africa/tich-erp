@if ($gradingTerminal && $objectiveTerminal)
    @php
        $allocation = $gradingTerminal['allocation'];
        $selected = $objectiveTerminal['selected'];
    @endphp

    <div class="tich-tabs__panel" data-panel="objective">
        <article class="tich-card">
            <h2 class="tich-h3">Objective auto-grading</h2>
            <p class="tich-caption">Build MCQ, true/false, or matching assessments with answer keys. Enter student responses, then run auto-grade to push scores into the competency spreadsheet.</p>

            <form method="GET" action="{{ route('staff.dashboard') }}" class="tich-mt-4" style="display:flex; flex-wrap:wrap; gap:1rem; align-items:end;">
                <input type="hidden" name="section" value="grading">
                <input type="hidden" name="allocation" value="{{ $allocation->id }}">
                <div class="tich-form-group" style="margin:0;">
                    <label class="tich-label">Existing assessment</label>
                    <select name="objective_assessment" class="tich-input" onchange="this.form.submit()">
                        <option value="">Create new…</option>
                        @foreach ($objectiveTerminal['assessments'] as $assessment)
                            <option value="{{ $assessment->id }}" @selected($selected?->id === $assessment->id)>
                                {{ $assessment->name }} ({{ ucfirst($assessment->status) }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </article>

        @if ($selected)
            <article class="tich-card tich-mt-6">
                <h3 class="tich-h3">{{ $selected->name }}</h3>
                <p class="tich-caption">{{ $objectiveTerminal['question_types'][$selected->assessment_type] ?? $selected->assessment_type }} · Max {{ number_format($selected->max_score, 0) }} marks · {{ $selected->questions->count() }} questions</p>

                @if ($selected->status === 'graded')
                    <p class="tich-text tich-mt-2">Auto-graded {{ $selected->auto_graded_at?->format('d M Y H:i') }}. Scores synced to cumulative sheet.</p>
                @endif

                <form method="POST" action="{{ route('staff.grading.objective.responses') }}" class="tich-mt-4">
                    @csrf
                    <input type="hidden" name="allocation_id" value="{{ $allocation->id }}">
                    <input type="hidden" name="objective_assessment_id" value="{{ $selected->id }}">

                    <div class="tich-competency-grid-wrap">
                        <table class="tich-competency-grid">
                            <thead>
                                <tr>
                                    <th class="tich-competency-grid__sticky">Reg. no.</th>
                                    <th class="tich-competency-grid__sticky">Student</th>
                                    @foreach ($selected->questions as $question)
                                        <th title="{{ $question->question_text }}">
                                            Q{{ $question->sort_order }}
                                            <span class="tich-caption">({{ $question->points }}pt)</span>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($objectiveTerminal['roster'] as $student)
                                    @php
                                        $responses = $objectiveTerminal['response_matrix'][$student->student_id] ?? [];
                                        $submission = $selected->submissions->firstWhere('student_id', $student->student_id);
                                    @endphp
                                    <tr>
                                        <td class="tich-competency-grid__sticky">{{ $student->registration_number }}</td>
                                        <td class="tich-competency-grid__sticky">
                                            {{ trim($student->student_name) }}
                                            @if ($submission?->auto_graded_at)
                                                <br><span class="tich-caption">{{ number_format($submission->score_obtained, 1) }}/{{ number_format($selected->max_score, 0) }}</span>
                                            @endif
                                        </td>
                                        @foreach ($selected->questions as $question)
                                            @php
                                                $value = $responses[(string) $question->id] ?? $responses[$question->id] ?? '';
                                            @endphp
                                            <td>
                                                @if ($question->question_type === 'true_false')
                                                    <select name="responses[{{ $student->student_id }}][{{ $question->id }}]" class="tich-competency-grid__input">
                                                        <option value="">—</option>
                                                        <option value="true" @selected(strtolower((string) $value) === 'true')>True</option>
                                                        <option value="false" @selected(strtolower((string) $value) === 'false')>False</option>
                                                    </select>
                                                @elseif ($question->question_type === 'mcq' && is_array($question->options))
                                                    <select name="responses[{{ $student->student_id }}][{{ $question->id }}]" class="tich-competency-grid__input">
                                                        <option value="">—</option>
                                                        @foreach ($question->options as $option)
                                                            <option value="{{ $option }}" @selected((string) $value === (string) $option)>{{ $option }}</option>
                                                        @endforeach
                                                    </select>
                                                @else
                                                    <input type="text"
                                                           name="responses[{{ $student->student_id }}][{{ $question->id }}]"
                                                           value="{{ $value }}"
                                                           class="tich-competency-grid__input"
                                                           placeholder="Answer">
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($objectiveTerminal['roster']->isNotEmpty())
                        <div style="display:flex; gap:1rem; flex-wrap:wrap;" class="tich-mt-4">
                            <button type="submit" class="tich-btn tich-btn-secondary">Save responses</button>
                        </div>
                    @endif
                </form>

                @if ($objectiveTerminal['roster']->isNotEmpty() && $selected->status !== 'graded')
                    <form method="POST" action="{{ route('staff.grading.objective.grade') }}" class="tich-mt-4">
                        @csrf
                        <input type="hidden" name="allocation_id" value="{{ $allocation->id }}">
                        <input type="hidden" name="objective_assessment_id" value="{{ $selected->id }}">
                        <button type="submit" class="tich-btn tich-btn-primary">Run auto-grade &amp; sync to cumulative scores</button>
                    </form>
                @endif

                <details class="tich-mt-6">
                    <summary class="tich-h3" style="cursor:pointer;">Answer key</summary>
                    <ol class="tich-mt-4">
                        @foreach ($selected->questions as $question)
                            <li class="tich-text tich-mt-2">
                                <strong>{{ $question->question_text }}</strong>
                                <br><span class="tich-caption">Correct: {{ $question->correct_answer }} · {{ $question->points }} pts</span>
                            </li>
                        @endforeach
                    </ol>
                </details>
            </article>
        @else
            <article class="tich-card tich-mt-6">
                <h3 class="tich-h3">New objective assessment</h3>
                <form method="POST" action="{{ route('staff.grading.objective.store') }}" class="tich-mt-4">
                    @csrf
                    <input type="hidden" name="allocation_id" value="{{ $allocation->id }}">
                    <div class="tich-grid tich-grid--3" style="gap:1rem;">
                        <div class="tich-form-group">
                            <label class="tich-label">Assessment name</label>
                            <input type="text" name="name" class="tich-input" required placeholder="CAT 1 — Objective section">
                        </div>
                        <div class="tich-form-group">
                            <label class="tich-label">Type</label>
                            <select name="assessment_type" class="tich-input">
                                @foreach ($objectiveTerminal['question_types'] as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="tich-form-group">
                            <label class="tich-label">Max score</label>
                            <input type="number" name="max_score" class="tich-input" value="30" min="1" step="0.01">
                        </div>
                    </div>

                    <p class="tich-caption tich-mt-4">Questions — separate MCQ options with <code>|</code> (e.g. <code>A|B|C|D</code>)</p>
                    @for ($i = 0; $i < 5; $i++)
                        <div class="tich-card tich-mt-4" style="padding:1rem; background:var(--tich-neutral-bg, #f8fafc);">
                            <div class="tich-form-group">
                                <label class="tich-label">Question {{ $i + 1 }}</label>
                                <input type="text" name="questions[{{ $i }}][question_text]" class="tich-input" placeholder="Question text (leave blank to skip)">
                            </div>
                            <div class="tich-grid tich-grid--3" style="gap:1rem;">
                                <div class="tich-form-group">
                                    <label class="tich-label">Type</label>
                                    <select name="questions[{{ $i }}][question_type]" class="tich-input">
                                        <option value="mcq">Multiple choice</option>
                                        <option value="true_false">True / False</option>
                                    </select>
                                </div>
                                <div class="tich-form-group">
                                    <label class="tich-label">Options (MCQ)</label>
                                    <input type="text" name="questions[{{ $i }}][options]" class="tich-input" placeholder="A|B|C|D">
                                </div>
                                <div class="tich-form-group">
                                    <label class="tich-label">Correct answer</label>
                                    <input type="text" name="questions[{{ $i }}][correct_answer]" class="tich-input" placeholder="A or true">
                                </div>
                            </div>
                            <div class="tich-form-group">
                                <label class="tich-label">Points</label>
                                <input type="number" name="questions[{{ $i }}][points]" class="tich-input" value="1" min="0.01" step="0.01" style="max-width:6rem;">
                            </div>
                        </div>
                    @endfor

                    <button type="submit" class="tich-btn tich-btn-primary tich-mt-4">Create assessment &amp; open response grid</button>
                </form>
            </article>
        @endif
    </div>
@endif
