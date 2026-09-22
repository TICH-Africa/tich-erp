<?php

namespace App\Services\Sidebar;

use App\Events\QaSidebarCountsUpdated;
use App\Models\Qa\IqaAssessment;
use App\Models\Qa\QaCorrectiveAction;
use App\Models\User;
use App\Services\Sidebar\Concerns\FormatsSidebarBadgeCounts;
use App\Support\SafelyBroadcasts;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class QaSidebarNotificationService
{
    use FormatsSidebarBadgeCounts;
    use SafelyBroadcasts;

    public const CACHE_KEY = 'qa.sidebar.counts';

    public const CACHE_TTL_SECONDS = 30;

    /** @var array<string, string> */
    public const MENU_KEYS = [
        'assessments' => 'IQA assessments',
        'corrective-actions' => 'Corrective actions',
    ];

    /** @var list<string> */
    public const DASHBOARD_LEAF_KEYS = ['assessments', 'corrective-actions'];

    /**
     * @return array<string, int>
     */
    public function counts(?User $user = null, bool $fresh = false): array
    {
        unset($user);

        return $fresh
            ? $this->computeOfficerCounts()
            : Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, fn () => $this->computeOfficerCounts());
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

        $this->safelyBroadcast(fn () => broadcast(new QaSidebarCountsUpdated(
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
        $assessments = 0;
        $corrective = 0;

        if (Schema::hasTable('iqa_assessments')) {
            $assessments = IqaAssessment::query()
                ->where('status', IqaAssessment::STATUS_DRAFT)
                ->count();
        }

        if (Schema::hasTable('qa_corrective_actions')) {
            $corrective = QaCorrectiveAction::query()
                ->whereIn('status', ['open', 'in_progress', 'overdue'])
                ->count();
        }

        return [
            'assessments' => $assessments,
            'corrective-actions' => $corrective,
        ];
    }
}
