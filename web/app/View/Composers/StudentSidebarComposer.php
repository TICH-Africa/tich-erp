<?php

namespace App\View\Composers;

use App\Services\Sidebar\StudentSidebarNotificationService;
use App\Services\StudentPortalService;
use Illuminate\View\View;

class StudentSidebarComposer
{
    public function __construct(
        protected StudentSidebarNotificationService $notifications,
        protected StudentPortalService $studentPortal,
    ) {}

    public function compose(View $view): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $student = $this->studentPortal->studentForUser($user);
        if (! $student) {
            return;
        }

        $counts = $this->notifications->countsFor($student);

        $view->with([
            'sidebarCounts' => $counts,
            'sidebarLabels' => $this->notifications->formattedCountsFor($student),
            'sidebarMenuLabels' => StudentSidebarNotificationService::MENU_KEYS,
            'sidebarId' => 'student-admin-sidebar',
            'sidebarPollUrl' => route('portal.sidebar-notifications'),
            'sidebarBroadcastEnabled' => true,
            'sidebarBroadcastChannel' => "student.sidebar.{$user->id}",
        ]);
    }
}
