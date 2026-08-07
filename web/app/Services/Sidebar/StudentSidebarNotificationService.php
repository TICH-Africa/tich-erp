<?php

namespace App\Services\Sidebar;

use App\Models\Student;
use App\Services\Sidebar\Concerns\FormatsSidebarBadgeCounts;
use Illuminate\Support\Facades\Cache;

class StudentSidebarNotificationService
{
    use FormatsSidebarBadgeCounts;

    public const CACHE_KEY_PREFIX = 'student.sidebar.counts.';

    public const CACHE_TTL_SECONDS = 30;

    /** @var array<string, string> */
    public const MENU_KEYS = [
        'finance' => 'Finance',
    ];

    /**
     * @return array<string, int>
     */
    public function countsFor(Student $student, bool $fresh = false): array
    {
        $cacheKey = self::CACHE_KEY_PREFIX.$student->id;

        if ($fresh) {
            return $this->computeCounts($student);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, fn () => $this->computeCounts($student));
    }

    /**
     * @return array<string, string|null>
     */
    public function formattedCountsFor(Student $student, bool $fresh = false): array
    {
        return $this->formattedCounts($this->countsFor($student, $fresh));
    }

    public function forget(Student $student): void
    {
        Cache::forget(self::CACHE_KEY_PREFIX.$student->id);
    }

    public function badgeKeyForSection(string $section): ?string
    {
        return match ($section) {
            'finance' => 'finance',
            default => null,
        };
    }

    /**
     * @return array<string, int>
     */
    private function computeCounts(Student $student): array
    {
        $outstanding = (float) ($student->overall_balance ?? 0);
        $needsClearance = ($student->fee_clearance_status ?? 'pending') !== 'cleared';

        $financeCount = 0;
        if ($outstanding > 0) {
            $financeCount++;
        } elseif ($needsClearance) {
            $financeCount = 1;
        }

        return [
            'finance' => $financeCount,
        ];
    }
}
