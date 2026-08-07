<?php

namespace App\Services\Sidebar;

use App\Models\AttendanceSession;
use App\Models\CurriculumVersion;
use App\Models\Department;
use App\Models\Unit;
use App\Models\User;
use App\Services\AcademicsAccessService;
use App\Services\Sidebar\Concerns\FormatsSidebarBadgeCounts;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AcademicsSidebarNotificationService
{
    use FormatsSidebarBadgeCounts;

    public const CACHE_KEY_PREFIX = 'academics.sidebar.counts.';

    public const CACHE_TTL_SECONDS = 30;

    /** @var array<string, string> */
    public const MENU_KEYS = [
        'units.pending-registry' => 'Unit catalog',
        'curriculum.workflow' => 'Programme curriculum',
        'lesson-plans.review' => 'Lesson plan approval',
        'attendance-ledger.hod' => 'Attendance ledger',
        'attendance-ledger.registrar' => 'Attendance ledger',
    ];

    public function __construct(
        protected AcademicsAccessService $access,
    ) {}

    /**
     * @return array<string, int>
     */
    public function countsFor(User $user, Department $hub, bool $fresh = false): array
    {
        return $this->countsForHub($hub, $fresh);
    }

    /**
     * @return array<string, int>
     */
    public function countsForHub(Department $hub, bool $fresh = false): array
    {
        $resolvedHub = $hub->isAcademicsHub() ? $hub : ($hub->academicsHub() ?? $hub);
        $cacheKey = self::CACHE_KEY_PREFIX.'hub.'.$resolvedHub->id;

        if ($fresh) {
            return $this->computeCounts($resolvedHub);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, fn () => $this->computeCounts($resolvedHub));
    }

    /**
     * @return array<string, string|null>
     */
    public function formattedCountsFor(User $user, Department $hub, bool $fresh = false): array
    {
        return $this->formattedCounts($this->countsForHub($hub, $fresh));
    }

    public function forget(User $user, Department $hub): void
    {
        $this->forgetHub($hub);
    }

    public function forgetHub(Department $hub): void
    {
        $resolvedHub = $hub->isAcademicsHub() ? $hub : ($hub->academicsHub() ?? $hub);
        Cache::forget(self::CACHE_KEY_PREFIX.'hub.'.$resolvedHub->id);
    }

    public function badgeKeyForRouteName(?string $routeName): ?string
    {
        if (! $routeName) {
            return null;
        }

        return match (true) {
            str_contains($routeName, 'departments.academics.units.') => 'units.pending-registry',
            str_contains($routeName, 'departments.academics.programs.') => 'curriculum.workflow',
            str_contains($routeName, 'departments.academics.lesson-plans.index'),
            str_contains($routeName, 'departments.academics.lesson-plans.show') => 'lesson-plans.review',
            str_contains($routeName, 'departments.academics.attendance-ledger.') => $this->attendanceLedgerBadgeKey(),
            default => null,
        };
    }

    private function attendanceLedgerBadgeKey(): ?string
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }

        if ($user->hasAnyRole(['Academic Registrar', 'Super Admin'])) {
            return 'attendance-ledger.registrar';
        }

        if ($user->hasAnyRole(['HOD', 'Dean', 'Super Admin'])) {
            return 'attendance-ledger.hod';
        }

        return 'attendance-ledger.hod';
    }

    /**
     * @return array<string, int>
     */
    private function computeCounts(Department $hub): array
    {
        $departmentIds = $this->access->scopeDepartmentIds($hub);

        $lessonPlanCount = 0;
        foreach ($departmentIds as $departmentId) {
            $lessonPlanCount += (int) DB::table('lesson_plans as lp')
                ->join('unit_allocations as ua', 'ua.id', '=', 'lp.unit_allocation_id')
                ->join('units as u', 'u.id', '=', 'ua.unit_id')
                ->where('u.department_id', $departmentId)
                ->whereIn('lp.status', ['submitted', 'modified'])
                ->count();
        }

        $attendanceHod = AttendanceSession::query()
            ->whereHas('allocation.unit', fn ($query) => $query->whereIn('department_id', $departmentIds))
            ->where('verification_status', 'submitted')
            ->count();

        $attendanceRegistrar = AttendanceSession::query()
            ->whereHas('allocation.unit', fn ($query) => $query->whereIn('department_id', $departmentIds))
            ->where('verification_status', 'hod_verified')
            ->count();

        return [
            'units.pending-registry' => Unit::query()
                ->whereIn('department_id', $departmentIds)
                ->where('status', 'pending_registry')
                ->count(),
            'curriculum.workflow' => CurriculumVersion::query()
                ->whereHas('program', fn ($query) => $query->whereIn('department_id', $departmentIds))
                ->whereIn('status', ['draft', 'pending_registry', 'pending_ceo'])
                ->count(),
            'lesson-plans.review' => $lessonPlanCount,
            'attendance-ledger.hod' => $attendanceHod,
            'attendance-ledger.registrar' => $attendanceRegistrar,
        ];
    }
}
