<?php

namespace App\View\Composers;

use App\Services\Procurement\ProcurementSidebarNotificationService;
use App\View\Composers\Concerns\InjectsQaTaskSidebarBadge;
use Illuminate\View\View;

class ProcurementSidebarComposer
{
    use InjectsQaTaskSidebarBadge;

    public function __construct(
        protected ProcurementSidebarNotificationService $notifications,
    ) {}

    public function compose(View $view): void
    {
        $counts = $this->notifications->counts();
        $labels = $this->notifications->formattedCounts();
        $menuKeys = ProcurementSidebarNotificationService::MENU_KEYS;
        [$counts, $labels, $menuKeys] = $this->withQaTaskSidebarBadge($counts, $labels, $menuKeys, null, 'procurement');

        $view->with([
            'procurementSidebarCounts' => $counts,
            'procurementSidebarLabels' => $labels,
            'sidebarCounts' => $counts,
            'sidebarLabels' => $labels,
            'sidebarMenuLabels' => $menuKeys,
            'sidebarId' => 'procurement-admin-sidebar',
            'sidebarPollUrl' => route('procurement.sidebar-notifications'),
            'sidebarBroadcastEnabled' => true,
            'sidebarBroadcastChannel' => 'procurement.sidebar',
        ]);
    }
}
