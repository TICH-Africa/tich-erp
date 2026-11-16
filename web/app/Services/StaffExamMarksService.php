<?php

namespace App\Services;

use App\Models\Staff;
use App\Models\UnitAllocation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StaffExamMarksService
{
    public function __construct(
        protected ContinuousAssessmentService $assessments,
        protected StaffPortalDashboardService $dashboard,
        protected AuditService $auditService,
        protected ExamMarksLockService $locks,
    ) {}

    /**
     * @return array{rows: list<array<string, mixed>>, exam_max: float, weights: array<string, float>, locked: bool, locked_at: string|null}
     */
    public function sheet(UnitAllocation $allocation, Staff $staff): array
    {
        abort_unless($this->dashboard->canMarkUnit($staff, $allocation), 403);

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
        $lock = $this->locks->state((int) $allocation->unit_id, $allocation->semester_id ? (int) $allocation->semester_id : null);

        $existing = DB::table('exam_results')
            ->where('unit_id', $allocation->unit_id)
            ->where('semester_id', $allocation->semester_id)
            // A lecturer marks the sitting the school set, never a supplementary repeat.
            ->where('is_supplementary', 0)
            ->whereIn('student_id', $roster->pluck('student_id'))
            ->get()
            ->keyBy('student_id');

        $rows = [];

        foreach ($roster as $student) {
            $studentId = (int) $student->student_id;
            $continuous = $this->assessments->continuousBreakdown($studentId, $allocation);
            $result = $existing->get($studentId);

            // The continuous figures are the marks on file: every CAT, assignment and practical
            // entered in the assessment table, and the attendance the signed registers worked out.
            // Nothing here is typed any more, so this sheet cannot disagree with that table.
            $catAvg = round($continuous['cat_avg'], 2);
            $practicalAvg = round($continuous['practical_avg'], 2);
            $attendancePct = $continuous['attendance_pct'];
            $cumulative = $this->assessments->cumulativeFrom($catAvg, $practicalAvg, $attendancePct, $allocation->unit);

            $examScore = $result?->final_exam_score === null ? null : (float) $result->final_exam_score;
            $finalTotal = $examScore !== null
                ? $this->assessments->finalScoreWithExam($cumulative, $examScore, $allocation->unit)
                : null;

            $rows[] = [
                'student_id' => $studentId,
                'registration_number' => $student->registration_number,
                'student_name' => trim($student->student_name),
                'cat_total' => $catAvg,
                'practical_total' => $practicalAvg,
                'attendance_pct' => $attendancePct,
                'continuous_total' => $cumulative,
                'exam_score' => $examScore,
                'final_total' => $finalTotal,
                'grade_letter' => $finalTotal !== null
                    ? $this->assessments->gradeLetterForScore($finalTotal, $passMark)
                    : null,
                'is_published' => (bool) ($result?->is_published ?? false),
            ];
        }

        return [
            'rows' => $rows,
            'exam_max' => $examMax,
            'weights' => $weights,
            'locked' => $lock !== null,
            'locked_at' => $lock?->locked_at,
        ];
    }

    /**
     * The exam mark is the only figure entered here. The CAT, practical and attendance averages come
     * from the continuous assessment table and the signed registers, so a sitting is always re-
     * weighted against what those tables actually hold.
     *
     * @param  array<int, mixed>  $examScores
     */
    public function save(
        UnitAllocation $allocation,
        Staff $staff,
        array $examScores,
        float $examMax = 100,
    ): void {
        abort_unless($this->dashboard->canMarkUnit($staff, $allocation), 403);

        $allocation->loadMissing(['semester']);
        $this->locks->assertEditable(
            (int) $allocation->unit_id,
            $allocation->semester_id ? (int) $allocation->semester_id : null,
            'the exam marks',
            (int) ($allocation->semester?->semester_number ?? 1),
        );

        $studentIds = collect($examScores)
            ->filter(fn ($raw) => $raw !== null && $raw !== '')
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        // Own unit is not enough: a lecturer marks the students registered on it, nobody else's.
        $this->dashboard->assertStudentsAreOnRoll($allocation, $studentIds);

        $examMax = max(1, $examMax);
        $passMark = $this->passMarkForAllocation($allocation);
        $saved = 0;

        $existing = DB::table('exam_results')
            ->where('unit_id', $allocation->unit_id)
            ->where('semester_id', $allocation->semester_id)
            ->where('is_supplementary', 0)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->keyBy('student_id');

        foreach ($studentIds as $studentId) {
            $studentId = (int) $studentId;
            $result = $existing->get($studentId);
            $examScore = min(max(0, (float) $examScores[$studentId]), $examMax);

            $derived = $this->assessments->continuousBreakdown($studentId, $allocation);
            $catTotal = round($derived['cat_avg'], 2);
            $practicalTotal = round($derived['practical_avg'], 2);
            $attendancePct = $derived['attendance_pct'];
            $cumulative = $this->assessments->cumulativeFrom($catTotal, $practicalTotal, $attendancePct, $allocation->unit);

            $finalTotal = $this->assessments->finalScoreWithExam($cumulative, $examScore, $allocation->unit);
            $gradeLetter = $this->assessments->gradeLetterForScore($finalTotal, $passMark);
            $examCardId = $this->ensureExamCard($studentId, (int) $allocation->semester_id);

            $match = [
                'student_id' => $studentId,
                'unit_id' => $allocation->unit_id,
                'semester_id' => $allocation->semester_id,
                'is_supplementary' => 0,
            ];

            $payload = [
                'exam_card_id' => $examCardId,
                'cat_total' => $catTotal,
                'practical_total' => $practicalTotal,
                // Any average a lecturer used to type here is cleared, so nothing outside the
                // assessment table can still steer this sitting's grade.
                'cat_avg_entry' => null,
                'practical_avg_entry' => null,
                'attendance_pct_entry' => null,
                'final_exam_score' => $examScore,
                'final_total_score' => $finalTotal,
                'grade_letter' => $gradeLetter,
                'grade_points' => $this->gradePoints($gradeLetter),
                'entered_by' => $staff->id,
                'updated_at' => now(),
            ];

            if ($result) {
                DB::table('exam_results')->where($match)->update($payload);
            } else {
                DB::table('exam_results')->insert([
                    ...$match,
                    ...$payload,
                    'created_at' => now(),
                ]);
            }

            // The grade the school reads follows the sitting it stands by, so a recorded
            // supplementary keeps its pass when the original marks are edited afterwards.
            $this->assessments->recordGrade(
                $studentId,
                (int) $allocation->unit_id,
                (int) $allocation->semester_id,
                $allocation->unit,
                $cumulative,
                $passMark,
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

    /**
     * Every sitting needs an exam card, including one Academics records themselves.
     */
    public function ensureExamCard(int $studentId, int $semesterId): int
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
        return $this->assessments->passMarkForUnit($allocation->unit);
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
