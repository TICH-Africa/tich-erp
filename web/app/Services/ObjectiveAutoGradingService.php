<?php

namespace App\Services;

use App\Models\CatScore;
use App\Models\ObjectiveAssessment;
use App\Models\ObjectiveQuestion;
use App\Models\ObjectiveSubmission;
use App\Models\Staff;
use App\Models\UnitAllocation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ObjectiveAutoGradingService
{
    public function __construct(
        protected ContinuousAssessmentService $assessments,
        protected AuditService $auditService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function terminalData(UnitAllocation $allocation): array
    {
        $assessments = ObjectiveAssessment::query()
            ->with(['questions', 'submissions'])
            ->where('unit_allocation_id', $allocation->id)
            ->orderByDesc('created_at')
            ->get();

        $selectedId = request()->integer('objective_assessment') ?: $assessments->first()?->id;
        $selected = $assessments->firstWhere('id', $selectedId);

        $roster = app(StaffPortalDashboardService::class)->rosterForAllocation($allocation->id);
        $responseMatrix = [];

        if ($selected) {
            foreach ($roster as $student) {
                $submission = $selected->submissions->firstWhere('student_id', $student->student_id);
                $responseMatrix[(int) $student->student_id] = $submission?->responses ?? [];
            }
        }

        return [
            'assessments' => $assessments,
            'selected' => $selected,
            'roster' => $roster,
            'response_matrix' => $responseMatrix,
            'question_types' => $this->questionTypeLabels(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $questions
     */
    public function createAssessment(Staff $staff, UnitAllocation $allocation, array $data, array $questions): ObjectiveAssessment
    {
        abort_unless(app(StaffPortalDashboardService::class)->canMarkUnit($staff, $allocation), 403);
        abort_unless($questions !== [], 422, 'Add at least one objective question.');

        $assessment = ObjectiveAssessment::query()->create([
            'unit_allocation_id' => $allocation->id,
            'unit_id' => $allocation->unit_id,
            'semester_id' => $allocation->semester_id,
            'name' => $data['name'],
            'assessment_type' => $data['assessment_type'] ?? 'mcq',
            'max_score' => (float) ($data['max_score'] ?? 100),
            'created_by' => $staff->id,
            'status' => 'ready',
            'created_at' => now(),
            'time_limit_minutes' => $data['time_limit_minutes'] ?? 30,
            'available_from' => $data['available_from'] ? now()->parse($data['available_from']) : null,
            'available_until' => $data['available_until'] ? now()->parse($data['available_until']) : null,
            'show_results_immediately' => (bool) ($data['show_results_immediately'] ?? false),
            'shuffle_questions' => (bool) ($data['shuffle_questions'] ?? false),
            'allow_multiple_attempts' => (bool) ($data['allow_multiple_attempts'] ?? false),
            'max_attempts' => $data['max_attempts'] ?? null,
        ]);

        $this->syncQuestions($assessment, $questions);

        $this->auditService->log(
            'staff.grading.objective_assessment_created',
            'objective_assessments',
            $assessment->id,
            null,
            [
                'name' => $data['name'],
                'assessment_type' => $data['assessment_type'] ?? 'mcq',
                'question_count' => count($questions),
                'unit_allocation_id' => $allocation->id,
            ],
            'Objective assessment created',
            'success',
            $staff->user_id ?? Auth::id(),
        );

        return $assessment->fresh(['questions']);
    }

    /**
     * @param  array<int, array<string, mixed>>  $responsesByStudent  student_id => [question_id => answer]
     */
    public function saveResponses(ObjectiveAssessment $assessment, Staff $staff, array $responsesByStudent): void
    {
        abort_unless((int) $assessment->created_by === (int) $staff->id, 403);

        foreach ($responsesByStudent as $studentId => $responses) {
            if (! is_array($responses)) {
                continue;
            }

            $filtered = array_filter($responses, fn ($value) => $value !== null && $value !== '');

            ObjectiveSubmission::query()->updateOrCreate(
                [
                    'objective_assessment_id' => $assessment->id,
                    'student_id' => (int) $studentId,
                ],
                [
                    'responses' => $filtered,
                    'updated_at' => now(),
                ]
            );
        }

        $this->auditService->log(
            'staff.grading.objective_responses_saved',
            'objective_assessments',
            $assessment->id,
            null,
            ['response_count' => count($responsesByStudent)],
            'Objective assessment responses saved',
            'success',
            $staff->user_id ?? Auth::id(),
        );
    }

    public function runAutoGrade(ObjectiveAssessment $assessment, Staff $staff, UnitAllocation $allocation): int
    {
        abort_unless((int) $assessment->created_by === (int) $staff->id, 403);

        return $this->grade($assessment, $staff, $allocation);
    }

    /**
     * Marks a paper whose window has closed. Runs from the scheduler with no logged-in user,
     * so the actor is the lecturer who created the paper, falling back to the unit's lecturer.
     */
    public function closeAndGrade(ObjectiveAssessment $assessment): int
    {
        $allocation = $assessment->allocation ?? UnitAllocation::query()->find($assessment->unit_allocation_id);
        abort_if(! $allocation, 422, 'The lecturer allocation for this assessment is missing.');

        $staff = Staff::query()->find($assessment->created_by) ?? Staff::query()->find($allocation->staff_id);
        abort_if(! $staff, 422, 'No lecturer is attached to this assessment.');

        return $this->grade($assessment, $staff, $allocation);
    }

    private function grade(ObjectiveAssessment $assessment, Staff $staff, UnitAllocation $allocation): int
    {
        $assessment->load(['questions', 'submissions']);
        $graded = 0;

        foreach ($assessment->submissions as $submission) {
            if (! $submission->student_submitted_at) {
                continue;
            }

            $this->markSubmission($assessment, $submission, $staff, $allocation);
            $graded++;
        }

        $assessment->update([
            'status' => 'graded',
            'auto_graded_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assessments->recalculateCumulativeScores($allocation);

        $this->auditService->log(
            'staff.grading.objective_auto_graded',
            'objective_assessments',
            $assessment->id,
            ['status' => 'ready'],
            ['status' => 'graded', 'students_graded' => $graded],
            'Objective assessment auto-graded',
            'success',
            $staff->user_id ?? Auth::id(),
        );

        return $graded;
    }

    private function markSubmission(
        ObjectiveAssessment $assessment,
        ObjectiveSubmission $submission,
        Staff $staff,
        UnitAllocation $allocation,
    ): void {
        $questions = $assessment->questions;
        $result = $this->gradeSubmission($questions, $submission->responses ?? [], (float) $questions->sum('points'), (float) $assessment->max_score);

        $submission->update([
            'score_obtained' => $result['score_obtained'],
            'percentage_score' => $result['percentage_score'],
            'correct_count' => $result['correct_count'],
            'question_count' => $result['question_count'],
            'auto_graded_at' => now(),
            'updated_at' => now(),
        ]);

        CatScore::query()->updateOrCreate(
            [
                'student_id' => $submission->student_id,
                'unit_id' => $allocation->unit_id,
                'semester_id' => $allocation->semester_id,
                'assessment_name' => $assessment->name,
            ],
            [
                'assessment_type' => 'objective_'.$assessment->assessment_type,
                'max_score' => $assessment->max_score,
                'score_obtained' => $result['score_obtained'],
                'percentage_score' => $result['percentage_score'],
                'weight_in_final' => $this->assessments->weightForAssessmentType('cat', $allocation->unit),
                'recorded_by' => $staff->id,
                'recorded_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Marks one student's make-up paper on its own, since their window can close long after the
     * rest of the class was graded. Nobody else's mark is touched.
     */
    public function gradeLateSubmission(ObjectiveSubmission $submission): bool
    {
        if (! $submission->student_submitted_at || $submission->auto_graded_at) {
            return false;
        }

        $assessment = $submission->assessment ?? ObjectiveAssessment::query()->find($submission->objective_assessment_id);

        if (! $assessment) {
            return false;
        }

        $allocation = $assessment->allocation ?? UnitAllocation::query()->find($assessment->unit_allocation_id);
        $staff = Staff::query()->find($assessment->created_by) ?? Staff::query()->find($allocation?->staff_id ?? 0);

        if (! $allocation || ! $staff) {
            return false;
        }

        $assessment->loadMissing('questions');

        $this->markSubmission($assessment, $submission, $staff, $allocation);
        $this->assessments->recalculateCumulativeScores($allocation);

        $this->auditService->log(
            'staff.grading.objective_makeup_graded',
            'objective_submissions',
            $submission->id,
            null,
            [
                'student_id' => $submission->student_id,
                'objective_assessment_id' => $assessment->id,
                'score_obtained' => $submission->score_obtained,
                'percentage_score' => $submission->percentage_score,
            ],
            'Make-up assessment auto-graded',
            'success',
            $staff->user_id ?? Auth::id(),
        );

        return true;
    }

    /**
     * Opens the paper for one student on their own dates, so a missed sitting can still be taken -
     * and a rescheduled one can be given a fresh copy of the questions.
     */
    public function openMakeupWindow(
        ObjectiveAssessment $assessment,
        Staff $staff,
        UnitAllocation $allocation,
        int $studentId,
        ?string $from,
        ?string $until,
        bool $reshuffle,
        bool $retake = false,
    ): ObjectiveSubmission {
        $this->assertMakeupAccess($staff, $allocation, $assessment, $studentId);

        $submission = ObjectiveSubmission::query()->updateOrCreate(
            [
                'objective_assessment_id' => $assessment->id,
                'student_id' => $studentId,
            ],
            [
                'open_from' => $from ? now()->parse($from) : null,
                'open_until' => $until ? now()->parse($until) : null,
                'updated_at' => now(),
            ],
        );

        if ($retake && $submission->student_submitted_at) {
            // A rescheduled sitting replaces the attempt on file, so the old mark cannot sit
            // beside the new one in the CAT grid.
            $submission->update([
                'responses' => null,
                'student_submitted_at' => null,
                'time_taken_seconds' => null,
                'score_obtained' => null,
                'percentage_score' => null,
                'correct_count' => 0,
                'question_count' => 0,
                'auto_graded_at' => null,
                'attempt_number' => (int) $submission->attempt_number + 1,
                'updated_at' => now(),
            ]);

            CatScore::query()
                ->where('student_id', $studentId)
                ->where('unit_id', $allocation->unit_id)
                ->where('semester_id', $allocation->semester_id)
                ->where('assessment_name', $assessment->name)
                ->delete();

            $this->assessments->recalculateCumulativeScores($allocation);
        }

        if ($reshuffle) {
            $assessment->drawShuffleSeed($submission, force: true);
        }

        $this->auditService->log(
            'staff.grading.objective_makeup_opened',
            'objective_assessments',
            $assessment->id,
            null,
            [
                'student_id' => $studentId,
                'open_from' => $from,
                'open_until' => $until,
                'reshuffled' => $reshuffle,
                'retake' => $retake,
            ],
            'Make-up assessment opened',
            'success',
            $staff->user_id ?? Auth::id(),
        );

        return $submission;
    }

    public function closeMakeupWindow(
        ObjectiveAssessment $assessment,
        Staff $staff,
        UnitAllocation $allocation,
        int $studentId,
    ): void {
        $this->assertMakeupAccess($staff, $allocation, $assessment, $studentId);

        ObjectiveSubmission::query()
            ->where('objective_assessment_id', $assessment->id)
            ->where('student_id', $studentId)
            ->update(['open_from' => null, 'open_until' => null, 'updated_at' => now()]);

        $this->auditService->log(
            'staff.grading.objective_makeup_closed',
            'objective_assessments',
            $assessment->id,
            null,
            ['student_id' => $studentId],
            'Make-up assessment closed',
            'success',
            $staff->user_id ?? Auth::id(),
        );
    }

    private function assertMakeupAccess(Staff $staff, UnitAllocation $allocation, ObjectiveAssessment $assessment, int $studentId): void
    {
        abort_unless(app(StaffPortalDashboardService::class)->canMarkUnit($staff, $allocation), 403);
        abort_unless((int) $assessment->unit_allocation_id === (int) $allocation->id, 403);
        app(StaffPortalDashboardService::class)->assertStudentsAreOnRoll($allocation, [$studentId]);
    }

    /**
     * @param  Collection<int, ObjectiveQuestion>  $questions
     * @param  array<string|int, mixed>  $responses
     * @return array{score_obtained: float, percentage_score: float, correct_count: int, question_count: int}
     */
    public function gradeSubmission(Collection $questions, array $responses, float $totalPoints, float $maxScore): array
    {
        $earned = 0.0;
        $correct = 0;

        foreach ($questions as $question) {
            $answer = $responses[(string) $question->id] ?? $responses[$question->id] ?? null;
            if ($this->answersMatch($question, $answer)) {
                $earned += (float) $question->points;
                $correct++;
            }
        }

        $ratio = $totalPoints > 0 ? $earned / $totalPoints : 0;
        $score = round($ratio * $maxScore, 2);

        return [
            'score_obtained' => $score,
            'percentage_score' => round($ratio * 100, 2),
            'correct_count' => $correct,
            'question_count' => $questions->count(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function questionTypeLabels(): array
    {
        return [
            'mcq' => 'Multiple choice',
            'true_false' => 'True / False',
            'matching' => 'Matching pairs',
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $questions
     */
    private function syncQuestions(ObjectiveAssessment $assessment, array $questions): void
    {
        ObjectiveQuestion::query()->where('objective_assessment_id', $assessment->id)->delete();

        $sort = 0;
        foreach ($questions as $row) {
            if (empty(trim((string) ($row['question_text'] ?? '')))) {
                continue;
            }
            $sort++;
            $options = null;
            if (! empty($row['options'])) {
                $options = is_array($row['options'])
                    ? $row['options']
                    : array_values(array_filter(array_map('trim', explode('|', (string) $row['options']))));
            }

            ObjectiveQuestion::query()->create([
                'objective_assessment_id' => $assessment->id,
                'sort_order' => $sort,
                'question_text' => $row['question_text'],
                'question_type' => $row['question_type'] ?? $assessment->assessment_type,
                'options' => $options,
                'correct_answer' => (string) ($row['correct_answer'] ?? ''),
                'points' => (float) ($row['points'] ?? 1),
            ]);
        }
    }

    public function answersMatch(ObjectiveQuestion $question, mixed $given): bool
    {
        if ($given === null || $given === '') {
            return false;
        }

        $expected = $this->normalizeAnswer($question->correct_answer, $question->question_type);
        $actual = $this->normalizeAnswer((string) $given, $question->question_type);

        if ($question->question_type === 'matching') {
            $expectedMap = json_decode($question->correct_answer, true);
            $actualMap = is_array($given) ? $given : json_decode((string) $given, true);

            if (! is_array($expectedMap) || ! is_array($actualMap)) {
                return false;
            }

            foreach ($expectedMap as $left => $right) {
                if (($actualMap[$left] ?? null) !== $right) {
                    return false;
                }
            }

            return count($expectedMap) === count($actualMap);
        }

        return $expected === $actual;
    }

    private function normalizeAnswer(string $value, string $type): string
    {
        $value = trim(strtolower($value));

        if ($type === 'true_false') {
            return in_array($value, ['1', 'true', 't', 'yes'], true) ? 'true' : 'false';
        }

        return $value;
    }
}
