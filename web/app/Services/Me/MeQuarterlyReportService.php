<?php

namespace App\Services\Me;

use App\Models\Department;
use App\Models\Me\MeQuarterlyReport;
use App\Models\Me\MeQuarterlyReportLine;
use App\Models\Me\MeTechnicalPlan;
use App\Models\User;
use App\Services\PlatformNotificationService;
use App\Services\RBACService;
use App\Services\StaffPortalService;
use Illuminate\Support\Facades\DB;

class MeQuarterlyReportService
{
    public function __construct(
        protected StaffPortalService $staffPortal,
        protected PlatformNotificationService $notifications,
        protected MeHealthScoreService $healthScores,
        protected RBACService $rbac,
    ) {}

    /**
     * Quarterly achievement reports are compiled by M&E (not department HODs).
     */
    public function userCanEnterAchieved(User $user, MeQuarterlyReport $report): bool
    {
        if (! $this->userIsMeReviewer($user)) {
            return false;
        }

        return in_array($report->status, ['draft', 'returned'], true);
    }

    public function userCanViewDepartmentReport(User $user, ?Department $department): bool
    {
        if ($department === null) {
            return false;
        }

        if ($this->userIsMeReviewer($user)) {
            return true;
        }

        return $this->userIsDepartmentRespondent($user, $department);
    }

    /** @deprecated Use userCanViewDepartmentReport / userCanEnterAchieved */
    public function userCanEditDepartmentReport(User $user, Department $department): bool
    {
        return $this->userIsMeReviewer($user);
    }

    /**
     * @deprecated HODs no longer submit quarterly reports.
     */
    public function userCanSubmitDepartmentReport(User $user, Department $department): bool
    {
        return false;
    }

    public function userIsDepartmentRespondent(User $user, Department $department): bool
    {
        $staff = $this->staffPortal->staffForUser($user);

        if ($staff) {
            if ($department->hod_id !== null && (int) $department->hod_id === (int) $staff->id) {
                return true;
            }

            if ($staff->department_id !== null && (int) $staff->department_id === (int) $department->id) {
                return true;
            }
        }

        // The Academics hub is institution-wide and has no HOD of its own, so the
        // officers who file its technical plan also file its quarterly report.
        if ($department->isAcademicsHub()
            && app(\App\Services\AcademicsAccessService::class)->canAccessAll($user)) {
            return true;
        }

        // Fallback for staff records that are unlinked or stale: a role scoped to
        // the department still makes the holder its M&E respondent.
        return in_array(
            (int) $department->id,
            array_map('intval', $this->rbac->getUserDepartmentIds($user)),
            true
        );
    }

    public function userIsMeReviewer(User $user): bool
    {
        return $user->hasAnyRole([
            'Super Admin',
            'Monitoring and Evaluation Officer',
            'Assistant Monitoring and Evaluation Officer',
        ]);
    }

    /**
     * @param  list<array{id?: int, achieved: float|int|string}>  $lines
     */
    public function saveDraft(MeQuarterlyReport $report, array $lines): MeQuarterlyReport
    {
        if (! in_array($report->status, ['draft', 'returned'], true)) {
            throw new \RuntimeException('Only draft or returned reports can be edited.');
        }

        foreach ($lines as $row) {
            $line = MeQuarterlyReportLine::query()
                ->where('quarterly_report_id', $report->id)
                ->where('id', (int) ($row['id'] ?? 0))
                ->first();

            if (! $line) {
                continue;
            }

            $achieved = round((float) $row['achieved'], 2);
            $planned = (float) $line->planned;
            $line->update([
                'achieved' => $achieved,
                'deviation' => round($achieved - $planned, 2),
            ]);
        }

        return $report->fresh('lines');
    }

    public function submit(MeQuarterlyReport $report, User $user): MeQuarterlyReport
    {
        if (! in_array($report->status, ['draft', 'returned'], true)) {
            throw new \RuntimeException('This report cannot be submitted in its current status.');
        }

        abort_unless($this->userIsMeReviewer($user), 403);

        $report->update([
            'status' => 'submitted',
            'submitted_by' => $user->id,
            'submitted_at' => now(),
        ]);

        return $report->fresh('lines');
    }

    /**
     * Save achieved values, mark submitted, verify, and deliver to CEO in one step.
     */
    public function completeAndDeliver(MeQuarterlyReport $report, User $user, array $lines, ?string $notes = null): MeQuarterlyReport
    {
        abort_unless($this->userIsMeReviewer($user), 403);

        if (! in_array($report->status, ['draft', 'returned', 'submitted'], true)) {
            throw new \RuntimeException('This report cannot be completed in its current status.');
        }

        $this->saveDraft($report, $lines);
        $report = $report->fresh(['department', 'quarter', 'lines']);

        if (in_array($report->status, ['draft', 'returned'], true)) {
            $this->submit($report, $user);
            $report = $report->fresh(['department', 'quarter', 'lines']);
        }

        if ($notes !== null && $notes !== '') {
            $report->update(['me_notes' => $notes]);
        }

        return $this->verify($report, $user, $notes);
    }

