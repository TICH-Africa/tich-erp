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
            'Lesson plan submitted for approval',
            'success',
            $staff->user_id ?? auth()->id(),
        );

        $this->notifyHod($plan->fresh(['allocation.unit', 'preparedByStaff']));
        $this->notifySubmissionStakeholders($plan->fresh(['allocation.unit', 'preparedByStaff']));

        return $plan->fresh();
    }

    public function approve(LessonPlan $plan, Staff $reviewer, ?string $comments = null): LessonPlan
    {
        $this->assertCanReview($plan, $reviewer);
        $actor = $this->reviewerIdentity($plan, $reviewer);

        $plan->update([
            'status' => 'approved',
            'hod_id' => $reviewer->id,
            'hod_comments' => $comments,
            'hod_action_at' => now(),
            'registrar_visible' => 1,
            'updated_at' => now(),
        ]);

        $this->recordDecision($plan, $reviewer, $actor['level'], 'approved', $comments);
        $this->clearTimetableForPlan($plan->fresh(['allocation']));
        $this->notifyTutor($plan, 'Lesson plan approved', 'Your lesson plan '.$plan->plan_number.' has been approved by '.$actor['label'].'.');

        $this->auditService->log(
            'staff.lesson_plan.approved',
            'lesson_plans',
            $plan->id,
            ['status' => 'submitted'],
            ['status' => 'approved', 'hod_comments' => $comments],
            'Lesson plan approved by '.$actor['role'],
            'success',
            $reviewer->user_id ?? auth()->id(),
        );

        return $plan->fresh();
    }

    public function reject(LessonPlan $plan, Staff $reviewer, string $comments): LessonPlan
    {
        $this->assertCanReview($plan, $reviewer);
        $actor = $this->reviewerIdentity($plan, $reviewer);

        $plan->update([
            'status' => 'rejected',
            'hod_id' => $reviewer->id,
            'hod_comments' => $comments,
            'hod_action_at' => now(),
            'registrar_visible' => 1,
            'updated_at' => now(),
        ]);

        $this->recordDecision($plan, $reviewer, $actor['level'], 'rejected', $comments);
        $this->notifyTutor($plan, 'Lesson plan rejected', 'Your lesson plan '.$plan->plan_number.' was rejected by '.$actor['label'].'. Comments: '.$comments);

        $this->auditService->log(
            'staff.lesson_plan.rejected',
            'lesson_plans',
            $plan->id,
            ['status' => 'submitted'],
            ['status' => 'rejected', 'hod_comments' => $comments],
            'Lesson plan rejected by '.$actor['role'],
            'success',
            $reviewer->user_id ?? auth()->id(),
        );

        return $plan->fresh();
    }

    public function requestModification(LessonPlan $plan, Staff $reviewer, string $comments): LessonPlan
    {
        $this->assertCanReview($plan, $reviewer);
        $actor = $this->reviewerIdentity($plan, $reviewer);

        $plan->update([
            'status' => 'modified',
            'hod_id' => $reviewer->id,
            'hod_comments' => $comments,
            'hod_action_at' => now(),
            'registrar_visible' => 1,
            'updated_at' => now(),
        ]);

        $this->recordDecision($plan, $reviewer, $actor['level'], 'request_modification', $comments);
        $this->notifyTutor($plan, 'Lesson plan needs revision', 'Your lesson plan '.$plan->plan_number.' requires changes. '.$actor['label'].' comments: '.$comments);

        $this->auditService->log(
            'staff.lesson_plan.modification_requested',
            'lesson_plans',
            $plan->id,
            ['status' => 'submitted'],
            ['status' => 'modified', 'hod_comments' => $comments],
            'Lesson plan sent back for modification by '.$actor['role'],
            'success',
            $reviewer->user_id ?? auth()->id(),
        );

        return $plan->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateByHod(LessonPlan $plan, Staff $hod, array $data): LessonPlan
    {
        $this->assertCanReview($plan, $hod);
        $actor = $this->reviewerIdentity($plan, $hod);

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

        $this->recordDecision($plan, $hod, $actor['level'], 'request_modification', 'Plan content updated by '.$actor['role'].'.');

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
            'Lesson plan content updated by '.$actor['role'],
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
        abort_unless($this->hasApprovedPlanForSession($allocation, $sessionDate), 422, 'An approved lesson plan is required for this unit and date before you can start a class session. Create and submit a lesson plan, then wait for it to be approved.');
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
     * QA inbox: submitted / visible plans awaiting or with acknowledgement (parallel to HOD).
     *
     * @return Collection<int, object>
     */
    public function qaInbox(?string $filter = null): Collection
    {
        $query = $this->basePlanQuery()
            ->where('lp.registrar_visible', 1)
            ->whereIn('lp.status', ['submitted', 'approved', 'modified', 'rejected'])
            ->orderByRaw('CASE WHEN lp.qa_acknowledged_at IS NULL THEN 0 ELSE 1 END')
            ->orderByDesc('lp.planned_date');

        if ($filter === 'pending') {
            $query->whereNull('lp.qa_acknowledged_at');
        } elseif ($filter === 'acknowledged') {
            $query->whereNotNull('lp.qa_acknowledged_at');
        }

        return $query->get();
    }

    public function userIsQaReviewer(User $user): bool
    {
        return $user->hasAnyRole(['QA Officer', 'Assistant QA Officer', 'Super Admin']);
    }

    /**
     * Parallel, non-blocking QA acknowledgement / comment.
     */
    public function acknowledgeByQa(LessonPlan $plan, Staff $qaStaff, string $comments): LessonPlan
    {
        abort_unless($this->userIsQaReviewer(auth()->user()), 403, 'Only QA can acknowledge lesson plans.');
        abort_unless((int) $plan->registrar_visible === 1, 422, 'This lesson plan is not yet available for QA review.');
        abort_unless(
            in_array($plan->status, ['submitted', 'approved', 'modified', 'rejected'], true),
            422,
            'This lesson plan cannot be acknowledged in its current state.'
        );

        $plan->update([
            'qa_acknowledged_by' => $qaStaff->id,
            'qa_acknowledged_at' => now(),
            'qa_comments' => $comments,
            'updated_at' => now(),
        ]);

        $this->recordDecision($plan, $qaStaff, 'qa', 'acknowledged', $comments);

        $this->notifyTutor(
            $plan,
            'Lesson plan acknowledged by QA',
            'QA has acknowledged your lesson plan '.$plan->plan_number.'. Comments: '.$comments
        );

        $this->auditService->log(
            'staff.lesson_plan.qa_acknowledged',
            'lesson_plans',
            $plan->id,
            null,
            ['qa_comments' => $comments],
            'Lesson plan acknowledged by QA',
            'success',
            $qaStaff->user_id ?? auth()->id(),
        );

        return $plan->fresh(['qaAcknowledgedByStaff', 'approvals.approver']);
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

    public function canReview(LessonPlan $plan, Staff $reviewer, User $user): bool
    {
        return $this->reviewerMayDecide($this->unitForPlan($plan)?->department_id, $reviewer, $user);
    }

    /**
     * @param  Collection<int, object>  $plans
     * @return list<int> Plan ids the acting user may approve or reject
     */
    public function decidablePlanIds(Collection $plans, ?Staff $reviewer, User $user): array
    {
        if (! $reviewer) {
            return [];
        }

        if ($user->hasAnyRole(['Super Admin', 'CEO', 'Dean of Students'])) {
            return $plans->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        $departments = Department::query()
            ->whereIn('id', $plans->pluck('department_id')->filter()->unique())
            ->get()
            ->keyBy('id');

        return $plans
            ->filter(fn ($plan) => isset($departments[$plan->department_id])
                && $this->reviewerMayDecide((int) $plan->department_id, $reviewer, $user, $departments[$plan->department_id]))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * The HOD of the unit's department decides. A department with no HOD to act falls back to
     * the Academic Registrar so a plan cannot sit in Submitted forever.
     */
    public function reviewerMayDecide(?int $departmentId, Staff $reviewer, User $user, ?Department $department = null): bool
    {
        if ($user->hasAnyRole(['Super Admin', 'CEO', 'Dean of Students'])) {
            return true;
        }

        if (! $departmentId) {
            return false;
        }

        $department ??= Department::query()->find($departmentId);
        if (! $department) {
            return false;
        }

        if ((int) $department->hod_id === (int) $reviewer->id) {
            return true;
        }

        if ($user->hasRole('HOD')) {
            return true;
        }

        return $user->hasRole('Academic Registrar') && ! $this->departmentHasReviewingHod($department);
    }

    private function departmentHasReviewingHod(?Department $department): bool
    {
        if (! $department?->hod_id) {
            return false;
        }

        $hod = Staff::query()->find($department->hod_id);

        return $hod !== null && $this->staffUserId($hod) !== null;
    }

    /**
     * @return array{level: string, role: string, label: string}
     */
    private function reviewerIdentity(LessonPlan $plan, Staff $reviewer): array
    {
        $user = auth()->user();
        $department = $this->departmentForPlan($plan);

        if ($user?->hasRole('Academic Registrar') && ! $this->departmentHasReviewingHod($department)) {
            return ['level' => 'registrar', 'role' => 'Academic Registrar', 'label' => 'the Academic Registrar'];
        }

        if ($user?->hasAnyRole(['Super Admin', 'CEO', 'Dean of Students']) && ! $this->isDepartmentHodOf($plan, $reviewer)) {
            return ['level' => 'registrar', 'role' => 'Academics registry', 'label' => 'the Academics registry'];
        }

        return ['level' => 'hod', 'role' => 'HOD', 'label' => 'the HOD'];
    }

    private function isDepartmentHodOf(LessonPlan $plan, Staff $reviewer): bool
    {
        return (int) ($this->departmentForPlan($plan)?->hod_id ?? 0) === (int) $reviewer->id;
    }

    private function departmentForPlan(LessonPlan $plan): ?Department
    {
        $departmentId = $this->unitForPlan($plan)?->department_id;

        return $departmentId ? Department::query()->find($departmentId) : null;
    }

    private function assertCanReview(LessonPlan $plan, Staff $reviewer): void
    {
        abort_unless(in_array($plan->status, ['submitted'], true), 422, 'Only submitted lesson plans can be reviewed.');
        abort_unless(
            $this->canReview($plan, $reviewer, auth()->user()),
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
            route('departments.academics.lesson-plans.show', $plan),
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
            'normal',
            route('staff.dashboard', ['section' => 'lesson-plans', 'edit_plan' => $plan->id]),
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
            ->leftJoin('departments as d', 'd.id', '=', 'u.department_id')
            ->leftJoin('semesters as sem', 'sem.id', '=', 'ua.semester_id')
            ->select([
                'lp.*',
                'u.unit_code',
                'u.unit_name',
                'u.department_id',
                'd.dept_name as department_name',
                DB::raw("TRIM(CONCAT(COALESCE(tutor.first_name, ''), ' ', COALESCE(tutor.surname, ''))) as tutor_name"),
                'tutor.first_name as tutor_first_name',
                'tutor.surname as tutor_surname',
                'sem.semester_label',
            ]);
    }
}
