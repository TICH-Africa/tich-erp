@php
    /** @var \App\Models\ObjectiveQuestion|null $question */
    $type = (string) ($question?->question_type ?? 'mcq');
    $optionsValue = $question ? implode('|', $question->options ?? []) : '';
    $answerValue = (string) ($question?->correct_answer ?? '');
    $pointsValue = $question?->points ?? 1;
@endphp
<div class="tich-card tich-grading-question-card tich-mt-4">
    <div class="tich-form-group">
        <label class="tich-label">Question {{ $index + 1 }}</label>
        <button type="button" class="tich-link tich-grading-question-remove" data-remove-question>Remove</button>
        <input type="text" name="questions[{{ $index }}][question_text]" class="tich-input" placeholder="Question text (leave blank to skip)" value="{{ $question?->question_text }}">
    </div>
    <div class="tich-grid tich-grid--3" style="gap:1rem;">
        <div class="tich-form-group">
            <label class="tich-label">Type</label>
            <select name="questions[{{ $index }}][question_type]" class="tich-input" onchange="toggleQuestionOptions(this)">
                <option value="mcq" @selected($type === 'mcq')>Multiple choice</option>
                <option value="true_false" @selected($type === 'true_false')>True / False</option>
                <option value="essay" @selected($type === 'essay')>Essay / Long answer</option>
                <option value="short_answer" @selected($type === 'short_answer')>Short answer</option>
            </select>
        </div>
        <div class="tich-form-group question-options-field" data-options-field>
            <label class="tich-label">Options (pipe-separated)</label>
            <input type="text" name="questions[{{ $index }}][options]" class="tich-input question-options-input" placeholder="A|B|C" value="{{ $optionsValue }}" oninput="updateCorrectAnswerOptions(this)">
        </div>
        <div class="tich-form-group question-correct-field" data-correct-field>
            <label class="tich-label">Correct answer</label>
            <select name="questions[{{ $index }}][correct_answer]" class="tich-input question-correct-select" style="display:none;">
                <option value="">Select correct answer</option>
                @if ($answerValue !== '')
                    <option value="{{ $answerValue }}" selected>{{ $answerValue }}</option>
                @endif
            </select>
            <input type="text" name="questions[{{ $index }}][correct_answer]" class="tich-input question-correct-text" placeholder="A or true" value="{{ in_array($type, ['mcq', 'true_false'], true) ? '' : $answerValue }}" style="display:none;">
        </div>
    </div>
    <div class="tich-form-group">
        <label class="tich-label">Points</label>
        <input type="number" name="questions[{{ $index }}][points]" class="tich-input" value="{{ $pointsValue }}" min="0.01" step="0.01" style="max-width:6rem;">
    </div>
</div>
