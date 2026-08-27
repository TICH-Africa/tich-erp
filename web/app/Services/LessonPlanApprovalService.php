<?php

namespace App\Services;

use App\Models\Department;
use App\Models\LessonPlan;
use App\Models\LessonPlanApproval;
use App\Models\Staff;
use App\Models\Unit;
use App\Models\UnitAllocation;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LessonPlanApprovalService
{
    public function __construct(
        protected PlatformNotificationService $notifications,
        protected AuditService $auditService,
    ) {}

    public function submitForApproval(LessonPlan $plan, Staff $staff): LessonPlan
    {
        abort_unless((int) $plan->prepared_by === (int) $staff->id, 403);
        abort_unless(in_array($plan->status, ['draft', 'modified', 'rejected'], true), 422, 'This lesson plan cannot be submitted in its current state.');

        $oldStatus = $plan->status;

        $plan->update([
            'status' => 'submitted',
            'updated_at' => now(),
            'registrar_visible' => 1,
        ]);

        $this->auditService->log(
            'staff.lesson_plan.submitted',
            'lesson_plans',
            $plan->id,
            ['status' => $oldStatus],
            ['status' => 'submitted'],
            'Lesson plan submitted for HOD approval',
            'success',
            $staff->user_id ?? auth()->id(),
        );

        $this->notifyHod($plan->fresh(['allocation.unit', 'preparedByStaff']));
        $this->notifySubmissionStakeholders($plan->fresh(['allocation.unit', 'preparedByStaff']));

        return $plan->fresh();
    }

    public function approve(LessonPlan $plan, Staff $hod, ?string $comments = null): LessonPlan
    {
        $this->assertHodCanReview($plan, $hod);

        $plan->update([
            'status' => 'approved',
            'hod_id' => $hod->id,
            'hod_comments' => $comments,
            'hod_action_at' => now(),
            'registrar_visible' => 1,
            'updated_at' => now(),
        ]);

        $this->recordDecision($plan, $hod, 'hod', 'approved', $comments);
        $this->clearTimetableForPlan($plan->fresh(['allocation']));
        $this->notifyTutor($plan, 'Lesson plan approved', 'Your lesson plan '.$plan->plan_number.' has been approved by the HOD.');

        $this->auditService->log(
            'staff.lesson_plan.approved',
            'lesson_plans',
            $plan->id,
            ['status' => 'submitted'],
            ['status' => 'approved', 'hod_comments' => $comments],
            'Lesson plan approved by HOD',
            'success',
            $hod->user_id ?? auth()->id(),
        );

        return $plan->fresh();
    }

    public function reject(LessonPlan $plan, Staff $hod, string $comments): LessonPlan
    {
        $this->assertHodCanReview($plan, $hod);

        $plan->update([
            'status' => 'rejected',
            'hod_id' => $hod->id,
            'hod_comments' => $comments,
            'hod_action_at' => now(),
            'registrar_visible' => 1,
            'updated_at' => now(),
        ]);

        $this->recordDecision($plan, $hod, 'hod', 'rejected', $comments);
        $this->notifyTutor($plan, 'Lesson plan rejected', 'Your lesson plan '.$plan->plan_number.' was rejected. HOD comments: '.$comments);

        $this->auditService->log(
            'staff.lesson_plan.rejected',
            'lesson_plans',
            $plan->id,
            ['status' => 'submitted'],
            ['status' => 'rejected', 'hod_comments' => $comments],
            'Lesson plan rejected by HOD',
            'success',
            $hod->user_id ?? auth()->id(),
        );

        return $plan->fresh();
    }

    public function requestModification(LessonPlan $plan, Staff $hod, string $comments): LessonPlan
    {
        $this->assertHodCanReview($plan, $hod);

        $plan->update([
            'status' => 'modified',
            'hod_id' => $hod->id,
            'hod_comments' => $comments,
            'hod_action_at' => now(),
            'registrar_visible' => 1,
            'updated_at' => now(),
        ]);

        $this->recordDecision($plan, $hod, 'hod', 'request_modification', $comments);
        $this->notifyTutor($plan, 'Lesson plan needs revision', 'Your lesson plan '.$plan->plan_number.' requires changes. HOD comments: '.$comments);

        $this->auditService->log(
            'staff.lesson_plan.modification_requested',
            'lesson_plans',
            $plan->id,
            ['status' => 'submitted'],
            ['status' => 'modified', 'hod_comments' => $comments],
            'Lesson plan sent back for modification',
            'success',
            $hod->user_id ?? auth()->id(),
        );

        return $plan->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateByHod(LessonPlan $plan, Staff $hod, array $data): LessonPlan
    {
        $this->assertHodCanReview($plan, $hod);

        $plan->update([
            'lesson_objectives' => $data['lesson_objectives'],
            'topics_covered' => $data['topics_covered'] ?? null,
            'competencies_targeted' => $data['competencies_targeted'] ?? null,
            'contact_hours' => (int) ($data['contact_hours'] ?? $plan->contact_hours),
            'week_number' => (int) ($data['week_number'] ?? $plan->week_number),
            'planned_date' => $data['planned_date'],
            'teaching_methods' => $data['teaching_methods'] ?? null,
            'resources_required' => $data['resources_required'] ?? null,
            'updated_at' => now(),
        ]);

        $this->recordDecision($plan, $hod, 'hod', 'request_modification', 'Plan content updated by HOD.');

        $this->auditService->log(
            'staff.lesson_plan.hod_updated',
            'lesson_plans',
            $plan->id,
            null,
            [
                'week_number' => (int) ($data['week_number'] ?? $plan->week_number),
                'planned_date' => $data['planned_date'],
                'contact_hours' => (int) ($data['contact_hours'] ?? $plan->contact_hours),
            ],
            'Lesson plan content updated by HOD',
            'success',
            $hod->user_id ?? auth()->id(),
        );

        return $plan->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateByTutor(LessonPlan $plan, Staff $staff, array $data): LessonPlan
    {
        abort_unless((int) $plan->prepared_by === (int) $staff->id, 403);
        abort_unless(in_array($plan->status, ['draft', 'modified', 'rejected'], true), 422, 'Approved or submitted lesson plans cannot be edited by the tutor.');

        $plan->update([
            'lesson_title' => $data['lesson_title'] ?? $plan->lesson_title,
            'lesson_objectives' => $data['lesson_objectives'],
            'topics_covered' => $data['topics_covered'] ?? null,
            'competencies_targeted' => $data['competencies_targeted'] ?? null,
            'contact_hours' => (int) ($data['contact_hours'] ?? $plan->contact_hours),
            'week_number' => (int) ($data['week_number'] ?? $plan->week_number),
            'planned_date' => $data['planned_date'],
            'teaching_methods' => $data['teaching_methods'] ?? null,
            'resources_required' => $data['resources_required'] ?? null,
            'form_payload' => array_key_exists('form_payload', $data) ? $data['form_payload'] : $plan->form_payload,
            'uploaded_file_path' => $data['uploaded_file_path'] ?? $plan->uploaded_file_path,
            'uploaded_file_name' => $data['uploaded_file_name'] ?? $plan->uploaded_file_name,
            'tutor_verified_at' => null,
            'updated_at' => now(),
        ]);

        $this->auditService->log(
            'staff.lesson_plan.updated',
            'lesson_plans',
            $plan->id,
            null,
            [
                'week_number' => (int) ($data['week_number'] ?? $plan->week_number),
                'planned_date' => $data['planned_date'],
                'status' => $plan->status,
            ],
            'Lesson plan updated by tutor',
            'success',
            $staff->user_id ?? auth()->id(),
        );

        return $plan->fresh();
    }

    public function assertCanInitiateSession(UnitAllocation $allocation, string $sessionDate): void
    {
        abort_unless($this->hasApprovedPlanForSession($allocation, $sessionDate), 422, 'An HOD-approved lesson plan is required for this unit and date before you can start a class session. Create and submit a lesson plan, then wait for HOD approval.');
    }

    public function hasApprovedPlanForSession(UnitAllocation $allocation, string $sessionDate): bool
    {
        return LessonPlan::query()
            ->where('unit_allocation_id', $allocation->id)
            ->where('status', 'approved')
            ->whereDate('planned_date', $sessionDate)
            ->exists();
    }

    /**
     * @return Collection<int, object>
     */
    public function inboxForDepartment(int $departmentId, ?string $status = null): Collection
    {
        $query = $this->basePlanQuery()
            ->where('u.department_id', $departmentId)
            ->orderByDesc('lp.updated_at');

        if ($status) {
            $query->where('lp.status', $status);
        } else {
            $query->whereIn('lp.status', ['submitted', 'modified']);
        }

        return $query->get();
    }

    /**
     * @param  list<int>  $departmentIds
     * @return Collection<int, object>
     */
    public function auditRepository(array $departmentIds, ?string $status = null, ?int $semesterId = null): Collection
    {
        $query = $this->basePlanQuery()
            ->where('lp.registrar_visible', 1)
            ->whereIn('lp.status', ['submitted', 'approved', 'modified', 'rejected'])
            ->orderByDesc('lp.hod_action_at')
            ->orderByDesc('lp.planned_date');

        if ($departmentIds !== []) {
            $query->whereIn('u.department_id', $departmentIds);
        }

        if ($status) {
            $query->where('lp.status', $status);
        }

        if ($semesterId) {
            $query->where('ua.semester_id', $semesterId);
        }

        return $query->get();
    }

    /**
     * @return Collection<int, LessonPlanApproval>
     */
    public function approvalHistory(LessonPlan $plan): Collection
    {
        return LessonPlanApproval::query()
            ->with('approver')
            ->where('lesson_plan_id', $plan->id)
            ->orderByDesc('decided_at')
            ->get();
    }

    public function hodCanReview(LessonPlan $plan, Staff $hod, User $user): bool
    {
        if ($user->hasAnyRole(['Super Admin', 'Dean'])) {
            return true;
        }

        $unit = $this->unitForPlan($plan);
        if (! $unit) {
            return false;
        }

        $department = Department::query()->find($unit->department_id);
        if (! $department) {
            return false;
        }

        if ((int) $department->hod_id === (int) $hod->id) {
            return true;
        }

        return $user->hasRole('HOD');
    }

    private function assertHodCanReview(LessonPlan $plan, Staff $hod): void
    {
        abort_unless(in_array($plan->status, ['submitted'], true), 422, 'Only submitted lesson plans can be reviewed.');
        abort_unless(
            $this->hodCanReview($plan, $hod, auth()->user()),
            403,
            'You are not authorized to review this lesson plan.'
        );
    }

    private function clearTimetableForPlan(LessonPlan $plan): void
    {
        $allocation = $plan->allocation;
        if (! $allocation) {
            return;
        }

        if (! Schema::hasColumn('program_timetable_sessions', 'lesson_plan_cleared')) {
            return;
        }

        DB::table('program_timetable_sessions')
            ->where('unit_id', $allocation->unit_id)
            ->where('staff_id', $allocation->staff_id)
            ->update([
                'lesson_plan_cleared' => 1,
                'lesson_plan_id' => $plan->id,
            ]);
    }

    private function recordDecision(LessonPlan $plan, Staff $hod, string $level, string $decision, ?string $comments): void
    {
        LessonPlanApproval::query()->create([
            'lesson_plan_id' => $plan->id,
            'approver_id' => $hod->id,
            'approval_level' => $level,
            'decision' => $decision,
            'comments' => $comments,
            'decided_at' => now(),
        ]);
    }

    private function notifyHod(LessonPlan $plan): void
    {
        $unit = $plan->allocation?->unit;
        if (! $unit) {
            return;
        }

        $department = Department::query()->find($unit->department_id);
        if (! $department?->hod_id) {
            return;
        }

        $hodStaff = Staff::query()->find($department->hod_id);
        $hodUserId = $this->staffUserId($hodStaff);
        if (! $hodUserId) {
            return;
        }

        $this->notifyStakeholder(
            $hodUserId,
            'Lesson plan awaiting approval',
            $this->submissionMessage($plan),
            $plan,
        );
    }

    private function notifySubmissionStakeholders(LessonPlan $plan): void
    {
        foreach ($this->userIdsForRoles(['Academic Registrar', 'QA Officer', 'Assistant QA Officer']) as $userId) {
            $this->notifyStakeholder(
                $userId,
                'Lesson plan submitted for review',
                $this->submissionMessage($plan),
                $plan,
            );
        }
    }

    private function submissionMessage(LessonPlan $plan): string
    {
        $tutorName = $plan->preparedByStaff?->fullName() ?? 'A tutor';
        $unitLabel = $plan->allocation?->unit?->unit_code ?? 'unit';
        $topic = $plan->lesson_title ?: ($plan->topics_covered ?: 'session');
        $sourceLabel = $plan->isUploadBased() ? 'uploaded document' : 'system-generated document';

        return "{$tutorName} submitted lesson plan {$plan->plan_number} ({$sourceLabel}) for {$unitLabel} - {$topic} on {$plan->planned_date?->format('d M Y')}.";
    }

    /**
     * @param  list<string>  $roleNames
     * @return list<int>
     */
    private function userIdsForRoles(array $roleNames): array
    {
        return DB::table('user_roles as ur')
            ->join('roles as r', 'r.id', '=', 'ur.role_id')
            ->whereIn('r.role_name', $roleNames)
            ->pluck('ur.user_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function notifyStakeholder(int $userId, string $title, string $body, LessonPlan $plan): void
    {
        $this->notifications->notifyUser(
            $userId,
            $title,
            $body,
            'lesson_plan',
            (string) $plan->id,
            'normal',
        );
    }

    private function notifyTutor(LessonPlan $plan, string $title, string $body): void
    {
        $tutorUserId = $this->staffUserId($plan->preparedByStaff);
        if (! $tutorUserId) {
            return;
        }

        $this->notifications->notifyUser(
            $tutorUserId,
            $title,
            $body,
            'lesson_plan',
            (string) $plan->id,
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

    private function unitForPlan(LessonPlan $plan): ?Unit
    {
        return $plan->allocation?->unit;
    }

    private function basePlanQuery()
    {
        return DB::table('lesson_plans as lp')
            ->join('unit_allocations as ua', 'ua.id', '=', 'lp.unit_allocation_id')
            ->join('units as u', 'u.id', '=', 'ua.unit_id')
            ->join('staff as tutor', 'tutor.id', '=', 'lp.prepared_by')
            ->leftJoin('semesters as sem', 'sem.id', '=', 'ua.semester_id')
            ->select([
                'lp.*',
                'u.unit_code',
                'u.unit_name',
                'u.department_id',
                'tutor.first_name as tutor_first_name',
                'tutor.surname as tutor_surname',
                'sem.semester_label',
            ]);
    }
}