    public function verify(MeQuarterlyReport $report, User $user, ?string $notes = null): MeQuarterlyReport
    {
        if ($report->status !== 'submitted') {
            throw new \RuntimeException('Only submitted reports can be verified.');
        }

        abort_unless($this->userIsMeReviewer($user), 403);

        $staff = $this->staffPortal->staffForUser($user);

        $report->update([
            'status' => 'me_verified',
            'me_verified_by' => $staff?->id,
            'me_verified_at' => now(),
            'me_notes' => $notes,
        ]);

        return $this->deliverToCeo($report->fresh(['department', 'quarter', 'lines']), $user);
    }

    public function returnToHod(MeQuarterlyReport $report, User $user, string $notes): MeQuarterlyReport
    {
        $staff = $this->staffPortal->staffForUser($user);

        $report->update([
            'status' => 'returned',
            'me_verified_by' => $staff?->id,
            'me_verified_at' => now(),
            'me_notes' => $notes,
        ]);

        return $report->fresh();
    }

    public function deliverToCeo(MeQuarterlyReport $report, ?User $user = null): MeQuarterlyReport
    {
        $report->update([
            'status' => 'ceo_delivered',
            'ceo_delivered_at' => now(),
        ]);

        $report->loadMissing(['department', 'quarter', 'technicalPlan']);
        $this->notifyCeo($report);
        $this->healthScores->recalculateForDepartment(
            $report->department_id,
            $report->technicalPlan?->fiscal_year
        );

        return $report->fresh(['lines', 'department', 'quarter']);
    }

    public function ceoSign(MeQuarterlyReport $report, User $user, string $signature, ?string $notes = null): MeQuarterlyReport
    {
        if ($report->status !== 'ceo_delivered') {
            throw new \RuntimeException('Only delivered reports can be signed by the CEO.');
        }

        $report->update([
            'ceo_reviewed_by' => $user->id,
            'ceo_reviewed_at' => now(),
            'ceo_signature' => $signature,
            'ceo_notes' => $notes,
        ]);

        return $report->fresh();
    }

    /**
     * Aggregate planned vs achieved across departments for a quarter number in a fiscal year.
     *
     * @return list<array{department: string, planned: float, achieved: float, deviation: float}>
     */
    public function pimeComparison(?string $fiscalYear, int $quarterNumber): array
    {
        $reports = MeQuarterlyReport::query()
            ->with(['department', 'lines', 'quarter', 'technicalPlan'])
            ->whereHas('quarter', fn ($q) => $q->where('quarter_number', $quarterNumber))
            ->whereIn('status', ['submitted', 'me_verified', 'ceo_delivered'])
            ->when($fiscalYear, function ($q) use ($fiscalYear) {
                $q->whereHas('technicalPlan', fn ($p) => $p->where('fiscal_year', $fiscalYear));
            })
            ->get();

        return $reports->map(function (MeQuarterlyReport $report) {
            $planned = (float) $report->lines->sum('planned');
            $achieved = (float) $report->lines->sum('achieved');

            return [
                'department' => $report->department?->dept_name ?? 'Unknown',
                'planned' => $planned,
                'achieved' => $achieved,
                'deviation' => round($achieved - $planned, 2),
                'report_id' => $report->id,
                'status' => $report->status,
            ];
        })->values()->all();
    }

    protected function notifyMeForVerification(MeQuarterlyReport $report): void
    {
        try {
            $userIds = DB::table('user_roles as ur')
                ->join('roles as r', 'r.id', '=', 'ur.role_id')
                ->whereIn('r.role_name', [
                    'Monitoring and Evaluation Officer',
                    'Assistant Monitoring and Evaluation Officer',
                    'Super Admin',
                ])
                ->pluck('ur.user_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            $this->notifications->notifyUsers(
                $userIds,
                'Quarterly M&E report submitted',
                ($report->department?->dept_name ?? 'Department').' submitted '.$report->quarter?->label().' report for verification.',
                'me_quarterly_report',
                (string) $report->id,
                'high',
                route('monitoring_evaluation.reports.show', $report),
            );
        } catch (\Throwable) {
        }
    }

    protected function notifyCeo(MeQuarterlyReport $report): void
    {
        try {
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
                'M&E quarterly report ready',
                ($report->department?->dept_name ?? 'Department').' '.$report->quarter?->label().' report verified by M&E and ready for executive review.',
                'me_quarterly_report',
                (string) $report->id,
                'high',
                route('ceo.me.show', $report),
            );
        } catch (\Throwable) {
        }
    }
}
