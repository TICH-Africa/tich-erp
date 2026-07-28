<?php

namespace App\Services;

use App\Models\CatScore;
use App\Models\ObjectiveAssessment;
use App\Models\ObjectiveQuestion;
use App\Models\ObjectiveSubmission;
use App\Models\Staff;
use App\Models\UnitAllocation;
use Illuminate\Support\Collection;

class ObjectiveAutoGradingService
{
    public function __construct(
        protected ContinuousAssessmentService $assessments,
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
        abort_unless((int) $allocation->staff_id === (int) $staff->id, 403);
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
        ]);

        $this->syncQuestions($assessment, $questions);

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
    }

    public function runAutoGrade(ObjectiveAssessment $assessment, Staff $staff, UnitAllocation $allocation): int
    {
        abort_unless((int) $assessment->created_by === (int) $staff->id, 403);

        $assessment->load(['questions', 'submissions']);
        $questions = $assessment->questions;
        $totalPoints = (float) $questions->sum('points');
        $graded = 0;

        foreach ($assessment->submissions as $submission) {
            $result = $this->gradeSubmission($questions, $submission->responses ?? [], $totalPoints, (float) $assessment->max_score);

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

            $graded++;
        }

        $assessment->update([
            'status' => 'graded',
            'auto_graded_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assessments->recalculateCumulativeScores($allocation);

        return $graded;
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

    private function answersMatch(ObjectiveQuestion $question, mixed $given): bool
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
