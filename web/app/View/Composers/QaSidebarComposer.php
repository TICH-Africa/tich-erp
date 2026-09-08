<?php

namespace App\View\Composers;

use App\Services\Sidebar\QaSidebarNotificationService;
use Illuminate\View\View;

class QaSidebarComposer
{
    public function __construct(
        protected QaSidebarNotificationService $notifications,
    ) {}

    public function compose(View $view): void
    {
        $user = auth()->user();
        $counts = $this->notifications->counts($user);
        $labels = $this->notifications->labels($user);

        $view->with([
            'sidebarCounts' => $counts,
            'sidebarLabels' => $labels,
            'sidebarMenuLabels' => QaSidebarNotificationService::MENU_KEYS,
            'sidebarId' => 'qa-admin-sidebar',
            'sidebarPollUrl' => route('qa.sidebar-notifications'),
            'sidebarBroadcastEnabled' => true,
            'sidebarBroadcastChannel' => 'qa.sidebar',
        ]);
    }
}
