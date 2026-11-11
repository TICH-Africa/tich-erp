@extends('layouts.portal')

@section('portal-content')
    <x-page-toolbar title="{{ $assessment->name }}" meta="{{ $assessment->allocation->unit->unit_code ?? '' }} · {{ $assessment->allocation->unit->unit_name ?? '' }}">
        <x-slot:actions>
            <span class="tich-badge tich-badge--warning">{{ $assessment->max_score }} marks</span>
            @if ($assessment->time_limit_minutes)
                <span class="tich-badge tich-badge--info">{{ $assessment->time_limit_minutes }} min</span>
            @endif
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-mt-6">
        <div class="tich-card__body">
            @if ($assessment->available_until)
                <div class="tich-notice tich-notice--info tich-mb-4">
                    <p class="tich-text" style="margin:0;">
                        This assessment closes on <strong>{{ $assessment->available_until->format('d M Y H:i') }}</strong>.
                        @if ($assessment->time_limit_minutes)
                            You have <strong>{{ $assessment->time_limit_minutes }} minutes</strong> once you start.
                        @endif
                    </p>
                </div>
            @endif

            <form method="POST" action="{{ route('portal.assessments.submit', $assessment) }}" id="assessment-form">
                @csrf
                <input type="hidden" name="time_taken_seconds" id="time-taken" value="0">

                <div id="integrity-warning" class="tich-notice tich-notice--warning tich-mb-4" style="display:none;">
                    <p class="tich-text" style="margin:0;"><strong>Note:</strong> Keep this page open until you submit. Nothing is saved until you press <strong>Submit assessment</strong>.</p>
                </div>

                <div id="answer-warning" class="tich-notice tich-notice--danger tich-mb-4" style="display:none;">
                    <p class="tich-text" style="margin:0;" id="answer-warning-text"></p>
                </div>

                @php $questions = $assessment->questions; $pages = ceil($questions->count() / 3); @endphp
                @foreach ($questions->chunk(3) as $pageIndex => $pageQuestions)
                    <div class="assessment-page" data-page="{{ $pageIndex }}" style="{{ $pageIndex > 0 ? 'display:none;' : '' }}">
                        @foreach ($pageQuestions as $question)
                            <div class="tich-question-card tich-mb-6" data-question-id="{{ $question->id }}">
                                <div class="tich-question-card__header">
                                    <span class="tich-question-card__number">Question {{ $question->sort_order }}</span>
                                    <span class="tich-question-card__points">{{ $question->points }} {{ str('point')->plural($question->points) }}</span>
                                </div>
                                <div class="tich-question-card__body">
                                    <p class="tich-question-text">{{ $question->question_text }}</p>

                                    @if ($question->question_type === 'mcq' && $question->options)
                                        <div class="tich-question-options tich-mt-4">
                                            @foreach ($question->options as $option)
                                                <label class="tich-option-label">
                                                    <input type="radio" name="responses[{{ $question->id }}]" value="{{ $option }}">
                                                    <span class="tich-option-letter">{{ chr(64 + $loop->iteration) }}</span>
                                                    <span class="tich-option-box">{{ $option }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @elseif ($question->question_type === 'true_false')
                                        <div class="tich-question-options tich-mt-4">
                                            @foreach (['true' => 'True', 'false' => 'False'] as $value => $caption)
                                                <label class="tich-option-label">
                                                    <input type="radio" name="responses[{{ $question->id }}]" value="{{ $value }}">
                                                    <span class="tich-option-letter">{{ chr(65 + $loop->iteration) }}</span>
                                                    <span class="tich-option-box">{{ $caption }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @elseif ($question->question_type === 'essay' || $question->question_type === 'long_answer')
                                        <div class="tich-form-group tich-mt-4">
                                            <textarea name="responses[{{ $question->id }}]" rows="8" class="tich-input tich-textarea" placeholder="Type your answer here..."></textarea>
                                        </div>
                                    @else
                                        <div class="tich-form-group tich-mt-4">
                                            <input type="text" name="responses[{{ $question->id }}]" class="tich-input" placeholder="Your answer">
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach

                <div class="tich-assessment-footer tich-mt-6">
                    <div class="tich-flex tich-flex--between tich-flex--center">
                        <div class="tich-flex tich-flex--center tich-gap-3">
                            <button type="button" class="tich-btn tich-btn-ghost" id="prev-page-btn" style="display:none;">Previous</button>
                            <button type="button" class="tich-btn tich-btn-primary" id="next-page-btn">Next</button>
                            <span class="tich-caption tich-ml-2" id="page-indicator">Page 1 of {{ $pages }}</span>
                        </div>
                        <div class="tich-flex tich-flex--center tich-gap-4">
                            <div id="timer-display" class="tich-timer" data-time-limit="{{ $timeLimit * 60 }}">
                                <span class="tich-timer__label">Time remaining:</span>
                                <span class="tich-timer__value" id="timer-value">{{ $timeLimit }}:00</span>
                            </div>
                            <button type="submit" class="tich-btn tich-btn-success" id="submit-btn">Submit assessment</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('assessment-form');
        const timerDisplay = document.getElementById('timer-display');
        const timerValue = document.getElementById('timer-value');
        const timeInput = document.getElementById('time-taken');
        const prevBtn = document.getElementById('prev-page-btn');
        const nextBtn = document.getElementById('next-page-btn');
        const pageIndicator = document.getElementById('page-indicator');
        const pages = document.querySelectorAll('.assessment-page');
        const totalPages = pages.length;
        let currentPage = 0;

        function showPage(index) {
            pages.forEach((page, i) => {
                page.style.display = i === index ? 'block' : 'none';
            });
            currentPage = index;
            pageIndicator.textContent = 'Page ' + (index + 1) + ' of ' + totalPages;
            prevBtn.style.display = index === 0 ? 'none' : 'inline-block';
            nextBtn.textContent = index === totalPages - 1 ? 'Submit' : 'Next';
            nextBtn.className = index === totalPages - 1 ? 'tich-btn tich-btn-success' : 'tich-btn tich-btn-primary';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        nextBtn.addEventListener('click', function () {
            if (currentPage === totalPages - 1) {
                form.requestSubmit();
            } else {
                showPage(currentPage + 1);
            }
        });

        prevBtn.addEventListener('click', function () {
            if (currentPage > 0) {
                showPage(currentPage - 1);
            }
        });

        showPage(0);

        const totalSeconds = parseInt(timerDisplay.dataset.timeLimit, 10);
        let remaining = totalSeconds;
        let timerInterval;
        let forceSubmit = false;
        const noteEl = document.getElementById('integrity-warning');
        const answerNote = document.getElementById('answer-warning');
        const answerNoteText = document.getElementById('answer-warning-text');

        // A reminder only: nothing here ever submits the paper or blocks the browser.
        function remind() {
            if (!noteEl) return;
            noteEl.style.display = 'block';
            setTimeout(() => { noteEl.style.display = 'none'; }, 6000);
        }

        document.addEventListener('visibilitychange', function () {
            if (document.hidden) remind();
        });

        function firstUnansweredCard() {
            const cards = Array.from(form.querySelectorAll('.tich-question-card'));
            for (const card of cards) {
                const inputs = Array.from(card.querySelectorAll('[name^="responses["]'));
                if (!inputs.length) continue;
                const answered = inputs[0].type === 'radio'
                    ? inputs.some((input) => input.checked)
                    : inputs.some((input) => String(input.value || '').trim());
                if (!answered) return card;
            }
            return null;
        }

        function revealUnanswered(card) {
            const pageIndex = Number(card.closest('.assessment-page').dataset.page);
            if (pageIndex !== currentPage) showPage(pageIndex);
            answerNoteText.textContent = card.querySelector('.tich-question-card__number').textContent
                + ' is not answered yet. Answer every question, then submit.';
            answerNote.style.display = 'block';
            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
            const input = card.querySelector('[name^="responses["]');
            if (input) input.focus({ preventScroll: true });
        }

        function updateTimer() {
            const mins = Math.floor(remaining / 60);
            const secs = remaining % 60;
            timerValue.textContent = mins + ':' + (secs < 10 ? '0' : '') + secs;

            if (remaining <= 0) {
                clearInterval(timerInterval);
                timerValue.textContent = '0:00';
                timerValue.classList.add('tich-timer__value--danger');
                forceSubmit = true;
                form.submit();
            }
            remaining--;
            if (timeInput) {
                timeInput.value = totalSeconds - remaining;
            }
        }

        updateTimer();
        timerInterval = setInterval(updateTimer, 1000);

        form.addEventListener('submit', function (event) {
            if (forceSubmit) {
                clearInterval(timerInterval);
                return;
            }
            const card = firstUnansweredCard();
            if (card) {
                event.preventDefault();
                revealUnanswered(card);
                return;
            }
            answerNote.style.display = 'none';
            if (!confirm('Submit your answers now? You cannot change them after submitting.')) {
                event.preventDefault();
                return;
            }
            clearInterval(timerInterval);
        });
    });
    </script>
@endsection
