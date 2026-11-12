<?php

namespace App\Http\Controllers\Api\Student\mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Student\mobile\Concerns\ResolvesPortalStudent;
use App\Models\ObjectiveAssessment;
use App\Models\ObjectiveSubmission;
use App\Models\Student;
use App\Services\StudentAcademicRecordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssessmentsController extends Controller
{
    use ResolvesPortalStudent;

    public function show(Request $request, ObjectiveAssessment $assessment): JsonResponse
    {
        $student = $this->portalStudent($request);
        $assessment->load(['allocation.unit']);

        $submission = ObjectiveSubmission::query()
            ->where('objective_assessment_id', $assessment->id)
            ->where('student_id', $student->id)
            ->first();

        $this->assertRegistered($student, $assessment, $submission);

        $alreadySubmitted = $submission && $submission->student_submitted_at;
        $canTake = $assessment->isOpenFor($submission) && ! $alreadySubmitted;
        $window = $assessment->windowFor($submission);

        // The app reads the window it is given, so a make-up sitting shows as this student's dates.
        if ($canTake) {
            $submission ??= ObjectiveSubmission::query()->create([
                'objective_assessment_id' => $assessment->id,
                'student_id' => $student->id,
                'created_at' => now(),
            ]);

            $assessment->drawShuffleSeed($submission);
        }

        $questions = $assessment->questionsFor($submission);
        $marked = $assessment->isMarkedFor($submission);

        return response()->json([
            'assessment' => [
                'id' => $assessment->id,
                'title' => $assessment->name,
                'status' => $assessment->status,
                'max_score' => (float) $assessment->max_score,
                'time_limit_minutes' => $assessment->time_limit_minutes,
                'available_from' => optional($window['from'])?->toIso8601String(),
                'available_until' => optional($window['until'])?->toIso8601String(),
                'is_makeup' => $window['makeup'],
                'unit_code' => $assessment->allocation?->unit?->unit_code,
                'unit_name' => $assessment->allocation?->unit?->unit_name,
                'can_take' => $canTake,
                'questions' => $questions->map(fn ($q) => [
                    'id' => $q->id,
                    'sort_order' => $q->sort_order,
                    'question_text' => $q->question_text,
                    'question_type' => $q->question_type,
                    'options' => $assessment->optionsFor($q, $submission),
                    'points' => (float) $q->points,
                ])->values(),
            ],
            'submission' => $submission ? [
                'id' => $submission->id,
                'score' => $marked ? $submission->score_obtained : null,
                'percentage_score' => $marked ? $submission->percentage_score : null,
                'correct_count' => $marked ? $submission->correct_count : null,
                'question_count' => $marked ? $submission->question_count : null,
                'marked' => $marked,
                'can_review' => $marked && (bool) $assessment->show_results_immediately,
                'submitted_at' => optional($submission->student_submitted_at)?->toIso8601String(),
            ] : null,
        ]);
    }

    public function submit(Request $request, ObjectiveAssessment $assessment): JsonResponse
    {
        $student = $this->portalStudent($request);

        $submission = ObjectiveSubmission::query()
            ->where('objective_assessment_id', $assessment->id)
            ->where('student_id', $student->id)
            ->first();

        $this->assertRegistered($student, $assessment, $submission);

        if ($submission && $submission->student_submitted_at) {
            $marked = $assessment->isMarkedFor($submission);

            return response()->json([
                'message' => 'You have already completed this assessment.',
                'submission' => [
                    'id' => $submission->id,
                    'score' => $marked ? $submission->score_obtained : null,
                    'percentage_score' => $marked ? $submission->percentage_score : null,
                    'marked' => $marked,
                    'can_review' => $marked && (bool) $assessment->show_results_immediately,
                    'submitted_at' => optional($submission->student_submitted_at)?->toIso8601String(),
                ],
            ], 422);
        }

        if (! $assessment->isOpenFor($submission)) {
            $window = $assessment->windowFor($submission);

            return response()->json([
                'message' => $window['from'] && now()->lt($window['from'])
                    ? 'This assessment is not yet available.'
                    : 'This assessment has expired. Ask your lecturer to open a make-up sitting for you.',
            ], 403);
        }

        $validated = $request->validate([
            'responses' => 'required|array',
            'responses.*' => 'nullable|string',
            'time_taken_seconds' => 'nullable|integer|min:0',
        ]);

        // Marking waits for the availability window to close, so the answer key stays safe.
        if (! $submission) {
            $submission = ObjectiveSubmission::query()->create([
                'objective_assessment_id' => $assessment->id,
                'student_id' => $student->id,
            ]);
        }

        $submission->update([
            'responses' => $validated['responses'],
            'student_submitted_at' => now(),
            'time_taken_seconds' => $validated['time_taken_seconds'] ?? null,
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Submitted successfully on '.now()->format('d M Y \a\t H:i').'. Your paper is marked when the assessment closes.',
            'submission' => [
                'id' => $submission->id,
                'score' => null,
                'percentage_score' => null,
                'correct_count' => null,
                'question_count' => null,
                'marked' => false,
                'can_review' => false,
                'submitted_at' => optional($submission->student_submitted_at)?->toIso8601String(),
            ],
        ]);
    }

    /**
     * An assessment belongs to a unit, and a student only works on units they are registered for.
     * Anyone who has already answered keeps access, even if Academics later un-assigns the unit.
     */
    private function assertRegistered(Student $student, ObjectiveAssessment $assessment, ?ObjectiveSubmission $submission): void
    {
        $held = app(StudentAcademicRecordService::class)->registeredUnitIds($student);

        abort_unless(
            in_array((int) ($assessment->allocation?->unit_id ?? 0), $held, true) || $submission !== null,
            403,
            'You are not registered on this unit yet. Contact your HOD.'
        );
    }
}
