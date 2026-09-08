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

@extends('layouts.qa')

@section('title', $isEdit ? 'Edit assessment sheet' : 'Build assessment sheet')

@section('qa-content')
    <x-page-toolbar
        :title="$isEdit ? 'Edit assessment sheet' : 'Build assessment sheet'"
        meta="Select department(s), define evaluation criteria, then dispatch"
    >
        <x-slot:actions>
            <a href="{{ $isEdit ? route('qa.assessments.show', $plan) : route('qa.assessments.index') }}" class="tich-btn tich-btn-ghost">Cancel</a>
        </x-slot:actions>
    </x-page-toolbar>

    <form
        method="POST"
        action="{{ $isEdit ? route('qa.assessments.update', $plan) : route('qa.assessments.store') }}"
        class="tich-mt-8"
        id="qa-assessment-form"
    >
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <div class="tich-card">
            <h2 class="tich-h3">Sheet details</h2>
            <div class="tich-form-grid tich-form-grid--2 tich-mt-4">
                <div class="tich-form-group" style="grid-column:1/-1;">
                    <label class="tich-label" for="plan_name">Title</label>
                    <input id="plan_name" name="plan_name" class="tich-input" required maxlength="300" value="{{ old('plan_name', $plan->plan_name ?? '') }}">
                </div>
                <div class="tich-form-group" style="grid-column:1/-1;">
                    <label class="tich-label" for="description">Description</label>
                    <textarea id="description" name="description" class="tich-input" rows="2">{{ old('description', $plan->description ?? '') }}</textarea>
                </div>
                <div class="tich-form-group" style="grid-column:1/-1;">
                    <label class="tich-label" for="instructions">Instructions for departments</label>
                    <textarea id="instructions" name="instructions" class="tich-input" rows="3">{{ old('instructions', $plan->instructions ?? '') }}</textarea>
                </div>
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
        </div>

        <div class="tich-card tich-mt-6">
            <h2 class="tich-h3">Target departments</h2>
            <p class="tich-caption tich-mt-2">Select academic/training or administrative departments that must complete this sheet.</p>
            <div class="tich-grid tich-grid--3 tich-mt-4" style="gap:0.5rem;">
                @foreach ($departments as $department)
                    <label style="display:flex;gap:0.5rem;align-items:flex-start;">
                        <input type="checkbox" name="department_ids[]" value="{{ $department->id }}" @checked(in_array($department->id, array_map('intval', (array) $selectedDepartments), true))>
                        <span class="tich-text">{{ $department->dept_name }} <span class="tich-caption">({{ $department->dept_code }})</span></span>
                    </label>
                @endforeach
            </div>
            @error('department_ids')<p class="tich-field-error">{{ $message }}</p>@enderror
        </div>

        <div class="tich-card tich-mt-6">
            <div class="tich-flex" style="justify-content:space-between;align-items:center;gap:1rem;">
                <div>
                    <h2 class="tich-h3">Evaluation criteria</h2>
                    <p class="tich-caption tich-mt-1">Dynamic form builder - each row becomes a scored metric on the department sheet.</p>
                </div>
                <button type="button" class="tich-btn tich-btn-secondary" id="qa-add-item">Add criterion</button>
            </div>

            <div id="qa-items" class="tich-mt-4">
                @foreach ($items as $index => $item)
                    <div class="tich-card tich-mt-3 qa-item-row" style="padding:1rem;background:var(--tich-surface-muted,#f8fafc);">
                        <div class="tich-form-grid tich-form-grid--2">
                            <div class="tich-form-group" style="grid-column:1/-1;">
                                <label class="tich-label">Criterion text</label>
                                <textarea name="items[{{ $index }}][text]" class="tich-input" rows="2" required>{{ $item['text'] ?? '' }}</textarea>
                            </div>
                            <div class="tich-form-group">
                                <label class="tich-label">Category</label>
                                <input name="items[{{ $index }}][category]" class="tich-input" value="{{ $item['category'] ?? '' }}" placeholder="e.g. Teaching, Records">
                            </div>
                            <div class="tich-form-group">
                                <label class="tich-label">Weight</label>
                                <input type="number" step="0.01" min="0.01" name="items[{{ $index }}][weight]" class="tich-input" value="{{ $item['weight'] ?? 1 }}">
                            </div>
                            <div class="tich-form-group">
                                <label class="tich-label">Max score</label>
                                <input type="number" step="0.01" min="1" name="items[{{ $index }}][max_score]" class="tich-input" value="{{ $item['max_score'] ?? 100 }}">
                            </div>
                            <div class="tich-form-group">
                                <label style="display:flex;gap:0.5rem;align-items:center;margin-top:1.6rem;">
                                    <input type="checkbox" name="items[{{ $index }}][requires_evidence]" value="1" @checked(!empty($item['requires_evidence']))>
                                    <span class="tich-text">Require evidence upload</span>
                                </label>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            @error('items')<p class="tich-field-error">{{ $message }}</p>@enderror
        </div>

        <div class="tich-flex-wrap tich-mt-6" style="gap:0.75rem;">
            <button type="submit" class="tich-btn tich-btn-primary">{{ $isEdit ? 'Save draft' : 'Create draft sheet' }}</button>
        </div>
    </form>
@endsection

@section('scripts')
    @parent
    <script>
        (function () {
            const container = document.getElementById('qa-items');
            const addBtn = document.getElementById('qa-add-item');
            if (!container || !addBtn) return;

            addBtn.addEventListener('click', function () {
                const index = container.querySelectorAll('.qa-item-row').length;
                const wrap = document.createElement('div');
                wrap.className = 'tich-card tich-mt-3 qa-item-row';
                wrap.style.padding = '1rem';
                wrap.style.background = 'var(--tich-surface-muted,#f8fafc)';
                wrap.innerHTML =
                    '<div class="tich-form-grid tich-form-grid--2">' +
                    '<div class="tich-form-group" style="grid-column:1/-1;"><label class="tich-label">Criterion text</label><textarea name="items[' + index + '][text]" class="tich-input" rows="2" required></textarea></div>' +
                    '<div class="tich-form-group"><label class="tich-label">Category</label><input name="items[' + index + '][category]" class="tich-input" placeholder="e.g. Teaching, Records"></div>' +
                    '<div class="tich-form-group"><label class="tich-label">Weight</label><input type="number" step="0.01" min="0.01" name="items[' + index + '][weight]" class="tich-input" value="1"></div>' +
                    '<div class="tich-form-group"><label class="tich-label">Max score</label><input type="number" step="0.01" min="1" name="items[' + index + '][max_score]" class="tich-input" value="100"></div>' +
                    '<div class="tich-form-group"><label style="display:flex;gap:0.5rem;align-items:center;margin-top:1.6rem;"><input type="checkbox" name="items[' + index + '][requires_evidence]" value="1" checked><span class="tich-text">Require evidence upload</span></label></div>' +
                    '</div>';
                container.appendChild(wrap);
            });
        })();
    </script>
@endsection
