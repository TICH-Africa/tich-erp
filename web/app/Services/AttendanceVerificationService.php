<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Department;
use App\Models\Staff;
use App\Models\Student;
use App\Models\UnitAllocation;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AttendanceVerificationService
{
    public const FLAG_GREEN = 'green';

    public const FLAG_AMBER = 'amber';

    public const FLAG_RED = 'red';

    public function __construct(
        protected ExamEligibilityService $examEligibility,
        protected PlatformNotificationService $notifications,
        protected AcademicsIntegrationRegistry $academicsRegistry,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public static function riskMatrix(): array
    {
        return [
            [
                'bound' => '90% and above',
                'flag' => self::FLAG_GREEN,
                'label' => 'GREEN: COMPLIANT',
                'action' => 'Clears the student for exam card generation once fees are fully paid.',
            ],
            [
                'bound' => '75% – 89%',
                'flag' => self::FLAG_AMBER,
                'label' => 'AMBER: AT RISK',
                'action' => 'Automated warning to the Student Portal, assigned tutor, and HOD panel.',
            ],
            [
                'bound' => '74% and below',
                'flag' => self::FLAG_RED,
                'label' => 'RED: ELIGIBILITY BLOCK',
                'action' => 'Locks the student out of the Exam Sitting Gatekeeper module.',
            ],
        ];
    }

    public static function statusFlag(float $percentage): string
    {
        if ($percentage >= 90) {
            return self::FLAG_GREEN;
        }

        if ($percentage >= 75) {
            return self::FLAG_AMBER;
        }

        return self::FLAG_RED;
    }

    public static function flagLabel(string $flag): string
    {
        return match ($flag) {
            self::FLAG_GREEN => 'GREEN: COMPLIANT',
            self::FLAG_AMBER => 'AMBER: AT RISK',
            self::FLAG_RED => 'RED: ELIGIBILITY BLOCK',
            default => strtoupper($flag),
        };
    }

    public function sheetData(AttendanceSession $session): array
    {
        $session->load([
            'allocation.unit',
            'allocation.semester.academicYear',
            'allocation.staff',
            'allocation.campus',
            'records.student.applicant',
            'recordedByStaff',
        ]);

        $allocation = $session->allocation;

        return [
            'session' => $session,
            'allocation' => $allocation,
            'unit' => $allocation?->unit,
            'tutor' => $allocation?->staff,
            'records' => $session->records->sortBy(fn ($r) => $r->student?->registration_number),
            'tracking_id' => $session->session_number,
        ];
    }

    public function uploadSignedSheet(AttendanceSession $session, Staff $staff, UploadedFile $file): AttendanceSession
    {
        abort_if($session->is_locked, 422, 'This session is locked and cannot be modified.');
        abort_unless((int) $session->recorded_by === (int) $staff->id, 403);

        $path = $file->store('attendance-sheets', 'public');
        $hash = hash_file('sha256', Storage::disk('public')->path($path));

        $session->update([
            'signed_sheet_image_path' => $path,
            'sheet_image_hash' => $hash,
        ]);

        return $session->fresh();
    }

    public function submitSession(AttendanceSession $session, Staff $staff): AttendanceSession
    {
        abort_if($session->is_locked, 422, 'This session is already submitted.');
        abort_unless((int) $session->recorded_by === (int) $staff->id, 403);
        abort_unless($session->signed_sheet_image_path, 422, 'Upload a photo of the signed attendance sheet before submitting.');

        $session->update([
            'is_locked' => 1,
            'verification_status' => 'submitted',
            'submitted_at' => now(),
        ]);

        return $session->fresh();
    }

    public function verifyAsHod(AttendanceSession $session, Staff $staff): AttendanceSession
    {
        abort_unless($session->verification_status === 'submitted', 422, 'Session must be submitted by the tutor first.');

        $session->update([
            'verification_status' => 'hod_verified',
            'hod_verified_by' => $staff->id,
            'hod_verified_at' => now(),
        ]);

        return $session->fresh();
    }

    public function verifyAsRegistrar(AttendanceSession $session, Staff $staff): AttendanceSession
    {
        abort_unless(in_array($session->verification_status, ['submitted', 'hod_verified'], true), 422, 'Session is not ready for registrar verification.');

        $session->update([
            'verification_status' => 'registrar_verified',
            'registrar_verified_by' => $staff->id,
            'registrar_verified_at' => now(),
        ]);

        return $session->fresh();
    }

    public function recalculateSummaries(AttendanceSession $session): void
    {
        $allocation = $session->allocation()->with(['unit', 'staff'])->first();
        if (! $allocation) {
            return;
        }

        $sessionIds = AttendanceSession::query()
            ->where('unit_allocation_id', $allocation->id)
            ->where('is_locked', 1)
            ->pluck('id');

        $records = AttendanceRecord::query()
            ->whereIn('session_id', $sessionIds)
            ->get()
            ->groupBy('student_id');

        foreach ($records as $studentId => $studentRecords) {
            $total = $studentRecords->count();
            $present = $studentRecords->where('is_present', true)->count();
            $percentage = $total > 0 ? round(($present / $total) * 100, 2) : 0;
            $flag = self::statusFlag($percentage);

            $existing = DB::table('attendance_summaries')
                ->where('student_id', $studentId)
                ->where('unit_id', $allocation->unit_id)
                ->where('semester_id', $allocation->semester_id)
                ->first();

            DB::table('attendance_summaries')->updateOrInsert(
                [
                    'student_id' => $studentId,
                    'unit_id' => $allocation->unit_id,
                    'semester_id' => $allocation->semester_id,
                ],
                [
                    'total_sessions' => $total,
                    'total_present' => $present,
                    'attendance_percentage' => $percentage,
                    'status_flag' => $flag,
                    'last_calculated_at' => now(),
                ]
            );

            $this->examEligibility->syncForStudentUnit(
                (int) $studentId,
                (int) $allocation->unit_id,
                (int) $allocation->semester_id,
                $percentage,
                $flag,
            );

            $this->dispatchFlagNotifications(
                (int) $studentId,
                $allocation,
                $percentage,
                $flag,
                $existing,
            );
        }
    }

    /**
     * @return Collection<int, object>
     */
    public function ledgerForDepartment(int $departmentId, ?string $status = null): Collection
    {
        $query = DB::table('attendance_sessions as s')
            ->join('unit_allocations as ua', 'ua.id', '=', 's.unit_allocation_id')
            ->join('units as u', 'u.id', '=', 'ua.unit_id')
            ->join('staff as tutor', 'tutor.id', '=', 's.recorded_by')
            ->leftJoin('staff as hod', 'hod.id', '=', 's.hod_verified_by')
            ->where('u.department_id', $departmentId)
            ->where('s.is_locked', 1)
            ->orderByDesc('s.submitted_at')
            ->select([
                's.*',
                'u.unit_code',
                'u.unit_name',
                'tutor.first_name as tutor_first_name',
                'tutor.surname as tutor_surname',
                'hod.first_name as hod_first_name',
                'hod.surname as hod_surname',
            ]);

        if ($status) {
            $query->where('s.verification_status', $status);
        }

        return $query->get();
    }

    private function dispatchFlagNotifications(
        int $studentId,
        UnitAllocation $allocation,
        float $percentage,
        string $flag,
        ?object $existing,
    ): void {
        if ($flag === self::FLAG_GREEN) {
            return;
        }

        $summary = DB::table('attendance_summaries')
            ->where('student_id', $studentId)
            ->where('unit_id', $allocation->unit_id)
            ->where('semester_id', $allocation->semester_id)
            ->first();

        if ($flag === self::FLAG_AMBER) {
            if ($summary?->amber_alert_sent_at && $existing?->status_flag === self::FLAG_AMBER) {
                return;
            }

            DB::table('attendance_summaries')
                ->where('student_id', $studentId)
                ->where('unit_id', $allocation->unit_id)
                ->where('semester_id', $allocation->semester_id)
                ->update(['amber_alert_sent_at' => now()]);
        }

        if ($flag === self::FLAG_RED) {
            if ($summary?->red_alert_sent_at && $existing?->status_flag === self::FLAG_RED) {
                return;
            }

            DB::table('attendance_summaries')
                ->where('student_id', $studentId)
                ->where('unit_id', $allocation->unit_id)
                ->where('semester_id', $allocation->semester_id)
                ->update(['red_alert_sent_at' => now()]);
        }

        $student = Student::query()->with('applicant')->find($studentId);
        $unit = $allocation->unit;
        $unitLabel = $unit ? "{$unit->unit_code} - {$unit->unit_name}" : 'your unit';
        $flagLabel = self::flagLabel($flag);

        $title = $flag === self::FLAG_RED
            ? 'Exam eligibility blocked'
            : 'Attendance warning';

        $body = $flag === self::FLAG_RED
            ? "Your attendance for {$unitLabel} is {$percentage}% ({$flagLabel}). You are blocked from exam sitting until this is resolved with your tutor and HOD."
            : "Your attendance for {$unitLabel} is {$percentage}% ({$flagLabel}). Please improve participation to remain eligible for exams.";

        $recipients = [];

        if ($student?->user_id) {
            $recipients[] = (int) $student->user_id;
        }

        if ($allocation->staff) {
            $tutorUserId = $this->staffUserId($allocation->staff);
            if ($tutorUserId) {
                $recipients[] = $tutorUserId;
            }
        }

        $department = Department::query()->find($unit?->department_id);
        if ($department?->hod_id) {
            $hodStaff = Staff::query()->find($department->hod_id);
            $hodUserId = $this->staffUserId($hodStaff);
            if ($hodUserId) {
                $recipients[] = $hodUserId;
            }
        }

        $this->notifications->notifyUsers(
            $recipients,
            $title,
            $body,
            'attendance_summary',
            "{$studentId}:{$allocation->unit_id}:{$allocation->semester_id}",
            $flag === self::FLAG_RED ? 'high' : 'normal',
        );
    }

    private function staffUserId(?Staff $staff): ?int
    {
        if (! $staff) {
            return null;
        }

        if ($staff->user_id) {
            return (int) $staff->user_id;
        }

        return User::query()->where('staff_id', $staff->id)->value('id');
    }
}
