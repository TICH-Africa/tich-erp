@extends('layouts.employee')

@section('title', ($mode === 'manager' ? 'Team appraisal' : 'My appraisal').' '.$appraisal->appraisal_number)

@section('employee-content')
    @php
        $isManager = $mode === 'manager';
        $isEmployee = $mode === 'employee';
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

    <div class="tich-card tich-mt-6">
        <div class="tich-grid tich-grid--2">
            <p><strong>Job title:</strong> {{ $appraisal->job_title_snapshot ?: '—' }}</p>
            <p><strong>Manager:</strong> {{ $appraisal->lineManager?->fullName() ?? '—' }}</p>
            <p><strong>Period:</strong> {{ $appraisal->cycle?->period_start?->format('d M Y') }} – {{ $appraisal->cycle?->period_end?->format('d M Y') }}</p>
            <p><strong>Final score:</strong> {{ $appraisal->finalScore() !== null ? number_format($appraisal->finalScore(), 2) : 'Pending' }}</p>
        </div>
    </div>

    {{-- Goals --}}
    <div class="tich-card tich-mt-6">
        <h2 class="tich-h3">Section I – Objectives &amp; SMART goals</h2>
        <p class="tich-caption">Objective 1 is always JD duties. Additional goals are role-specific SMART objectives; link a corporate KPI where cascading applies. Weights must total 100%.</p>

        @php $weightTotal = round((float) $appraisal->goals->sum('weight'), 2); @endphp
        <p class="tich-caption tich-mt-2">Current weight total: <strong>{{ $weightTotal }}%</strong></p>

        @foreach ($appraisal->goals as $goal)
            <div class="tich-mt-4" style="padding:0.75rem; border:1px solid var(--tich-neutral-border); border-radius:0.5rem;">
                <strong>{{ $goal->sort_order }}. {{ $goal->title }}</strong>
                <span class="tich-caption"> · {{ number_format((float) $goal->weight, 1) }}% · {{ str_replace('_', ' ', $goal->goal_type) }}</span>
                @if ($goal->corporateGoal)
                    <div class="tich-caption">Cascaded from: {{ $goal->corporateGoal->title }}</div>
                @endif

                @if ($isEmployee && $appraisal->canEmployeeEditGoals())
                    <form method="POST" action="{{ route('employee.appraisals.goals.update', [$appraisal, $goal]) }}" class="tich-mt-3">
                        @csrf
                        @method('PUT')
                        @include('employee.appraisals.partials.goal-fields', ['goal' => $goal, 'cascadingGoals' => $cascadingGoals])
                        <div style="display:flex; gap:0.5rem; margin-top:0.75rem;">
                            <button type="submit" class="tich-btn tich-btn-primary tich-btn--sm">Save goal</button>
                        </div>
                    </form>
                    @unless ($goal->isJdDuties())
                        <form method="POST" action="{{ route('employee.appraisals.goals.destroy', [$appraisal, $goal]) }}" class="tich-mt-2" onsubmit="return confirm('Remove this goal?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="tich-btn tich-btn-ghost tich-btn--sm">Remove</button>
                        </form>
                    @endunless
                @else
                    @if ($goal->smart_specific)
                        <p class="tich-caption tich-mt-2"><strong>S:</strong> {{ $goal->smart_specific }} · <strong>M:</strong> {{ $goal->smart_measurable }} · <strong>A:</strong> {{ $goal->smart_achievable }} · <strong>R:</strong> {{ $goal->smart_relevant }} · <strong>T:</strong> {{ $goal->smart_timebound }}</p>
                    @endif
                    @if ($goal->employee_achievement)
                        <p class="tich-mt-2"><strong>Achievements:</strong> {{ $goal->employee_achievement }}</p>
                    @endif
                    <p class="tich-caption">Self rating: {{ $goal->self_rating ?? '—' }} · Manager rating: {{ $goal->manager_rating ?? '—' }}</p>
                @endif
            </div>
        @endforeach

        @if ($isEmployee && $appraisal->canEmployeeEditGoals())
            <details class="tich-mt-4">
                <summary class="tich-btn tich-btn-ghost">Add SMART goal</summary>
                <form method="POST" action="{{ route('employee.appraisals.goals.store', $appraisal) }}" class="tich-mt-3">
                    @csrf
                    @include('employee.appraisals.partials.goal-fields', ['goal' => null, 'cascadingGoals' => $cascadingGoals])
                    <button type="submit" class="tich-btn tich-btn-primary tich-mt-3">Add goal</button>
                </form>
            </details>

            <form method="POST" action="{{ route('employee.appraisals.goals.submit', $appraisal) }}" class="tich-mt-4">
                @csrf
                <button type="submit" class="tich-btn tich-btn-primary">Submit goals to supervisor</button>
            </form>
        @endif

        @if ($isManager && $appraisal->canManagerApproveGoals())
            <div class="tich-mt-4" style="display:flex; flex-wrap:wrap; gap:0.75rem;">
                <form method="POST" action="{{ route('employee.appraisals.team.goals.approve', $appraisal) }}">
                    @csrf
                    <input type="hidden" name="notes" value="">
                    <button type="submit" class="tich-btn tich-btn-primary">Approve goals</button>
                </form>
                <form method="POST" action="{{ route('employee.appraisals.team.goals.return', $appraisal) }}" style="display:flex; gap:0.5rem; align-items:end;">
                    @csrf
                    <input type="text" name="reason" class="tich-input" placeholder="Return reason" required>
                    <button type="submit" class="tich-btn tich-btn-ghost">Return</button>
                </form>
            </div>
        @endif
    </div>

    {{-- Self-assessment --}}
    @if ($isEmployee && $appraisal->canEmployeeSelfAssess())
        <form method="POST" action="{{ route('employee.appraisals.self.submit', $appraisal) }}" class="tich-card tich-mt-6">
            @csrf
            <h2 class="tich-h3">Self-assessment</h2>
            @foreach ($appraisal->goals as $goal)
                <div class="tich-form-group tich-mt-3">
                    <label class="tich-label">{{ $goal->sort_order }}. Achievements — {{ $goal->title }}</label>
                    <textarea name="goals[{{ $goal->id }}][employee_achievement]" class="tich-input" rows="3" required>{{ old('goals.'.$goal->id.'.employee_achievement', $goal->employee_achievement) }}</textarea>
                    <label class="tich-label tich-mt-2">Self rating (1–5)</label>
                    <select name="goals[{{ $goal->id }}][self_rating]" class="tich-input" required>
                        <option value="">Select</option>
                        @foreach ($ratingScale as $score => $meta)
                            <option value="{{ $score }}" @selected((int) old('goals.'.$goal->id.'.self_rating', $goal->self_rating) === $score)>{{ $score }} — {{ $meta['short'] }}</option>
                        @endforeach
                    </select>
                </div>
            @endforeach

            <h3 class="tich-h3 tich-mt-4">Competencies</h3>
            @foreach ($appraisal->competencies->groupBy('category') as $category => $items)
                <p class="tich-caption tich-mt-3"><strong>{{ ucfirst($category) }}</strong></p>
                @foreach ($items as $comp)
                    <div class="tich-grid tich-grid--2 tich-mt-2" style="align-items:end;">
                        <div>
                            @if ($category === 'functional')
                                <label class="tich-caption" style="display:flex; gap:0.4rem; align-items:center;">
                                    <input type="hidden" name="competencies[{{ $comp->id }}][is_applicable]" value="0">
                                    <input type="checkbox" name="competencies[{{ $comp->id }}][is_applicable]" value="1" @checked(old('competencies.'.$comp->id.'.is_applicable', $comp->is_applicable))>
                                    {{ $comp->competency_label }}
                                </label>
                            @else
                                <span class="tich-caption">{{ $comp->competency_label }}</span>
                                @if (! $comp->is_applicable)
                                    <span class="tich-caption">(not applicable for your role)</span>
                                @endif
                            @endif
                        </div>
                        @if ($comp->is_applicable || $category === 'functional')
                            <select name="competencies[{{ $comp->id }}][self_rating]" class="tich-input">
                                <option value="">Rating</option>
                                @foreach ($ratingScale as $score => $meta)
                                    <option value="{{ $score }}" @selected((int) old('competencies.'.$comp->id.'.self_rating', $comp->self_rating) === $score)>{{ $score }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                @endforeach
            @endforeach

            <div class="tich-form-group tich-mt-4">
                <label class="tich-label">Overall comments</label>
                <textarea name="employee_self_comments" class="tich-input" rows="3">{{ old('employee_self_comments', $appraisal->employee_self_comments) }}</textarea>
            </div>

            <div style="display:flex; gap:0.5rem;">
                <button type="submit" formaction="{{ route('employee.appraisals.self.save', $appraisal) }}" class="tich-btn tich-btn-ghost">Save draft</button>
                <button type="submit" class="tich-btn tich-btn-primary">Submit to manager</button>
            </div>
        </form>
    @endif

    {{-- Manager review --}}
    @if ($isManager && $appraisal->canManagerReview())
        <form method="POST" action="{{ route('employee.appraisals.team.review.submit', $appraisal) }}" class="tich-card tich-mt-6">
            @csrf
            <h2 class="tich-h3">Manager review</h2>
            <p class="tich-caption">Rate objectives and competencies (1–5). Justify ratings of 1 or 5.</p>

            @foreach ($appraisal->goals as $goal)
                <div class="tich-mt-4">
                    <strong>{{ $goal->sort_order }}. {{ $goal->title }}</strong>
                    <p class="tich-caption">Employee: {{ $goal->employee_achievement }} (self {{ $goal->self_rating }})</p>
                    <div class="tich-grid tich-grid--2 tich-mt-2">
                        <div class="tich-form-group">
                            <label class="tich-label">Manager rating</label>
                            <select name="goals[{{ $goal->id }}][manager_rating]" class="tich-input" required>
                                <option value="">Select</option>
                                @foreach ($ratingScale as $score => $meta)
                                    <option value="{{ $score }}" @selected((int) old('goals.'.$goal->id.'.manager_rating', $goal->manager_rating) === $score)>{{ $score }} — {{ $meta['short'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="tich-form-group">
                            <label class="tich-label">Comments</label>
                            <textarea name="goals[{{ $goal->id }}][manager_comments]" class="tich-input" rows="2">{{ old('goals.'.$goal->id.'.manager_comments', $goal->manager_comments) }}</textarea>
                        </div>
                    </div>
                </div>
            @endforeach

            <h3 class="tich-h3 tich-mt-4">Competencies</h3>
            @foreach ($appraisal->competencies as $comp)
                <div class="tich-grid tich-grid--3 tich-mt-2" style="align-items:end;">
                    <div>
                        @if ($comp->category === 'functional')
                            <label class="tich-caption" style="display:flex; gap:0.4rem;">
                                <input type="hidden" name="competencies[{{ $comp->id }}][is_applicable]" value="0">
                                <input type="checkbox" name="competencies[{{ $comp->id }}][is_applicable]" value="1" @checked($comp->is_applicable)>
                                {{ $comp->competency_label }}
                            </label>
                        @else
                            <span class="tich-caption">{{ $comp->competency_label }} @unless($comp->is_applicable) (N/A) @endunless</span>
                        @endif
                        <div class="tich-caption">Self: {{ $comp->self_rating ?? '—' }}</div>
                    </div>
                    @if ($comp->is_applicable || $comp->category === 'functional')
                        <select name="competencies[{{ $comp->id }}][manager_rating]" class="tich-input">
                            <option value="">Rating</option>
                            @foreach ($ratingScale as $score => $meta)
                                <option value="{{ $score }}" @selected((int) $comp->manager_rating === $score)>{{ $score }}</option>
                            @endforeach
                        </select>
                        <input type="text" name="competencies[{{ $comp->id }}][comments]" class="tich-input" value="{{ $comp->comments }}" placeholder="Comment">
                    @endif
                </div>
            @endforeach

            <div class="tich-form-group tich-mt-4">
                <label class="tich-label">Comments on objectives</label>
                <textarea name="manager_objectives_comments" class="tich-input" rows="2">{{ old('manager_objectives_comments', $appraisal->manager_objectives_comments) }}</textarea>
            </div>
            <div class="tich-form-group">
                <label class="tich-label">Comments on competencies</label>
                <textarea name="manager_competencies_comments" class="tich-input" rows="2">{{ old('manager_competencies_comments', $appraisal->manager_competencies_comments) }}</textarea>
            </div>
            <div class="tich-form-group">
                <label class="tich-label">Strengths</label>
                <textarea name="strengths" class="tich-input" rows="2">{{ old('strengths', $appraisal->strengths) }}</textarea>
            </div>
            <div class="tich-form-group">
                <label class="tich-label">Development areas</label>
                <textarea name="development_areas" class="tich-input" rows="2">{{ old('development_areas', $appraisal->development_areas) }}</textarea>
            </div>
            <div class="tich-form-group">
                <label class="tich-label">Training recommendations</label>
                <textarea name="training_recommendations" class="tich-input" rows="2">{{ old('training_recommendations', $appraisal->training_recommendations) }}</textarea>
            </div>

            <div style="display:flex; gap:0.5rem;">
                <button type="submit" formaction="{{ route('employee.appraisals.team.review.save', $appraisal) }}" class="tich-btn tich-btn-ghost">Save draft</button>
                <button type="submit" class="tich-btn tich-btn-primary">Submit to HR</button>
            </div>
        </form>
    @endif

    @if ($appraisal->status === 'completed')
        <div class="tich-alert tich-alert--success tich-mt-6">
            Completed. Final score: {{ number_format($appraisal->finalScore() ?? 0, 2) }}
            ({{ str_replace('_', ' ', $appraisal->overall_rating ?? '') }}).
            This record remains in your history after role or department changes.
        </div>
    @endif
@endsection
