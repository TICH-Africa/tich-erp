<?php

namespace App\Services\Sidebar;

use App\Events\AdministrationSidebarCountsUpdated;
use App\Models\Administration\BudgetRequest;
use App\Models\Administration\InspectionCheck;
use App\Models\Administration\StatutoryCertification;
use App\Models\Applicant;
use App\Support\SafelyBroadcasts;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class AdministrationSidebarNotificationService
{
    use SafelyBroadcasts;
    public const CACHE_KEY = 'administration.sidebar.counts';

    public const CACHE_TTL_SECONDS = 30;

    /** @var array<string, string> */
    public const MENU_KEYS = [
        'approvals' => 'Approval workflow',
        'applications' => 'Application framework',
        'lifecycle' => 'Automated lifecycle',
        'statutory' => 'Statutory tracking',
        'inspection' => 'Inspection readiness',
        'planning-funds' => 'Planning & funds',
        'admissions-ops' => 'Admissions ops',
        'compliance' => 'Compliance',
    ];

    public function counts(bool $fresh = false): array
    {
        if ($fresh) {
            return $this->computeCounts();
        }

        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, fn () => $this->computeCounts());
    }

    public function formattedCounts(bool $fresh = false): array
    {
        return collect($this->counts($fresh))
            ->mapWithKeys(fn (int $count, string $key) => [$key => $this->formatCount($count)])
            ->all();
    }

    public function formatCount(int $count): ?string
    {
        if ($count <= 0) {
            return null;
        }

        return $count > 99 ? '99+' : (string) $count;
    }

    public function broadcastCounts(): void
    {
        Cache::forget(self::CACHE_KEY);
        $counts = $this->counts(true);

        $this->safelyBroadcast(fn () => broadcast(new AdministrationSidebarCountsUpdated(
            $counts,
            collect($counts)->map(fn (int $count) => $this->formatCount($count))->all()
        )));
    }

    private function computeCounts(): array
    {
        $approvals = 0;
        if (Schema::hasTable('admin_budget_requests')) {
            $approvals = BudgetRequest::query()
                ->whereIn('status', ['submitted', 'finance_review', 'executive_review'])
                ->count();
        }

        $applications = 0;
        $lifecycle = 0;
        if (Schema::hasTable('applicants')) {
            $applications = Applicant::query()
                ->whereIn('status', ['submitted_admin', 'submitted'])
                ->count();
            $lifecycle = Applicant::query()
                ->whereIn('status', ['academic_review', 'fee_pending', 'paid'])
                ->count();
        }

        $statutory = 0;
        if (Schema::hasTable('admin_statutory_certifications')) {
            $statutory = StatutoryCertification::query()
                ->where(function ($q) {
                    $q->whereIn('status', ['expired', 'expiring'])
                        ->orWhere(function ($inner) {
                            $inner->whereNotNull('expires_on')
                                ->where('expires_on', '<=', now()->addDays(60));
                        });
                })
                ->count();
        }

        $inspection = 0;
        if (Schema::hasTable('admin_inspection_checks')) {
            $inspection = InspectionCheck::query()
                ->whereIn('status', ['pending', 'gap'])
                ->count();
        }

        return [
            'approvals' => $approvals,
            'applications' => $applications,
            'lifecycle' => $lifecycle,
            'statutory' => $statutory,
            'inspection' => $inspection,
            'planning-funds' => $approvals,
            'admissions-ops' => $applications + $lifecycle,
            'compliance' => $statutory + $inspection,
        ];
    }
}
