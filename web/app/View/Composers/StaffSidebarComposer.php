<?php

namespace App\View\Composers;

use App\Services\Sidebar\StaffSidebarNotificationService;
use App\Services\StaffPortalService;
use Illuminate\View\View;

class StaffSidebarComposer
{
    public function __construct(
        protected StaffSidebarNotificationService $notifications,
        protected StaffPortalService $staffPortal,
    ) {}

    public function compose(View $view): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $staff = $this->staffPortal->staffForUser($user);
        if (! $staff) {
            return;
        }

        $counts = $this->notifications->countsFor($staff, $user);

        $view->with([
            'sidebarCounts' => $counts,
            'sidebarLabels' => $this->notifications->formattedCounts($counts),
            'sidebarMenuLabels' => StaffSidebarNotificationService::MENU_KEYS,
            'sidebarId' => 'staff-admin-sidebar',
            'sidebarPollUrl' => route('staff.sidebar-notifications'),
            'sidebarBroadcastEnabled' => true,
            'sidebarBroadcastChannel' => "staff.sidebar.{$user->id}",
        ]);
    }
}
