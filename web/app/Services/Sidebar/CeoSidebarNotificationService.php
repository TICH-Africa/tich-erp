<?php

namespace App\Services\Sidebar;

use App\Events\CeoSidebarCountsUpdated;
use App\Models\Administration\BudgetRequest;
use App\Models\CurriculumVersion;
use App\Models\Me\MeQuarterlyReport;
use App\Models\Qa\QaCorrectiveAction;
use App\Models\Qa\QaPlan;
use App\Services\Sidebar\Concerns\FormatsSidebarBadgeCounts;
use App\Support\SafelyBroadcasts;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class CeoSidebarNotificationService
{
    use FormatsSidebarBadgeCounts;
    use SafelyBroadcasts;

    public const CACHE_KEY = 'ceo.sidebar.counts';

    public const CACHE_TTL_SECONDS = 30;

    /** @var array<string, string> */
    public const MENU_KEYS = [
        'budgets' => 'Budget authorizations',
        'approvals' => 'Approval workflow',
        'curriculum' => 'Curriculum sign-off',
        'quality' => 'Quality reports',
        'me' => 'M&E reports',
    ];

    /** @var list<string> */
    public const DASHBOARD_LEAF_KEYS = ['budgets', 'approvals', 'curriculum', 'quality', 'me'];

    /**
     * @return array<string, int>
     */
    public function counts(bool $fresh = false): array
    {
        if ($fresh) {
            return $this->computeCounts();
        }

        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, fn () => $this->computeCounts());
    }

    /**
     * @return array<string, string|null>
     */
    public function labels(bool $fresh = false): array
    {
        return $this->formattedCounts($this->counts($fresh));
    }

    public function broadcastCounts(): void
    {
        Cache::forget(self::CACHE_KEY);
        $counts = $this->counts(true);

        $this->safelyBroadcast(fn () => broadcast(new CeoSidebarCountsUpdated(
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
    private function computeCounts(): array
    {
        $budgets = 0;
        $approvals = 0;
        if (Schema::hasTable('admin_budget_requests')) {
            $budgets = BudgetRequest::query()->where('status', 'executive_review')->count();
            $approvals = BudgetRequest::query()
                ->whereIn('status', ['submitted', 'draft'])
                ->count();
        }

        $curriculum = 0;
        if (Schema::hasTable('curriculum_versions')) {
            $curriculum = CurriculumVersion::query()->where('status', 'pending_ceo')->count();
        }

        $quality = 0;
        if (Schema::hasTable('qa_plans')) {
            $quality += QaPlan::query()
                ->where('status', 'compiled')
                ->whereNotNull('compiled_at')
                ->where('compiled_at', '>=', now()->subDays(30))
                ->count();
        }
        if (Schema::hasTable('qa_corrective_actions')) {
            $quality += QaCorrectiveAction::query()
                ->whereIn('status', ['open', 'in_progress', 'overdue'])
                ->count();
        }

        $me = 0;
        if (Schema::hasTable('me_quarterly_reports')) {
            $me = MeQuarterlyReport::query()
                ->where('status', 'ceo_delivered')
                ->whereNull('ceo_reviewed_at')
                ->count();
        }

        return [
            'budgets' => $budgets,
            'approvals' => $approvals,
            'curriculum' => $curriculum,
            'quality' => $quality,
            'me' => $me,
        ];
    }
}
