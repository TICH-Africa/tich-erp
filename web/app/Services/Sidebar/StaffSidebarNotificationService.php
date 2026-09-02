<?php

namespace App\Services\Sidebar;

use App\Models\LeaveRequest;
use App\Models\Staff;
use App\Models\StaffDocument;
use App\Models\User;
use App\Services\Sidebar\Concerns\FormatsSidebarBadgeCounts;
use App\Services\StaffPortalNavigationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StaffSidebarNotificationService
{
    use FormatsSidebarBadgeCounts;

    public const CACHE_KEY_PREFIX = 'staff.sidebar.counts.';

    public const CACHE_TTL_SECONDS = 30;

    /** @var array<string, string> */
    public const MENU_KEYS = [
        'lesson-plans' => 'Lesson plans',
        'attendance.incomplete' => 'Attendance',
        'leave.returned' => 'Leave',
        'documents' => 'My Documents',
        'hod-management' => 'HOD management',
        'hod-lesson-plans' => 'Lesson plans',
        'hod-unit-allocations' => 'Unit allocations',
        'hod-attendance' => 'Attendance review',
        'hod-leave' => 'Department leave',
        'hod-performance' => 'Performance',
    ];

    public function __construct(
        protected StaffPortalNavigationService $navigation,
    ) {}

    /**
     * @return array<string, int>
     */
    public function countsFor(Staff $staff, User $user, bool $fresh = false): array
    {
        $cacheKey = self::CACHE_KEY_PREFIX.$staff->id;

        if ($fresh) {
            return $this->computeCounts($staff, $user);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, fn () => $this->computeCounts($staff, $user));
    }

    /**
     * @return array<string, string|null>
     */
    public function formattedCountsFor(Staff $staff, User $user, bool $fresh = false): array
    {
        return $this->formattedCounts($this->countsFor($staff, $user, $fresh));
    }

    public function forget(Staff $staff): void
    {
        Cache::forget(self::CACHE_KEY_PREFIX.$staff->id);
    }

    public function badgeKeyForSection(string $section): ?string
    {
        return match ($section) {
            'lesson-plans' => 'lesson-plans',
            'attendance' => 'attendance.incomplete',
            'leave' => 'leave.returned',
            'documents' => 'documents',
            'hod-management' => 'hod-management',
            'hod-lesson-plans' => 'hod-lesson-plans',
            'hod-attendance' => 'hod-attendance',
            'hod-leave' => 'hod-leave',
            default => null,
        };
    }

    /**
     * @return array<string, int>
     */
    private function computeCounts(Staff $staff, User $user): array
    {
        $allocationIds = DB::table('unit_allocations')
            ->where('staff_id', $staff->id)
            ->where('is_active', 1)
            ->pluck('id')
            ->all();

        $counts = [
            'lesson-plans' => $this->lessonPlansNeedingAction($allocationIds),
            'attendance.incomplete' => $this->incompleteAttendanceSessions($allocationIds),
            'leave.returned' => $this->returnedLeaveRequests($staff),
            'documents' => $this->unverifiedDocuments($staff),
            'hod-management' => 0,
            'hod-lesson-plans' => 0,
            'hod-attendance' => 0,
            'hod-leave' => 0,
        ];

        if ($user->hasAnyRole(['HOD', 'Dean of Students', 'Academic Registrar', 'Super Admin'])) {
            $counts['hod-management'] = $this->hodLessonPlansPending($staff) + $this->hodAttendancePending($staff);
            $counts['hod-lesson-plans'] = $this->hodLessonPlansPending($staff);
            $counts['hod-attendance'] = $this->hodAttendancePending($staff);
            $counts['hod-leave'] = $this->hodDepartmentLeavePending($staff);
        }

        return $counts;
    }

    /**
     * @param  list<int>  $allocationIds
     */
    private function lessonPlansNeedingAction(array $allocationIds): int
    {
        if ($allocationIds === []) {
            return 0;
        }

        return (int) DB::table('lesson_plans')
            ->whereIn('unit_allocation_id', $allocationIds)
            ->whereIn('status', ['draft', 'modified', 'rejected'])
            ->count();
    }

    /**
     * @param  list<int>  $allocationIds
     */
    private function incompleteAttendanceSessions(array $allocationIds): int
    {
        if ($allocationIds === []) {
            return 0;
        }

        return (int) DB::table('attendance_sessions')
            ->whereIn('unit_allocation_id', $allocationIds)
            ->where('is_locked', 0)
            ->whereDate('session_date', '>=', now()->subWeek()->toDateString())
            ->count();
    }

    private function returnedLeaveRequests(Staff $staff): int
    {
        return LeaveRequest::query()
            ->where('staff_id', $staff->id)
            ->where('overall_status', 'returned')
            ->where('is_cancelled', false)
            ->count();
    }

    private function unverifiedDocuments(Staff $staff): int
    {
        if (! Schema::hasTable('staff_documents')) {
            return 0;
        }

        return StaffDocument::query()
            ->where('staff_id', $staff->id)
            ->where('is_verified', 0)
            ->whereNotNull('file_path')
            ->count();
    }

    private function hodLessonPlansPending(Staff $staff): int
    {
        $departmentId = (int) ($staff->department_id ?? 0);
        if ($departmentId <= 0) {
            return 0;
        }

        return (int) DB::table('lesson_plans as lp')
            ->join('unit_allocations as ua', 'ua.id', '=', 'lp.unit_allocation_id')
            ->join('units as u', 'u.id', '=', 'ua.unit_id')
            ->join('staff as st', 'st.id', '=', 'lp.prepared_by')
            ->where('st.department_id', $departmentId)
            ->whereIn('lp.status', ['submitted', 'modified'])
            ->count();
    }

    private function hodAttendancePending(Staff $staff): int
    {
        $departmentId = (int) ($staff->department_id ?? 0);
        if ($departmentId <= 0) {
            return 0;
        }

        return (int) DB::table('attendance_sessions as s')
            ->join('unit_allocations as ua', 'ua.id', '=', 's.unit_allocation_id')
            ->join('units as u', 'u.id', '=', 'ua.unit_id')
            ->join('staff as tutor', 'tutor.id', '=', 's.recorded_by')
            ->where('tutor.department_id', $departmentId)
            ->where('s.is_locked', 1)
            ->where('s.verification_status', 'submitted')
            ->count();
    }

    private function hodDepartmentLeavePending(Staff $staff): int
    {
        $departmentId = (int) ($staff->department_id ?? 0);
        if ($departmentId <= 0) {
            return 0;
        }

        return (int) LeaveRequest::query()
            ->where('staff_id', '!=', $staff->id)
            ->where('is_cancelled', false)
            ->whereIn('overall_status', ['pending_hod', 'pending_hr'])
            ->whereHas('staff', fn ($query) => $query->where('department_id', $departmentId))
            ->count();
    }
}
