<?php

namespace App\View\Composers;

use App\Services\Finance\FinanceSidebarNotificationService;
use Illuminate\View\View;

class FinanceSidebarComposer
{
    public function __construct(
        protected FinanceSidebarNotificationService $notifications,
    ) {}

    public function compose(View $view): void
    {
        $counts = $this->notifications->counts();
        $labels = $this->notifications->formattedCounts();

        $view->with([
            'financeSidebarCounts' => $counts,
            'financeSidebarLabels' => $labels,
            'sidebarCounts' => $counts,
            'sidebarLabels' => $labels,
            'sidebarMenuLabels' => FinanceSidebarNotificationService::MENU_KEYS,
            'sidebarId' => 'finance-admin-sidebar',
            'sidebarPollUrl' => route('finance.sidebar-notifications'),
            'sidebarBroadcastEnabled' => true,
            'sidebarBroadcastChannel' => 'finance.sidebar',
        ]);
    }
}
