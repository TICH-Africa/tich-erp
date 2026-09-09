@php
    $isEdit = isset($plan);
    $selectedDepartments = old('department_ids', $isEdit ? $plan->targetDepartmentIds() : []);
    $items = old('items');
    if (! is_array($items)) {
        $items = $isEdit
            ? $plan->checklists->map(fn ($item) => [
                'text' => $item->checklist_item_text,
                'category' => $item->item_category,
                'weight' => $item->weight,
                'max_score' => $item->max_score,
                'requires_evidence' => $item->requires_evidence ? 1 : 0,
            ])->values()->all()
            : [['text' => '', 'category' => '', 'weight' => 1, 'max_score' => 100, 'requires_evidence' => 1]];
    }
@endphp

<form
    method="POST"
    action="{{ $isEdit ? route('qa.assessments.update', $plan) : route('qa.assessments.store') }}"
    class="qa-form-page tich-mt-8"
    id="qa-assessment-form"
>
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <section class="qa-form-section">
        <header class="qa-form-section__head">
            <h2 class="tich-h3">Sheet details</h2>
            <p class="tich-caption tich-mt-2">Name the sheet and set the review window before choosing who responds.</p>
        </header>

        <div class="qa-form-fields">
            <div class="tich-form-group">
                <label class="tich-label" for="plan_name">Title</label>
                <input id="plan_name" name="plan_name" class="tich-input" required maxlength="300" value="{{ old('plan_name', $plan->plan_name ?? '') }}">
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="description">Description</label>
                <textarea id="description" name="description" class="tich-input" rows="3" placeholder="Optional summary of what this sheet covers">{{ old('description', $plan->description ?? '') }}</textarea>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="instructions">Instructions for departments</label>
                <textarea id="instructions" name="instructions" class="tich-input" rows="4" placeholder="Guidance shown to departments when they open the sheet">{{ old('instructions', $plan->instructions ?? '') }}</textarea>
            </div>
        </div>

        <div class="qa-form-fields qa-form-fields--schedule tich-mt-6">
            <div class="tich-form-group">
                <label class="tich-label" for="period_start">Period start</label>
                <input id="period_start" type="date" name="period_start" class="tich-input" required value="{{ old('period_start', isset($plan) ? $plan->period_start?->format('Y-m-d') : '') }}">
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="period_end">Period end</label>
                <input id="period_end" type="date" name="period_end" class="tich-input" required value="{{ old('period_end', isset($plan) ? $plan->period_end?->format('Y-m-d') : '') }}">
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="due_at">Response due</label>
                <input id="due_at" type="datetime-local" name="due_at" class="tich-input" value="{{ old('due_at', isset($plan) && $plan->due_at ? $plan->due_at->format('Y-m-d\\TH:i') : '') }}">
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="pass_threshold">Pass threshold (%)</label>
                <input id="pass_threshold" type="number" step="0.01" min="0" max="100" name="pass_threshold" class="tich-input" value="{{ old('pass_threshold', $plan->pass_threshold ?? 70) }}">
            </div>
        </div>
    </section>

    <section class="qa-form-section">
        <header class="qa-form-section__head">
            <h2 class="tich-h3">Target departments</h2>
            <p class="tich-caption tich-mt-2">Choose which academic or administrative units must complete this sheet.</p>
        </header>

        <div class="qa-dept-picker">
            @foreach ($departments as $department)
                <label class="qa-dept-picker__item">
                    <input type="checkbox" name="department_ids[]" value="{{ $department->id }}" @checked(in_array($department->id, array_map('intval', (array) $selectedDepartments), true))>
                    <span>
                        <span class="qa-dept-picker__name">{{ $department->dept_name }}</span>
                        <span class="tich-caption">{{ $department->dept_code }}</span>
                    </span>
                </label>
            @endforeach
        </div>
        @error('department_ids')
            <p class="tich-field-error tich-mt-3">{{ $message }}</p>
        @enderror
    </section>

    <section class="qa-form-section">
        <header class="qa-form-section__head">
            <h2 class="tich-h3">Evaluation criteria</h2>
            <p class="tich-caption tich-mt-2">Each row becomes a scored metric on the department response sheet.</p>
        </header>

        <div id="qa-items" class="qa-criteria-list">
            @foreach ($items as $index => $item)
                <article class="qa-criterion qa-item-row">
                    <div class="qa-criterion__badge">Criterion {{ $index + 1 }}</div>
                    <div class="qa-criterion__fields">
                        <div class="tich-form-group qa-criterion__text">
                            <label class="tich-label">Criterion text</label>
                            <textarea name="items[{{ $index }}][text]" class="tich-input" rows="2" required>{{ $item['text'] ?? '' }}</textarea>
                        </div>
                        <div class="qa-criterion__meta">
                            <div class="tich-form-group qa-criterion__category">
                                <label class="tich-label">Category</label>
                                <input name="items[{{ $index }}][category]" class="tich-input" value="{{ $item['category'] ?? '' }}" placeholder="e.g. Teaching, Records">
                            </div>
                            <div class="tich-form-group qa-criterion__weight">
                                <label class="tich-label">Weight</label>
                                <input type="number" step="0.01" min="0.01" name="items[{{ $index }}][weight]" class="tich-input" value="{{ $item['weight'] ?? 1 }}">
                            </div>
                            <div class="tich-form-group qa-criterion__score">
                                <label class="tich-label">Max score</label>
                                <input type="number" step="1" min="1" name="items[{{ $index }}][max_score]" class="tich-input" value="{{ (int) ($item['max_score'] ?? 100) }}">
                            </div>
                            <div class="tich-form-group qa-criterion__evidence">
                                <label class="qa-check">
                                    <input type="checkbox" name="items[{{ $index }}][requires_evidence]" value="1" @checked(! empty($item['requires_evidence']))>
                                    <span>Require evidence upload</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="qa-criteria-add">
            <button type="button" class="tich-btn tich-btn-secondary" id="qa-add-item">Add criterion</button>
        </div>
        @error('items')
            <p class="tich-field-error tich-mt-3">{{ $message }}</p>
        @enderror
    </section>

    <div class="qa-form-footer">
        <button type="submit" class="tich-btn tich-btn-primary">{{ $isEdit ? 'Save draft' : 'Create draft sheet' }}</button>
        <a href="{{ $isEdit ? route('qa.assessments.show', $plan) : route('qa.assessments.index') }}" class="tich-btn tich-btn-ghost">Cancel</a>
    </div>
</form>
