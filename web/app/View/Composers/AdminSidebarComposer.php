<?php

namespace App\View\Composers;

use App\Services\Sidebar\AdminSidebarNotificationService;
use Illuminate\View\View;

class AdminSidebarComposer
{
    public function __construct(
        protected AdminSidebarNotificationService $notifications,
    ) {}

    public function compose(View $view): void
    {
        $counts = $this->notifications->counts();
        $labels = $this->notifications->formattedCounts();

        $view->with([
            'adminSidebarCounts' => $counts,
            'adminSidebarLabels' => $labels,
            'adminSidebarMenuLabels' => AdminSidebarNotificationService::MENU_KEYS,
            'sidebarCounts' => $counts,
            'sidebarLabels' => $labels,
            'sidebarMenuLabels' => AdminSidebarNotificationService::MENU_KEYS,
            'sidebarId' => 'admin-platform-sidebar',
            'sidebarPollUrl' => route('admin.sidebar-notifications'),
            'sidebarBroadcastEnabled' => true,
            'sidebarBroadcastChannel' => 'admin.sidebar',
        ]);
    }
}
