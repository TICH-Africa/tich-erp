@php
    $rows = $rows ?? [];
    $slotCount = max(count($rows), $slots ?? 5) + 2;
@endphp

<p class="tich-caption tich-mt-4">Questions - separate MCQ options with <code>|</code> (e.g. <code>A|B|C</code>). For essay/long answer questions, leave options blank. <strong>Add as many questions as the paper has</strong> - use the button under the last one.</p>
<div class="tich-grading-questions" data-question-list>
    @for ($index = 0; $index < $slotCount; $index++)
        @include('staff.partials.grading-objective-question-card', [
            'index' => $index,
            'question' => $rows[$index] ?? null,
        ])
    @endfor
</div>

<button type="button" class="tich-btn tich-btn-secondary tich-mt-4" data-add-question>+ Add another question</button>

<script>
function setQuestionSelectVisible(select, visible) {
    var wrapper = select.tichDropdownWrapper;

    if (wrapper) {
        wrapper.style.display = visible ? '' : 'none';
    } else {
        select.style.display = visible ? '' : 'none';
    }
}

function toggleQuestionOptions(select) {
    var card = select.closest('.tich-grading-question-card');
    var type = select.value;
    var optionsField = card.querySelector('[data-options-field]');
    var correctField = card.querySelector('[data-correct-field]');
    var correctSelect = card.querySelector('.question-correct-select');
    var correctText = card.querySelector('.question-correct-text');
    var currentAnswer = correctSelect.value || correctText.value;

    if (type === 'mcq') {
        optionsField.style.display = '';
        correctField.style.display = '';
        setQuestionSelectVisible(correctSelect, true);
        correctSelect.disabled = false;
        correctSelect.tichDropdownWrapper?.classList.remove('is-disabled');
        correctText.style.display = 'none';
        correctText.disabled = true;
        updateCorrectAnswerOptions(card.querySelector('.question-options-input'), currentAnswer);
    } else if (type === 'true_false') {
        optionsField.style.display = 'none';
        correctField.style.display = '';
        setQuestionSelectVisible(correctSelect, true);
        correctText.style.display = 'none';
        correctSelect.disabled = false;
        correctSelect.tichDropdownWrapper?.classList.remove('is-disabled');
        correctText.disabled = true;
        correctSelect.innerHTML = '<option value="">Select correct answer</option>' +
            '<option value="true">True</option>' +
            '<option value="false">False</option>';
        correctSelect.value = currentAnswer === 'true' || currentAnswer === 'false' ? currentAnswer : '';
        correctSelect.dispatchEvent(new Event('change', { bubbles: true }));
    } else {
        optionsField.style.display = 'none';
        correctField.style.display = '';
        setQuestionSelectVisible(correctSelect, false);
        correctSelect.disabled = true;
        correctSelect.tichDropdownWrapper?.classList.add('is-disabled');
        correctText.style.display = '';
        correctText.disabled = false;
    }
}

function updateCorrectAnswerOptions(input, selectedAnswer) {
    var card = input.closest('.tich-grading-question-card');
    var select = card.querySelector('.question-correct-select');
    var current = selectedAnswer === undefined ? select.value : selectedAnswer;
    var raw = input.value || '';
    var options = raw.split('|').map(function(s) { return s.trim(); }).filter(function(s) { return s !== ''; });

    select.innerHTML = '<option value="">Select correct answer</option>';
    options.forEach(function(opt) {
        var option = document.createElement('option');
        option.value = opt;
        option.textContent = opt;
        if (opt === current) {
            option.selected = true;
        }
        select.appendChild(option);
    });
    select.dispatchEvent(new Event('change', { bubbles: true }));
}

document.querySelectorAll('.tich-grading-question-card .question-type-select').forEach(function(select) {
    toggleQuestionOptions(select);
});

// The paper is rarely five questions long, so the form carries its own slots:
// a new one is a copy of the last card with its answers cleared, and the fields
// are renumbered from the position each card sits in so blanks never leave a hole.
var questionList = document.querySelector('[data-question-list]');

function renumberQuestions() {
    questionList.querySelectorAll('.tich-grading-question-card').forEach(function(card, index) {
        card.querySelectorAll('[name]').forEach(function(field) {
            field.name = field.name.replace(/questions\[[^\]]*\]/, 'questions[' + index + ']');
        });

        var label = card.querySelector('.tich-label');

        if (label) {
            label.textContent = 'Question ' + (index + 1);
        }
    });
}

document.querySelector('[data-add-question]').addEventListener('click', function() {
    var cards = questionList.querySelectorAll('.tich-grading-question-card');
    var added = cards[cards.length - 1].cloneNode(true);

    added.querySelectorAll('.tich-dropdown').forEach(function(wrapper) {
        var select = wrapper.querySelector('select');

        if (!select) {
            return;
        }

        wrapper.parentNode.insertBefore(select, wrapper);
        wrapper.remove();
        select.classList.remove('tich-dropdown__native');
        delete select.dataset.tichDropdownEnhanced;
    });

    added.querySelectorAll('input').forEach(function(input) {
        input.value = input.type === 'number' ? '1' : '';
    });
    added.querySelectorAll('select').forEach(function(select) {
        select.value = select.options.length ? select.options[0].value : '';
    });

    var correctSelect = added.querySelector('.question-correct-select');

    if (correctSelect) {
        correctSelect.innerHTML = '<option value="">Select correct answer</option>';
    }

    questionList.appendChild(added);
    renumberQuestions();
    added.querySelectorAll('.question-type-select').forEach(function(select) {
        toggleQuestionOptions(select);
    });
    if (window.TichSelect) {
        window.TichSelect.refresh();
    }
    added.querySelector('input[type=text]').focus();
});

questionList.addEventListener('click', function(event) {
    var button = event.target.closest('[data-remove-question]');

    if (! button || questionList.querySelectorAll('.tich-grading-question-card').length <= 1) {
        return;
    }

    button.closest('.tich-grading-question-card').remove();
    renumberQuestions();
});
</script>
