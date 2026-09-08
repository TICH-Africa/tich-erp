<?php

namespace App\View\Composers;

use App\Services\Sidebar\MeSidebarNotificationService;
use Illuminate\View\View;

class MeSidebarComposer
{
    public function __construct(
        protected MeSidebarNotificationService $notifications,
    ) {}

    public function compose(View $view): void
    {
        $user = auth()->user();
        $counts = $this->notifications->counts($user);
        $labels = $this->notifications->labels($user);

        $view->with([
            'sidebarCounts' => $counts,
            'sidebarLabels' => $labels,
            'sidebarMenuLabels' => MeSidebarNotificationService::MENU_KEYS,
            'sidebarId' => 'monitoring-evaluation-admin-sidebar',
            'sidebarPollUrl' => route('monitoring_evaluation.sidebar-notifications'),
            'sidebarBroadcastEnabled' => true,
            'sidebarBroadcastChannel' => 'me.sidebar',
        ]);
    }
}
