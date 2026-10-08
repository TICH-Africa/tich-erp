@php
    /** @var \App\Models\HrAppraisalGoal|null $goal */
    $isJd = $goal?->isJdDuties() ?? false;
@endphp

<div class="uf-form-grid-2">
    <div class="uf-field" style="grid-column:1/-1;">
        <label for="goal-title-{{ $goal->id ?? 'new' }}">Goal title <span class="uf-req">*</span></label>
        <input
            type="text"
            id="goal-title-{{ $goal->id ?? 'new' }}"
            name="title"
            value="{{ old('title', $goal->title ?? '') }}"
            required
            @if($isJd) readonly @endif
        >
    </div>
    <div class="uf-field" style="grid-column:1/-1;">
        <label for="goal-description-{{ $goal->id ?? 'new' }}">Description / expected outcomes</label>
        <textarea id="goal-description-{{ $goal->id ?? 'new' }}" name="description" rows="2">{{ old('description', $goal->description ?? '') }}</textarea>
    </div>
    @unless ($isJd)
        <div class="uf-field" style="grid-column:1/-1;">
            <label for="goal-corporate-{{ $goal->id ?? 'new' }}">Link corporate / cascading KPI (optional)</label>
            <select id="goal-corporate-{{ $goal->id ?? 'new' }}" name="corporate_goal_id">
                <option value="">None — free-text SMART goal</option>
                @foreach ($cascadingGoals as $cg)
                    <option value="{{ $cg->id }}" @selected((int) old('corporate_goal_id', $goal->corporate_goal_id ?? 0) === $cg->id)>
                        {{ $cg->code ? $cg->code.' · ' : '' }}{{ $cg->title }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="uf-field">
            <label>Specific <span class="uf-req">*</span></label>
            <textarea name="smart_specific" rows="2" required>{{ old('smart_specific', $goal->smart_specific ?? '') }}</textarea>
        </div>
        <div class="uf-field">
            <label>Measurable <span class="uf-req">*</span></label>
            <textarea name="smart_measurable" rows="2" required>{{ old('smart_measurable', $goal->smart_measurable ?? '') }}</textarea>
        </div>
        <div class="uf-field">
            <label>Achievable <span class="uf-req">*</span></label>
            <textarea name="smart_achievable" rows="2" required>{{ old('smart_achievable', $goal->smart_achievable ?? '') }}</textarea>
        </div>
        <div class="uf-field">
            <label>Relevant <span class="uf-req">*</span></label>
            <textarea name="smart_relevant" rows="2" required>{{ old('smart_relevant', $goal->smart_relevant ?? '') }}</textarea>
        </div>
        <div class="uf-field">
            <label>Time-bound <span class="uf-req">*</span></label>
            <textarea name="smart_timebound" rows="2" required>{{ old('smart_timebound', $goal->smart_timebound ?? '') }}</textarea>
        </div>
        <div class="uf-field">
            <label>Target date</label>
            <input type="date" name="target_date" value="{{ old('target_date', optional($goal->target_date ?? null)->format('Y-m-d')) }}">
        </div>
    @endunless
    <div class="uf-field">
        <label>Weight % <span class="uf-req">*</span></label>
        <input type="number" step="0.01" min="0" max="100" name="weight" value="{{ old('weight', $goal->weight ?? 20) }}" required>
    </div>
</div>
