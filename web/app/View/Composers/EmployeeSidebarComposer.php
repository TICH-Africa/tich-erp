<?php

namespace App\View\Composers;

use App\Services\EmployeePortalService;
use App\Services\Sidebar\EmployeeSidebarNotificationService;
use Illuminate\View\View;

class EmployeeSidebarComposer
{
    public function __construct(
        protected EmployeeSidebarNotificationService $notifications,
        protected EmployeePortalService $employeePortal,
    ) {}

    public function compose(View $view): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $staff = $this->employeePortal->staffForUser($user);
        if (! $staff) {
            return;
        }

        $counts = $this->notifications->countsFor($staff);

        $view->with([
            'staff' => $staff,
            'mustCompleteProfile' => app(\App\Services\EmployeeProfileCompletenessService::class)->isComplete($staff) === false,
            'sidebarCounts' => $counts,
            'sidebarLabels' => $this->notifications->formattedCounts($counts),
            'sidebarMenuLabels' => EmployeeSidebarNotificationService::MENU_KEYS,
            'sidebarId' => 'employee-admin-sidebar',
            'sidebarPollUrl' => route('employee.sidebar-notifications'),
            'sidebarBroadcastEnabled' => true,
            'sidebarBroadcastChannel' => "employee.sidebar.{$user->id}",
        ]);
    }
}
