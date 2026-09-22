<?php

namespace App\Services\Sidebar\Concerns;

use App\Models\User;

trait MergesQaTaskSidebarCounts
{
    /**
     * Department QA assessment tasks removed — IQA is QA-module only.
     *
     * @param  array<string, int>  $counts
     * @return array<string, int>
     */
    protected function withQaTaskCount(array $counts, ?User $user = null, ?string $moduleKey = null): array
    {
        unset($user, $moduleKey);
        $counts['qa.tasks'] = 0;

        return $counts;
    }

    /**
     * @param  array<string, string>  $menuKeys
     * @return array<string, string>
     */
    protected function withQaTaskMenuKey(array $menuKeys): array
    {
        return $menuKeys;
    }
}
