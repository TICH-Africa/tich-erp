@extends('layouts.hr')

@section('title', 'Appraisal '.$appraisal->appraisal_number)

@section('hr-content')
    <x-page-toolbar title="{{ $appraisal->staff?->fullName() }}" meta="{{ $appraisal->appraisal_number }} · {{ $appraisal->cycle?->label() }} · {{ $statuses[$appraisal->status] ?? $appraisal->status }}">
        <a href="{{ route('hr.appraisals.index') }}" class="tich-btn tich-btn-ghost">Back</a>
    </x-page-toolbar>

    @if (session('status'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="tich-alert tich-alert--danger tich-mt-4">{{ $errors->first() }}</div>
    @endif

    <div class="tich-grid tich-grid--3 tich-mt-6">
        <div class="tich-stat"><p class="tich-stat__label">Objectives</p><p class="tich-stat__value">{{ $appraisal->objectives_score !== null ? number_format($appraisal->objectives_score, 2) : '—' }}</p></div>
        <div class="tich-stat"><p class="tich-stat__label">Competencies</p><p class="tich-stat__value">{{ $appraisal->competencies_score !== null ? number_format($appraisal->competencies_score, 2) : '—' }}</p></div>
        <div class="tich-stat"><p class="tich-stat__label">Final score</p><p class="tich-stat__value">{{ $appraisal->finalScore() !== null ? number_format($appraisal->finalScore(), 2) : '—' }}</p></div>
    </div>

    <div class="tich-card tich-mt-6">
        <h2 class="tich-h3">Record header</h2>
        <div class="tich-grid tich-grid--2 tich-mt-3">
            <p><strong>Job title:</strong> {{ $appraisal->job_title_snapshot ?: '—' }}</p>
            <p><strong>Department:</strong> {{ $appraisal->staff?->department?->dept_name ?? '—' }}</p>
            <p><strong>Immediate manager:</strong> {{ $appraisal->lineManager?->fullName() ?? '—' }}</p>
            <p><strong>Period:</strong> {{ $appraisal->cycle?->period_start?->format('d M Y') }} – {{ $appraisal->cycle?->period_end?->format('d M Y') }}</p>
        </div>
        @if ($appraisal->job_description_snapshot)
            <p class="tich-caption tich-mt-3"><strong>JD snapshot:</strong> {{ \Illuminate\Support\Str::limit($appraisal->job_description_snapshot, 400) }}</p>
        @endif
    </div>

    <div class="tich-card tich-mt-6">
        <h2 class="tich-h3">Section I – Objectives</h2>
        @foreach ($appraisal->goals as $goal)
            <div class="tich-mt-4" style="padding-bottom:0.75rem; border-bottom:1px solid var(--tich-neutral-border);">
                <strong>{{ $goal->sort_order }}. {{ $goal->title }}</strong>
                <span class="tich-caption"> · {{ ucfirst(str_replace('_', ' ', $goal->goal_type)) }} · Weight {{ number_format((float) $goal->weight, 1) }}%</span>
                @if ($goal->employee_achievement)
                    <p class="tich-mt-2"><strong>Achievements:</strong> {{ $goal->employee_achievement }}</p>
                @endif
                <p class="tich-caption tich-mt-1">Self: {{ $goal->self_rating ?? '—' }} · Manager: {{ $goal->manager_rating ?? '—' }}</p>
                @if ($goal->manager_comments)
                    <p class="tich-caption">{{ $goal->manager_comments }}</p>
                @endif
            </div>
        @endforeach
        @if ($appraisal->manager_objectives_comments)
            <p class="tich-mt-3"><strong>Manager comments (objectives):</strong> {{ $appraisal->manager_objectives_comments }}</p>
        @endif
    </div>

    <div class="tich-card tich-mt-6">
        <h2 class="tich-h3">Section II – Competencies</h2>
        @foreach ($appraisal->competencies->groupBy('category') as $category => $items)
            <h3 class="tich-h3 tich-mt-4" style="font-size:1rem;">{{ ucfirst($category) }}</h3>
            @foreach ($items as $comp)
                @continue(! $comp->is_applicable && $comp->self_rating === null && $comp->manager_rating === null)
                <div class="tich-caption tich-mt-2">
                    {{ $comp->competency_label }}
                    — Self: {{ $comp->self_rating ?? '—' }} · Manager: {{ $comp->manager_rating ?? '—' }}
                    @unless ($comp->is_applicable) <em>(not applicable)</em> @endunless
                </div>
            @endforeach
        @endforeach
        @if ($appraisal->manager_competencies_comments)
            <p class="tich-mt-3"><strong>Manager comments (competencies):</strong> {{ $appraisal->manager_competencies_comments }}</p>
        @endif
    </div>

    @if ($appraisal->strengths || $appraisal->development_areas)
        <div class="tich-card tich-mt-6">
            <p><strong>Strengths:</strong> {{ $appraisal->strengths ?: '—' }}</p>
            <p class="tich-mt-2"><strong>Development areas:</strong> {{ $appraisal->development_areas ?: '—' }}</p>
            <p class="tich-mt-2"><strong>Training recommendations:</strong> {{ $appraisal->training_recommendations ?: '—' }}</p>
        </div>
    @endif

    @if (in_array($appraisal->status, ['pending_calibration', 'pending_hr', 'manager_review'], true) || $appraisal->cycle?->status === 'calibration')
        <div class="tich-card tich-mt-6">
            <h2 class="tich-h3">Calibration</h2>
            <p class="tich-caption">Managers review scores together with HR to avoid grade inflation. Adjust the final score if needed.</p>
            <form method="POST" action="{{ route('hr.appraisals.calibrate', $appraisal) }}" class="tich-mt-3">
                @csrf
                <div class="tich-grid tich-grid--2" style="gap:0.75rem;">
                    <div class="tich-form-group">
                        <label class="tich-label">Calibrated score (1–5)</label>
                        <input type="number" step="0.01" min="1" max="5" name="calibrated_score" class="tich-input" value="{{ old('calibrated_score', $appraisal->calibrated_score ?? $appraisal->overall_score) }}" required>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Reason</label>
                        <textarea name="calibration_reason" class="tich-input" rows="2" required>{{ old('calibration_reason', $appraisal->calibration_reason) }}</textarea>
                    </div>
                </div>
                <button type="submit" class="tich-btn tich-btn-ghost tich-mt-3">Save calibrated score</button>
            </form>
        </div>
    @endif

    @if (in_array($appraisal->status, ['pending_hr', 'pending_calibration'], true) && $appraisal->manager_submitted_at)
        <div class="tich-card tich-mt-6">
            <h2 class="tich-h3">HR sign-off</h2>
            <form method="POST" action="{{ route('hr.appraisals.sign-off', $appraisal) }}">
                @csrf
                <div class="tich-form-group">
                    <label class="tich-label">HR comments</label>
                    <textarea name="hr_comments" class="tich-input" rows="3">{{ old('hr_comments', $appraisal->hr_comments) }}</textarea>
                </div>
                <label class="tich-caption" style="display:flex; gap:0.5rem; align-items:center;">
                    <input type="checkbox" name="staff_agrees" value="1" @checked(old('staff_agrees', $appraisal->staff_agrees))>
                    Staff agrees with the review (as confirmed)
                </label>
                <button type="submit" class="tich-btn tich-btn-primary tich-mt-4">Complete &amp; archive to record</button>
            </form>
        </div>
    @endif

    @if ($appraisal->status === 'completed')
        <div class="tich-alert tich-alert--success tich-mt-6">
            Completed {{ $appraisal->completed_at?->format('d M Y H:i') }}
            @if ($appraisal->hrSignedBy) by {{ $appraisal->hrSignedBy->fullName() }} @endif.
            Historical record remains on file after transfers or resignations.
        </div>
    @endif

    <div class="tich-card tich-mt-6">
        <h3 class="tich-h3">Rating scale (1–5)</h3>
        <ul class="tich-caption tich-mt-2">
            @foreach ($ratingScale as $score => $meta)
                <li><strong>{{ $score }}</strong> — {{ $meta['label'] }}</li>
            @endforeach
        </ul>
    </div>
@endsection
