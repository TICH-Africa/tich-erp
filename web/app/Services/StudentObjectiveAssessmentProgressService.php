<?php

namespace App\Services;

use App\Models\ObjectiveAssessment;
use App\Models\ObjectiveSubmission;
use App\Models\Student;
use Illuminate\Validation\ValidationException;

class StudentObjectiveAssessmentProgressService
{
    public function startOrResume(ObjectiveAssessment $assessment, Student $student): ObjectiveSubmission
    {
        $submission = ObjectiveSubmission::query()->firstOrCreate(
            [
                'objective_assessment_id' => $assessment->id,
                'student_id' => $student->id,
            ],
            [
                'student_started_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        if (! $submission->student_started_at) {
            $submission->update([
                'student_started_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $submission;
    }

    public function remainingSeconds(ObjectiveAssessment $assessment, ObjectiveSubmission $submission): int
    {
        $timeLimit = max(1, (int) ($assessment->time_limit_minutes ?: 30)) * 60;

        if (! $submission->student_started_at) {
            return $timeLimit;
        }

        return max(0, $timeLimit - $this->elapsedSeconds($submission));
    }

    public function elapsedSeconds(ObjectiveSubmission $submission): int
    {
        if (! $submission->student_started_at) {
            return 0;
        }

        return max(0, (int) $submission->student_started_at->diffInSeconds(now(), false));
    }

    /**
     * @param  array<string|int, string|null>  $responses
     */
    public function saveProgress(
        ObjectiveAssessment $assessment,
        Student $student,
        array $responses,
    ): ObjectiveSubmission {
        $submission = ObjectiveSubmission::query()
            ->where('objective_assessment_id', $assessment->id)
            ->where('student_id', $student->id)
            ->first();

        abort_if($submission?->student_submitted_at, 409, 'This assessment has already been submitted.');
        abort_unless($assessment->isOpenFor($submission), 403, 'This assessment is not currently open.');

        $submission = $this->startOrResume($assessment, $student);

        abort_if(
            $this->remainingSeconds($assessment, $submission) <= 0,
            409,
            'The assessment time limit has elapsed. Submit the answers currently saved.',
        );

        $questionIds = $assessment->questions()
            ->pluck('id')
            ->map(fn ($id): string => (string) $id)
            ->all();

        $unknownQuestionIds = array_filter(
            array_keys($responses),
            fn ($questionId): bool => ! in_array((string) $questionId, $questionIds, true),
        );

        if ($unknownQuestionIds !== []) {
            throw ValidationException::withMessages([
                'responses' => 'An answer refers to a question that is not part of this assessment.',
            ]);
        }

        $responses = collect($responses)
            ->mapWithKeys(fn ($answer, $questionId) => [(string) $questionId => $answer])
            ->all();

        $submission->update([
            'responses' => $responses,
            'time_taken_seconds' => $this->elapsedSeconds($submission),
            'updated_at' => now(),
        ]);

        return $submission->fresh();
    }
}
