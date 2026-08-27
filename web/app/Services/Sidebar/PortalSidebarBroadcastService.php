<?php

namespace App\Services\Sidebar;

use App\Events\PortalSidebarCountsUpdated;
use App\Models\Department;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use App\Support\SafelyBroadcasts;

class PortalSidebarBroadcastService
{
    use SafelyBroadcasts;
    public function __construct(
        protected EmployeeSidebarNotificationService $employeeNotifications,
        protected StaffSidebarNotificationService $staffNotifications,
        protected StudentSidebarNotificationService $studentNotifications,
        protected AcademicsSidebarNotificationService $academicsNotifications,
    ) {}

    public function broadcastEmployeeForStaff(Staff $staff): void
    {
        $user = $this->userForStaff($staff);
        if (! $user) {
            return;
        }

        $this->employeeNotifications->forget($staff);
        $counts = $this->employeeNotifications->countsFor($staff, true);
        $labels = $this->employeeNotifications->formattedCounts($counts);

        $this->broadcast("employee.sidebar.{$user->id}", $counts, $labels);
    }

    public function broadcastStaffForStaff(Staff $staff): void
    {
        $user = $this->userForStaff($staff);
        if (! $user) {
            return;
        }

        $this->staffNotifications->forget($staff);
        $counts = $this->staffNotifications->countsFor($staff, $user, true);
        $labels = $this->staffNotifications->formattedCounts($counts);

        $this->broadcast("staff.sidebar.{$user->id}", $counts, $labels);
    }

    public function broadcastStaffPortals(Staff $staff): void
    {
        $this->broadcastEmployeeForStaff($staff);
        $this->broadcastStaffForStaff($staff);
    }

    public function broadcastStudent(Student $student): void
    {
        if (! $student->user_id) {
            return;
        }

        $this->studentNotifications->forget($student);
        $counts = $this->studentNotifications->countsFor($student, true);
        $labels = $this->studentNotifications->formattedCounts($counts);

        $this->broadcast("student.sidebar.{$student->user_id}", $counts, $labels);
    }

    public function broadcastAcademicsHub(Department $department): void
    {
        $hub = $department->isAcademicsHub() ? $department : $department->academicsHub();
        if (! $hub) {
            return;
        }

        $this->academicsNotifications->forgetHub($hub);
        $counts = $this->academicsNotifications->countsForHub($hub, true);
        $labels = $this->academicsNotifications->formattedCounts($counts);

        $this->broadcast("academics.sidebar.{$hub->id}", $counts, $labels);
    }

    public function broadcastAcademicsForDepartmentId(?int $departmentId): void
    {
        if (! $departmentId) {
            return;
        }

        $department = Department::query()->find($departmentId);
        if ($department) {
            $this->broadcastAcademicsHub($department);
        }
    }

    /**
     * @param  array<string, int>  $counts
     * @param  array<string, string|null>  $labels
     */
    private function broadcast(string $channel, array $counts, array $labels): void
    {
        $this->safelyBroadcast(
            fn () => broadcast(new PortalSidebarCountsUpdated($channel, $counts, $labels))
        );
    }

    private function userForStaff(Staff $staff): ?User
    {
        if ($staff->user_id) {
            return User::query()->find($staff->user_id);
        }

        return User::query()->where('staff_id', $staff->id)->first();
    }
}
