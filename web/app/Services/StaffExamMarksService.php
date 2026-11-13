<?php

namespace App\Services;

use App\Models\Staff;
use App\Models\Student;
use App\Models\UnitAllocation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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

            // What the lecturer typed stands; otherwise the marks the system holds.
            $catAvg = $this->standingOr($result?->cat_avg_entry, $continuous['cat_avg']);
            $practicalAvg = $this->standingOr($result?->practical_avg_entry, $continuous['practical_avg']);
            $attendancePct = $this->standingOr($result?->attendance_pct_entry, $continuous['attendance_pct']);
            $cumulative = $this->assessments->cumulativeFrom($catAvg, $practicalAvg, $attendancePct, $allocation->unit);

            $examScore = $result ? (float) $result->final_exam_score : null;
            $finalTotal = $result
                ? (float) $result->final_total_score
                : ($examScore !== null
                    ? $this->assessments->finalScoreWithExam($cumulative, $examScore, $allocation->unit)
                    : null);

            $rows[] = [
                'student_id' => $studentId,
                'registration_number' => $student->registration_number,
                'student_name' => trim($student->student_name),
                'cat_total' => $catAvg,
                'practical_total' => $practicalAvg,
                'attendance_pct' => $attendancePct,
                'continuous_total' => $cumulative,
                'cat_entered' => $result !== null && $result->cat_avg_entry !== null,
                'practical_entered' => $result !== null && $result->practical_avg_entry !== null,
                'attendance_entered' => $result !== null && $result->attendance_pct_entry !== null,
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
     * @param  array<string, array<int, mixed>>  $continuous  typed cat, practical and attendance averages
     */
    public function save(
        UnitAllocation $allocation,
        Staff $staff,
        array $examScores,
        float $examMax = 100,
        array $continuous = [],
    ): void {
        abort_unless($this->dashboard->canMarkUnit($staff, $allocation), 403);

        $entries = [
            'cat' => $continuous['cat'] ?? [],
            'practical' => $continuous['practical'] ?? [],
            'attendance' => $continuous['attendance'] ?? [],
        ];

        $filled = fn (array $values) => collect($values)
            ->filter(fn ($raw) => $raw !== null && $raw !== '')
            ->keys()
            ->map(fn ($id) => (int) $id);

        $studentIds = $filled($examScores)
            ->merge($filled($entries['cat']))
            ->merge($filled($entries['practical']))
            ->merge($filled($entries['attendance']))
            ->unique();

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

        // A typed average is part of a sitting, so it cannot stand on its own: without an
        // exam mark there is no sitting to attach it to, and inventing one would publish a
        // grade for a student who has not been marked.
        $unattached = $studentIds->filter(function (int $studentId) use ($examScores, $existing) {
            $exam = $examScores[$studentId] ?? null;

            return ($exam === null || $exam === '') && ! $existing->has($studentId);
        });

        if ($unattached->isNotEmpty()) {
            $names = Student::query()->whereIn('id', $unattached)->pluck('registration_number')->take(5)->implode(', ');

            throw ValidationException::withMessages([
                'exam_scores' => 'Enter the exam mark for '.$names.' too. A CAT, practical or attendance average is saved with the sitting, never on its own.',
            ]);
        }

        foreach ($studentIds as $studentId) {
            $studentId = (int) $studentId;
            $result = $existing->get($studentId);
            $rawExam = $examScores[$studentId] ?? null;
            $examScore = ($rawExam === null || $rawExam === '')
                ? ($result ? (float) $result->final_exam_score : null)
                : min(max(0, (float) $rawExam), $examMax);

            if ($examScore === null) {
                continue;
            }

            $derived = $this->assessments->continuousBreakdown($studentId, $allocation);
            $catTotal = $this->standingOr($entries['cat'][$studentId] ?? null, $derived['cat_avg']);
            $practicalTotal = $this->standingOr($entries['practical'][$studentId] ?? null, $derived['practical_avg']);
            $attendancePct = $this->standingOr($entries['attendance'][$studentId] ?? null, $derived['attendance_pct']);
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
                'cat_avg_entry' => $this->entryOrNull($entries['cat'][$studentId] ?? null),
                'practical_avg_entry' => $this->entryOrNull($entries['practical'][$studentId] ?? null),
                'attendance_pct_entry' => $this->entryOrNull($entries['attendance'][$studentId] ?? null),
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

    /** A figure the lecturer typed, or the one the system worked out. */
    private function standingOr(mixed $entry, float $derived): float
    {
        return $this->entryOrNull($entry) ?? round($derived, 2);
    }

    private function entryOrNull(mixed $entry): ?float
    {
        return $entry === null || $entry === '' ? null : round((float) $entry, 2);
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
