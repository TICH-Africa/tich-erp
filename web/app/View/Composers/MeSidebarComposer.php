<?php

namespace App\View\Composers;

use App\Services\Sidebar\MeSidebarNotificationService;
use App\View\Composers\Concerns\InjectsQaTaskSidebarBadge;
use Illuminate\View\View;

class MeSidebarComposer
{
    use InjectsQaTaskSidebarBadge;

    public function __construct(
        protected MeSidebarNotificationService $notifications,
    ) {}

    public function compose(View $view): void
    {
        $user = auth()->user();
        $counts = $this->notifications->counts($user);
        $labels = $this->notifications->labels($user);
        $menuKeys = MeSidebarNotificationService::MENU_KEYS;
        [$counts, $labels, $menuKeys] = $this->withQaTaskSidebarBadge($counts, $labels, $menuKeys, $user);

        $view->with([
            'sidebarCounts' => $counts,
            'sidebarLabels' => $labels,
            'sidebarMenuLabels' => $menuKeys,
            'sidebarId' => 'monitoring-evaluation-admin-sidebar',
            'sidebarPollUrl' => route('monitoring_evaluation.sidebar-notifications'),
            'sidebarBroadcastEnabled' => true,
            'sidebarBroadcastChannel' => 'me.sidebar',
        ]);
    }
}
