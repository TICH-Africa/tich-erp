@php
    $q = is_array($question) ? $question : $question->toArray();
    $optionsText = '';
    if (! empty($q['options']) && is_array($q['options'])) {
        $optionsText = implode("\n", $q['options']);
    } elseif (! empty($q['options_text'])) {
        $optionsText = $q['options_text'];
    }
    $number = $displayNumber ?? 1;
@endphp
<div class="question-row ob-q-question-row">
    @if (! empty($q['id']))
        <input type="hidden" name="questions[{{ $index }}][id]" value="{{ $q['id'] }}">
    @endif
    <div class="ob-q-question-row__head">
        <span>Question <span class="question-row__num">{{ $number }}</span></span>
        <button type="button" class="tich-btn tich-btn-ghost tich-btn--sm remove-question-btn">Remove</button>
    </div>
    <div class="ob-q-question-row__body">
        <div class="uf-field">
            <label for="question_text_{{ $index }}">Question text</label>
            <textarea id="question_text_{{ $index }}" name="questions[{{ $index }}][question_text]" rows="2" maxlength="1000" placeholder="What would you like the employee to answer?">{{ $q['question_text'] ?? '' }}</textarea>
        </div>
        <div class="uf-form-grid-2">
            <div class="uf-field">
                <label for="input_type_{{ $index }}">Input type</label>
                <select id="input_type_{{ $index }}" name="questions[{{ $index }}][input_type]" class="question-type-select">
                    @foreach ($inputTypes as $value => $label)
                        <option value="{{ $value }}" @selected(($q['input_type'] ?? 'textarea') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="uf-field uf-field--check" style="align-self: end;">
                <input type="hidden" name="questions[{{ $index }}][is_required]" value="0">
                <label class="tich-checkbox" for="is_required_{{ $index }}">
                    <input type="checkbox" id="is_required_{{ $index }}" name="questions[{{ $index }}][is_required]" value="1" @checked(! isset($q['is_required']) || $q['is_required'])>
                    Required answer
                </label>
            </div>
        </div>
        <div class="uf-field options-wrap" style="display: none;">
            <label for="options_{{ $index }}">Choices (one per line)</label>
            <textarea id="options_{{ $index }}" name="questions[{{ $index }}][options_text]" rows="3" placeholder="Option A&#10;Option B">{{ $optionsText }}</textarea>
            <p class="uf-hint">Shown only when input type is “Dropdown / select”.</p>
        </div>
    </div>
</div>