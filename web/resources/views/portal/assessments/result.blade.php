@extends('layouts.portal')

@section('portal-content')
    <x-page-toolbar title="{{ $assessment->name }}" meta="Assessment result">
        <x-slot:actions>
            <span class="tich-badge tich-badge--{{ $submission->percentage_score >= 50 ? 'success' : 'danger' }}">
                {{ $submission->percentage_score }}%
            </span>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-mt-6">
        <div class="tich-card__body">
            <div class="tich-result-summary">
                <div class="tich-result-summary__score">
                    <span class="tich-result-summary__value">{{ $submission->score_obtained ?? 0 }}</span>
                    <span class="tich-result-summary__max">/ {{ $assessment->max_score }}</span>
                </div>
                <div class="tich-result-summary__meta">
                    <div>Correct: {{ $submission->correct_count }} / {{ $submission->question_count }}</div>
                    <div>Time taken: {{ $submission->time_taken_seconds ? floor($submission->time_taken_seconds / 60) . ' min ' . ($submission->time_taken_seconds % 60) . ' sec' : '-' }}</div>
                    <div>Submitted: {{ $submission->student_submitted_at->format('d M Y H:i') }}</div>
                </div>
            </div>

            @if ($assessment->show_results_immediately || $assessment->auto_graded_at)
                <div class="tich-mt-6">
                    <h3 class="tich-h4">Review</h3>
                    @foreach ($assessment->questions as $question)
                        @php
                            $studentAnswer = $submission->responses[$question->id] ?? $submission->responses[(string) $question->id] ?? null;
                            $isCorrect = app(\App\Services\ObjectiveAutoGradingService::class)->answersMatch($question, $studentAnswer);
                        @endphp
                        <div class="tich-review-card tich-mb-4 {{ $isCorrect ? 'tich-review-card--correct' : 'tich-review-card--incorrect' }}">
                            <div class="tich-review-card__header">
                                <span>Q{{ $question->sort_order }}: {{ $question->question_text }}</span>
                                <span class="tich-badge tich-badge--{{ $isCorrect ? 'success' : 'danger' }}">{{ $isCorrect ? '+' . $question->points . ' pts' : '0 pts' }}</span>
                            </div>
                            <div class="tich-review-card__body">
                                <div>Your answer: <strong>{{ $studentAnswer ?? 'Not answered' }}</strong></div>
                                @if (! $isCorrect && $question->correct_answer)
                                    <div class="tich-caption tich-mt-1">Correct answer: <strong>{{ $question->correct_answer }}</strong></div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="tich-notice tich-notice--info tich-mt-4">
                    <p class="tich-text" style="margin:0;">Results will be released by your lecturer after grading is complete.</p>
                </div>
            @endif

            <div class="tich-mt-6">
                <a href="{{ route('portal.assessments.index') }}" class="tich-btn tich-btn-ghost">Back to assessments</a>
            </div>
        </div>
    </div>
@endsection
