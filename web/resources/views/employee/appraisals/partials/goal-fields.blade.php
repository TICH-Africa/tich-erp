@php
    /** @var \App\Models\HrAppraisalGoal|null $goal */
    $isJd = $goal?->isJdDuties() ?? false;
@endphp

<div class="tich-grid tich-grid--2" style="gap:0.75rem;">
    <div class="tich-form-group" style="grid-column:1/-1;">
        <label class="tich-label">Goal title</label>
        <input type="text" name="title" class="tich-input" value="{{ old('title', $goal->title ?? '') }}" required @if($isJd) readonly @endif>
    </div>
    <div class="tich-form-group" style="grid-column:1/-1;">
        <label class="tich-label">Description / expected outcomes</label>
        <textarea name="description" class="tich-input" rows="2">{{ old('description', $goal->description ?? '') }}</textarea>
    </div>
    @unless ($isJd)
        <div class="tich-form-group" style="grid-column:1/-1;">
            <label class="tich-label">Link corporate / cascading KPI (optional)</label>
            <select name="corporate_goal_id" class="tich-input">
                <option value="">None — free-text SMART goal</option>
                @foreach ($cascadingGoals as $cg)
                    <option value="{{ $cg->id }}" @selected((int) old('corporate_goal_id', $goal->corporate_goal_id ?? 0) === $cg->id)>
                        {{ $cg->code ? $cg->code.' · ' : '' }}{{ $cg->title }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="tich-form-group">
            <label class="tich-label">Specific</label>
            <textarea name="smart_specific" class="tich-input" rows="2" required>{{ old('smart_specific', $goal->smart_specific ?? '') }}</textarea>
        </div>
        <div class="tich-form-group">
            <label class="tich-label">Measurable</label>
            <textarea name="smart_measurable" class="tich-input" rows="2" required>{{ old('smart_measurable', $goal->smart_measurable ?? '') }}</textarea>
        </div>
        <div class="tich-form-group">
            <label class="tich-label">Achievable</label>
            <textarea name="smart_achievable" class="tich-input" rows="2" required>{{ old('smart_achievable', $goal->smart_achievable ?? '') }}</textarea>
        </div>
        <div class="tich-form-group">
            <label class="tich-label">Relevant</label>
            <textarea name="smart_relevant" class="tich-input" rows="2" required>{{ old('smart_relevant', $goal->smart_relevant ?? '') }}</textarea>
        </div>
        <div class="tich-form-group">
            <label class="tich-label">Time-bound</label>
            <textarea name="smart_timebound" class="tich-input" rows="2" required>{{ old('smart_timebound', $goal->smart_timebound ?? '') }}</textarea>
        </div>
        <div class="tich-form-group">
            <label class="tich-label">Target date</label>
            <input type="date" name="target_date" class="tich-input" value="{{ old('target_date', optional($goal->target_date ?? null)->format('Y-m-d')) }}">
        </div>
    @endunless
    <div class="tich-form-group">
        <label class="tich-label">Weight %</label>
        <input type="number" step="0.01" min="0" max="100" name="weight" class="tich-input" value="{{ old('weight', $goal->weight ?? 20) }}" required>
    </div>
</div>
