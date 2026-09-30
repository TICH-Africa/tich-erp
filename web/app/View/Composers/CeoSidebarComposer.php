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
        $isInstitutionAdmin = request()->routeIs('institution-admin.*');

        $view->with([
            'sidebarCounts' => $counts,
            'sidebarLabels' => $labels,
            'sidebarMenuLabels' => CeoSidebarNotificationService::MENU_KEYS,
            'sidebarId' => $isInstitutionAdmin ? 'institution-admin-sidebar' : 'ceo-admin-sidebar',
            'sidebarPollUrl' => $isInstitutionAdmin
                ? route('institution-admin.sidebar-notifications')
                : route('ceo.sidebar-notifications'),
            'sidebarBroadcastEnabled' => true,
            'sidebarBroadcastChannel' => 'ceo.sidebar',
        ]);
    }
}
