<?php

namespace App\Support;

use App\Models\User;

class QaTaskSidebarBadge
{
    /**
     * Department QA assessment tasks removed — IQA is QA-module only.
     *
     * @param  array<string, int>  $counts
     * @param  array<string, string|null>  $labels
     * @return array{0: array<string, int>, 1: array<string, string|null>}
     */
    public static function merge(array $counts, array $labels, ?User $user = null, ?string $moduleKey = null): array
    {
        unset($user, $moduleKey);
        $counts['qa.tasks'] = 0;
        $labels['qa.tasks'] = null;

        return [$counts, $labels];
    }
}
