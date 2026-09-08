<?php

namespace App\Services\Sidebar;

use App\Events\MeSidebarCountsUpdated;
use App\Models\Me\MePolicy;
use App\Models\Me\MeQuarterlyReport;
use App\Models\Me\MeTechnicalPlan;
use App\Models\User;
use App\Services\Me\MePolicyService;
use App\Services\Sidebar\Concerns\FormatsSidebarBadgeCounts;
use App\Services\StaffPortalService;
use App\Support\SafelyBroadcasts;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class MeSidebarNotificationService
{
    use FormatsSidebarBadgeCounts;
    use SafelyBroadcasts;

    public const CACHE_KEY = 'me.sidebar.counts';

    public const CACHE_TTL_SECONDS = 30;

    /** @var array<string, string> */
    public const MENU_KEYS = [
        'plans' => 'Technical plans',
        'reports' => 'Quarterly reports',
        'policies' => 'M&E policy portal',
        'policy.sign' => 'Sign M&E policy',
        'department' => 'My department reports',
    ];

    /** @var list<string> */
    public const DASHBOARD_LEAF_KEYS = ['plans', 'reports', 'policies'];

    public function __construct(
        protected MePolicyService $policies,
        protected StaffPortalService $staffPortal,
    ) {}

    /**
     * @return array<string, int>
     */
    public function counts(?User $user = null, bool $fresh = false): array
    {
        $base = $fresh
            ? $this->computeOfficerCounts()
            : Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, fn () => $this->computeOfficerCounts());

        if (! $user) {
            return $base;
        }

        return array_merge($base, $this->computeUserCounts($user));
    }

    /**
     * @return array<string, string|null>
     */
    public function labels(?User $user = null, bool $fresh = false): array
    {
        return $this->formattedCounts($this->counts($user, $fresh));
    }

    public function broadcastCounts(): void
    {
        Cache::forget(self::CACHE_KEY);
        $counts = $this->counts(null, true);

        $this->safelyBroadcast(fn () => broadcast(new MeSidebarCountsUpdated(
            $counts,
            $this->formattedCounts($counts)
        )));
    }

    public function dashboardTotal(): int
    {
        $counts = $this->counts();

        return (int) collect(self::DASHBOARD_LEAF_KEYS)->sum(fn (string $key) => (int) ($counts[$key] ?? 0));
    }

    /**
     * @return array<string, int>
     */
    private function computeOfficerCounts(): array
    {
        $plans = Schema::hasTable('me_technical_plans')
            ? MeTechnicalPlan::query()->where('status', 'me_review')->count()
            : 0;

        $reports = Schema::hasTable('me_quarterly_reports')
            ? MeQuarterlyReport::query()->where('status', 'submitted')->count()
            : 0;

        $policies = 0;
        if (Schema::hasTable('me_policies') && Schema::hasTable('me_policy_signoffs')) {
            $policy = MePolicy::query()->published()->orderByDesc('published_at')->orderByDesc('id')->first();
            if ($policy) {
                $progress = $this->policies->signoffProgress($policy);
                $policies = max(0, (int) $progress['total'] - (int) $progress['signed']);
            }
        }

        return [
            'plans' => $plans,
            'reports' => $reports,
            'policies' => $policies,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function computeUserCounts(User $user): array
    {
        $policySign = 0;
        $department = 0;

        $staff = $this->staffPortal->staffForUser($user);
        $policy = $this->policies->currentPublishedPolicy();

        if ($staff?->department && $policy) {
            $dept = $staff->department;
            if ((int) ($dept->hod_id ?? 0) === (int) $staff->id
                && ! $this->policies->departmentHasHodSignoff($policy, $dept)) {
                $policySign = 1;
            }

            if (Schema::hasTable('me_quarterly_reports')) {
                $department = MeQuarterlyReport::query()
                    ->where('department_id', $dept->id)
                    ->whereIn('status', ['draft', 'returned'])
                    ->count();
            }
        }

        return [
            'policy.sign' => $policySign,
            'department' => $department,
        ];
    }
}
