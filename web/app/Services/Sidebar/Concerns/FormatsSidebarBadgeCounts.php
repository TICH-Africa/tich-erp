<?php

namespace App\Services\Sidebar\Concerns;

trait FormatsSidebarBadgeCounts
{
    public function formatCount(int $count): ?string
    {
        if ($count <= 0) {
            return null;
        }

        return $count > 99 ? '99+' : (string) $count;
    }

    /**
     * @param  array<string, int>  $counts
     * @return array<string, string|null>
     */
    public function formattedCounts(array $counts): array
    {
        return collect($counts)
            ->mapWithKeys(fn (int $count, string $key) => [$key => $this->formatCount($count)])
            ->all();
    }
}
