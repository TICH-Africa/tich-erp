<?php

namespace App\Http\Controllers\Api\Student\mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Student\mobile\Concerns\ResolvesPortalStudent;
use App\Models\ObjectiveAssessment;
use App\Models\ObjectiveSubmission;
use App\Services\ObjectiveAutoGradingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssessmentsController extends Controller
{
    use ResolvesPortalStudent;

    public function show(Request $request, ObjectiveAssessment $assessment): JsonResponse
    {
        $student = $this->portalStudent($request);
        $assessment->load(['questions', 'allocation.unit']);

        $submission = ObjectiveSubmission::query()
            ->where('objective_assessment_id', $assessment->id)
            ->where('student_id', $student->id)
            ->first();

        $alreadySubmitted = $submission && $submission->student_submitted_at;

        return response()->json([
            'assessment' => [
                'id' => $assessment->id,
                'title' => $assessment->name,
                'status' => $assessment->status,
                'max_score' => (float) $assessment->max_score,
                'time_limit_minutes' => $assessment->time_limit_minutes,
                'available_from' => optional($assessment->available_from)?->toIso8601String(),
                'available_until' => optional($assessment->available_until)?->toIso8601String(),
                'unit_code' => $assessment->allocation?->unit?->unit_code,
                'unit_name' => $assessment->allocation?->unit?->unit_name,
                'can_take' => $assessment->status === 'ready' && ! $alreadySubmitted,
                'questions' => $assessment->questions->sortBy('sort_order')->values()->map(fn ($q) => [
                    'id' => $q->id,
                    'sort_order' => $q->sort_order,
                    'question_text' => $q->question_text,
                    'question_type' => $q->question_type,
                    'options' => $q->options ?? [],
                    'points' => (float) $q->points,
                ]),
            ],
            'submission' => $submission ? [
                'id' => $submission->id,
                'score' => $submission->score_obtained,
                'percentage_score' => $submission->percentage_score,
                'correct_count' => $submission->correct_count,
                'question_count' => $submission->question_count,
                'submitted_at' => optional($submission->student_submitted_at)?->toIso8601String(),
            ] : null,
        ]);
    }

    public function submit(Request $request, ObjectiveAssessment $assessment): JsonResponse
    {
        $student = $this->portalStudent($request);
        abort_if($assessment->status !== 'ready', 403, 'This assessment is not open.');

        $submission = ObjectiveSubmission::query()
            ->where('objective_assessment_id', $assessment->id)
            ->where('student_id', $student->id)
            ->first();

        if ($submission && $submission->student_submitted_at) {
            return response()->json([
                'message' => 'You have already completed this assessment.',
                'submission' => [
                    'id' => $submission->id,
                    'score' => $submission->score_obtained,
                    'percentage_score' => $submission->percentage_score,
                ],
            ], 422);
        }

        if ($assessment->available_from && now()->lt($assessment->available_from)) {
            return response()->json(['message' => 'This assessment is not yet available.'], 403);
        }
        if ($assessment->available_until && now()->gt($assessment->available_until)) {
            return response()->json(['message' => 'This assessment has expired.'], 403);
        }

        $validated = $request->validate([
            'responses' => 'required|array',
            'responses.*' => 'nullable|string',
            'time_taken_seconds' => 'nullable|integer|min:0',
        ]);

        $questions = $assessment->questions;
        $totalPoints = (float) $questions->sum('points');

        $gradeResult = app(ObjectiveAutoGradingService::class)->gradeSubmission(
            $questions,
            $validated['responses'],
            $totalPoints,
            (float) $assessment->max_score
        );

        if (! $submission) {
            $submission = ObjectiveSubmission::query()->create([
                'objective_assessment_id' => $assessment->id,
                'student_id' => $student->id,
            ]);
        }

        $submission->update([
            'responses' => $validated['responses'],
            'score_obtained' => $gradeResult['score_obtained'],
            'percentage_score' => $gradeResult['percentage_score'],
            'correct_count' => $gradeResult['correct_count'],
            'question_count' => $gradeResult['question_count'],
            'student_submitted_at' => now(),
            'time_taken_seconds' => $validated['time_taken_seconds'] ?? null,
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Assessment submitted successfully.',
            'submission' => [
                'id' => $submission->id,
                'score' => $submission->score_obtained,
                'percentage_score' => $submission->percentage_score,
                'correct_count' => $submission->correct_count,
                'question_count' => $submission->question_count,
                'submitted_at' => optional($submission->student_submitted_at)?->toIso8601String(),
            ],
        ]);
    }
}
