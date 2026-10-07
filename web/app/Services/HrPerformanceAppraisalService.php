<?php

namespace App\Services;

use App\Models\HrAppraisal;
use App\Models\HrAppraisalCompetency;
use App\Models\HrAppraisalCycle;
use App\Models\HrAppraisalGoal;
use App\Models\HrCorporateGoal;
use App\Models\Staff;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class HrPerformanceAppraisalService
{
    public function __construct(
        protected AuditService $auditService,
        protected PlatformNotificationService $notifications,
        protected HrSidebarNotificationService $hrSidebar,
    ) {}

    public function ratingScale(): array
    {
        return config('tich-performance-appraisals.rating_scale', []);
    }

    public function competencyCatalog(): array
    {
        return config('tich-performance-appraisals.competencies', []);
    }

    public function createCycle(Staff $hrStaff, array $data): HrAppraisalCycle
    {
        $cycle = HrAppraisalCycle::query()->create([
            'name' => $data['name'],
            'fiscal_year' => (int) $data['fiscal_year'],
            'quarter' => (int) $data['quarter'],
            'period_start' => $data['period_start'],
            'period_end' => $data['period_end'],
            'status' => 'draft',
            'initiated_by' => $hrStaff->id,
            'instructions' => $data['instructions'] ?? null,
        ]);

        $this->auditService->log(
            'hr.appraisal_cycle.created',
            'hr_appraisal_cycles',
            $cycle->id,
            null,
            $cycle->only(['name', 'fiscal_year', 'quarter', 'period_start', 'period_end']),
            null,
            'success',
            $hrStaff->user_id
        );

        return $cycle;
    }

    public function updateCycle(HrAppraisalCycle $cycle, array $data): HrAppraisalCycle
    {
        if (in_array($cycle->status, ['closed'], true)) {
            throw new \RuntimeException('Closed cycles cannot be edited.');
        }

        $old = $cycle->only(['name', 'period_start', 'period_end', 'instructions']);
        $cycle->update([
            'name' => $data['name'] ?? $cycle->name,
            'period_start' => $data['period_start'] ?? $cycle->period_start,
            'period_end' => $data['period_end'] ?? $cycle->period_end,
            'instructions' => $data['instructions'] ?? $cycle->instructions,
        ]);

        $this->auditService->log(
            'hr.appraisal_cycle.updated',
            'hr_appraisal_cycles',
            $cycle->id,
            $old,
            $cycle->only(['name', 'period_start', 'period_end', 'instructions']),
            null,
            'success'
        );

        return $cycle->fresh();
    }

    /**
     * Open cycle and create appraisal shells for active staff (and on_leave).
     * Resignations/terminations are excluded from new shells; existing appraisals remain.
     */
    public function openCycle(HrAppraisalCycle $cycle, Staff $hrStaff): HrAppraisalCycle
    {
        if ($cycle->status !== 'draft') {
            throw new \RuntimeException('Only draft cycles can be opened.');
        }

        return DB::transaction(function () use ($cycle, $hrStaff) {
            $staffRows = Staff::query()
                ->excludePlatformOperators()
                ->whereIn('employment_status', ['active', 'on_leave', 'onboarding'])
                ->with('lineManager')
                ->get();

            foreach ($staffRows as $staff) {
                $this->createAppraisalShell($cycle, $staff);
            }

            $cycle->update([
                'status' => 'open',
                'opened_at' => now(),
            ]);

            $this->auditService->log(
                'hr.appraisal_cycle.opened',
                'hr_appraisal_cycles',
                $cycle->id,
                ['status' => 'draft'],
                ['status' => 'open', 'shells' => $staffRows->count()],
                null,
                'success',
                $hrStaff->user_id
            );

            foreach ($staffRows as $staff) {
                if ($staff->user_id) {
                    $this->notifications->notifyUser(
                        (int) $staff->user_id,
                        'Performance appraisal opened',
                        sprintf('Your %s appraisal is open. Set your SMART goals for supervisor approval.', $cycle->label()),
                        'hr_appraisal',
                        (string) $cycle->id,
                        'normal',
                        route('employee.appraisals.index'),
                        true
                    );
                }
            }

            $this->hrSidebar->broadcastCounts();

            return $cycle->fresh();
        });
    }

    public function createAppraisalShell(HrAppraisalCycle $cycle, Staff $staff): HrAppraisal
    {
        $existing = HrAppraisal::query()
            ->where('cycle_id', $cycle->id)
            ->where('staff_id', $staff->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $appraisal = HrAppraisal::query()->create([
            'appraisal_number' => $this->nextAppraisalNumber($cycle),
            'cycle_id' => $cycle->id,
            'staff_id' => $staff->id,
            'line_manager_id' => $staff->line_manager_id,
            'department_id' => $staff->department_id,
            'job_title_snapshot' => $staff->job_title,
            'job_description_snapshot' => $staff->job_description,
            'status' => 'draft_goals',
        ]);

        $jdWeight = (float) config('tich-performance-appraisals.jd_objective_default_weight', 40);

        HrAppraisalGoal::query()->create([
            'appraisal_id' => $appraisal->id,
            'sort_order' => 1,
            'goal_type' => 'jd_duties',
            'title' => 'Achieve all duties outlined in the Job Description',
            'description' => $staff->job_description ?: 'Complete all duties applicable to this role as set out in the job description.',
            'weight' => $jdWeight,
            'status' => 'draft',
        ]);

        $this->seedCompetencies($appraisal, $staff);

        return $appraisal;
    }

    public function seedCompetencies(HrAppraisal $appraisal, Staff $staff): void
    {
        $hasSubordinates = $staff->subordinates()->exists()
            || Staff::query()->where('line_manager_id', $staff->id)->exists();

        foreach ($this->competencyCatalog() as $category => $group) {
            $optional = ! empty($group['optional']);
            foreach ($group['items'] as $key => $label) {
                $applicable = true;
                if ($category === 'managerial' && ! $hasSubordinates) {
                    $applicable = false;
                }
                if ($optional) {
                    $applicable = false; // employee/manager tick applicable functional items
                }

                HrAppraisalCompetency::query()->create([
                    'appraisal_id' => $appraisal->id,
                    'category' => $category,
                    'competency_key' => $key,
                    'competency_label' => $label,
                    'is_applicable' => $applicable,
                ]);
            }
        }
    }

    public function nextAppraisalNumber(HrAppraisalCycle $cycle): string
    {
        $seq = HrAppraisal::query()->where('cycle_id', $cycle->id)->count() + 1;

        return sprintf('APR-%d-Q%d-%04d', $cycle->fiscal_year, $cycle->quarter, $seq);
    }

    public function saveCorporateGoal(Staff $hrStaff, array $data, ?HrCorporateGoal $goal = null): HrCorporateGoal
    {
        $payload = [
            'cycle_id' => $data['cycle_id'] ?? null,
            'parent_id' => $data['parent_id'] ?? null,
            'code' => $data['code'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'role_scope' => $data['role_scope'] ?? 'all',
            'scope_values' => $data['scope_values'] ?? null,
            'weight_hint' => $data['weight_hint'] ?? null,
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true,
        ];

        if ($goal) {
            $goal->update($payload);

            return $goal->fresh();
        }

        $payload['created_by'] = $hrStaff->id;

        return HrCorporateGoal::query()->create($payload);
    }

    /**
     * @return Collection<int, HrCorporateGoal>
     */
    public function cascadingGoalsFor(Staff $staff, ?int $cycleId = null): Collection
    {
        return HrCorporateGoal::query()
            ->where('is_active', true)
            ->when($cycleId, fn ($q) => $q->where(function ($inner) use ($cycleId) {
                $inner->whereNull('cycle_id')->orWhere('cycle_id', $cycleId);
            }))
            ->orderBy('title')
            ->get()
            ->filter(fn (HrCorporateGoal $goal) => $goal->appliesTo($staff))
            ->values();
    }

    public function upsertGoal(HrAppraisal $appraisal, array $data, ?HrAppraisalGoal $goal = null): HrAppraisalGoal
    {
        if (! $appraisal->canEmployeeEditGoals() && ! in_array($appraisal->status, ['draft_goals', 'goals_pending_approval'], true)) {
            throw new \RuntimeException('Goals can only be edited during goal setting.');
        }

        if ($goal && $goal->isJdDuties() && (($data['goal_type'] ?? null) && $data['goal_type'] !== 'jd_duties')) {
            throw new \RuntimeException('The Job Description objective cannot change type.');
        }

        $payload = [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'smart_specific' => $data['smart_specific'] ?? null,
            'smart_measurable' => $data['smart_measurable'] ?? null,
            'smart_achievable' => $data['smart_achievable'] ?? null,
            'smart_relevant' => $data['smart_relevant'] ?? null,
            'smart_timebound' => $data['smart_timebound'] ?? null,
            'target_date' => $data['target_date'] ?? null,
            'weight' => (float) ($data['weight'] ?? 0),
            'corporate_goal_id' => $data['corporate_goal_id'] ?? null,
            'goal_type' => $goal?->goal_type ?? ($data['corporate_goal_id'] ?? null ? 'cascaded' : ($data['goal_type'] ?? 'personal')),
            'status' => 'draft',
        ];

        if ($goal) {
            if ($goal->isJdDuties()) {
                $payload['goal_type'] = 'jd_duties';
            }
            $goal->update($payload);

            return $goal->fresh();
        }

        $maxOrder = (int) $appraisal->goals()->max('sort_order');
        $payload['appraisal_id'] = $appraisal->id;
        $payload['sort_order'] = $maxOrder + 1;

        return HrAppraisalGoal::query()->create($payload);
    }

    public function deleteGoal(HrAppraisalGoal $goal): void
    {
        if ($goal->isJdDuties()) {
            throw new \RuntimeException('Objective 1 (Job Description duties) cannot be removed.');
        }

        $appraisal = $goal->appraisal;
        if (! $appraisal || ! $appraisal->canEmployeeEditGoals()) {
            throw new \RuntimeException('Goals cannot be deleted at this stage.');
        }

        $goal->delete();
    }

    public function submitGoalsForApproval(HrAppraisal $appraisal, Staff $employee): HrAppraisal
    {
        if ($appraisal->staff_id !== $employee->id) {
            throw new \RuntimeException('Only the appraisee can submit goals.');
        }

        if (! in_array($appraisal->status, ['draft_goals', 'goals_pending_approval'], true)) {
            throw new \RuntimeException('Goals are not editable.');
        }

        $this->assertGoalsReady($appraisal);

        $appraisal->update([
            'status' => 'goals_pending_approval',
            'goals_submitted_at' => now(),
        ]);

        $appraisal->goals()->update(['status' => 'pending_supervisor']);

        $manager = $appraisal->lineManager;
        if ($manager?->user_id) {
            $this->notifications->notifyUser(
                (int) $manager->user_id,
                'Goals awaiting your approval',
                sprintf('%s submitted appraisal goals for %s.', $employee->fullName(), $appraisal->cycle?->label() ?? 'this cycle'),
                'hr_appraisal',
                (string) $appraisal->id,
                'normal',
                route('employee.appraisals.team.show', $appraisal),
                true
            );
        }

        return $appraisal->fresh(['goals', 'cycle']);
    }

    public function approveGoals(HrAppraisal $appraisal, Staff $manager, ?string $notes = null): HrAppraisal
    {
        $this->assertIsLineManager($appraisal, $manager);

        if ($appraisal->status !== 'goals_pending_approval') {
            throw new \RuntimeException('No goals pending approval.');
        }

        $this->assertGoalsReady($appraisal);

        $appraisal->update([
            'status' => 'self_assessment',
            'goals_approved_at' => now(),
            'goals_approved_by' => $manager->id,
            'manager_objectives_comments' => $notes ?: $appraisal->manager_objectives_comments,
        ]);
        $appraisal->goals()->update(['status' => 'approved']);

        if ($appraisal->staff?->user_id) {
            $this->notifications->notifyUser(
                (int) $appraisal->staff->user_id,
                'Appraisal goals approved',
                'Your supervisor approved your goals. Complete your self-assessment.',
                'hr_appraisal',
                (string) $appraisal->id,
                'normal',
                route('employee.appraisals.show', $appraisal),
                true
            );
        }

        return $appraisal->fresh();
    }

    public function returnGoals(HrAppraisal $appraisal, Staff $manager, string $reason): HrAppraisal
    {
        $this->assertIsLineManager($appraisal, $manager);

        if ($appraisal->status !== 'goals_pending_approval') {
            throw new \RuntimeException('No goals pending approval.');
        }

        $appraisal->update([
            'status' => 'draft_goals',
            'manager_objectives_comments' => $reason,
            'goals_submitted_at' => null,
        ]);
        $appraisal->goals()->update(['status' => 'draft']);

        if ($appraisal->staff?->user_id) {
            $this->notifications->notifyUser(
                (int) $appraisal->staff->user_id,
                'Appraisal goals returned',
                'Your supervisor returned your goals for changes: '.$reason,
                'hr_appraisal',
                (string) $appraisal->id,
                'high',
                route('employee.appraisals.show', $appraisal),
                true
            );
        }

        return $appraisal->fresh();
    }

    public function saveSelfAssessment(HrAppraisal $appraisal, Staff $employee, array $data): HrAppraisal
    {
        if ($appraisal->staff_id !== $employee->id) {
            throw new \RuntimeException('Only the appraisee can update the self-assessment.');
        }

        if ($appraisal->status !== 'self_assessment') {
            throw new \RuntimeException('Self-assessment is not open.');
        }

        foreach ($data['goals'] ?? [] as $goalId => $goalData) {
            $goal = $appraisal->goals()->where('id', $goalId)->first();
            if (! $goal) {
                continue;
            }
            $goal->update([
                'employee_achievement' => $goalData['employee_achievement'] ?? $goal->employee_achievement,
                'self_rating' => isset($goalData['self_rating']) && $goalData['self_rating'] !== ''
                    ? (int) $goalData['self_rating']
                    : null,
            ]);
        }

        foreach ($data['competencies'] ?? [] as $compId => $compData) {
            $comp = $appraisal->competencies()->where('id', $compId)->first();
            if (! $comp) {
                continue;
            }
            $comp->update([
                'is_applicable' => array_key_exists('is_applicable', $compData)
                    ? filter_var($compData['is_applicable'], FILTER_VALIDATE_BOOLEAN)
                    : $comp->is_applicable,
                'self_rating' => isset($compData['self_rating']) && $compData['self_rating'] !== ''
                    ? (int) $compData['self_rating']
                    : null,
            ]);
        }

        $appraisal->update([
            'employee_self_comments' => $data['employee_self_comments'] ?? $appraisal->employee_self_comments,
        ]);

        return $appraisal->fresh(['goals', 'competencies']);
    }

    public function submitSelfAssessment(HrAppraisal $appraisal, Staff $employee, array $data): HrAppraisal
    {
        $appraisal = $this->saveSelfAssessment($appraisal, $employee, $data);
        $appraisal->load(['goals', 'competencies']);

        foreach ($appraisal->goals as $goal) {
            if (blank($goal->employee_achievement) || ! $goal->self_rating) {
                throw new \RuntimeException('Complete achievements and self-ratings for all objectives before submitting.');
            }
        }

        $coreApplicable = $appraisal->competencies()->where('category', 'core')->where('is_applicable', true)->get();
        foreach ($coreApplicable as $comp) {
            if (! $comp->self_rating) {
                throw new \RuntimeException('Rate all applicable core competencies before submitting.');
            }
        }

        $appraisal->update([
            'status' => 'manager_review',
            'self_submitted_at' => now(),
        ]);

        $manager = $appraisal->lineManager;
        if ($manager?->user_id) {
            $this->notifications->notifyUser(
                (int) $manager->user_id,
                'Appraisal ready for manager review',
                sprintf('%s submitted a self-assessment for %s.', $employee->fullName(), $appraisal->cycle?->label() ?? 'this cycle'),
                'hr_appraisal',
                (string) $appraisal->id,
                'normal',
                route('employee.appraisals.team.show', $appraisal),
                true
            );
        }

        return $appraisal->fresh();
    }

    public function saveManagerReview(HrAppraisal $appraisal, Staff $manager, array $data): HrAppraisal
    {
        $this->assertIsLineManager($appraisal, $manager);

        if ($appraisal->status !== 'manager_review') {
            throw new \RuntimeException('Manager review is not open for this appraisal.');
        }

        foreach ($data['goals'] ?? [] as $goalId => $goalData) {
            $goal = $appraisal->goals()->where('id', $goalId)->first();
            if (! $goal) {
                continue;
            }
            $goal->update([
                'manager_rating' => isset($goalData['manager_rating']) && $goalData['manager_rating'] !== ''
                    ? (int) $goalData['manager_rating']
                    : null,
                'manager_comments' => $goalData['manager_comments'] ?? $goal->manager_comments,
            ]);
        }

        foreach ($data['competencies'] ?? [] as $compId => $compData) {
            $comp = $appraisal->competencies()->where('id', $compId)->first();
            if (! $comp) {
                continue;
            }
            $comp->update([
                'is_applicable' => array_key_exists('is_applicable', $compData)
                    ? filter_var($compData['is_applicable'], FILTER_VALIDATE_BOOLEAN)
                    : $comp->is_applicable,
                'manager_rating' => isset($compData['manager_rating']) && $compData['manager_rating'] !== ''
                    ? (int) $compData['manager_rating']
                    : null,
                'comments' => $compData['comments'] ?? $comp->comments,
            ]);
        }

        $appraisal->update([
            'manager_objectives_comments' => $data['manager_objectives_comments'] ?? $appraisal->manager_objectives_comments,
            'manager_competencies_comments' => $data['manager_competencies_comments'] ?? $appraisal->manager_competencies_comments,
            'strengths' => $data['strengths'] ?? $appraisal->strengths,
            'development_areas' => $data['development_areas'] ?? $appraisal->development_areas,
            'training_recommendations' => $data['training_recommendations'] ?? $appraisal->training_recommendations,
        ]);

        $this->recalculateScores($appraisal->fresh(['goals', 'competencies']));

        return $appraisal->fresh(['goals', 'competencies']);
    }

    public function submitManagerReview(HrAppraisal $appraisal, Staff $manager, array $data): HrAppraisal
    {
        $appraisal = $this->saveManagerReview($appraisal, $manager, $data);
        $appraisal->load(['goals', 'competencies']);

        foreach ($appraisal->goals as $goal) {
            if (! $goal->manager_rating) {
                throw new \RuntimeException('Provide manager ratings for all objectives.');
            }
            if (in_array((int) $goal->manager_rating, [1, 5], true) && blank($goal->manager_comments) && blank($data['manager_objectives_comments'] ?? null)) {
                throw new \RuntimeException('Justify extreme ratings (1 or 5) in comments.');
            }
        }

        foreach ($appraisal->competencies()->where('is_applicable', true)->get() as $comp) {
            if (! $comp->manager_rating) {
                throw new \RuntimeException('Rate all applicable competencies.');
            }
        }

        $cycle = $appraisal->cycle;
        $nextStatus = $cycle && $cycle->status === 'calibration' ? 'pending_calibration' : 'pending_hr';

        $appraisal->update([
            'status' => $nextStatus,
            'manager_submitted_at' => now(),
            'manager_reviewed_by' => $manager->id,
        ]);

        $this->recalculateScores($appraisal->fresh(['goals', 'competencies']));
        $this->hrSidebar->broadcastCounts();

        return $appraisal->fresh();
    }

    public function startCalibration(HrAppraisalCycle $cycle, Staff $hrStaff): HrAppraisalCycle
    {
        if (! in_array($cycle->status, ['open', 'calibration'], true)) {
            throw new \RuntimeException('Calibration can only start on an open cycle.');
        }

        $cycle->update([
            'status' => 'calibration',
            'calibration_started_at' => $cycle->calibration_started_at ?? now(),
        ]);

        HrAppraisal::query()
            ->where('cycle_id', $cycle->id)
            ->where('status', 'pending_hr')
            ->update(['status' => 'pending_calibration']);

        $this->auditService->log(
            'hr.appraisal_cycle.calibration',
            'hr_appraisal_cycles',
            $cycle->id,
            null,
            ['status' => 'calibration'],
            null,
            'success',
            $hrStaff->user_id
        );

        $this->hrSidebar->broadcastCounts();

        return $cycle->fresh();
    }

    public function calibrateAppraisal(HrAppraisal $appraisal, Staff $hrStaff, float $adjustedScore, string $reason): HrAppraisal
    {
        if (! in_array($appraisal->status, ['pending_calibration', 'pending_hr', 'manager_review'], true)
            && $appraisal->cycle?->status !== 'calibration') {
            throw new \RuntimeException('This appraisal is not in calibration.');
        }

        if ($adjustedScore < 1 || $adjustedScore > 5) {
            throw new \RuntimeException('Calibrated score must be between 1 and 5.');
        }

        $previous = $appraisal->finalScore();
        $appraisal->update([
            'calibrated_score' => round($adjustedScore, 2),
            'overall_rating' => $this->ratingSlugFromScore($adjustedScore),
            'calibration_reason' => $reason,
            'calibrated_at' => now(),
            'calibrated_by' => $hrStaff->id,
            'status' => 'pending_hr',
        ]);

        $this->auditService->log(
            'hr.appraisal.calibrated',
            'hr_appraisals',
            $appraisal->id,
            ['score' => $previous],
            ['calibrated_score' => $adjustedScore, 'reason' => $reason],
            null,
            'success',
            $hrStaff->user_id
        );

        return $appraisal->fresh();
    }

    public function hrSignOff(HrAppraisal $appraisal, Staff $hrStaff, array $data): HrAppraisal
    {
        if (! in_array($appraisal->status, ['pending_hr', 'pending_calibration'], true)) {
            throw new \RuntimeException('Appraisal is not awaiting HR sign-off.');
        }

        if ($appraisal->manager_submitted_at === null) {
            throw new \RuntimeException('Manager review must be completed first.');
        }

        $this->recalculateScores($appraisal->fresh(['goals', 'competencies']));

        $appraisal->update([
            'status' => 'completed',
            'hr_comments' => $data['hr_comments'] ?? $appraisal->hr_comments,
            'staff_agrees' => (bool) ($data['staff_agrees'] ?? $appraisal->staff_agrees),
            'hr_signed_at' => now(),
            'hr_signed_by' => $hrStaff->id,
            'completed_at' => now(),
            'overall_rating' => $this->ratingSlugFromScore($appraisal->fresh()->finalScore() ?? 3),
        ]);

        if ($appraisal->staff?->user_id) {
            $this->notifications->notifyUser(
                (int) $appraisal->staff->user_id,
                'Performance appraisal completed',
                sprintf('Your %s appraisal has been signed off by HR.', $appraisal->cycle?->label() ?? 'performance'),
                'hr_appraisal',
                (string) $appraisal->id,
                'normal',
                route('employee.appraisals.show', $appraisal),
                true
            );
        }

        $this->auditService->log(
            'hr.appraisal.hr_signed',
            'hr_appraisals',
            $appraisal->id,
            null,
            ['status' => 'completed', 'final_score' => $appraisal->finalScore()],
            null,
            'success',
            $hrStaff->user_id
        );

        $this->hrSidebar->broadcastCounts();

        return $appraisal->fresh();
    }

    public function closeCycle(HrAppraisalCycle $cycle, Staff $hrStaff): HrAppraisalCycle
    {
        $pending = $cycle->appraisals()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->count();

        if ($pending > 0) {
            throw new \RuntimeException("Cannot close cycle while {$pending} appraisal(s) are still open.");
        }

        $cycle->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        $this->auditService->log(
            'hr.appraisal_cycle.closed',
            'hr_appraisal_cycles',
            $cycle->id,
            null,
            ['status' => 'closed'],
            null,
            'success',
            $hrStaff->user_id
        );

        return $cycle->fresh();
    }

    public function recalculateScores(HrAppraisal $appraisal): void
    {
        $goals = $appraisal->goals;
        $weightTotal = (float) $goals->sum('weight');
        $objectives = null;

        if ($weightTotal > 0 && $goals->whereNotNull('manager_rating')->isNotEmpty()) {
            $sum = 0.0;
            foreach ($goals as $goal) {
                if ($goal->manager_rating === null) {
                    continue;
                }
                $sum += ((float) $goal->weight / $weightTotal) * (int) $goal->manager_rating;
            }
            $objectives = round($sum, 2);
        } elseif ($goals->whereNotNull('manager_rating')->isNotEmpty()) {
            $objectives = round((float) $goals->avg('manager_rating'), 2);
        }

        $comps = $appraisal->competencies->where('is_applicable', true)->whereNotNull('manager_rating');
        $competencies = $comps->isNotEmpty() ? round((float) $comps->avg('manager_rating'), 2) : null;

        $blend = config('tich-performance-appraisals.score_blend', ['objectives' => 0.6, 'competencies' => 0.4]);
        $overall = null;
        if ($objectives !== null && $competencies !== null) {
            $overall = round(
                ($objectives * (float) $blend['objectives']) + ($competencies * (float) $blend['competencies']),
                2
            );
        } elseif ($objectives !== null) {
            $overall = $objectives;
        } elseif ($competencies !== null) {
            $overall = $competencies;
        }

        $appraisal->update([
            'objectives_score' => $objectives,
            'competencies_score' => $competencies,
            'overall_score' => $overall,
            'overall_rating' => $overall !== null
                ? $this->ratingSlugFromScore($appraisal->calibrated_score ?? $overall)
                : $appraisal->overall_rating,
        ]);
    }

    public function ratingSlugFromScore(float $score): string
    {
        $rounded = (int) max(1, min(5, (int) round($score)));
        $scale = $this->ratingScale();

        return $scale[$rounded]['slug'] ?? 'good';
    }

    public function assertGoalsReady(HrAppraisal $appraisal): void
    {
        $goals = $appraisal->goals()->get();
        if ($goals->where('goal_type', 'jd_duties')->isEmpty()) {
            throw new \RuntimeException('Objective 1 (Job Description) is required.');
        }

        if ($goals->count() < 2) {
            throw new \RuntimeException('Add at least one additional objective beyond the Job Description.');
        }

        foreach ($goals as $goal) {
            if ($goal->goal_type === 'jd_duties') {
                continue;
            }
            foreach (['smart_specific', 'smart_measurable', 'smart_achievable', 'smart_relevant', 'smart_timebound'] as $field) {
                if (blank($goal->{$field})) {
                    throw new \RuntimeException('Complete all SMART fields for each personal/cascaded goal before submitting.');
                }
            }
            if (blank($goal->title)) {
                throw new \RuntimeException('Each goal needs a title.');
            }
        }

        if (config('tich-performance-appraisals.require_weight_total_100', true)) {
            $total = round((float) $goals->sum('weight'), 2);
            if (abs($total - 100.0) > 0.01) {
                throw new \RuntimeException("Goal weights must total 100% (currently {$total}%).");
            }
        }
    }

    public function assertIsLineManager(HrAppraisal $appraisal, Staff $manager): void
    {
        if ((int) $appraisal->line_manager_id !== (int) $manager->id) {
            throw new \RuntimeException('Only the snapshotted immediate manager can act on this appraisal.');
        }
    }

    public function hrIndex(array $filters): LengthAwarePaginator
    {
        return HrAppraisal::query()
            ->with(['staff.department', 'lineManager', 'cycle'])
            ->when($filters['cycle_id'] ?? null, fn ($q, $id) => $q->where('cycle_id', $id))
            ->when(($filters['status'] ?? null) && ($filters['status'] !== 'all'), fn ($q) => $q->where('status', $filters['status']))
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->whereHas('staff', function ($staff) use ($search) {
                    $staff->where('first_name', 'like', "%{$search}%")
                        ->orWhere('surname', 'like', "%{$search}%")
                        ->orWhere('employee_number', 'like', "%{$search}%");
                });
            })
            ->orderByRaw("FIELD(status,'pending_hr','pending_calibration','manager_review','goals_pending_approval','self_assessment','draft_goals','completed','cancelled')")
            ->orderByDesc('updated_at')
            ->paginate(25)
            ->withQueryString();
    }

    public function employeeIndex(Staff $staff): Collection
    {
        return HrAppraisal::query()
            ->with(['cycle', 'lineManager'])
            ->where('staff_id', $staff->id)
            ->orderByDesc('id')
            ->get();
    }

    public function teamIndex(Staff $manager): Collection
    {
        return HrAppraisal::query()
            ->with(['staff.department', 'cycle'])
            ->where('line_manager_id', $manager->id)
            ->whereNotIn('status', ['cancelled'])
            ->orderByRaw("FIELD(status,'goals_pending_approval','manager_review','self_assessment','pending_calibration','pending_hr','draft_goals','completed')")
            ->orderByDesc('updated_at')
            ->get();
    }

    public function cycleCompletionStats(HrAppraisalCycle $cycle): array
    {
        $rows = $cycle->appraisals()->select('status')->get();
        $total = $rows->count();
        $completed = $rows->where('status', 'completed')->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'pending_hr' => $rows->whereIn('status', ['pending_hr', 'pending_calibration'])->count(),
            'in_progress' => $rows->whereNotIn('status', ['completed', 'cancelled'])->count(),
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
        ];
    }
}
