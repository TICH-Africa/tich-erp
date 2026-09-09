<?php

namespace App\View\Composers;

use App\Services\Sidebar\AdministrationSidebarNotificationService;
use App\View\Composers\Concerns\InjectsQaTaskSidebarBadge;
use Illuminate\View\View;

class AdministrationSidebarComposer
{
    use InjectsQaTaskSidebarBadge;

    public function __construct(
        protected AdministrationSidebarNotificationService $notifications,
    ) {}

    public function compose(View $view): void
    {
        $counts = $this->notifications->counts();
        $labels = $this->notifications->formattedCounts();
        $menuKeys = AdministrationSidebarNotificationService::MENU_KEYS;
        [$counts, $labels, $menuKeys] = $this->withQaTaskSidebarBadge($counts, $labels, $menuKeys);

        $view->with([
            'administrationSidebarCounts' => $counts,
            'administrationSidebarLabels' => $labels,
            'administrationSidebarMenuLabels' => $menuKeys,
            'sidebarCounts' => $counts,
            'sidebarLabels' => $labels,
            'sidebarMenuLabels' => $menuKeys,
            'sidebarId' => 'administration-admin-sidebar',
            'sidebarPollUrl' => route('administration.sidebar-notifications'),
            'sidebarBroadcastEnabled' => true,
            'sidebarBroadcastChannel' => 'administration.sidebar',
        ]);
    }
}
