<?php

namespace App\View\Composers;

use App\Services\HrSidebarNotificationService;
use Illuminate\View\View;

class HrSidebarComposer
{
    public function __construct(
        protected HrSidebarNotificationService $notifications,
    ) {}

    public function compose(View $view): void
    {
        $counts = $this->notifications->counts();
        $labels = $this->notifications->formattedCounts();

        $view->with([
            'hrSidebarCounts' => $counts,
            'hrSidebarLabels' => $labels,
            'hrSidebarMenuLabels' => HrSidebarNotificationService::MENU_KEYS,
            'sidebarCounts' => $counts,
            'sidebarLabels' => $labels,
            'sidebarMenuLabels' => HrSidebarNotificationService::MENU_KEYS,
            'sidebarId' => 'hr-admin-sidebar',
            'sidebarPollUrl' => route('hr.sidebar-notifications'),
            'sidebarBroadcastEnabled' => true,
            'sidebarBroadcastChannel' => 'hr.sidebar',
        ]);
    }
}
