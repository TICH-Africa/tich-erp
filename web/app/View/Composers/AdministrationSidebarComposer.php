<?php

namespace App\View\Composers;

use App\Services\Sidebar\AdministrationSidebarNotificationService;
use Illuminate\View\View;

class AdministrationSidebarComposer
{
    public function __construct(
        protected AdministrationSidebarNotificationService $notifications,
    ) {}

    public function compose(View $view): void
    {
        $counts = $this->notifications->counts();
        $labels = $this->notifications->formattedCounts();

        $view->with([
            'administrationSidebarCounts' => $counts,
            'administrationSidebarLabels' => $labels,
            'administrationSidebarMenuLabels' => AdministrationSidebarNotificationService::MENU_KEYS,
            'sidebarCounts' => $counts,
            'sidebarLabels' => $labels,
            'sidebarMenuLabels' => AdministrationSidebarNotificationService::MENU_KEYS,
            'sidebarId' => 'administration-admin-sidebar',
            'sidebarPollUrl' => route('administration.sidebar-notifications'),
            'sidebarBroadcastEnabled' => true,
            'sidebarBroadcastChannel' => 'administration.sidebar',
        ]);
    }
}
