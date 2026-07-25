<?php

namespace App\Services;

use App\Models\CatScore;
use App\Models\Staff;
use App\Models\Unit;
use App\Models\UnitAllocation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ContinuousAssessmentService
{
    /**
     * @return list<array{key: string, name: string, type: string, max: float, label: string}>
     */
    public function defaultAssessmentColumns(): array
    {
        return [
            ['key' => 'cat_1', 'name' => 'CAT 1', 'type' => 'cat', 'max' => 30, 'label' => 'CAT 1'],
            ['key' => 'cat_2', 'name' => 'CAT 2', 'type' => 'cat', 'max' => 30, 'label' => 'CAT 2'],
            ['key' => 'theory_1', 'name' => 'Theoretical review 1', 'type' => 'theoretical_review', 'max' => 20, 'label' => 'Theory 1'],
            ['key' => 'assign_1', 'name' => 'Assignment 1', 'type' => 'assignment', 'max' => 20, 'label' => 'Assignment 1'],
            ['key' => 'practical_1', 'name' => 'Practical 1', 'type' => 'practical', 'max' => 25, 'label' => 'Practical 1'],
            ['key' => 'skills_1', 'name' => 'Skills lab 1', 'type' => 'skills_lab', 'max' => 25, 'label' => 'Skills lab 1'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function terminalData(UnitAllocation $allocation, Staff $staff): array
    {
        abort_unless((int) $allocation->staff_id === (int) $staff->id, 403);

        $allocation->load(['unit', 'semester.academicYear', 'campus']);
        $roster = app(StaffPortalDashboardService::class)->rosterForAllocation($allocation->id);
        $columns = $this->columnsForAllocation($allocation);
        $scores = $this->scoreMatrix($allocation, $columns);
        $cumulative = $this->cumulativeSheet($allocation);

        return [
            'allocation' => $allocation,
            'columns' => $columns,
            'roster' => $roster,
            'scores' => $scores,
            'cumulative' => $cumulative,
            'weights' => $this->weightProfile($allocation->unit),
            'assessmentTypes' => $this->assessmentTypeLabels(),
        ];
    }

    /**
     * @return list<array{key: string, name: string, type: string, max: float, label: string}>
     */
    public function columnsForAllocation(UnitAllocation $allocation): array
    {
        $existing = DB::table('cat_scores')
            ->where('unit_id', $allocation->unit_id)
            ->where('semester_id', $allocation->semester_id)
            ->select(['assessment_name', 'assessment_type', 'max_score'])
            ->distinct()
            ->orderBy('assessment_name')
            ->get();

        if ($existing->isEmpty()) {
            return $this->defaultAssessmentColumns();
        }

        return $existing->map(function ($row, $index) {
            $name = (string) $row->assessment_name;

            return [
                'key' => 'col_'.$index,
                'name' => $name,
                'type' => (string) $row->assessment_type,
                'max' => (float) $row->max_score,
                'label' => $name,
            ];
        })->values()->all();
    }

    /**
     * @param  list<array{key: string, name: string, type: string, max: float}>  $columns
     * @return array<int, array<string, array{score: ?float, max: float, id: ?int}>>
     */
    public function scoreMatrix(UnitAllocation $allocation, array $columns): array
    {
        $records = CatScore::query()
            ->where('unit_id', $allocation->unit_id)
            ->where('semester_id', $allocation->semester_id)
            ->get()
            ->groupBy('student_id');

        $matrix = [];

        foreach ($records as $studentId => $studentScores) {
            foreach ($columns as $column) {
                $match = $studentScores->first(fn ($score) => $score->assessment_name === $column['name']);
                $matrix[(int) $studentId][$column['key']] = [
                    'score' => $match ? (float) $match->score_obtained : null,
                    'max' => (float) $column['max'],
                    'id' => $match?->id,
                ];
            }
        }

        return $matrix;
    }

    /**
     * @param  array<int, array<string, array{score?: mixed, max?: mixed}>>  $gridScores
     */
    public function saveGrid(UnitAllocation $allocation, Staff $staff, array $columns, array $gridScores): void
    {
        abort_unless((int) $allocation->staff_id === (int) $staff->id, 403);

        foreach ($gridScores as $studentId => $cells) {
            foreach ($columns as $column) {
                $key = $column['key'];
                if (! array_key_exists($key, $cells)) {
                    continue;
                }

                $raw = $cells[$key];
                if ($raw === null || $raw === '') {
                    continue;
                }

                $score = (float) $raw;
                $max = (float) $column['max'];
                $percentage = $max > 0 ? round(($score / $max) * 100, 2) : 0;
                $weight = $this->defaultWeightForType($column['type'], $allocation->unit);

                CatScore::query()->updateOrCreate(
                    [
                        'student_id' => (int) $studentId,
                        'unit_id' => $allocation->unit_id,
                        'semester_id' => $allocation->semester_id,
                        'assessment_name' => $column['name'],
                    ],
                    [
                        'assessment_type' => $column['type'],
                        'max_score' => $max,
                        'score_obtained' => min($score, $max),
                        'percentage_score' => $percentage,
                        'weight_in_final' => $weight,
                        'recorded_by' => $staff->id,
                        'recorded_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        $this->recalculateCumulativeScores($allocation);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function cumulativeSheet(UnitAllocation $allocation): array
    {
        $roster = app(StaffPortalDashboardService::class)->rosterForAllocation($allocation->id);
        $unit = $allocation->unit;
        $weights = $this->weightProfile($unit);
        $rows = [];

        foreach ($roster as $student) {
            $studentId = (int) $student->student_id;
            $scores = CatScore::query()
                ->where('student_id', $studentId)
                ->where('unit_id', $allocation->unit_id)
                ->where('semester_id', $allocation->semester_id)
                ->get();

            $attendancePct = (float) (DB::table('attendance_summaries')
                ->where('student_id', $studentId)
                ->where('unit_id', $allocation->unit_id)
                ->where('semester_id', $allocation->semester_id)
                ->value('attendance_percentage') ?? 0);

            $breakdown = $this->buildWeightedBreakdown($scores, $attendancePct, $weights);
            $cumulative = $breakdown['cumulative'];
            $passMark = (float) (DB::table('academic_programs')
                ->join('students', 'students.program_id', '=', 'academic_programs.id')
                ->where('students.id', $studentId)
                ->value('theory_pass_mark') ?? 40);

            $rows[] = [
                'student_id' => $studentId,
                'registration_number' => $student->registration_number,
                'student_name' => trim($student->student_name),
                'breakdown' => $breakdown,
                'cumulative' => $cumulative,
                'grade_letter' => $this->gradeLetter($cumulative, $passMark),
                'at_risk' => $cumulative < $passMark,
            ];
        }

        return $rows;
    }

    public function recalculateCumulativeScores(UnitAllocation $allocation): void
    {
        $sheet = $this->cumulativeSheet($allocation);

        foreach ($sheet as $row) {
            DB::table('grade_records')->updateOrInsert(
                [
                    'student_id' => $row['student_id'],
                    'unit_id' => $allocation->unit_id,
                    'semester_id' => $allocation->semester_id,
                ],
                [
                    'final_score' => $row['cumulative'],
                    'grade_letter' => $row['grade_letter'],
                    'grade_points' => $this->gradePoints($row['grade_letter']),
                    'credit_hours' => $allocation->unit?->credit_hours ?? 0,
                    'recorded_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    /**
     * @return array<string, float>
     */
    public function weightProfile(?Unit $unit): array
    {
        return [
            'cat' => (float) ($unit?->assessment_weight_cat_pct ?? 30),
            'practical' => (float) ($unit?->assessment_weight_practical_pct ?? 15),
            'attendance' => (float) ($unit?->assessment_weight_attendance_pct ?? 5),
            'exam' => (float) ($unit?->assessment_weight_exam_pct ?? 50),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function assessmentTypeLabels(): array
    {
        return [
            'cat' => 'CAT',
            'theoretical_review' => 'Theoretical review',
            'assignment' => 'Assignment',
            'practical' => 'Practical',
            'skills_lab' => 'Skills lab',
            'project' => 'Project',
            'field_log' => 'Field log',
        ];
    }

    /**
     * @param  Collection<int, CatScore>  $scores
     * @param  array<string, float>  $weights
     * @return array{cumulative: float, cat_avg: float, practical_avg: float, attendance_pct: float, components: array<string, float>}
     */
    private function buildWeightedBreakdown(Collection $scores, float $attendancePct, array $weights): array
    {
        $catTypes = ['cat', 'theoretical_review', 'assignment'];
        $practicalTypes = ['practical', 'skills_lab', 'field_log', 'project'];

        $catAvg = $this->averagePercentage($scores->whereIn('assessment_type', $catTypes));
        $practicalAvg = $this->averagePercentage($scores->whereIn('assessment_type', $practicalTypes));

        $continuousWeight = $weights['cat'] + $weights['practical'] + $weights['attendance'];
        $cumulative = 0.0;

        if ($continuousWeight > 0) {
            $cumulative = (
                ($catAvg * $weights['cat']) +
                ($practicalAvg * $weights['practical']) +
                ($attendancePct * $weights['attendance'])
            ) / $continuousWeight;
        }

        return [
            'cumulative' => round($cumulative, 2),
            'cat_avg' => round($catAvg, 2),
            'practical_avg' => round($practicalAvg, 2),
            'attendance_pct' => round($attendancePct, 2),
            'components' => [
                'cat' => round($catAvg * $weights['cat'] / max($continuousWeight, 1), 2),
                'practical' => round($practicalAvg * $weights['practical'] / max($continuousWeight, 1), 2),
                'attendance' => round($attendancePct * $weights['attendance'] / max($continuousWeight, 1), 2),
            ],
        ];
    }

    /**
     * @param  Collection<int, CatScore>  $scores
     */
    private function averagePercentage(Collection $scores): float
    {
        if ($scores->isEmpty()) {
            return 0.0;
        }

        return (float) $scores->avg(fn (CatScore $score) => (float) $score->percentage_score);
    }

    private function defaultWeightForType(string $type, ?Unit $unit): float
    {
        $weights = $this->weightProfile($unit);
        $catTypes = ['cat', 'theoretical_review', 'assignment'];
        $practicalTypes = ['practical', 'skills_lab', 'field_log', 'project'];

        if (in_array($type, $catTypes, true)) {
            return $weights['cat'] / max(count(array_filter($catTypes)), 1);
        }

        if (in_array($type, $practicalTypes, true)) {
            return $weights['practical'] / max(count(array_filter($practicalTypes)), 1);
        }

        return 0;
    }

    private function gradeLetter(float $score, float $passMark = 40): string
    {
        if ($score >= 70) {
            return 'A';
        }
        if ($score >= 60) {
            return 'B';
        }
        if ($score >= 50) {
            return 'C';
        }
        if ($score >= $passMark) {
            return 'D';
        }

        return 'F';
    }

    private function gradePoints(string $letter): float
    {
        return match ($letter) {
            'A' => 4.0,
            'B' => 3.0,
            'C' => 2.0,
            'D' => 1.0,
            default => 0.0,
        };
    }
}
