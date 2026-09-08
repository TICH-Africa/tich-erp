<?php

namespace App\Services\Me;

use App\Models\Department;
use App\Models\Me\MeQuarterlyReport;
use App\Models\Me\MeQuarterlyReportLine;
use App\Models\Me\MeTechnicalPlan;
use App\Models\User;
use App\Services\PlatformNotificationService;
use App\Services\StaffPortalService;
use Illuminate\Support\Facades\DB;

class MeQuarterlyReportService
{
    public function __construct(
        protected StaffPortalService $staffPortal,
        protected PlatformNotificationService $notifications,
        protected MeHealthScoreService $healthScores,
    ) {}

    public function userCanEditDepartmentReport(User $user, Department $department): bool
    {
        if ($user->hasAnyRole(['Super Admin', 'Monitoring and Evaluation Officer', 'Assistant Monitoring and Evaluation Officer'])) {
            return true;
        }

        $staff = $this->staffPortal->staffForUser($user);
        if (! $staff) {
            return false;
        }

        if ((int) $department->hod_id === (int) $staff->id) {
            return true;
        }

        return (int) $staff->department_id === (int) $department->id;
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

        $report->update([
            'status' => 'submitted',
            'submitted_by' => $user->id,
            'submitted_at' => now(),
        ]);

        $this->notifyMeForVerification($report->fresh(['department', 'quarter']));

        return $report->fresh('lines');
    }

    public function verify(MeQuarterlyReport $report, User $user, ?string $notes = null): MeQuarterlyReport
    {
        if ($report->status !== 'submitted') {
            throw new \RuntimeException('Only submitted reports can be verified.');
        }

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
