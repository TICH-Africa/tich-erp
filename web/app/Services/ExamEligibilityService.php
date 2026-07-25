<?php

namespace App\Services;

use App\Models\AcademicProgram;
use App\Models\AttendanceSession;
use App\Models\Staff;
use App\Models\Student;
use App\Models\UnitAllocation;
use Illuminate\Support\Facades\DB;

class ExamEligibilityService
{
    public function __construct(protected AcademicsIntegrationRegistry $academicsRegistry) {}

    public function syncForStudentUnit(int $studentId, int $unitId, int $semesterId, float $attendancePercentage, string $statusFlag): void
    {
        $student = Student::query()->find($studentId);
        if (! $student) {
            return;
        }

        $threshold = $this->academicsRegistry->examEligibilityThreshold((int) $student->program_id);
        $attendancePassed = $attendancePercentage >= $threshold ? 1 : 0;
        $feeCleared = $this->studentFeesCleared($studentId, $semesterId);
        $examBlocked = $statusFlag === 'red' ? 1 : 0;
        $eligible = $attendancePassed && $feeCleared && ! $examBlocked;

        DB::table('exam_eligibility_matrix')->updateOrInsert(
            [
                'student_id' => $studentId,
                'unit_id' => $unitId,
                'semester_id' => $semesterId,
            ],
            [
                'attendance_percentage' => $attendancePercentage,
                'attendance_check_passed' => $attendancePassed,
                'fee_clearance_check_passed' => $feeCleared ? 1 : 0,
                'eligible_for_exams' => $eligible ? 1 : 0,
                'calculated_at' => now(),
                'created_at' => now(),
            ]
        );

        DB::table('attendance_summaries')
            ->where('student_id', $studentId)
            ->where('unit_id', $unitId)
            ->where('semester_id', $semesterId)
            ->update(['exam_eligibility_blocked' => $examBlocked]);
    }

    private function studentFeesCleared(int $studentId, int $semesterId): bool
    {
        $registration = DB::table('student_semester_registrations')
            ->where('student_id', $studentId)
            ->where('semester_id', $semesterId)
            ->first();

        if ($registration) {
            return (bool) $registration->is_fee_cleared;
        }

        $student = Student::query()->find($studentId);

        return $student && $student->fee_clearance_status === 'cleared';
    }
}
