<?php

namespace App\Services;

use App\Models\AcademicProgram;
use App\Models\AttendanceSession;
use App\Models\Staff;
use App\Models\Student;
use App\Models\UnitAllocation;
use Illuminate\Support\Collection;
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

    /**
     * @return Collection<int, object{student_id: int, registration_number: string, student_name: string, attendance_percentage: float, status_flag: string}>
     */
    public function blockedStudentsForSession(AttendanceSession $session): Collection
    {
        $allocation = $session->allocation;
        if (! $allocation) {
            return collect();
        }

        return DB::table('attendance_summaries as a')
            ->join('students as st', 'st.id', '=', 'a.student_id')
            ->leftJoin('applicants as ap', 'ap.id', '=', 'st.application_id')
            ->where('a.unit_id', $allocation->unit_id)
            ->where('a.semester_id', $allocation->semester_id)
            ->where(function ($query) {
                $query->where('a.status_flag', 'red')
                    ->orWhere('a.attendance_percentage', '<', 90);
            })
            ->orderBy('a.attendance_percentage')
            ->select([
                'st.id as student_id',
                'st.registration_number',
                DB::raw("TRIM(CONCAT(COALESCE(ap.first_name,''), ' ', COALESCE(ap.surname,''))) as student_name"),
                'a.attendance_percentage',
                'a.status_flag',
            ])
            ->get();
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
