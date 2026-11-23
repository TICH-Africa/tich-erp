@extends('layouts.hr')

@section('title', $template->exists ? 'Edit questionnaire' : 'New questionnaire template')

@section('hr-content')
    <x-page-toolbar :title="$template->exists ? 'Edit template' : 'New questionnaire template'" meta="Add as many questions as you need. Assign at least one employee type.">
        <x-slot:actions>
            <a href="{{ route('hr.offboarding.questionnaires.templates.index') }}" class="tich-btn tich-btn-ghost">← Templates</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if ($errors->any())
        <div class="tich-alert tich-alert--error tich-mt-4">{{ $errors->first() }}</div>
    @endif

    <div class="uf-form">
        <form method="POST"
            action="{{ $template->exists ? route('hr.offboarding.questionnaires.templates.update', $template) : route('hr.offboarding.questionnaires.templates.store') }}"
            data-uf="ready">
            @csrf
            @if ($template->exists)
                @method('PUT')
            @endif

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">OBQ · Offboarding</div>
                    <div class="uf-amount-bar__sum">{{ $template->exists ? 'Edit questionnaire template' : 'New questionnaire template' }}</div>
                </div>
                <span class="uf-badge">{{ $template->exists ? 'Editing' : 'Draft' }}</span>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Template details</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="title">Template title <span class="uf-req">*</span></label>
                            <input type="text" name="title" id="title" required maxlength="200" value="{{ old('title', $template->title) }}" class="{{ $errors->has('title') ? 'is-invalid' : '' }}">
                            @error('title')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field uf-field--check" style="align-self: end;">
                            <input type="hidden" name="is_active" value="0">
                            <label class="tich-checkbox" for="is_active">
                                <input type="checkbox" name="is_active" id="is_active" value="1" @checked(old('is_active', $template->is_active ?? true))>
                                Active (available when sending questionnaires)
                            </label>
                        </div>
                    </div>
                    <div class="uf-field">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" rows="2" placeholder="Optional note for HR — not shown to the employee">{{ old('description', $template->description) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Assigned employee types</div>
                <div class="uf-section-body">
                    <p class="uf-hint">Select every employment category that should receive this template when HR sends an exit questionnaire. At least one is required.</p>
                    @error('employment_categories')
                        <span class="uf-error">{{ $message }}</span>
                    @enderror
                    <div class="ob-q-category-grid" role="group" aria-labelledby="employment-types-label">
                        <span id="employment-types-label" class="tich-sr-only">Employee types</span>
                        @foreach ($assignable as $code)
                            <label class="ob-q-category-chip tich-checkbox">
                                <input type="checkbox" name="employment_categories[]" value="{{ $code }}"
                                    @checked(in_array($code, old('employment_categories', $template->employment_categories ?? []), true))>
                                <span>{{ $categoryLabels[$code] ?? \App\Support\EmploymentCategory::label($code) }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Questions</div>
                <div class="uf-section-body">
                    <div class="tich-flex tich-flex--between tich-flex--center tich-mb-2" style="flex-wrap: wrap; gap: 0.75rem;">
                        <p class="uf-hint" style="margin: 0;">Employees answer these when their contract is ending. Add follow-ups as separate questions.</p>
                        <button type="button" class="tich-btn tich-btn-ghost tich-btn--sm" id="add-question-btn">+ Add question</button>
                    </div>
                    @error('questions')
                        <span class="uf-error">{{ $message }}</span>
                    @enderror
                    <div id="questions-container" class="ob-q-questions-stack">
                        @php
                            $oldQuestions = old('questions');
                            $questions = $oldQuestions ?? ($template->exists ? $template->questions->toArray() : [['question_text' => '', 'input_type' => 'textarea', 'is_required' => true]]);
                        @endphp
                        @foreach ($questions as $index => $q)
                            @include('hr.offboarding.questionnaires.templates._question_row', [
                                'index' => $index,
                                'displayNumber' => $loop->iteration,
                                'question' => $q,
                                'inputTypes' => $inputTypes,
                            ])
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Save</div>
                <div class="uf-section-body">
                    <div class="uf-form-actions">
                        <button type="submit" class="uf-btn uf-btn-primary">Save template</button>
                        <a href="{{ route('hr.offboarding.questionnaires.templates.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                        @if ($template->exists)
                            <button type="submit" form="delete-template-form" class="uf-btn uf-btn-secondary" style="margin-left: auto; color: var(--uf-req, #c62828); border-color: var(--uf-req, #c62828);">Delete template</button>
                        @endif
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>

        @if ($template->exists)
            <form id="delete-template-form" method="POST" action="{{ route('hr.offboarding.questionnaires.templates.destroy', $template) }}" onsubmit="return confirm('Delete this template?');">
                @csrf
                @method('DELETE')
            </form>
        @endif
    </div>

    <template id="question-row-template">
        @include('hr.offboarding.questionnaires.templates._question_row', [
            'index' => '__INDEX__',
            'displayNumber' => '__NUMBER__',
            'question' => ['question_text' => '', 'input_type' => 'textarea', 'is_required' => true, 'options' => []],
            'inputTypes' => $inputTypes,
        ])
    </template>

    <style>
        .ob-q-category-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.5rem 1rem;
            padding: 0.875rem 1rem;
            border: 1px solid var(--uf-border, #c4cad2);
            border-radius: 4px;
            background: var(--uf-input-fill, #f0f2f5);
        }

        @media (min-width: 900px) {
            .ob-q-category-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        .ob-q-category-chip {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            margin: 0;
            font-size: 0.8125rem;
            line-height: 1.35;
        }

        .ob-q-category-chip span {
            flex: 1;
        }

        .ob-q-questions-stack {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .ob-q-question-row {
            border: 1px solid var(--uf-border, #c4cad2);
            border-radius: 4px;
            overflow: hidden;
            background: var(--uf-surface, #fff);
        }

        .ob-q-question-row__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.5rem 1rem;
            background: var(--uf-input-fill, #f0f2f5);
            border-bottom: 1px solid var(--uf-border, #c4cad2);
            font-size: 0.8125rem;
            font-weight: 700;
        }

        .ob-q-question-row__body {
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        [data-theme="dark"] .ob-q-category-grid,
        [data-theme="dark"] .ob-q-question-row__head {
            background: #454b54;
        }

        [data-theme="dark"] .ob-q-question-row {
            background: #2d3239;
        }
    </style>

    <script>
        (function () {
            let nextIndex = document.querySelectorAll('.question-row').length;
            const container = document.getElementById('questions-container');
            const tpl = document.getElementById('question-row-template');

            function renumberQuestions() {
                container.querySelectorAll('.question-row').forEach(function (row, i) {
                    var num = row.querySelector('.question-row__num');
                    if (num) {
                        num.textContent = String(i + 1);
                    }
                });
            }

            document.getElementById('add-question-btn')?.addEventListener('click', function () {
                const count = container.querySelectorAll('.question-row').length + 1;
                const html = tpl.innerHTML
                    .replaceAll('__INDEX__', String(nextIndex++))
                    .replaceAll('__NUMBER__', String(count));
                const wrap = document.createElement('div');
                wrap.innerHTML = html.trim();
                container.appendChild(wrap.firstElementChild);
                bindRows();
                renumberQuestions();
            });

            function bindRows() {
                container.querySelectorAll('.remove-question-btn').forEach(function (btn) {
                    if (btn.dataset.bound === '1') return;
                    btn.dataset.bound = '1';
                    btn.addEventListener('click', function () {
                        const row = btn.closest('.question-row');
                        if (container.querySelectorAll('.question-row').length > 1) {
                            row.remove();
                            renumberQuestions();
                        }
                    });
                });
                container.querySelectorAll('.question-type-select').forEach(function (sel) {
                    if (sel.dataset.bound === '1') return;
                    sel.dataset.bound = '1';
                    sel.addEventListener('change', function () {
                        const row = sel.closest('.question-row');
                        const optWrap = row.querySelector('.options-wrap');
                        if (optWrap) {
                            optWrap.style.display = sel.value === 'select' ? 'block' : 'none';
                        }
                    });
                    sel.dispatchEvent(new Event('change'));
                });
            }

            bindRows();
            renumberQuestions();
        })();
    </script>
@endsection