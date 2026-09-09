<?php

namespace App\View\Composers;

use App\Services\Sidebar\IctSidebarNotificationService;
use App\View\Composers\Concerns\InjectsQaTaskSidebarBadge;
use Illuminate\View\View;

class IctSidebarComposer
{
    use InjectsQaTaskSidebarBadge;

    public function __construct(
        protected IctSidebarNotificationService $notifications,
    ) {}

    public function compose(View $view): void
    {
        $counts = $this->notifications->counts();
        $labels = $this->notifications->labels();
        $menuKeys = IctSidebarNotificationService::MENU_KEYS;
        [$counts, $labels, $menuKeys] = $this->withQaTaskSidebarBadge($counts, $labels, $menuKeys);

        $view->with([
            'sidebarCounts' => $counts,
            'sidebarLabels' => $labels,
            'sidebarMenuLabels' => $menuKeys,
            'sidebarId' => 'ict-admin-sidebar',
            'sidebarPollUrl' => route('ict.sidebar-notifications'),
            'sidebarBroadcastEnabled' => true,
            'sidebarBroadcastChannel' => 'ict.sidebar',
        ]);
    }
}
