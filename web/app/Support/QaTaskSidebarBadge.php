<?php

namespace App\Support;

use App\Models\User;
use App\Services\Qa\QaAssessmentService;

class QaTaskSidebarBadge
{
    /**
     * @param  array<string, int>  $counts
     * @param  array<string, string|null>  $labels
     * @return array{0: array<string, int>, 1: array<string, string|null>}
     */
    public static function merge(array $counts, array $labels, ?User $user = null): array
    {
        $user ??= auth()->user();
        $taskCount = $user
            ? app(QaAssessmentService::class)->outstandingTaskCountForUser($user)
            : 0;

        $counts['qa.tasks'] = $taskCount;
        $labels['qa.tasks'] = $taskCount <= 0 ? null : ($taskCount > 99 ? '99+' : (string) $taskCount);

        return [$counts, $labels];
    }
}
