@extends('layouts.employee')

@section('title', ($mode === 'manager' ? 'Team appraisal' : 'My appraisal').' '.$appraisal->appraisal_number)

@section('employee-content')
    @php
        $isManager = $mode === 'manager';
        $isEmployee = $mode === 'employee';
        $weightTotal = round((float) $appraisal->goals->sum('weight'), 2);
    @endphp

    <x-page-toolbar
        title="{{ $isManager ? ($appraisal->staff?->fullName() ?? 'Team appraisal') : 'My appraisal' }}"
        meta="{{ $appraisal->appraisal_number }} · {{ $appraisal->cycle?->label() }} · {{ $statuses[$appraisal->status] ?? $appraisal->status }}"
    >
        <a href="{{ route('employee.appraisals.index') }}" class="tich-btn tich-btn-ghost">Back</a>
    </x-page-toolbar>

    @if (session('status'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="tich-alert tich-alert--danger tich-mt-4">{{ $errors->first() }}</div>
    @endif

    <div class="uf-form tich-appraisal-form">
        <div class="uf-form-surface">
        <div class="uf-form-section">
            <div class="uf-section-head">Appraisal summary</div>
            <div class="uf-section-body">
                <div class="uf-form-grid-2">
                    <div class="uf-field">
                        <label>Job title</label>
                        <input type="text" value="{{ $appraisal->job_title_snapshot ?: '—' }}" readonly>
                    </div>
                    <div class="uf-field">
                        <label>Immediate manager</label>
                        <input type="text" value="{{ $appraisal->lineManager?->fullName() ?? '—' }}" readonly>
                    </div>
                    <div class="uf-field">
                        <label>Review period</label>
                        <input type="text" value="{{ $appraisal->cycle?->period_start?->format('d M Y') }} – {{ $appraisal->cycle?->period_end?->format('d M Y') }}" readonly>
                    </div>
                    <div class="uf-field">
                        <label>Final score</label>
                        <input type="text" value="{{ $appraisal->finalScore() !== null ? number_format($appraisal->finalScore(), 2) : 'Pending' }}" readonly>
                    </div>
                </div>
            </div>
        </div>

        <div class="uf-form-section">
            <div class="uf-section-head">Section I — Objectives &amp; SMART goals</div>
            <div class="uf-section-body">
                <p class="uf-hint">Objective 1 is always JD duties. Add role-specific SMART goals and link a corporate KPI where cascading applies. Weights must total 100%.</p>
                <p class="uf-hint">Current weight total: <strong>{{ $weightTotal }}%</strong></p>

                @foreach ($appraisal->goals as $goal)
                    <div class="tich-appraisal-goal">
                        <p class="uf-hint">
                            <strong>{{ $goal->sort_order }}. {{ $goal->title }}</strong>
                            · {{ number_format((float) $goal->weight, 1) }}%
                            · {{ str_replace('_', ' ', $goal->goal_type) }}
                            @if ($goal->corporateGoal)
                                · Cascaded from: {{ $goal->corporateGoal->title }}
                            @endif
                        </p>

                        @if ($isEmployee && $appraisal->canEmployeeEditGoals())
                            <form method="POST" action="{{ route('employee.appraisals.goals.update', [$appraisal, $goal]) }}">
                                @csrf
                                @method('PUT')
                                @include('employee.appraisals.partials.goal-fields', ['goal' => $goal, 'cascadingGoals' => $cascadingGoals])
                                <div class="uf-form-actions">
                                    <button type="submit" class="tich-btn tich-btn-primary tich-btn--sm">Save goal</button>
                                </div>
                            </form>
                            @unless ($goal->isJdDuties())
                                <form method="POST" action="{{ route('employee.appraisals.goals.destroy', [$appraisal, $goal]) }}" onsubmit="return confirm('Remove this goal?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="tich-btn tich-btn-ghost tich-btn--sm">Remove</button>
                                </form>
                            @endunless
                        @else
                            @if ($goal->smart_specific)
                                <p class="uf-hint"><strong>S:</strong> {{ $goal->smart_specific }} · <strong>M:</strong> {{ $goal->smart_measurable }} · <strong>A:</strong> {{ $goal->smart_achievable }} · <strong>R:</strong> {{ $goal->smart_relevant }} · <strong>T:</strong> {{ $goal->smart_timebound }}</p>
                            @endif
                            @if ($goal->employee_achievement)
                                <p class="uf-hint"><strong>Achievements:</strong> {{ $goal->employee_achievement }}</p>
                            @endif
                            <p class="uf-hint">Self rating: {{ $goal->self_rating ?? '—' }} · Manager rating: {{ $goal->manager_rating ?? '—' }}</p>
                        @endif
                    </div>
                @endforeach

                @if ($isEmployee && $appraisal->canEmployeeEditGoals())
                    <details class="tich-appraisal-add-goal">
                        <summary>Add SMART goal</summary>
                        <form method="POST" action="{{ route('employee.appraisals.goals.store', $appraisal) }}">
                            @csrf
                            @include('employee.appraisals.partials.goal-fields', ['goal' => null, 'cascadingGoals' => $cascadingGoals])
                            <div class="uf-form-actions">
                                <button type="submit" class="tich-btn tich-btn-primary">Add goal</button>
                            </div>
                        </form>
                    </details>

                    <form method="POST" action="{{ route('employee.appraisals.goals.submit', $appraisal) }}">
                        @csrf
                        <div class="uf-form-actions">
                            <button type="submit" class="tich-btn tich-btn-primary">Submit goals to supervisor</button>
                        </div>
                    </form>
                @endif

                @if ($isManager && $appraisal->canManagerApproveGoals())
                    <div class="uf-form-actions">
                        <form method="POST" action="{{ route('employee.appraisals.team.goals.approve', $appraisal) }}">
                            @csrf
                            <input type="hidden" name="notes" value="">
                            <button type="submit" class="tich-btn tich-btn-primary">Approve goals</button>
                        </form>
                        <form method="POST" action="{{ route('employee.appraisals.team.goals.return', $appraisal) }}" class="tich-appraisal-return">
                            @csrf
                            <div class="uf-field">
                                <label for="return-reason">Return reason <span class="uf-req">*</span></label>
                                <input type="text" id="return-reason" name="reason" required placeholder="What should the employee change?">
                            </div>
                            <button type="submit" class="tich-btn tich-btn-ghost">Return</button>
                        </form>
                    </div>
                @endif
            </div>
        </div>

        @if ($isEmployee && $appraisal->canEmployeeSelfAssess())
            <form method="POST" action="{{ route('employee.appraisals.self.submit', $appraisal) }}">
                @csrf
                <div class="uf-form-section">
                    <div class="uf-section-head">Self-assessment</div>
                    <div class="uf-section-body">
                        @foreach ($appraisal->goals as $goal)
                            <div class="uf-field">
                                <label>{{ $goal->sort_order }}. Achievements — {{ $goal->title }} <span class="uf-req">*</span></label>
                                <textarea name="goals[{{ $goal->id }}][employee_achievement]" rows="3" required>{{ old('goals.'.$goal->id.'.employee_achievement', $goal->employee_achievement) }}</textarea>
                            </div>
                            <div class="uf-field">
                                <label>Self rating (1–5) <span class="uf-req">*</span></label>
                                <select name="goals[{{ $goal->id }}][self_rating]" required>
                                    <option value="">Select</option>
                                    @foreach ($ratingScale as $score => $meta)
                                        <option value="{{ $score }}" @selected((int) old('goals.'.$goal->id.'.self_rating', $goal->self_rating) === $score)>{{ $score }} — {{ $meta['short'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach

                        <p class="uf-hint"><strong>Competencies</strong></p>
                        @foreach ($appraisal->competencies->groupBy('category') as $category => $items)
                            <p class="uf-hint">{{ ucfirst($category) }}</p>
                            @foreach ($items as $comp)
                                <div class="uf-form-grid-2">
                                    <div class="uf-field">
                                        @if ($category === 'functional')
                                            <label>
                                                <input type="hidden" name="competencies[{{ $comp->id }}][is_applicable]" value="0">
                                                <input type="checkbox" name="competencies[{{ $comp->id }}][is_applicable]" value="1" @checked(old('competencies.'.$comp->id.'.is_applicable', $comp->is_applicable))>
                                                {{ $comp->competency_label }}
                                            </label>
                                        @else
                                            <label>{{ $comp->competency_label }}@if (! $comp->is_applicable) <span class="uf-hint">(not applicable)</span>@endif</label>
                                        @endif
                                    </div>
                                    @if ($comp->is_applicable || $category === 'functional')
                                        <div class="uf-field">
                                            <label>Rating</label>
                                            <select name="competencies[{{ $comp->id }}][self_rating]">
                                                <option value="">Select</option>
                                                @foreach ($ratingScale as $score => $meta)
                                                    <option value="{{ $score }}" @selected((int) old('competencies.'.$comp->id.'.self_rating', $comp->self_rating) === $score)>{{ $score }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        @endforeach

                        <div class="uf-field">
                            <label>Overall comments</label>
                            <textarea name="employee_self_comments" rows="3">{{ old('employee_self_comments', $appraisal->employee_self_comments) }}</textarea>
                        </div>

                        <div class="uf-form-actions">
                            <button type="submit" formaction="{{ route('employee.appraisals.self.save', $appraisal) }}" class="tich-btn tich-btn-ghost">Save draft</button>
                            <button type="submit" class="tich-btn tich-btn-primary">Submit to manager</button>
                        </div>
                    </div>
                </div>
            </form>
        @endif

        @if ($isManager && $appraisal->canManagerReview())
            <form method="POST" action="{{ route('employee.appraisals.team.review.submit', $appraisal) }}">
                @csrf
                <div class="uf-form-section">
                    <div class="uf-section-head">Manager review</div>
                    <div class="uf-section-body">
                        <p class="uf-hint">Rate objectives and competencies (1–5). Justify ratings of 1 or 5.</p>

                        @foreach ($appraisal->goals as $goal)
                            <p class="uf-hint"><strong>{{ $goal->sort_order }}. {{ $goal->title }}</strong> — Employee: {{ $goal->employee_achievement }} (self {{ $goal->self_rating }})</p>
                            <div class="uf-form-grid-2">
                                <div class="uf-field">
                                    <label>Manager rating <span class="uf-req">*</span></label>
                                    <select name="goals[{{ $goal->id }}][manager_rating]" required>
                                        <option value="">Select</option>
                                        @foreach ($ratingScale as $score => $meta)
                                            <option value="{{ $score }}" @selected((int) old('goals.'.$goal->id.'.manager_rating', $goal->manager_rating) === $score)>{{ $score }} — {{ $meta['short'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="uf-field">
                                    <label>Comments</label>
                                    <textarea name="goals[{{ $goal->id }}][manager_comments]" rows="2">{{ old('goals.'.$goal->id.'.manager_comments', $goal->manager_comments) }}</textarea>
                                </div>
                            </div>
                        @endforeach

                        <p class="uf-hint"><strong>Competencies</strong></p>
                        @foreach ($appraisal->competencies as $comp)
                            <div class="uf-form-grid-3">
                                <div class="uf-field">
                                    @if ($comp->category === 'functional')
                                        <label>
                                            <input type="hidden" name="competencies[{{ $comp->id }}][is_applicable]" value="0">
                                            <input type="checkbox" name="competencies[{{ $comp->id }}][is_applicable]" value="1" @checked($comp->is_applicable)>
                                            {{ $comp->competency_label }}
                                        </label>
                                    @else
                                        <label>{{ $comp->competency_label }}@unless($comp->is_applicable) (N/A)@endunless</label>
                                    @endif
                                    <span class="uf-hint">Self: {{ $comp->self_rating ?? '—' }}</span>
                                </div>
                                @if ($comp->is_applicable || $comp->category === 'functional')
                                    <div class="uf-field">
                                        <label>Rating</label>
                                        <select name="competencies[{{ $comp->id }}][manager_rating]">
                                            <option value="">Select</option>
                                            @foreach ($ratingScale as $score => $meta)
                                                <option value="{{ $score }}" @selected((int) $comp->manager_rating === $score)>{{ $score }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="uf-field">
                                        <label>Comment</label>
                                        <input type="text" name="competencies[{{ $comp->id }}][comments]" value="{{ $comp->comments }}">
                                    </div>
                                @endif
                            </div>
                        @endforeach

                        <div class="uf-field">
                            <label>Comments on objectives</label>
                            <textarea name="manager_objectives_comments" rows="2">{{ old('manager_objectives_comments', $appraisal->manager_objectives_comments) }}</textarea>
                        </div>
                        <div class="uf-field">
                            <label>Comments on competencies</label>
                            <textarea name="manager_competencies_comments" rows="2">{{ old('manager_competencies_comments', $appraisal->manager_competencies_comments) }}</textarea>
                        </div>
                        <div class="uf-field">
                            <label>Strengths</label>
                            <textarea name="strengths" rows="2">{{ old('strengths', $appraisal->strengths) }}</textarea>
                        </div>
                        <div class="uf-field">
                            <label>Development areas</label>
                            <textarea name="development_areas" rows="2">{{ old('development_areas', $appraisal->development_areas) }}</textarea>
                        </div>
                        <div class="uf-field">
                            <label>Training recommendations</label>
                            <textarea name="training_recommendations" rows="2">{{ old('training_recommendations', $appraisal->training_recommendations) }}</textarea>
                        </div>

                        <div class="uf-form-actions">
                            <button type="submit" formaction="{{ route('employee.appraisals.team.review.save', $appraisal) }}" class="tich-btn tich-btn-ghost">Save draft</button>
                            <button type="submit" class="tich-btn tich-btn-primary">Submit to HR</button>
                        </div>
                    </div>
                </div>
            </form>
        @endif

        @if ($appraisal->status === 'completed')
            <div class="uf-form-section">
                <div class="uf-section-head">Completed</div>
                <div class="uf-section-body">
                    <p class="uf-hint">
                        Final score: <strong>{{ number_format($appraisal->finalScore() ?? 0, 2) }}</strong>
                        ({{ str_replace('_', ' ', $appraisal->overall_rating ?? '') }}).
                        This record remains in your history after role or department changes.
                    </p>
                </div>
            </div>
        @endif
        </div>
    </div>
@endsection
