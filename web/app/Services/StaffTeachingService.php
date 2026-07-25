<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\CatScore;
use App\Models\LessonPlan;
use App\Models\Staff;
use App\Models\UnitAllocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StaffTeachingService
{
    public function __construct(
        protected AttendanceVerificationService $attendanceVerification,
    ) {}
    public function createLessonPlan(Staff $staff, UnitAllocation $allocation, array $data): LessonPlan
    {
        abort_unless((int) $allocation->staff_id === (int) $staff->id, 403);

        return LessonPlan::query()->create([
            'plan_number' => 'LP-'.now()->format('Ymd').'-'.strtoupper(Str::random(4)),
            'unit_allocation_id' => $allocation->id,
            'prepared_by' => $staff->id,
            'lesson_objectives' => $data['lesson_objectives'],
            'topics_covered' => $data['topics_covered'] ?? null,
            'competencies_targeted' => $data['competencies_targeted'] ?? null,
            'contact_hours' => (int) ($data['contact_hours'] ?? 2),
            'week_number' => (int) ($data['week_number'] ?? 1),
            'planned_date' => $data['planned_date'],
            'teaching_methods' => $data['teaching_methods'] ?? null,
            'resources_required' => $data['resources_required'] ?? null,
            'status' => $data['status'] ?? 'draft',
            'created_at' => now(),
        ]);
    }

    public function submitLessonPlan(LessonPlan $plan, Staff $staff): LessonPlan
    {
        abort_unless((int) $plan->prepared_by === (int) $staff->id, 403);

        $plan->update(['status' => 'submitted', 'updated_at' => now()]);

        return $plan->fresh();
    }

    public function createAttendanceSession(Staff $staff, UnitAllocation $allocation, array $data): AttendanceSession
    {
        abort_unless((int) $allocation->staff_id === (int) $staff->id, 403);

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

        $session->update(['total_expected_attendees' => $roster->count()]);

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

        foreach ($session->records as $record) {
            $record->update([
                'is_present' => in_array((int) $record->student_id, $present, true),
                'recorded_by_tutor' => 1,
            ]);
        }

        $this->attendanceVerification->recalculateSummaries($session);
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

        return $score;
    }

    public function storeLearningContent(Staff $staff, int $unitId, array $data, string $storedPath): void
    {
        DB::table('media_attachments')->insert([
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
    }

    public function uploadSignedSheet(AttendanceSession $session, Staff $staff, \Illuminate\Http\UploadedFile $file): AttendanceSession
    {
        return $this->attendanceVerification->uploadSignedSheet($session, $staff, $file);
    }
}
