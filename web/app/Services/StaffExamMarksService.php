<?php

namespace App\Services;

use App\Models\Staff;
use App\Models\UnitAllocation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StaffExamMarksService
{
    public function __construct(
        protected ContinuousAssessmentService $assessments,
        protected StaffPortalDashboardService $dashboard,
        protected AuditService $auditService,
    ) {}

    /**
     * @return array{rows: list<array<string, mixed>>, exam_max: float, weights: array<string, float>}
     */
    public function sheet(UnitAllocation $allocation, Staff $staff): array
    {
        abort_unless((int) $allocation->staff_id === (int) $staff->id, 403);

        $allocation->load(['unit', 'semester']);
        $programId = $allocation->unit?->program_id;
        $roster = $this->dashboard->rosterForAllocation(
            $allocation->id,
            $programId ? (int) $programId : null,
            $allocation->semester?->semester_number,
        );

        $weights = $this->assessments->weightProfile($allocation->unit);
        $examMax = 100.0;
        $passMark = $this->passMarkForAllocation($allocation);

        $existing = DB::table('exam_results')
            ->where('unit_id', $allocation->unit_id)
            ->where('semester_id', $allocation->semester_id)
            ->whereIn('student_id', $roster->pluck('student_id'))
            ->get()
            ->keyBy('student_id');

        $rows = [];

        foreach ($roster as $student) {
            $studentId = (int) $student->student_id;
            $continuous = $this->assessments->continuousBreakdown($studentId, $allocation);
            $result = $existing->get($studentId);

            $examScore = $result ? (float) $result->final_exam_score : null;
            $finalTotal = $result
                ? (float) $result->final_total_score
                : ($examScore !== null
                    ? $this->assessments->finalScoreWithExam($continuous['cumulative'], $examScore, $allocation->unit)
                    : null);

            $rows[] = [
                'student_id' => $studentId,
                'registration_number' => $student->registration_number,
                'student_name' => trim($student->student_name),
                'cat_total' => $continuous['cat_avg'],
                'practical_total' => $continuous['practical_avg'],
                'attendance_pct' => $continuous['attendance_pct'],
                'continuous_total' => $continuous['cumulative'],
                'exam_score' => $examScore,
                'final_total' => $finalTotal,
                'grade_letter' => $result?->grade_letter
                    ?? ($finalTotal !== null ? $this->assessments->gradeLetterForScore($finalTotal, $passMark) : null),
                'is_published' => (bool) ($result?->is_published ?? false),
            ];
        }

        return [
            'rows' => $rows,
            'exam_max' => $examMax,
            'weights' => $weights,
        ];
    }

    /**
     * @param  array<int, mixed>  $examScores
     */
    public function save(UnitAllocation $allocation, Staff $staff, array $examScores, float $examMax = 100): void
    {
        abort_unless((int) $allocation->staff_id === (int) $staff->id, 403);

        $examMax = max(1, $examMax);
        $passMark = $this->passMarkForAllocation($allocation);
        $saved = 0;

        foreach ($examScores as $studentId => $raw) {
            if ($raw === null || $raw === '') {
                continue;
            }

            $studentId = (int) $studentId;
            $examScore = min(max(0, (float) $raw), $examMax);
            $continuous = $this->assessments->continuousBreakdown($studentId, $allocation);
            $catTotal = $continuous['cat_avg'];
            $practicalTotal = $continuous['practical_avg'];
            $finalTotal = $this->assessments->finalScoreWithExam(
                $continuous['cumulative'],
                $examScore,
                $allocation->unit,
            );
            $gradeLetter = $this->assessments->gradeLetterForScore($finalTotal, $passMark);
            $examCardId = $this->ensureExamCard($studentId, (int) $allocation->semester_id);

            $match = [
                'student_id' => $studentId,
                'unit_id' => $allocation->unit_id,
                'semester_id' => $allocation->semester_id,
            ];

            $payload = [
                'exam_card_id' => $examCardId,
                'cat_total' => $catTotal,
                'practical_total' => $practicalTotal,
                'final_exam_score' => $examScore,
                'final_total_score' => $finalTotal,
                'grade_letter' => $gradeLetter,
                'grade_points' => $this->gradePoints($gradeLetter),
                'entered_by' => $staff->id,
                'updated_at' => now(),
            ];

            if (DB::table('exam_results')->where($match)->exists()) {
                DB::table('exam_results')->where($match)->update($payload);
            } else {
                DB::table('exam_results')->insert([
                    ...$match,
                    ...$payload,
                    'created_at' => now(),
                ]);
            }

            DB::table('grade_records')->updateOrInsert(
                [
                    'student_id' => $studentId,
                    'unit_id' => $allocation->unit_id,
                    'semester_id' => $allocation->semester_id,
                ],
                [
                    'final_score' => $finalTotal,
                    'grade_letter' => $gradeLetter,
                    'grade_points' => $this->gradePoints($gradeLetter),
                    'credit_hours' => $allocation->unit?->credit_hours ?? 0,
                    'recorded_at' => now(),
                    'created_at' => now(),
                ]
            );

            $saved++;
        }

        $this->auditService->log(
            'staff.grading.exam_marks_saved',
            'unit_allocations',
            $allocation->id,
            null,
            [
                'unit_id' => $allocation->unit_id,
                'semester_id' => $allocation->semester_id,
                'students_updated' => $saved,
            ],
            'Exam marks saved',
            'success',
            $staff->user_id ?? Auth::id(),
        );
    }

    private function ensureExamCard(int $studentId, int $semesterId): int
    {
        $existing = DB::table('exam_cards')
            ->where('student_id', $studentId)
            ->where('semester_id', $semesterId)
            ->value('id');

        if ($existing) {
            return (int) $existing;
        }

        return (int) DB::table('exam_cards')->insertGetId([
            'exam_card_number' => 'EC-'.now()->format('Ymd').'-'.strtoupper(Str::random(4)),
            'student_id' => $studentId,
            'semester_id' => $semesterId,
            'issued_at' => now(),
        ]);
    }

    private function passMarkForAllocation(UnitAllocation $allocation): float
    {
        return (float) (DB::table('academic_programs')
            ->join('units', 'units.program_id', '=', 'academic_programs.id')
            ->where('units.id', $allocation->unit_id)
            ->value('theory_pass_mark') ?? 40);
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
