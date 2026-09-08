<?php

namespace App\Services\Sidebar;

use App\Events\QaSidebarCountsUpdated;
use App\Models\Qa\QaCorrectiveAction;
use App\Models\Qa\QaPlan;
use App\Models\User;
use App\Services\Qa\QaAssessmentService;
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
        'assessments' => 'Assessment sheets',
        'corrective-actions' => 'Corrective actions',
        'tasks' => 'My department tasks',
    ];

    /** @var list<string> */
    public const DASHBOARD_LEAF_KEYS = ['assessments', 'corrective-actions'];

    public function __construct(
        protected QaAssessmentService $qa,
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

        return array_merge($base, [
            'tasks' => $this->pendingTasksForUser($user),
        ]);
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

        if (Schema::hasTable('qa_plans')) {
            $assessments = QaPlan::query()
                ->whereIn('status', ['draft', 'dispatched', 'in_progress'])
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

    private function pendingTasksForUser(User $user): int
    {
        if (! Schema::hasTable('qa_plans')) {
            return 0;
        }

        $departments = $this->qa->respondableDepartments($user);
        if ($departments->isEmpty()) {
            return 0;
        }

        return QaPlan::query()
            ->whereIn('status', ['dispatched', 'in_progress'])
            ->where(function ($query) use ($departments) {
                foreach ($departments as $department) {
                    $query->orWhereJsonContains('department_ids', (int) $department->id)
                        ->orWhereJsonContains('department_ids', (string) $department->id);
                }
            })
            ->count();
    }
}
