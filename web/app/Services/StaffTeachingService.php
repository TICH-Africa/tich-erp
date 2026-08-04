<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\CatScore;
use App\Models\LessonPlan;
use App\Models\ProgramTimetableSession;
use App\Models\Staff;
use App\Models\UnitAllocation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StaffTeachingService
{
    public function __construct(
        protected AttendanceVerificationService $attendanceVerification,
        protected LessonPlanApprovalService $lessonPlanApprovals,
        protected AuditService $auditService,
    ) {}
    public function createLessonPlan(Staff $staff, UnitAllocation $allocation, array $data): LessonPlan
    {
        abort_unless((int) $allocation->staff_id === (int) $staff->id, 403);

        $sourceType = ($data['source_type'] ?? 'form') === 'upload' ? 'upload' : 'form';

        $plan = LessonPlan::query()->create([
            'plan_number' => 'LP-'.now()->format('Ymd').'-'.strtoupper(Str::random(4)),
            'unit_allocation_id' => $allocation->id,
            'prepared_by' => $staff->id,
            'source_type' => $sourceType,
            'lesson_title' => $data['lesson_title'] ?? null,
            'lesson_objectives' => $data['lesson_objectives'],
            'topics_covered' => $data['topics_covered'] ?? null,
            'competencies_targeted' => $data['competencies_targeted'] ?? null,
            'contact_hours' => (int) ($data['contact_hours'] ?? 2),
            'week_number' => (int) ($data['week_number'] ?? 1),
            'planned_date' => $data['planned_date'],
            'teaching_methods' => $data['teaching_methods'] ?? null,
            'resources_required' => $data['resources_required'] ?? null,
            'uploaded_file_path' => $data['uploaded_file_path'] ?? null,
            'uploaded_file_name' => $data['uploaded_file_name'] ?? null,
            'form_payload' => $data['form_payload'] ?? null,
            'tutor_verified_at' => null,
            'status' => $data['status'] ?? 'draft',
            'created_at' => now(),
        ]);

        $this->auditService->log(
            'staff.lesson_plan.created',
            'lesson_plans',
            $plan->id,
            null,
            [
                'plan_number' => $plan->plan_number,
                'unit_allocation_id' => $allocation->id,
                'planned_date' => $data['planned_date'],
                'status' => $plan->status,
            ],
            'Lesson plan created',
            'success',
            $staff->user_id ?? Auth::id(),
        );

        return $plan;
    }

    public function submitLessonPlan(LessonPlan $plan, Staff $staff): LessonPlan
    {
        abort_unless((int) $plan->prepared_by === (int) $staff->id, 403);
        abort_unless($plan->isReadyToSubmit(), 422, $plan->isFormBased()
            ? 'Preview the generated lesson plan and verify it before submitting.'
            : 'Upload your lesson plan document before submitting.');

        return $this->lessonPlanApprovals->submitForApproval($plan, $staff);
    }

    public function verifyLessonPlan(LessonPlan $plan, Staff $staff): LessonPlan
    {
        abort_unless((int) $plan->prepared_by === (int) $staff->id, 403);
        abort_unless($plan->isFormBased(), 422, 'Only system-generated lesson plans require tutor verification.');
        abort_unless($plan->isEditableByTutor(), 422, 'This lesson plan cannot be verified in its current state.');

        $plan->update([
            'tutor_verified_at' => now(),
            'updated_at' => now(),
        ]);

        $this->auditService->log(
            'staff.lesson_plan.verified',
            'lesson_plans',
            $plan->id,
            null,
            ['tutor_verified_at' => $plan->tutor_verified_at?->toIso8601String()],
            'Lesson plan verified by tutor before submission',
            'success',
            $staff->user_id ?? Auth::id(),
        );

        return $plan->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateLessonPlan(LessonPlan $plan, Staff $staff, array $data): LessonPlan
    {
        return $this->lessonPlanApprovals->updateByTutor($plan, $staff, $data);
    }

    public function createAttendanceSession(Staff $staff, UnitAllocation $allocation, array $data): AttendanceSession
    {
        abort_unless((int) $allocation->staff_id === (int) $staff->id, 403);

        $this->lessonPlanApprovals->assertCanInitiateSession($allocation, $data['session_date']);

        $session = AttendanceSession::query()->create([
            'session_number' => 'ATT-'.now()->format('YmdHis'),
            'unit_allocation_id' => $allocation->id,
            'session_date' => $data['session_date'],
            'start_time' => $data['start_time'] ?? '08:00:00',
            'end_time' => $data['end_time'] ?? '10:00:00',
            'venue' => $data['venue'] ?? null,
            'session_type' => $data['session_type'] ?? 'physical',
            'recorded_by' => $staff->id,
            'recorded_at' => now(),
        ]);

        $roster = app(StaffPortalDashboardService::class)->rosterForAllocation($allocation->id);

        foreach ($roster as $student) {
            AttendanceRecord::query()->create([
                'session_id' => $session->id,
                'student_id' => $student->student_id,
                'is_present' => 0,
                'recorded_by_tutor' => 1,
                'created_at' => now(),
            ]);
        }

        $session->update([
            'total_expected_attendees' => $roster->count(),
            'roster_verified_at' => now(),
            'roster_verified_by' => $staff->id,
        ]);

        $this->auditService->log(
            'staff.attendance.session_created',
            'attendance_sessions',
            $session->id,
            null,
            [
                'session_number' => $session->session_number,
                'unit_allocation_id' => $allocation->id,
                'session_date' => $data['session_date'],
                'expected_attendees' => $roster->count(),
            ],
            'Attendance session created',
            'success',
            $staff->user_id ?? Auth::id(),
        );

        return $session->fresh(['records']);
    }

    public function createAttendanceSessionFromTimetable(
        Staff $staff,
        UnitAllocation $allocation,
        ProgramTimetableSession $slot,
        Carbon $date,
    ): AttendanceSession {
        abort_unless((int) $allocation->staff_id === (int) $staff->id, 403);
        abort_unless((int) $slot->staff_id === (int) $staff->id, 403);

        $slot->loadMissing(['room', 'timetable.program']);

        $programId = $slot->timetable?->program_id;
        $teachingPeriod = $slot->timetable?->teaching_period;

        $session = AttendanceSession::query()->create([
            'session_number' => 'ATT-'.$date->format('Ymd').'-'.strtoupper(Str::random(4)),
            'unit_allocation_id' => $allocation->id,
            'program_timetable_session_id' => $slot->id,
            'session_date' => $date->toDateString(),
            'start_time' => $slot->start_time,
            'end_time' => $slot->end_time,
            'venue' => $slot->venue ?? $slot->room?->name,
            'session_type' => in_array($slot->session_type, ['virtual', 'field_practical', 'clinical'], true)
                ? $slot->session_type
                : 'physical',
            'recorded_by' => $staff->id,
            'recorded_at' => now(),
            'verification_status' => 'draft',
            'roster_verified_at' => now(),
            'roster_verified_by' => $staff->id,
        ]);

        $roster = app(StaffPortalDashboardService::class)->rosterForAllocation(
            $allocation->id,
            $programId ? (int) $programId : null,
            $teachingPeriod ? (int) $teachingPeriod : null,
        );

        foreach ($roster as $student) {
            AttendanceRecord::query()->create([
                'session_id' => $session->id,
                'student_id' => $student->student_id,
                'is_present' => 0,
                'recorded_by_tutor' => 1,
                'created_at' => now(),
            ]);
        }

        $session->update(['total_expected_attendees' => $roster->count()]);

        $this->auditService->log(
            'staff.attendance.session_created',
            'attendance_sessions',
            $session->id,
            null,
            [
                'session_number' => $session->session_number,
                'unit_allocation_id' => $allocation->id,
                'program_timetable_session_id' => $slot->id,
                'session_date' => $date->toDateString(),
                'expected_attendees' => $roster->count(),
                'source' => 'timetable',
            ],
            'Attendance session auto-created from timetable',
            'success',
            $staff->user_id ?? Auth::id(),
        );

        return $session->fresh(['records']);
    }

    /**
     * @param  array<int, int>  $presentStudentIds
     */
    public function saveAttendance(AttendanceSession $session, Staff $staff, array $presentStudentIds): void
    {
        abort_if($session->is_locked, 422, 'This attendance session is locked.');
        abort_unless((int) $session->recorded_by === (int) $staff->id, 403);

        $session->load('records');
        $present = collect($presentStudentIds)->map(fn ($id) => (int) $id)->all();
        $rosterStudentIds = $session->records->pluck('student_id')->map(fn ($id) => (int) $id)->all();

        foreach ($session->records as $record) {
            abort_if(! in_array((int) $record->student_id, $rosterStudentIds, true), 422, 'Student is not on the approved roster for this session.');

            $record->update([
                'is_present' => in_array((int) $record->student_id, $present, true),
                'recorded_by_tutor' => 1,
            ]);
        }

        $this->attendanceVerification->recalculateSummaries($session);

        $this->auditService->log(
            'staff.attendance.saved',
            'attendance_sessions',
            $session->id,
            null,
            [
                'present_count' => count($present),
                'total_records' => $session->records->count(),
            ],
            'Attendance marks saved',
            'success',
            $staff->user_id ?? Auth::id(),
        );
    }

    public function lockAttendanceSession(AttendanceSession $session, Staff $staff): void
    {
        $this->attendanceVerification->submitSession($session, $staff);
    }

    public function recordCatScore(Staff $staff, UnitAllocation $allocation, array $data): CatScore
    {
        abort_unless((int) $allocation->staff_id === (int) $staff->id, 403);

        $max = (float) $data['max_score'];
        $obtained = (float) $data['score_obtained'];

        $score = CatScore::query()->create([
            'student_id' => (int) $data['student_id'],
            'unit_id' => $allocation->unit_id,
            'semester_id' => $allocation->semester_id,
            'assessment_type' => $data['assessment_type'] ?? 'cat',
            'assessment_name' => $data['assessment_name'],
            'max_score' => $max,
            'score_obtained' => $obtained,
            'percentage_score' => $max > 0 ? round(($obtained / $max) * 100, 2) : 0,
            'weight_in_final' => (float) ($data['weight_in_final'] ?? 0),
            'recorded_by' => $staff->id,
            'recorded_at' => now(),
        ]);

        app(ContinuousAssessmentService::class)->recalculateCumulativeScores($allocation);

        $this->auditService->log(
            'staff.grading.cat_score_recorded',
            'cat_scores',
            $score->id,
            null,
            [
                'student_id' => (int) $data['student_id'],
                'unit_id' => $allocation->unit_id,
                'assessment_name' => $data['assessment_name'],
                'score_obtained' => $obtained,
                'max_score' => $max,
            ],
            'CAT score recorded',
            'success',
            $staff->user_id ?? Auth::id(),
        );

        return $score;
    }

    public function storeLearningContent(Staff $staff, int $unitId, array $data, string $storedPath): void
    {
        $attachmentId = DB::table('media_attachments')->insertGetId([
            'entity_type' => 'unit',
            'entity_id' => $unitId,
            'file_path' => $storedPath,
            'file_type' => $data['file_type'] ?? 'document',
            'title' => $data['title'],
            'caption' => $data['caption'] ?? null,
            'display_order' => 0,
            'uploaded_by' => $staff->id,
            'uploaded_at' => now(),
            'created_by' => $staff->id,
        ]);

        $this->auditService->log(
            'staff.learning_content.uploaded',
            'media_attachments',
            $attachmentId,
            null,
            [
                'unit_id' => $unitId,
                'title' => $data['title'],
                'file_type' => $data['file_type'] ?? 'document',
            ],
            'Learning content uploaded',
            'success',
            $staff->user_id ?? Auth::id(),
        );
    }

    public function uploadSignedSheet(AttendanceSession $session, Staff $staff, \Illuminate\Http\UploadedFile $file): AttendanceSession
    {
        return $this->attendanceVerification->uploadSignedSheet($session, $staff, $file);
    }

    public function uploadClassPhoto(AttendanceSession $session, Staff $staff, \Illuminate\Http\UploadedFile $file): AttendanceSession
    {
        return $this->attendanceVerification->uploadClassPhoto($session, $staff, $file);
    }
}
