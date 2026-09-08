<?php

namespace App\View\Composers;

use App\Services\Sidebar\CeoSidebarNotificationService;
use Illuminate\View\View;

class CeoSidebarComposer
{
    public function __construct(
        protected CeoSidebarNotificationService $notifications,
    ) {}

    public function compose(View $view): void
    {
        $counts = $this->notifications->counts();
        $labels = $this->notifications->labels();

        $view->with([
            'sidebarCounts' => $counts,
            'sidebarLabels' => $labels,
            'sidebarMenuLabels' => CeoSidebarNotificationService::MENU_KEYS,
            'sidebarId' => 'ceo-admin-sidebar',
            'sidebarPollUrl' => route('ceo.sidebar-notifications'),
            'sidebarBroadcastEnabled' => true,
            'sidebarBroadcastChannel' => 'ceo.sidebar',
        ]);
    }
}
