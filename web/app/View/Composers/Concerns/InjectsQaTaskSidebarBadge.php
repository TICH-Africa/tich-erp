<?php

namespace App\View\Composers\Concerns;

use App\Models\User;
use App\Services\Qa\QaAssessmentService;
use App\Services\Sidebar\Concerns\FormatsSidebarBadgeCounts;

trait InjectsQaTaskSidebarBadge
{
    use FormatsSidebarBadgeCounts;

    /**
     * @param  array<string, int>  $counts
     * @param  array<string, string|null>  $labels
     * @param  array<string, string>  $menuKeys
     * @return array{0: array<string, int>, 1: array<string, string|null>, 2: array<string, string>}
     */
    protected function withQaTaskSidebarBadge(array $counts, array $labels, array $menuKeys, ?User $user = null): array
    {
        $user ??= auth()->user();
        $taskCount = $user
            ? app(QaAssessmentService::class)->outstandingTaskCountForUser($user)
            : 0;

        $counts['qa.tasks'] = $taskCount;
        $labels['qa.tasks'] = $this->formatCount($taskCount);
        $menuKeys['qa.tasks'] = 'QA assessment tasks';

        return [$counts, $labels, $menuKeys];
    }
}
