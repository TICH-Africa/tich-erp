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
            @if ($window['until'])
                <div class="tich-notice tich-notice--info tich-mb-4">
                    <p class="tich-text" style="margin:0;">
                        This assessment closes on <strong>{{ $window['until']->format('d M Y H:i') }}</strong>.
                        @if ($window['makeup'])
                            <br>This is a make-up sitting your lecturer opened for you.
                        @endif
                        @if ($assessment->time_limit_minutes)
                            You have <strong>{{ $assessment->time_limit_minutes }} minutes</strong> once you start.
                        @endif
                    </p>
                </div>
            @endif

            <form method="POST"
                  action="{{ route('portal.assessments.submit', $assessment) }}"
                  data-progress-url="{{ route('portal.assessments.progress', $assessment) }}"
                  data-draft-key="tich.assessment-draft.{{ $studentId }}.{{ $assessment->id }}.{{ $submission->attempt_number }}"
                  data-server-updated-at="{{ $serverUpdatedAt ?? 0 }}"
                  data-no-autosave
                  id="assessment-form">
                @csrf
                <input type="hidden" name="time_taken_seconds" id="time-taken" value="0">

                <div id="integrity-warning" class="tich-notice tich-notice--warning tich-mb-4" style="display:none;">
                    <p class="tich-text" style="margin:0;"><strong>Note:</strong> Your answers auto-save while you are online. Keep this page open until you submit.</p>
                </div>

                <p id="progress-save-status" class="tich-caption tich-mb-4" aria-live="polite">
                    Answers save automatically as you work.
                </p>

                <div id="answer-warning" class="tich-notice tich-notice--danger tich-mb-4" style="display:none;">
                    <p class="tich-text" style="margin:0;" id="answer-warning-text"></p>
                </div>

                @php $pages = ceil($questions->count() / 3); @endphp
                @foreach ($questions->chunk(3) as $pageIndex => $pageQuestions)
                    <div class="assessment-page" data-page="{{ $pageIndex }}" style="{{ $pageIndex > 0 ? 'display:none;' : '' }}">
                        @foreach ($pageQuestions as $question)
                            @php $number = $pageIndex * 3 + $loop->iteration; @endphp
                            <div class="tich-question-card tich-mb-6" data-question-id="{{ $question->id }}">
                                <div class="tich-question-card__header">
                                    <span class="tich-question-card__number">Question {{ $number }}</span>
                                    <span class="tich-question-card__points">{{ $question->points }} {{ str('point')->plural($question->points) }}</span>
                                </div>
                                <div class="tich-question-card__body">
                                    <p class="tich-question-text">{{ $question->question_text }}</p>
                                    @php
                                        $savedAnswer = old(
                                            'responses.'.$question->id,
                                            $submission->responses[$question->id] ?? $submission->responses[(string) $question->id] ?? '',
                                        );
                                    @endphp

                                    @if ($question->question_type === 'mcq' && $question->options)
                                        <div class="tich-question-options tich-mt-4">
                                            @foreach ($assessment->optionsFor($question, $submission) as $option)
                                                <label class="tich-option-label">
                                                    <input type="radio" name="responses[{{ $question->id }}]" value="{{ $option }}" @checked((string) $savedAnswer === (string) $option)>
                                                    <span class="tich-option-letter">{{ chr(64 + $loop->iteration) }}</span>
                                                    <span class="tich-option-box">{{ $option }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @elseif ($question->question_type === 'true_false')
                                        <div class="tich-question-options tich-mt-4">
                                            @foreach (['true' => 'True', 'false' => 'False'] as $value => $caption)
                                                <label class="tich-option-label">
                                                    <input type="radio" name="responses[{{ $question->id }}]" value="{{ $value }}" @checked(strtolower((string) $savedAnswer) === $value)>
                                                    <span class="tich-option-letter">{{ chr(65 + $loop->iteration) }}</span>
                                                    <span class="tich-option-box">{{ $caption }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @elseif (in_array($question->question_type, ['short_answer', 'essay', 'long_answer'], true))
                                        <div class="tich-form-group tich-mt-4">
                                            <textarea name="responses[{{ $question->id }}]" rows="{{ $question->question_type === 'short_answer' ? 3 : 8 }}" class="tich-input tich-textarea" placeholder="Type your answer here...">{{ $savedAnswer }}</textarea>
                                        </div>
                                    @else
                                        <div class="tich-form-group tich-mt-4">
                                            <input type="text" name="responses[{{ $question->id }}]" class="tich-input" placeholder="Your answer" value="{{ $savedAnswer }}">
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
                            <div id="timer-display"
                                 class="tich-timer"
                                 data-time-limit="{{ $timeLimit * 60 }}"
                                 data-remaining-seconds="{{ $remainingSeconds }}">
                                <span class="tich-timer__label">Time remaining:</span>
                                <span class="tich-timer__value" id="timer-value">{{ intdiv($remainingSeconds, 60) }}:{{ str_pad((string) ($remainingSeconds % 60), 2, '0', STR_PAD_LEFT) }}</span>
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
        let remaining = parseInt(timerDisplay.dataset.remainingSeconds, 10);
        let elapsedSeconds = Math.max(0, totalSeconds - remaining);
        let timerInterval;
        let forceSubmit = false;
        const noteEl = document.getElementById('integrity-warning');
        const answerNote = document.getElementById('answer-warning');
        const answerNoteText = document.getElementById('answer-warning-text');
        const saveStatus = document.getElementById('progress-save-status');
        const draftKey = form.dataset.draftKey;
        const progressUrl = form.dataset.progressUrl;
        let serverUpdatedAt = Number(form.dataset.serverUpdatedAt || 0);
        let localSaveTimer;
        let progressSaveTimer;
        let progressSaving = false;
        let progressSaveQueued = false;
        let needsSync = false;

        function collectResponses() {
            const responses = {};
            form.querySelectorAll('[name^="responses["]').forEach((input) => {
                const match = input.name.match(/^responses\[([^\]]+)\]$/);
                if (!match) return;
                const questionId = match[1];
                if (input.type === 'radio') {
                    if (!Object.prototype.hasOwnProperty.call(responses, questionId)) responses[questionId] = '';
                    if (input.checked) responses[questionId] = input.value;
                    return;
                }
                responses[questionId] = input.value;
            });
            return responses;
        }

        function setSaveStatus(message) {
            if (saveStatus) saveStatus.textContent = message;
        }

        function saveLocalDraft() {
            try {
                window.localStorage.setItem(draftKey, JSON.stringify({
                    baseServerUpdatedAt: serverUpdatedAt,
                    responses: collectResponses(),
                }));
            } catch (error) {
                setSaveStatus('Device backup is unavailable. Answers will sync when online.');
            }
        }

        function restoreLocalDraft() {
            try {
                const raw = window.localStorage.getItem(draftKey);
                if (!raw) return;
                const draft = JSON.parse(raw);
                if (!draft || Number(draft.baseServerUpdatedAt) !== serverUpdatedAt || !draft.responses) {
                    window.localStorage.removeItem(draftKey);
                    return;
                }

                const currentResponses = collectResponses();
                const draftQuestionIds = Object.keys(draft.responses);
                const currentQuestionIds = Object.keys(currentResponses);
                if (draftQuestionIds.length === currentQuestionIds.length
                    && currentQuestionIds.every((questionId) =>
                        String(draft.responses[questionId] ?? '') === String(currentResponses[questionId] ?? '')
                    )) {
                    window.localStorage.removeItem(draftKey);
                    return;
                }

                form.querySelectorAll('[name^="responses["]').forEach((input) => {
                    const match = input.name.match(/^responses\[([^\]]+)\]$/);
                    if (!match || !Object.prototype.hasOwnProperty.call(draft.responses, match[1])) return;
                    const answer = draft.responses[match[1]] ?? '';
                    if (input.type === 'radio') {
                        input.checked = String(input.value) === String(answer);
                    } else {
                        input.value = answer;
                    }
                });
                needsSync = true;
                setSaveStatus('Restored unsynced answers from this device. Syncing now…');
            } catch (error) {
                setSaveStatus('Could not restore the device backup. Your account copy is still available.');
            }
        }

        async function saveProgress() {
            if (!needsSync) return;
            if (progressSaving) {
                progressSaveQueued = true;
                return;
            }
            if (!navigator.onLine) {
                setSaveStatus('Offline. Answers are saved on this device and will sync when reconnected.');
                return;
            }

            progressSaving = true;
            progressSaveQueued = false;
            const snapshot = collectResponses();
            try {
                const response = await fetch(progressUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ responses: snapshot }),
                });
                let result = {};
                try {
                    result = await response.json();
                } catch (error) {
                    result = {};
                }
                if (!response.ok) throw new Error(result.message || 'Answers could not be synced.');

                serverUpdatedAt = Number(result.updated_at || serverUpdatedAt);
                form.dataset.serverUpdatedAt = String(serverUpdatedAt);
                needsSync = JSON.stringify(snapshot) !== JSON.stringify(collectResponses());
                saveLocalDraft();
                setSaveStatus(needsSync ? 'New changes are waiting to sync…' : 'Answers saved to your account.');
            } catch (error) {
                setSaveStatus('Not synced yet. Your answers are saved on this device and will retry when online.');
            } finally {
                const retryQueued = progressSaveQueued;
                progressSaving = false;
                progressSaveQueued = false;
                if (retryQueued) window.setTimeout(saveProgress, 0);
            }
        }

        function queueProgressSave() {
            needsSync = true;
            if (localSaveTimer) window.clearTimeout(localSaveTimer);
            localSaveTimer = window.setTimeout(saveLocalDraft, 150);
            if (progressSaveTimer) window.clearTimeout(progressSaveTimer);
            progressSaveTimer = window.setTimeout(saveProgress, 700);
        }

        restoreLocalDraft();
        if (needsSync) window.setTimeout(saveProgress, 0);
        form.addEventListener('input', queueProgressSave);
        form.addEventListener('change', queueProgressSave);
        window.addEventListener('online', saveProgress);
        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                saveLocalDraft();
                saveProgress();
            }
        });
        window.addEventListener('pagehide', function () {
            saveLocalDraft();
            if (!needsSync || !navigator.sendBeacon) return;
            const token = form.querySelector('input[name="_token"]').value;
            const body = new Blob(
                [JSON.stringify({ _token: token, responses: collectResponses() })],
                { type: 'application/json' }
            );
            navigator.sendBeacon(progressUrl, body);
        });

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
                timeInput.value = String(totalSeconds);
                saveLocalDraft();
                form.submit();
                return;
            }
            timeInput.value = String(elapsedSeconds);
            elapsedSeconds++;
            remaining--;
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
            if (progressSaveTimer) window.clearTimeout(progressSaveTimer);
            saveLocalDraft();
        });
    });
    </script>
@endsection
