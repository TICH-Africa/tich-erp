<?php

namespace App\View\Composers;

use App\Services\HrSidebarNotificationService;
use App\View\Composers\Concerns\InjectsQaTaskSidebarBadge;
use Illuminate\View\View;

class HrSidebarComposer
{
    use InjectsQaTaskSidebarBadge;

    public function __construct(
        protected HrSidebarNotificationService $notifications,
    ) {}

    public function compose(View $view): void
    {
        $counts = $this->notifications->counts();
        $labels = $this->notifications->formattedCounts();
        $menuKeys = HrSidebarNotificationService::MENU_KEYS;
        [$counts, $labels, $menuKeys] = $this->withQaTaskSidebarBadge($counts, $labels, $menuKeys);

        $view->with([
            'hrSidebarCounts' => $counts,
            'hrSidebarLabels' => $labels,
            'hrSidebarMenuLabels' => $menuKeys,
            'sidebarCounts' => $counts,
            'sidebarLabels' => $labels,
            'sidebarMenuLabels' => $menuKeys,
            'sidebarId' => 'hr-admin-sidebar',
            'sidebarPollUrl' => route('hr.sidebar-notifications'),
            'sidebarBroadcastEnabled' => true,
            'sidebarBroadcastChannel' => 'hr.sidebar',
        ]);
    }
}
