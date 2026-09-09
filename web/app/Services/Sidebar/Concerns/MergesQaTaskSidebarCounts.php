<?php

namespace App\Services\Sidebar\Concerns;

use App\Models\User;
use App\Services\Qa\QaAssessmentService;

trait MergesQaTaskSidebarCounts
{
    /**
     * @param  array<string, int>  $counts
     * @return array<string, int>
     */
    protected function withQaTaskCount(array $counts, ?User $user = null): array
    {
        $user ??= auth()->user();
        $counts['qa.tasks'] = $user
            ? app(QaAssessmentService::class)->outstandingTaskCountForUser($user)
            : 0;

        return $counts;
    }

    /**
     * @param  array<string, string>  $menuKeys
     * @return array<string, string>
     */
    protected function withQaTaskMenuKey(array $menuKeys): array
    {
        $menuKeys['qa.tasks'] = 'QA assessment tasks';

        return $menuKeys;
    }
}
