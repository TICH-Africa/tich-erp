<?php

namespace App\View\Composers;

use App\Services\Sidebar\IctSidebarNotificationService;
use Illuminate\View\View;

class IctSidebarComposer
{
    public function __construct(
        protected IctSidebarNotificationService $notifications,
    ) {}

    public function compose(View $view): void
    {
        $counts = $this->notifications->counts();
        $labels = $this->notifications->labels();

        $view->with([
            'sidebarCounts' => $counts,
            'sidebarLabels' => $labels,
            'sidebarMenuLabels' => IctSidebarNotificationService::MENU_KEYS,
            'sidebarId' => 'ict-admin-sidebar',
            'sidebarPollUrl' => route('ict.sidebar-notifications'),
            'sidebarBroadcastEnabled' => true,
            'sidebarBroadcastChannel' => 'ict.sidebar',
        ]);
    }
}
