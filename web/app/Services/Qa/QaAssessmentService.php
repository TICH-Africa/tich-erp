<?php

namespace App\Services\Qa;

use App\Models\Department;
use App\Models\Qa\QaAuditChecklist;
use App\Models\Qa\QaComplianceScore;
use App\Models\Qa\QaCorrectiveAction;
use App\Models\Qa\QaDepartmentSubmission;
use App\Models\Qa\QaEvidenceAttachment;
use App\Models\Qa\QaPlan;
use App\Models\Staff;
use App\Models\User;
use App\Services\AuditService;
use App\Services\PlatformNotificationService;
use App\Services\RBACService;
use App\Services\StoredFileService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QaAssessmentService
{
    public function __construct(
        protected PlatformNotificationService $notifications,
        protected AuditService $audit,
        protected StoredFileService $files,
        protected RBACService $rbac,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array{text: string, category?: ?string, weight?: float|int, max_score?: float|int, requires_evidence?: bool}>  $items
     * @param  list<int>  $departmentIds
     */
    public function createPlan(User $user, array $data, array $items, array $departmentIds): QaPlan
    {
        $staffId = $user->staff_id;
        abort_unless($staffId, 422, 'A staff profile is required to create QA assessment sheets.');

        return DB::transaction(function () use ($user, $data, $items, $departmentIds, $staffId) {
            $plan = QaPlan::query()->create([
                'plan_name' => $data['plan_name'],
                'description' => $data['description'] ?? null,
                'instructions' => $data['instructions'] ?? null,
                'period_start' => $data['period_start'],
                'period_end' => $data['period_end'],
                'due_at' => $data['due_at'] ?? null,
                'pass_threshold' => $data['pass_threshold'] ?? 70,
                'scope_type' => count($departmentIds) > 1 ? 'department_specific' : (count($departmentIds) === 1 ? 'department_specific' : 'institution_wide'),
                'department_ids' => array_values(array_map('intval', $departmentIds)),
                'status' => 'draft',
                'created_by' => $staffId,
                'deployed_by' => null,
                'deployed_at' => null,
            ]);

            $this->syncChecklistItems($plan, $items);

            $this->audit->log(
                'qa.plan.created',
                'qa_plans',
                $plan->id,
                null,
                $plan->only(['plan_name', 'status', 'department_ids']),
                null,
                'success',
                $user->id,
            );

            return $plan->fresh('checklists');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array{text: string, category?: ?string, weight?: float|int, max_score?: float|int, requires_evidence?: bool}>  $items
     * @param  list<int>  $departmentIds
     */
    public function updatePlan(User $user, QaPlan $plan, array $data, array $items, array $departmentIds): QaPlan
    {
        abort_unless($plan->isDraft(), 422, 'Only draft assessment sheets can be edited.');

        return DB::transaction(function () use ($user, $plan, $data, $items, $departmentIds) {
            $old = $plan->only(['plan_name', 'department_ids', 'status']);

            $plan->update([
                'plan_name' => $data['plan_name'],
                'description' => $data['description'] ?? null,
                'instructions' => $data['instructions'] ?? null,
                'period_start' => $data['period_start'],
                'period_end' => $data['period_end'],
                'due_at' => $data['due_at'] ?? null,
                'pass_threshold' => $data['pass_threshold'] ?? 70,
                'scope_type' => count($departmentIds) > 0 ? 'department_specific' : 'institution_wide',
                'department_ids' => array_values(array_map('intval', $departmentIds)),
            ]);

            $plan->checklists()->delete();
            $this->syncChecklistItems($plan, $items);

            $this->audit->log(
                'qa.plan.updated',
                'qa_plans',
                $plan->id,
                $old,
                $plan->fresh()->only(['plan_name', 'department_ids', 'status']),
                null,
                'success',
                $user->id,
            );

            return $plan->fresh('checklists');
        });
    }

    public function dispatchPlan(User $user, QaPlan $plan): QaPlan
    {
        abort_unless($plan->isDraft(), 422, 'Only draft sheets can be dispatched.');
        abort_unless($plan->checklists()->where('is_active', 1)->exists(), 422, 'Add at least one evaluation criterion before dispatch.');
        abort_unless($plan->targetDepartmentIds() !== [], 422, 'Select at least one department before dispatch.');

        $staffId = $user->staff_id;
        abort_unless($staffId, 422, 'A staff profile is required to dispatch assessment sheets.');

        return DB::transaction(function () use ($user, $plan, $staffId) {
            $plan->load('checklists');
            $departmentIds = $plan->targetDepartmentIds();

            foreach ($departmentIds as $departmentId) {
                foreach ($plan->checklists->where('is_active', true) as $item) {
                    if ($item->applies_to_department_id && (int) $item->applies_to_department_id !== (int) $departmentId) {
                        continue;
                    }

                    QaDepartmentSubmission::query()->firstOrCreate(
                        [
                            'qa_plan_id' => $plan->id,
                            'checklist_item_id' => $item->id,
                            'department_id' => $departmentId,
                        ],
                        [
                            'submitted_by' => $staffId,
                            'submission_status' => 'pending',
                            'submitted_at' => now(),
                        ],
                    );
                }

                QaComplianceScore::query()->updateOrCreate(
                    [
                        'qa_plan_id' => $plan->id,
                        'department_id' => $departmentId,
                    ],
                    [
                        'total_items' => $plan->checklists->where('is_active', true)->count(),
                        'items_submitted' => 0,
                        'weighted_score' => 0,
                        'pass_fail_status' => 'pending',
                        'is_below_threshold' => 0,
                        'calculated_at' => now(),
                    ],
                );
            }

            $plan->update([
                'status' => 'dispatched',
                'deployed_by' => $staffId,
                'deployed_at' => now(),
                'dispatched_at' => now(),
            ]);

            $this->notifyDepartmentsOfDispatch($plan);

            $this->audit->log(
                'qa.plan.dispatched',
                'qa_plans',
                $plan->id,
                ['status' => 'draft'],
                ['status' => 'dispatched', 'departments' => $departmentIds],
                null,
                'success',
                $user->id,
            );

            return $plan->fresh(['checklists', 'complianceScores']);
        });
    }

    /**
     * @param  array<int, array{submission_text?: ?string, score?: float|int|null}>  $answers  keyed by checklist_item_id
     * @param  array<int, list<UploadedFile>>  $evidenceFiles  keyed by checklist_item_id
     */
    public function saveDepartmentResponses(
        User $user,
        QaPlan $plan,
        Department $department,
        array $answers,
        array $evidenceFiles = [],
        bool $finalSubmit = false,
    ): void {
        abort_unless($plan->isOpenForSubmission(), 422, 'This assessment sheet is not open for responses.');
        abort_unless(in_array((int) $department->id, $plan->targetDepartmentIds(), true), 403);
        abort_unless($this->userCanRespondForDepartment($user, $department), 403);

        $staffId = $user->staff_id;
        abort_unless($staffId, 422, 'A staff profile is required to submit QA responses.');

        DB::transaction(function () use ($user, $plan, $department, $answers, $evidenceFiles, $finalSubmit, $staffId) {
            $items = $plan->checklists()->where('is_active', 1)->get()->keyBy('id');

            foreach ($answers as $itemId => $payload) {
                $item = $items->get((int) $itemId);
                if (! $item) {
                    continue;
                }

                $submission = QaDepartmentSubmission::query()->firstOrCreate(
                    [
                        'qa_plan_id' => $plan->id,
                        'checklist_item_id' => $item->id,
                        'department_id' => $department->id,
                    ],
                    [
                        'submitted_by' => $staffId,
                        'submission_status' => 'draft',
                        'submitted_at' => now(),
                    ],
                );

                abort_unless($submission->isEditable() || ! $finalSubmit, 422, 'Some items are already locked and cannot be resubmitted.');

                if (! $submission->isEditable()) {
                    continue;
                }

                $score = isset($payload['score']) && $payload['score'] !== '' && $payload['score'] !== null
                    ? (float) $payload['score']
                    : null;

                if ($score !== null) {
                    $score = max(0, min((float) $item->max_score, $score));
                }

                $status = $finalSubmit ? 'submitted' : 'draft';

                if ($finalSubmit && $item->requires_evidence) {
                    $existingEvidence = QaEvidenceAttachment::query()
                        ->where('evidence_type', 'checklist_submission')
                        ->where('linked_id', $submission->id)
                        ->exists();
                    $newFiles = $evidenceFiles[$item->id] ?? [];
                    abort_unless($existingEvidence || $newFiles !== [], 422, "Evidence is required for: {$item->checklist_item_text}");
                }

                $submission->update([
                    'submitted_by' => $staffId,
                    'submission_text' => $payload['submission_text'] ?? null,
                    'score' => $score,
                    'submission_status' => $status,
                    'submitted_at' => now(),
                ]);

                foreach ($evidenceFiles[$item->id] ?? [] as $file) {
                    if ($file instanceof UploadedFile) {
                        $this->storeEvidence($submission, $file, $staffId);
                    }
                }
            }

            if ($plan->status === 'dispatched') {
                $plan->update(['status' => 'in_progress']);
            }

            $this->recalculateDepartmentScore($plan, $department);

            if ($finalSubmit) {
                $this->notifyQaOfSubmission($plan, $department, $user);
                $this->audit->log(
                    'qa.submission.submitted',
                    'qa_plans',
                    $plan->id,
                    null,
                    ['department_id' => $department->id],
                    null,
                    'success',
                    $user->id,
                );
            }
        });
    }

    public function compilePlan(User $user, QaPlan $plan): QaPlan
    {
        abort_unless(in_array($plan->status, ['dispatched', 'in_progress', 'compiled'], true), 422);

        return DB::transaction(function () use ($user, $plan) {
            foreach ($plan->targetDepartments() as $department) {
                $this->recalculateDepartmentScore($plan, $department);
            }

            $plan->update([
                'status' => 'compiled',
                'compiled_at' => now(),
            ]);

            $this->audit->log(
                'qa.plan.compiled',
                'qa_plans',
                $plan->id,
                null,
                ['status' => 'compiled'],
                null,
                'success',
                $user->id,
            );

            $this->notifyCeoOfCompiledReport($plan);

            try {
                $health = app(\App\Services\Me\MeHealthScoreService::class);
                foreach ($plan->targetDepartments() as $department) {
                    $health->recalculateForDepartment((int) $department->id);
                }
            } catch (\Throwable) {
                // Non-fatal: M&E health sync may run later from PIME workspace.
            }

            return $plan->fresh(['complianceScores.department', 'correctiveActions']);
        });
    }

    public function recalculateDepartmentScore(QaPlan $plan, Department $department): QaComplianceScore
    {
        $items = $plan->checklists()->where('is_active', 1)->get();
        $submissions = QaDepartmentSubmission::query()
            ->where('qa_plan_id', $plan->id)
            ->where('department_id', $department->id)
            ->whereIn('submission_status', ['submitted', 'verified', 'approved'])
            ->get()
            ->keyBy('checklist_item_id');

        $totalWeight = 0.0;
        $earned = 0.0;
        $submittedCount = 0;

        foreach ($items as $item) {
            $weight = (float) $item->weight;
            $max = max(0.01, (float) $item->max_score);
            $totalWeight += $weight;

            $submission = $submissions->get($item->id);
            if (! $submission || $submission->score === null) {
                continue;
            }

            $submittedCount++;
            $earned += ($weight * ((float) $submission->score / $max));
        }

        $weighted = $totalWeight > 0 ? round(($earned / $totalWeight) * 100, 2) : 0.0;
        $threshold = (float) ($plan->pass_threshold ?? 70);
        $below = $submittedCount > 0 && $weighted < $threshold;
        $passFail = $submittedCount === 0
            ? 'pending'
            : ($below ? 'fail' : 'pass');

        $score = QaComplianceScore::query()->updateOrCreate(
            [
                'qa_plan_id' => $plan->id,
                'department_id' => $department->id,
            ],
            [
                'total_items' => $items->count(),
                'items_submitted' => $submittedCount,
                'weighted_score' => $weighted,
                'pass_fail_status' => $passFail,
                'is_below_threshold' => $below ? 1 : 0,
                'threshold_met_at' => (! $below && $submittedCount > 0) ? now() : null,
                'calculated_at' => now(),
            ],
        );

        if ($below && $submittedCount === $items->count()) {
            $this->flagCorrectiveAction($plan, $department, $weighted);
        }

        return $score;
    }

    public function resolveCorrectiveAction(User $user, QaCorrectiveAction $action, string $notes): QaCorrectiveAction
    {
        $staffId = $user->staff_id;

        $action->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by' => $staffId,
            'resolution_notes' => $notes,
            'is_module_lock_active' => 0,
        ]);

        $this->audit->log(
            'qa.corrective_action.resolved',
            'qa_corrective_actions',
            $action->id,
            null,
            ['status' => 'resolved'],
            null,
            'success',
            $user->id,
        );

        return $action->fresh();
    }

    public function userCanRespondForDepartment(User $user, Department $department): bool
    {
        if ($this->rbac->isPlatformAdministrator($user) || $this->rbac->hasAnyRole($user, ['QA Officer', 'Assistant QA Officer', 'CEO', 'Super Admin'])) {
            return true;
        }

        if ($this->rbac->hasAnyRole($user, ['HOD', 'Administration Manager', 'Assistant Administrator'])) {
            $deptIds = $this->rbac->getUserDepartmentIds($user);
            if (in_array((int) $department->id, $deptIds, true)) {
                return true;
            }
        }

        if ($department->hod_id && $user->staff_id && (int) $department->hod_id === (int) $user->staff_id) {
            return true;
        }

        $staff = $user->staff_id ? Staff::query()->find($user->staff_id) : null;

        return $staff && (int) $staff->department_id === (int) $department->id;
    }

    /**
     * Departments the user may fill assessments for.
     *
     * @return Collection<int, Department>
     */
    public function respondableDepartments(User $user): Collection
    {
        if ($this->rbac->isPlatformAdministrator($user) || $this->rbac->hasAnyRole($user, ['QA Officer', 'Assistant QA Officer', 'CEO', 'Super Admin'])) {
            return Department::query()->active()->orderBy('dept_name')->get();
        }

        $ids = $this->rbac->getUserDepartmentIds($user);
        $staff = $user->staff_id ? Staff::query()->find($user->staff_id) : null;
        if ($staff?->department_id) {
            $ids[] = (int) $staff->department_id;
        }

        Department::query()
            ->where('hod_id', $user->staff_id)
            ->pluck('id')
            ->each(function ($id) use (&$ids) {
                $ids[] = (int) $id;
            });

        $ids = array_values(array_unique(array_filter($ids)));

        if ($ids === []) {
            return collect();
        }

        return Department::query()->whereIn('id', $ids)->active()->orderBy('dept_name')->get();
    }

    private function flagCorrectiveAction(QaPlan $plan, Department $department, float $score): void
    {
        $existing = QaCorrectiveAction::query()
            ->where('qa_plan_id', $plan->id)
            ->where('department_id', $department->id)
            ->whereIn('status', ['open', 'in_progress', 'overdue'])
            ->exists();

        if ($existing) {
            return;
        }

        $action = QaCorrectiveAction::query()->create([
            'qa_plan_id' => $plan->id,
            'department_id' => $department->id,
            'flagged_reason' => "Compliance score {$score}% fell below the {$plan->pass_threshold}% threshold for assessment \"{$plan->plan_name}\".",
            'compliance_score_at_flag' => $score,
            'resolution_deadline' => now()->addDays(14)->toDateString(),
            'status' => 'open',
            'responsible_officer_id' => $department->hod_id,
            'is_module_lock_active' => 0,
        ]);

        $this->notifyCorrectiveAction($action);
    }

    private function storeEvidence(QaDepartmentSubmission $submission, UploadedFile $file, int $staffId): QaEvidenceAttachment
    {
        $path = $this->files->store($file, 'qa/evidence', 'public');
        $mime = (string) $file->getMimeType();
        $type = str_starts_with($mime, 'image/') ? 'image'
            : (str_contains($mime, 'pdf') ? 'pdf' : 'document');

        return QaEvidenceAttachment::query()->create([
            'evidence_type' => 'checklist_submission',
            'linked_id' => $submission->id,
            'file_path' => $path,
            'file_type' => $type,
            'description' => $file->getClientOriginalName(),
            'uploaded_by' => $staffId,
            'uploaded_at' => now(),
        ]);
    }

    /**
     * @param  list<array{text: string, category?: ?string, weight?: float|int, max_score?: float|int, requires_evidence?: bool}>  $items
     */
    private function syncChecklistItems(QaPlan $plan, array $items): void
    {
        foreach (array_values($items) as $index => $item) {
            $text = trim((string) ($item['text'] ?? ''));
            if ($text === '') {
                continue;
            }

            QaAuditChecklist::query()->create([
                'qa_plan_id' => $plan->id,
                'checklist_item_text' => $text,
                'item_category' => $item['category'] ?? null,
                'weight' => $item['weight'] ?? 1,
                'max_score' => $item['max_score'] ?? 100,
                'requires_evidence' => ! empty($item['requires_evidence']) ? 1 : 0,
                'display_order' => $index + 1,
                'is_active' => 1,
            ]);
        }
    }

    private function notifyDepartmentsOfDispatch(QaPlan $plan): void
    {
        foreach ($plan->targetDepartments() as $department) {
            $userIds = $this->respondentUserIdsForDepartment($department);
            if ($userIds === []) {
                continue;
            }

            $this->notifications->notifyUsers(
                $userIds,
                'QA assessment sheet assigned',
                "\"{$plan->plan_name}\" has been dispatched to {$department->dept_name}. Complete and return it from your QA tasks.",
                'qa_plan',
                (string) $plan->id,
                'high',
                route('qa.tasks.show', ['plan' => $plan->id, 'department' => $department->id]),
            );
        }
    }

    private function notifyQaOfSubmission(QaPlan $plan, Department $department, User $submitter): void
    {
        $userIds = DB::table('user_roles as ur')
            ->join('roles as r', 'r.id', '=', 'ur.role_id')
            ->whereIn('r.role_name', ['QA Officer', 'Assistant QA Officer', 'Super Admin'])
            ->pluck('ur.user_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $this->notifications->notifyUsers(
            $userIds,
            'QA assessment returned',
            "{$department->dept_name} submitted responses for \"{$plan->plan_name}\" (by {$submitter->displayName()}).",
            'qa_plan',
            (string) $plan->id,
            'normal',
            route('qa.assessments.show', $plan),
        );
    }

    private function notifyCeoOfCompiledReport(QaPlan $plan): void
    {
        $userIds = DB::table('user_roles as ur')
            ->join('roles as r', 'r.id', '=', 'ur.role_id')
            ->whereIn('r.role_name', ['CEO', 'Super Admin'])
            ->pluck('ur.user_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $this->notifications->notifyUsers(
            $userIds,
            'Quality Level Report ready',
            "Assessment \"{$plan->plan_name}\" has been compiled. Review compliance scores and corrective actions in the CEO office.",
            'qa_plan',
            (string) $plan->id,
            'high',
            route('ceo.quality.index'),
        );
    }

    private function notifyCorrectiveAction(QaCorrectiveAction $action): void
    {
        $action->loadMissing('department', 'plan');
        $userIds = $this->respondentUserIdsForDepartment($action->department);

        $qaIds = DB::table('user_roles as ur')
            ->join('roles as r', 'r.id', '=', 'ur.role_id')
            ->whereIn('r.role_name', ['QA Officer', 'Assistant QA Officer', 'CEO', 'Super Admin'])
            ->pluck('ur.user_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->notifications->notifyUsers(
            array_values(array_unique(array_merge($userIds, $qaIds))),
            'Quality Corrective Action flagged',
            $action->flagged_reason,
            'qa_corrective_action',
            (string) $action->id,
            'high',
            route('qa.corrective-actions.index'),
        );
    }

    /**
     * @return list<int>
     */
    private function respondentUserIdsForDepartment(Department $department): array
    {
        $ids = [];

        if ($department->hod_id) {
            $hodUserId = DB::table('users')->where('staff_id', $department->hod_id)->value('id');
            if ($hodUserId) {
                $ids[] = (int) $hodUserId;
            }
        }

        $roleIds = DB::table('user_roles as ur')
            ->join('roles as r', 'r.id', '=', 'ur.role_id')
            ->whereIn('r.role_name', ['HOD', 'Administration Manager', 'Assistant Administrator'])
            ->where(function ($query) use ($department) {
                $query->where('ur.department_id', $department->id)
                    ->orWhereNull('ur.department_id');
            })
            ->pluck('ur.user_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $staffUserIds = [];
        if (Schema::hasTable('staff') && Schema::hasTable('users')) {
            $staffUserIds = DB::table('users')
                ->whereIn('staff_id', function ($query) use ($department) {
                    $query->select('id')->from('staff')->where('department_id', $department->id);
                })
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return array_values(array_unique(array_merge($ids, $roleIds, $staffUserIds)));
    }
}
