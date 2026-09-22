<?php

namespace App\View\Composers\Concerns;

use App\Models\User;
use App\Services\Sidebar\Concerns\FormatsSidebarBadgeCounts;

trait InjectsQaTaskSidebarBadge
{
    use FormatsSidebarBadgeCounts;

    /**
     * Department QA assessment tasks removed — IQA is QA-module only.
     *
     * @param  array<string, int>  $counts
     * @param  array<string, string|null>  $labels
     * @param  array<string, string>  $menuKeys
     * @return array{0: array<string, int>, 1: array<string, string|null>, 2: array<string, string>}
     */
    protected function withQaTaskSidebarBadge(array $counts, array $labels, array $menuKeys, ?User $user = null, ?string $moduleKey = null): array
    {
        unset($user, $moduleKey);
        $counts['qa.tasks'] = 0;
        $labels['qa.tasks'] = null;

        return [$counts, $labels, $menuKeys];
    }
}
