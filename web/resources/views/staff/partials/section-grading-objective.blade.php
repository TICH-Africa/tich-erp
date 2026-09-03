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

                <form method="POST" action="{{ route('staff.grading.objective.availability', $selected) }}" class="tich-mt-4">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="allocation_id" value="{{ $allocation->id }}">
                    <div class="tich-grid tich-grid--3" style="gap:1rem; align-items:end;">
                        <div class="tich-form-group" style="margin:0;">
                            <label class="tich-label">Available from (date & time)</label>
                            <input type="datetime-local" name="available_from" class="tich-input" value="{{ $selected->available_from?->format('Y-m-d\TH:i') }}">
                        </div>
                        <div class="tich-form-group" style="margin:0;">
                            <label class="tich-label">Available until (date & time)</label>
                            <input type="datetime-local" name="available_until" class="tich-input" value="{{ $selected->available_until?->format('Y-m-d\TH:i') }}">
                        </div>
                        <div class="tich-form-group" style="margin:0;">
                            <button type="submit" class="tich-btn tich-btn-secondary">Update availability</button>
                        </div>
                    </div>
                    <p class="tich-caption tich-mt-2">Extend or change the dates to re-open this assessment for students.</p>
                </form>

                <form method="POST" action="{{ route('staff.grading.objective.responses') }}" class="tich-mt-4" data-autosave="grading-objective-responses">
                    @csrf
                    <input type="hidden" name="allocation_id" value="{{ $allocation->id }}">
                    <input type="hidden" name="objective_assessment_id" value="{{ $selected->id }}">

                    <div class="tich-form-toolbar tich-mb-4">
                        <p class="tich-caption">Enter student responses below. They save automatically; you can also use the buttons at the bottom.</p>
                        <span class="tich-autosave-status" data-autosave-status="grading-objective-responses" data-state="idle" aria-live="polite">Changes save automatically</span>
                    </div>

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
                                                        <option value="">-</option>
                                                        <option value="true" @selected(strtolower((string) $value) === 'true')>True</option>
                                                        <option value="false" @selected(strtolower((string) $value) === 'false')>False</option>
                                                    </select>
                                                @elseif ($question->question_type === 'mcq' && is_array($question->options))
                                                    <select name="responses[{{ $student->student_id }}][{{ $question->id }}]" class="tich-competency-grid__input">
                                                        <option value="">-</option>
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

                @php
                    $hasSubjective = $selected->questions->contains(fn ($q) => in_array($q->question_type, ['essay', 'long_answer']));
                @endphp

                @if ($hasSubjective && $objectiveTerminal['roster']->isNotEmpty())
                    <article class="tich-card tich-mt-6">
                        <h3 class="tich-h3">Manual grading</h3>
                        <p class="tich-caption">Grade long answer submissions individually. Objective questions are auto-graded above.</p>

                        <form method="POST" action="{{ route('staff.grading.objective.manual-grade') }}" class="tich-mt-4">
                            @csrf
                            <input type="hidden" name="allocation_id" value="{{ $allocation->id }}">
                            <input type="hidden" name="objective_assessment_id" value="{{ $selected->id }}">

                            <div class="tich-form-group">
                                <label class="tich-label">Select student</label>
                                <select name="student_id" class="tich-input" required onchange="this.form.submit()">
                                    <option value="">Choose student…</option>
                                    @foreach ($objectiveTerminal['roster'] as $student)
                                        @php
                                            $submission = $selected->submissions->firstWhere('student_id', $student->student_id);
                                        @endphp
                                        <option value="{{ $student->student_id }}" @selected($submission && $submission->student_submitted_at)>
                                            {{ trim($student->student_name) }} ({{ $student->registration_number }})
                                            @if ($submission && $submission->student_submitted_at)
                                                - {{ $submission->percentage_score }}%
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            @if ($selectedStudent = $objectiveTerminal['roster']->firstWhere('student_id', request()->integer('student_id')))
                                @php
                                    $selectedSubmission = $selected->submissions->firstWhere('student_id', $selectedStudent->student_id);
                                @endphp
                                @if ($selectedSubmission && $selectedSubmission->student_submitted_at)
                                    <div class="tich-mt-4">
                                        <h4 class="tich-h4">{{ trim($selectedStudent->student_name) }} - {{ $selectedStudent->registration_number }}</h4>

                                        @foreach ($selected->questions as $question)
                                            @if (in_array($question->question_type, ['essay', 'long_answer']))
                                                @php
                                                    $answer = $selectedSubmission->responses[$question->id] ?? $selectedSubmission->responses[(string) $question->id] ?? '';
                                                @endphp
                                                <div class="tich-card tich-mt-4">
                                                    <p class="tich-h4">{{ $question->question_text }}</p>
                                                    <div class="tich-card__body" style="background: #f8f9fa; padding: 1rem; border-radius: 0.5rem; margin-top: 0.5rem;">
                                                        <p>{{ $answer ?: 'No answer provided' }}</p>
                                                    </div>
                                                    <div class="tich-form-group tich-mt-4">
                                                        <label class="tich-label">Mark (max {{ $question->points }})</label>
                                                        <input type="number" name="marks[{{ $question->id }}]" class="tich-input" value="{{ $selectedSubmission->score_obtained ?? 0 }}" min="0" max="{{ $question->points }}" step="0.01" style="max-width: 8rem;">
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach

                                        <div class="tich-grid tich-grid--2 tich-mt-4">
                                            <div class="tich-form-group">
                                                <label class="tich-label">Total score</label>
                                                <input type="number" name="score_obtained" class="tich-input" value="{{ $selectedSubmission->score_obtained ?? 0 }}" min="0" max="{{ $selected->max_score }}" step="0.01" required>
                                            </div>
                                            <div class="tich-form-group">
                                                <label class="tich-label">Feedback</label>
                                                <textarea name="feedback" class="tich-input" rows="3">{{ $selectedSubmission->feedback ?? '' }}</textarea>
                                            </div>
                                        </div>

                                        <input type="hidden" name="submission_id" value="{{ $selectedSubmission->id }}">
                                        <input type="hidden" name="student_id" value="{{ $selectedStudent->student_id }}">

                                        <button type="submit" class="tich-btn tich-btn-primary tich-mt-4">Save grade</button>
                                    </div>
                                @else
                                    <p class="tich-text tich-mt-4">This student has not submitted yet.</p>
                                @endif
                            @endif
                        </form>
                    </article>
                @endif

                <details class="tich-grading-details tich-mt-6">
                    <summary class="tich-h3 tich-grading-details__summary">Answer key</summary>
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
                            <input type="text" name="name" class="tich-input" required placeholder="CAT 1 - Objective section">
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

                    <div class="tich-grid tich-grid--3" style="gap:1rem; margin-top:1rem;">
                        <div class="tich-form-group">
                            <label class="tich-label">Available from (date & time)</label>
                            <input type="datetime-local" name="available_from" class="tich-input">
                        </div>
                        <div class="tich-form-group">
                            <label class="tich-label">Available until (date & time)</label>
                            <input type="datetime-local" name="available_until" class="tich-input">
                        </div>
                        <div class="tich-form-group">
                            <label class="tich-label">Time limit (minutes)</label>
                            <input type="number" name="time_limit_minutes" class="tich-input" value="30" min="1" max="480">
                        </div>
                    </div>

                    <p class="tich-caption tich-mt-4">Questions - separate MCQ options with <code>|</code> (e.g. <code>A|B|C</code>). For essay/long answer questions, leave options blank.</p>
                    @for ($i = 0; $i < 5; $i++)
                        <div class="tich-card tich-grading-question-card tich-mt-4">
                            <div class="tich-form-group">
                                <label class="tich-label">Question {{ $i + 1 }}</label>
                                <input type="text" name="questions[{{ $i }}][question_text]" class="tich-input" placeholder="Question text (leave blank to skip)">
                            </div>
                            <div class="tich-grid tich-grid--3" style="gap:1rem;">
                                <div class="tich-form-group">
                                    <label class="tich-label">Type</label>
                                    <select name="questions[{{ $i }}][question_type]" class="tich-input" onchange="toggleQuestionOptions(this)">
                                        <option value="mcq">Multiple choice</option>
                                        <option value="true_false">True / False</option>
                                        <option value="essay">Essay / Long answer</option>
                                    </select>
                                </div>
                                <div class="tich-form-group question-options-field">
                                    <label class="tich-label">Options (pipe-separated)</label>
                                    <input type="text" name="questions[{{ $i }}][options]" class="tich-input" placeholder="A|B|C">
                                </div>
                                <div class="tich-form-group question-options-field">
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

                    <script>
                    function toggleQuestionOptions(select) {
                        var card = select.closest('.tich-grading-question-card');
                        var isObjective = select.value === 'mcq' || select.value === 'true_false';
                        var fields = card.querySelectorAll('.question-options-field');
                        fields.forEach(function(field) {
                            field.style.display = isObjective ? '' : 'none';
                        });
                    }
                    document.querySelectorAll('.tich-grading-question-card select').forEach(function(select) {
                        toggleQuestionOptions(select);
                    });
                    </script>

                    <button type="submit" class="tich-btn tich-btn-primary tich-mt-4">Create assessment &amp; open response grid</button>
                </form>
            </article>
        @endif
    </div>
@endif
