<?php

namespace App\Services;

use App\Models\AcademicWorkplan;
use App\Models\AcademicWorkplanActivity;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AcademicWorkplanService
{
    public function __construct(
        protected PlatformNotificationService $notifications,
        protected RBACService $rbac,
    ) {}

    public function createDraft(Staff $staff, int $departmentId, int $semesterId, string $title): AcademicWorkplan
    {
        abort_unless($this->hodOwnsDepartment($staff, $departmentId), 403, 'Only the HOD can create semester workplans for this department.');

        return AcademicWorkplan::query()->create([
            'workplan_number' => $this->nextWorkplanNumber(),
            'department_id' => $departmentId,
            'semester_id' => $semesterId,
            'title' => $title,
            'status' => AcademicWorkplan::STATUS_DRAFT,
            'prepared_by_staff_id' => $staff->id,
            'registrar_status' => AcademicWorkplan::REVIEW_PENDING,
            'qa_status' => AcademicWorkplan::REVIEW_PENDING,
        ]);
    }

    /**
     * @param  array{
     *     title?: string,
     *     objectives?: ?string,
     *     resources?: ?string,
     *     kpis?: ?string,
     *     semester_id?: int,
     *     activities?: list<array{activity?: string, timeline_start?: ?string, timeline_end?: ?string, kpi?: ?string, resources?: ?string}>
     * }  $data
     */
    public function update(Staff $staff, AcademicWorkplan $workplan, array $data): AcademicWorkplan
    {
        abort_unless($this->hodOwns($staff, $workplan), 403, 'You cannot edit this workplan.');
        abort_unless($workplan->isEditableByHod(), 422, 'This workplan cannot be edited in its current state.');

        return DB::transaction(function () use ($workplan, $data) {
            $wasPending = $workplan->status === AcademicWorkplan::STATUS_PENDING;

            $payload = array_filter([
                'title' => $data['title'] ?? null,
                'objectives' => $data['objectives'] ?? null,
                'resources' => $data['resources'] ?? null,
                'kpis' => $data['kpis'] ?? null,
                'semester_id' => $data['semester_id'] ?? null,
            ], static fn ($value) => $value !== null);

            if ($wasPending) {
                $payload['registrar_status'] = AcademicWorkplan::REVIEW_PENDING;
                $payload['registrar_staff_id'] = null;
                $payload['registrar_acted_at'] = null;
                $payload['registrar_comments'] = null;
                $payload['qa_status'] = AcademicWorkplan::REVIEW_PENDING;
                $payload['qa_staff_id'] = null;
                $payload['qa_acted_at'] = null;
                $payload['qa_comments'] = null;
            }

            $workplan->update($payload);

            if (array_key_exists('activities', $data)) {
                $this->syncActivities($workplan, $data['activities'] ?? []);
            }

            return $workplan->fresh(['activities', 'department', 'semester', 'preparedByStaff', 'registrarStaff', 'qaStaff']);
        });
    }

    public function submit(Staff $staff, AcademicWorkplan $workplan): AcademicWorkplan
    {
        abort_unless($this->hodOwns($staff, $workplan), 403, 'You cannot submit this workplan.');
        abort_unless($workplan->isSubmittable(), 422, 'This workplan cannot be submitted in its current state.');
        abort_unless(filled($workplan->title), 422, 'Add a title before submitting.');

        $workplan->update([
            'status' => AcademicWorkplan::STATUS_PENDING,
            'submitted_at' => now(),
            'registrar_status' => AcademicWorkplan::REVIEW_PENDING,
            'registrar_staff_id' => null,
            'registrar_acted_at' => null,
            'registrar_comments' => null,
            'qa_status' => AcademicWorkplan::REVIEW_PENDING,
            'qa_staff_id' => null,
            'qa_acted_at' => null,
            'qa_comments' => null,
        ]);

        $workplan = $workplan->fresh(['department', 'semester', 'preparedByStaff']);
        $this->notifySubmissionStakeholders($workplan);

        return $workplan;
    }

    public function approveAsRegistrar(Staff $staff, AcademicWorkplan $workplan, ?string $comments = null): AcademicWorkplan
    {
        $this->ensureRegistrarCanAct($staff, $workplan);

        $workplan->update([
            'registrar_status' => AcademicWorkplan::REVIEW_APPROVED,
            'registrar_staff_id' => $staff->id,
            'registrar_acted_at' => now(),
            'registrar_comments' => $comments,
        ]);

        return $this->recalculateOverallStatus($workplan->fresh());
    }

    public function rejectAsRegistrar(Staff $staff, AcademicWorkplan $workplan, string $comments): AcademicWorkplan
    {
        $this->ensureRegistrarCanAct($staff, $workplan);
        abort_unless(filled(trim($comments)), 422, 'Comments are required when rejecting.');

        $workplan->update([
            'registrar_status' => AcademicWorkplan::REVIEW_REJECTED,
            'registrar_staff_id' => $staff->id,
            'registrar_acted_at' => now(),
            'registrar_comments' => $comments,
            'status' => AcademicWorkplan::STATUS_REJECTED,
        ]);

        $this->notifyHodDecision($workplan->fresh(), 'Academic workplan rejected', 'The Academic Registrar rejected your workplan.');

        return $workplan->fresh(['registrarStaff', 'qaStaff', 'preparedByStaff']);
    }

    public function requestChangesAsRegistrar(Staff $staff, AcademicWorkplan $workplan, string $comments): AcademicWorkplan
    {
        $this->ensureRegistrarCanAct($staff, $workplan);
        abort_unless(filled(trim($comments)), 422, 'Comments are required when requesting changes.');

        $workplan->update([
            'registrar_status' => AcademicWorkplan::REVIEW_CHANGES_REQUESTED,
            'registrar_staff_id' => $staff->id,
            'registrar_acted_at' => now(),
            'registrar_comments' => $comments,
            'status' => AcademicWorkplan::STATUS_CHANGES_REQUESTED,
        ]);

        $this->notifyHodDecision($workplan->fresh(), 'Academic workplan needs changes', 'The Academic Registrar requested changes to your workplan.');

        return $workplan->fresh(['registrarStaff', 'qaStaff', 'preparedByStaff']);
    }

    public function approveAsQa(Staff $staff, AcademicWorkplan $workplan, ?string $comments = null): AcademicWorkplan
    {
        $this->ensureQaCanAct($staff, $workplan);

        $workplan->update([
            'qa_status' => AcademicWorkplan::REVIEW_APPROVED,
            'qa_staff_id' => $staff->id,
            'qa_acted_at' => now(),
            'qa_comments' => $comments,
        ]);

        return $this->recalculateOverallStatus($workplan->fresh());
    }

    public function rejectAsQa(Staff $staff, AcademicWorkplan $workplan, string $comments): AcademicWorkplan
    {
        $this->ensureQaCanAct($staff, $workplan);
        abort_unless(filled(trim($comments)), 422, 'Comments are required when rejecting.');

        $workplan->update([
            'qa_status' => AcademicWorkplan::REVIEW_REJECTED,
            'qa_staff_id' => $staff->id,
            'qa_acted_at' => now(),
            'qa_comments' => $comments,
            'status' => AcademicWorkplan::STATUS_REJECTED,
        ]);

        $this->notifyHodDecision($workplan->fresh(), 'Academic workplan rejected', 'QA rejected your workplan.');

        return $workplan->fresh(['registrarStaff', 'qaStaff', 'preparedByStaff']);
    }

    public function requestChangesAsQa(Staff $staff, AcademicWorkplan $workplan, string $comments): AcademicWorkplan
    {
        $this->ensureQaCanAct($staff, $workplan);
        abort_unless(filled(trim($comments)), 422, 'Comments are required when requesting changes.');

        $workplan->update([
            'qa_status' => AcademicWorkplan::REVIEW_CHANGES_REQUESTED,
            'qa_staff_id' => $staff->id,
            'qa_acted_at' => now(),
            'qa_comments' => $comments,
            'status' => AcademicWorkplan::STATUS_CHANGES_REQUESTED,
        ]);

        $this->notifyHodDecision($workplan->fresh(), 'Academic workplan needs changes', 'QA requested changes to your workplan.');

        return $workplan->fresh(['registrarStaff', 'qaStaff', 'preparedByStaff']);
    }

    public function isQaOfficer(User $user): bool
    {
        return $this->rbac->hasAnyRole($user, ['QA Officer', 'Assistant QA Officer', 'Super Admin']);
    }

    public function isRegistrar(User $user): bool
    {
        return $this->rbac->hasAnyRole($user, ['Academic Registrar', 'Head of Academics', 'Super Admin']);
    }

    public function hodOwns(Staff $staff, AcademicWorkplan $workplan): bool
    {
        return $this->hodOwnsDepartment($staff, (int) $workplan->department_id);
    }

    public function hodOwnsDepartment(Staff $staff, int $departmentId): bool
    {
        $user = $staff->user_id ? User::query()->find($staff->user_id) : null;
        if (! $user || ! $user->hasAnyRole(['HOD', 'Super Admin'])) {
            return false;
        }

        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return (int) $staff->department_id === $departmentId;
    }

    /**
     * @return Collection<int, AcademicWorkplan>
     */
    public function inboxForQa(?string $status = null): Collection
    {
        return AcademicWorkplan::query()
            ->with(['department', 'semester.academicYear', 'preparedByStaff', 'registrarStaff', 'qaStaff'])
            ->when(
                $status,
                fn ($q) => $q->where('status', $status),
                fn ($q) => $q->where('status', AcademicWorkplan::STATUS_PENDING)
                    ->where('qa_status', AcademicWorkplan::REVIEW_PENDING)
            )
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return Collection<int, AcademicWorkplan>
     */
    public function inboxForRegistrar(?string $status = null): Collection
    {
        return AcademicWorkplan::query()
            ->with(['department', 'semester.academicYear', 'preparedByStaff', 'registrarStaff', 'qaStaff'])
            ->when(
                $status,
                fn ($q) => $q->where('status', $status),
                fn ($q) => $q->where('status', AcademicWorkplan::STATUS_PENDING)
                    ->where('registrar_status', AcademicWorkplan::REVIEW_PENDING)
            )
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return Collection<int, AcademicWorkplan>
     */
    public function forDepartment(int $departmentId): Collection
    {
        return AcademicWorkplan::query()
            ->with(['semester.academicYear', 'preparedByStaff', 'registrarStaff', 'qaStaff', 'activities'])
            ->where('department_id', $departmentId)
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return array{
     *     overall: string,
     *     registrar: array{status: string, pending: bool, approved: bool, actor: ?string, acted_at: ?string, comments: ?string},
     *     qa: array{status: string, pending: bool, approved: bool, actor: ?string, acted_at: ?string, comments: ?string}
     * }
     */
    public function approvalSummary(AcademicWorkplan $workplan): array
    {
        $workplan->loadMissing(['registrarStaff', 'qaStaff']);

        return [
            'overall' => $workplan->status,
            'registrar' => [
                'status' => $workplan->registrar_status,
                'pending' => $workplan->registrarPending(),
                'approved' => $workplan->registrar_status === AcademicWorkplan::REVIEW_APPROVED,
                'actor' => $workplan->registrarStaff?->fullName(),
                'acted_at' => $workplan->registrar_acted_at?->format('d M Y H:i'),
                'comments' => $workplan->registrar_comments,
            ],
            'qa' => [
                'status' => $workplan->qa_status,
                'pending' => $workplan->qaPending(),
                'approved' => $workplan->qa_status === AcademicWorkplan::REVIEW_APPROVED,
                'actor' => $workplan->qaStaff?->fullName(),
                'acted_at' => $workplan->qa_acted_at?->format('d M Y H:i'),
                'comments' => $workplan->qa_comments,
            ],
        ];
    }

    private function recalculateOverallStatus(AcademicWorkplan $workplan): AcademicWorkplan
    {
        if ($workplan->registrar_status === AcademicWorkplan::REVIEW_REJECTED
            || $workplan->qa_status === AcademicWorkplan::REVIEW_REJECTED) {
            $workplan->update(['status' => AcademicWorkplan::STATUS_REJECTED]);
            $this->notifyHodDecision($workplan->fresh(), 'Academic workplan rejected', 'Your semester workplan was rejected.');

            return $workplan->fresh(['registrarStaff', 'qaStaff', 'preparedByStaff']);
        }

        if ($workplan->registrar_status === AcademicWorkplan::REVIEW_CHANGES_REQUESTED
            || $workplan->qa_status === AcademicWorkplan::REVIEW_CHANGES_REQUESTED) {
            $workplan->update(['status' => AcademicWorkplan::STATUS_CHANGES_REQUESTED]);
            $this->notifyHodDecision($workplan->fresh(), 'Academic workplan needs changes', 'Changes were requested on your semester workplan.');

            return $workplan->fresh(['registrarStaff', 'qaStaff', 'preparedByStaff']);
        }

        if ($workplan->registrar_status === AcademicWorkplan::REVIEW_APPROVED
            && $workplan->qa_status === AcademicWorkplan::REVIEW_APPROVED) {
            $workplan->update(['status' => AcademicWorkplan::STATUS_APPROVED]);
            $this->notifyHodDecision($workplan->fresh(), 'Academic workplan approved', 'Your semester workplan was approved by the Academic Registrar and QA.');

            return $workplan->fresh(['registrarStaff', 'qaStaff', 'preparedByStaff']);
        }

        $workplan->update(['status' => AcademicWorkplan::STATUS_PENDING]);

        return $workplan->fresh(['registrarStaff', 'qaStaff', 'preparedByStaff']);
    }

    private function ensureRegistrarCanAct(Staff $staff, AcademicWorkplan $workplan): void
    {
        $user = $staff->user_id ? User::query()->find($staff->user_id) : null;
        if (! $user || ! $this->isRegistrar($user)) {
            throw new HttpException(403, 'Only the Academic Registrar can perform this action.');
        }

        abort_unless(
            $workplan->status === AcademicWorkplan::STATUS_PENDING
            && $workplan->registrar_status === AcademicWorkplan::REVIEW_PENDING,
            422,
            'This workplan is not awaiting registrar review.'
        );
    }

    private function ensureQaCanAct(Staff $staff, AcademicWorkplan $workplan): void
    {
        $user = $staff->user_id ? User::query()->find($staff->user_id) : null;
        if (! $user || ! $this->isQaOfficer($user)) {
            throw new HttpException(403, 'Only QA Officers can perform this action.');
        }

        abort_unless(
            $workplan->status === AcademicWorkplan::STATUS_PENDING
            && $workplan->qa_status === AcademicWorkplan::REVIEW_PENDING,
            422,
            'This workplan is not awaiting QA review.'
        );
    }

    /**
     * @param  list<array{activity?: string, timeline_start?: ?string, timeline_end?: ?string, kpi?: ?string, resources?: ?string}>  $rows
     */
    private function syncActivities(AcademicWorkplan $workplan, array $rows): void
    {
        $workplan->activities()->delete();

        $sort = 0;
        foreach ($rows as $row) {
            $activity = trim((string) ($row['activity'] ?? ''));
            if ($activity === '') {
                continue;
            }

            AcademicWorkplanActivity::query()->create([
                'workplan_id' => $workplan->id,
                'activity' => $activity,
                'timeline_start' => $row['timeline_start'] ?: null,
                'timeline_end' => $row['timeline_end'] ?: null,
                'kpi' => isset($row['kpi']) && trim((string) $row['kpi']) !== '' ? trim((string) $row['kpi']) : null,
                'resources' => isset($row['resources']) && trim((string) $row['resources']) !== '' ? trim((string) $row['resources']) : null,
                'sort_order' => $sort++,
            ]);
        }
    }

    private function nextWorkplanNumber(): string
    {
        return 'AWP-'.now()->format('Ymd').'-'.strtoupper(Str::random(4));
    }

    private function notifySubmissionStakeholders(AcademicWorkplan $workplan): void
    {
        $dept = $workplan->department?->dept_name ?? 'department';
        $semester = $workplan->semester?->displayLabel() ?? 'semester';
        $hod = $workplan->preparedByStaff?->fullName() ?? 'HOD';
        $body = "{$hod} submitted semester workplan {$workplan->workplan_number} ({$workplan->title}) for {$dept} — {$semester}.";

        foreach ($this->userIdsForRoles(['Academic Registrar', 'QA Officer', 'Assistant QA Officer']) as $userId) {
            $this->notifications->notifyUser(
                $userId,
                'Semester workplan submitted for review',
                $body,
                'academic_workplan',
                (string) $workplan->id,
                'normal',
                $this->reviewUrlForRole($userId, $workplan),
            );
        }
    }

    private function notifyHodDecision(AcademicWorkplan $workplan, string $title, string $prefix): void
    {
        $hodUserId = $this->staffUserId($workplan->preparedByStaff);
        if (! $hodUserId) {
            return;
        }

        $this->notifications->notifyUser(
            $hodUserId,
            $title,
            $prefix.' '.$workplan->workplan_number.' — '.$workplan->title.'.',
            'academic_workplan',
            (string) $workplan->id,
            'normal',
            route('staff.workplans.show', $workplan),
        );
    }

    private function reviewUrlForRole(int $userId, AcademicWorkplan $workplan): string
    {
        $user = User::query()->find($userId);
        if ($user && $this->isQaOfficer($user) && ! $this->isRegistrar($user)) {
            return route('qa.workplans.show', $workplan);
        }

        return route('departments.academics.workplans.show', $workplan);
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

    private function staffUserId(?Staff $staff): ?int
    {
        if (! $staff) {
            return null;
        }

        if ($staff->user_id) {
            return (int) $staff->user_id;
        }

        return null;
    }
}
