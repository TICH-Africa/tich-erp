<?php

namespace App\Services\Sidebar;

use App\Models\Applicant;
use App\Models\AttendanceSession;
use App\Models\CurriculumVersion;
use App\Models\Department;
use App\Models\Unit;
use App\Models\User;
use App\Services\AcademicsAccessService;
use App\Services\Sidebar\Concerns\FormatsSidebarBadgeCounts;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AcademicsSidebarNotificationService
{
    use FormatsSidebarBadgeCounts;

    public const CACHE_KEY_PREFIX = 'academics.sidebar.counts.';

    public const CACHE_TTL_SECONDS = 30;

    /** @var array<string, string> */
    public const MENU_KEYS = [
        'curriculum' => 'Curriculum & Teaching',
        'assessment' => 'Assessment & Exams',
        'units.pending-registry' => 'Unit catalog',
        'curriculum.workflow' => 'Programme curriculum',
        'lesson-plans.review' => 'Lesson plan approval',
        'attendance-ledger.hod' => 'Attendance ledger',
        'attendance-ledger.registrar' => 'Attendance ledger',
        'special-exam-requests.pending' => 'Special exam requests',
        'supplementary-requests.pending' => 'Supplementary requests',
        'suggestions.open' => 'Suggestion box',
        'lifecycle.pending' => 'Deferment requests',
        'applications.pending' => 'Application review',
    ];

    public function __construct(
        protected AcademicsAccessService $access,
    ) {}

    /**
     * @return array<string, int>
     */
    public function countsFor(User $user, Department $hub, bool $fresh = false): array
    {
        if ($this->access->isTeachingOnly($user)) {
            return [];
        }

        $departmentIds = $this->access->accessibleLearningDepartmentIds($user, $hub);
        $counts = $this->countsForDepartmentIds($hub, $departmentIds, $fresh);

        return $this->filterCountsForUser($user, $counts);
    }

    /**
     * @return array<string, int>
     */
    public function countsForHub(Department $hub, bool $fresh = false): array
    {
        return $this->countsForDepartmentIds($hub, $this->access->scopeDepartmentIds($hub), $fresh);
    }

    /**
     * @return array<string, string|null>
     */
    public function formattedCountsFor(User $user, Department $hub, bool $fresh = false): array
    {
        return $this->formattedCounts($this->countsFor($user, $hub, $fresh));
    }

    public function forget(User $user, Department $hub): void
    {
        $this->forgetHub($hub);
        Cache::forget($this->userCacheKey($user, $hub));
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
            str_contains($routeName, 'departments.academics.special-exam-requests.') => 'special-exam-requests.pending',
            str_contains($routeName, 'departments.academics.supplementary-requests.') => 'supplementary-requests.pending',
            str_contains($routeName, 'departments.academics.suggestions.') => 'suggestions.open',
            str_contains($routeName, 'departments.academics.lifecycle-requests.') => 'lifecycle.pending',
            str_contains($routeName, 'departments.academics.applications.') => 'applications.pending',
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

        if ($user->hasAnyRole(['HOD', 'Dean of Students', 'Super Admin'])) {
            return 'attendance-ledger.hod';
        }

        return 'attendance-ledger.hod';
    }

    /**
     * @param  list<int>  $departmentIds
     * @return array<string, int>
     */
    private function countsForDepartmentIds(Department $hub, array $departmentIds, bool $fresh = false): array
    {
        $resolvedHub = $hub->isAcademicsHub() ? $hub : ($hub->academicsHub() ?? $hub);
        $idsKey = implode('-', $departmentIds) ?: 'none';
        $cacheKey = self::CACHE_KEY_PREFIX.'hub.'.$resolvedHub->id.'.depts.'.$idsKey;

        if ($fresh) {
            $counts = $this->computeCounts($departmentIds);
            Cache::put($cacheKey, $counts, self::CACHE_TTL_SECONDS);

            return $counts;
        }

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, fn () => $this->computeCounts($departmentIds));
    }

    private function userCacheKey(User $user, Department $hub): string
    {
        $resolvedHub = $hub->isAcademicsHub() ? $hub : ($hub->academicsHub() ?? $hub);

        return self::CACHE_KEY_PREFIX.'hub.'.$resolvedHub->id.'.user.'.$user->id;
    }

    /**
     * @param  array<string, int>  $counts
     * @return array<string, int>
     */
    private function filterCountsForUser(User $user, array $counts): array
    {
        if ($this->access->isSuggestionsOnly($user)) {
            return array_intersect_key($counts, array_flip(['suggestions.open', 'lifecycle.pending']));
        }

        if ($this->access->isDepartmentHod($user)) {
            $keys = [
                'applications.pending',
                'curriculum.workflow',
                'lesson-plans.review',
                'attendance-ledger.hod',
                'special-exam-requests.pending',
                'supplementary-requests.pending',
                'curriculum',
                'assessment',
            ];

            $filtered = array_intersect_key($counts, array_flip($keys));
            $filtered['curriculum'] = (int) ($filtered['curriculum.workflow'] ?? 0) + (int) ($filtered['lesson-plans.review'] ?? 0);
            $filtered['assessment'] = (int) ($filtered['attendance-ledger.hod'] ?? 0)
                + (int) ($filtered['special-exam-requests.pending'] ?? 0)
                + (int) ($filtered['supplementary-requests.pending'] ?? 0);

            return $filtered;
        }

        return $counts;
    }

    /**
     * @param  list<int>  $departmentIds
     * @return array<string, int>
     */
    private function computeCounts(array $departmentIds): array
    {
        if ($departmentIds === []) {
            return [
                'units.pending-registry' => 0,
                'curriculum.workflow' => 0,
                'lesson-plans.review' => 0,
                'attendance-ledger.hod' => 0,
                'attendance-ledger.registrar' => 0,
                'special-exam-requests.pending' => 0,
                'supplementary-requests.pending' => 0,
                'suggestions.open' => 0,
                'lifecycle.pending' => 0,
                'applications.pending' => 0,
                'curriculum' => 0,
                'assessment' => 0,
            ];
        }

        $lessonPlanReview = 0;
        $lessonPlanIncomplete = 0;
        foreach ($departmentIds as $departmentId) {
            $lessonPlanReview += (int) DB::table('lesson_plans as lp')
                ->join('unit_allocations as ua', 'ua.id', '=', 'lp.unit_allocation_id')
                ->join('units as u', 'u.id', '=', 'ua.unit_id')
                ->where('u.department_id', $departmentId)
                ->whereIn('lp.status', ['submitted', 'modified'])
                ->count();

            $lessonPlanIncomplete += (int) DB::table('lesson_plans as lp')
                ->join('unit_allocations as ua', 'ua.id', '=', 'lp.unit_allocation_id')
                ->join('units as u', 'u.id', '=', 'ua.unit_id')
                ->where('u.department_id', $departmentId)
                ->whereIn('lp.status', ['draft', 'rejected'])
                ->count();
        }

        $lessonPlanCount = $lessonPlanReview + $lessonPlanIncomplete;

        $attendanceHod = AttendanceSession::query()
            ->whereHas('allocation.unit', fn ($query) => $query->whereIn('department_id', $departmentIds))
            ->where('verification_status', 'submitted')
            ->count();

        $attendanceRegistrar = AttendanceSession::query()
            ->whereHas('allocation.unit', fn ($query) => $query->whereIn('department_id', $departmentIds))
            ->where('verification_status', 'hod_verified')
            ->count();

        $attendanceIncomplete = AttendanceSession::query()
            ->whereHas('allocation.unit', fn ($query) => $query->whereIn('department_id', $departmentIds))
            ->where('verification_status', 'draft')
            ->where(function ($query) {
                $query->whereNotNull('recorded_at')
                    ->orWhereHas('records');
            })
            ->count();

        $unitsPending = Unit::query()
            ->whereIn('department_id', $departmentIds)
            ->whereIn('status', ['draft', 'pending_registry'])
            ->count();

        $curriculumWorkflow = CurriculumVersion::query()
            ->whereHas('program', fn ($query) => $query->whereIn('department_id', $departmentIds))
            ->whereIn('status', ['draft', 'pending_registry', 'pending_ceo'])
            ->count();

        $attendanceForUser = $this->attendanceCountForCurrentUser(
            $attendanceHod,
            $attendanceRegistrar,
            $attendanceIncomplete,
        );

        $applicationsPending = Applicant::query()
            ->where(function ($query) use ($departmentIds) {
                $query->whereIn('handling_department_id', $departmentIds)
                    ->orWhereHas('program', fn ($programQuery) => $programQuery->whereIn('department_id', $departmentIds));
            })
            ->where('status', 'academic_review')
            ->where('academic_review_status', 'under_review')
            ->count();

        $specialExamPending = 0;
        if (Schema::hasTable('special_exam_requests')) {
            $specialExamPending = (int) DB::table('special_exam_requests as ser')
                ->leftJoin('units as u', 'u.id', '=', 'ser.unit_id')
                ->whereIn('ser.status', ['pending', 'on_hold'])
                ->where(function ($query) use ($departmentIds) {
                    $query->whereIn('u.department_id', $departmentIds)
                        ->orWhereNull('ser.unit_id');
                })
                ->count();
        }

        $supplementaryPending = 0;
        if (Schema::hasTable('supplementary_requests')) {
            $supplementaryPending = (int) DB::table('supplementary_requests as sr')
                ->leftJoin('units as u', 'u.id', '=', 'sr.unit_id')
                ->whereIn('sr.application_status', ['pending_review', 'pending_fee', 'on_hold'])
                ->where(function ($query) use ($departmentIds) {
                    $query->whereIn('u.department_id', $departmentIds)
                        ->orWhereNull('sr.unit_id');
                })
                ->count();
        }

        return [
            'units.pending-registry' => $unitsPending,
            'curriculum.workflow' => $curriculumWorkflow,
            'lesson-plans.review' => $lessonPlanCount,
            'attendance-ledger.hod' => $attendanceHod + $attendanceIncomplete,
            'attendance-ledger.registrar' => $attendanceRegistrar + $attendanceIncomplete,
            'special-exam-requests.pending' => $specialExamPending,
            'supplementary-requests.pending' => $supplementaryPending,
            'suggestions.open' => Schema::hasTable('student_suggestions')
                ? (int) DB::table('student_suggestions')->whereIn('status', ['open', 'under_review'])->count()
                : 0,
            'lifecycle.pending' => Schema::hasTable('student_lifecycle_requests')
                ? (int) DB::table('student_lifecycle_requests')
                    ->where('request_type', 'deferment')
                    ->whereIn('status', ['pending', 'partially_approved', 'on_hold'])
                    ->count()
                : 0,
            'applications.pending' => $applicationsPending,
            'curriculum' => $unitsPending + $curriculumWorkflow + $lessonPlanCount,
            'assessment' => $attendanceForUser + $specialExamPending + $supplementaryPending,
        ];
    }

    private function attendanceCountForCurrentUser(int $hodCount, int $registrarCount, int $incompleteCount): int
    {
        $user = auth()->user();
        if (! $user) {
            return 0;
        }

        if ($user->hasAnyRole(['Academic Registrar', 'Super Admin'])) {
            return $registrarCount + $incompleteCount;
        }

        if ($user->hasAnyRole(['HOD', 'Dean of Students', 'Super Admin'])) {
            return $hodCount + $incompleteCount;
        }

        return $incompleteCount;
    }
}
