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
                    <p class="tich-text" style="margin:0;"><strong>Warning:</strong> Copying, switching tabs, or opening new windows during this assessment is prohibited. Your responses may be submitted automatically if violations continue.</p>
                </div>

                @php $questions = $assessment->questions; $pages = ceil($questions->count() / 3); @endphp
                @foreach ($questions->chunk(3) as $pageIndex => $pageQuestions)
                    <div class="assessment-page" data-page="{{ $pageIndex }}" style="{{ $pageIndex > 0 ? 'display:none;' : '' }}">
                        @foreach ($pageQuestions as $question)
                            <div class="tich-question-card tich-mb-6" data-question-id="{{ $question->id }}">
                                <div class="tich-question-card__header">
                                    <span class="tich-question-card__number">Q{{ $question->sort_order }}</span>
                                    <span class="tich-question-card__points">{{ $question->points }} {{ str('point')->plural($question->points) }}</span>
                                </div>
                                <div class="tich-question-card__body">
                                    <p class="tich-question-text">{{ $question->question_text }}</p>

                                    @if ($question->question_type === 'mcq' && $question->options)
                                        <div class="tich-question-options tich-mt-4">
                                            @foreach ($question->options as $option)
                                                <label class="tich-option-label">
                                                    <input type="radio" name="responses[{{ $question->id }}]" value="{{ $option }}" required>
                                                    <span class="tich-option-box">{{ $option }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @elseif ($question->question_type === 'true_false')
                                        <div class="tich-question-options tich-mt-4">
                                            <label class="tich-option-label">
                                                <input type="radio" name="responses[{{ $question->id }}]" value="true" required>
                                                <span class="tich-option-box">True</span>
                                            </label>
                                            <label class="tich-option-label">
                                                <input type="radio" name="responses[{{ $question->id }}]" value="false" required>
                                                <span class="tich-option-box">False</span>
                                            </label>
                                        </div>
                                    @elseif ($question->question_type === 'essay' || $question->question_type === 'long_answer')
                                        <div class="tich-form-group tich-mt-4">
                                            <textarea name="responses[{{ $question->id }}]" rows="8" class="tich-input tich-textarea" placeholder="Type your answer here..." required></textarea>
                                        </div>
                                    @else
                                        <div class="tich-form-group tich-mt-4">
                                            <input type="text" name="responses[{{ $question->id }}]" class="tich-input" placeholder="Your answer" required>
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
                            <button type="submit" class="tich-btn tich-btn-primary" onclick="return confirm('Are you sure you want to submit? This action cannot be undone.')">Submit assessment</button>
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
                if (confirm('Are you sure you want to submit? This action cannot be undone.')) {
                    form.submit();
                }
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
        let violationCount = 0;
        const maxViolations = 3;
        const warningEl = document.getElementById('integrity-warning');

        function showWarning() {
            violationCount++;
            if (warningEl) {
                warningEl.style.display = 'block';
                setTimeout(() => { warningEl.style.display = 'none'; }, 4000);
            }
            if (violationCount >= maxViolations) {
                alert('Multiple integrity violations detected. Your assessment will be submitted automatically.');
                form.submit();
            }
        }

        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                showWarning();
            }
        });

        window.addEventListener('blur', showWarning);
        document.addEventListener('copy', showWarning);
        document.addEventListener('cut', showWarning);
        document.addEventListener('paste', showWarning);
        document.addEventListener('contextmenu', function (e) {
            e.preventDefault();
            showWarning();
        });

        function updateTimer() {
            const mins = Math.floor(remaining / 60);
            const secs = remaining % 60;
            timerValue.textContent = mins + ':' + (secs < 10 ? '0' : '') + secs;

            if (remaining <= 0) {
                clearInterval(timerInterval);
                timerValue.textContent = '0:00';
                timerValue.classList.add('tich-timer__value--danger');
                alert('Time has expired. Your assessment will be submitted automatically.');
                form.submit();
            }
            remaining--;
            if (timeInput) {
                timeInput.value = totalSeconds - remaining;
            }
        }

        updateTimer();
        timerInterval = setInterval(updateTimer, 1000);

        form.addEventListener('submit', function () {
            clearInterval(timerInterval);
        });
    });
    </script>
@endsection
