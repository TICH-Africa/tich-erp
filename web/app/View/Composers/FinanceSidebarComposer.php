<?php

namespace App\View\Composers;

use App\Services\Finance\FinanceSidebarNotificationService;
use App\View\Composers\Concerns\InjectsQaTaskSidebarBadge;
use Illuminate\View\View;

class FinanceSidebarComposer
{
    use InjectsQaTaskSidebarBadge;

    public function __construct(
        protected FinanceSidebarNotificationService $notifications,
    ) {}

    public function compose(View $view): void
    {
        $counts = $this->notifications->counts();
        $labels = $this->notifications->formattedCounts();
        $menuKeys = FinanceSidebarNotificationService::MENU_KEYS;
        [$counts, $labels, $menuKeys] = $this->withQaTaskSidebarBadge($counts, $labels, $menuKeys);

        $view->with([
            'financeSidebarCounts' => $counts,
            'financeSidebarLabels' => $labels,
            'sidebarCounts' => $counts,
            'sidebarLabels' => $labels,
            'sidebarMenuLabels' => $menuKeys,
            'sidebarId' => 'finance-admin-sidebar',
            'sidebarPollUrl' => route('finance.sidebar-notifications'),
            'sidebarBroadcastEnabled' => true,
            'sidebarBroadcastChannel' => 'finance.sidebar',
        ]);
    }
}
