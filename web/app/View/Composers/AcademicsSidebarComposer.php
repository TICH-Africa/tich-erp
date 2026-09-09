<?php

namespace App\View\Composers;

use App\Models\Department;
use App\Services\Sidebar\AcademicsSidebarNotificationService;
use App\View\Composers\Concerns\InjectsQaTaskSidebarBadge;
use Illuminate\View\View;

class AcademicsSidebarComposer
{
    use InjectsQaTaskSidebarBadge;

    public function __construct(
        protected AcademicsSidebarNotificationService $notifications,
    ) {}

    public function compose(View $view): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        /** @var Department|null $department */
        $department = $view->getData()['department'] ?? null;
        if (! $department instanceof Department) {
            return;
        }

        if (! empty($view->getData()['learningDepartment']) || request()->routeIs('departments.academics.programs.curriculum')) {
            return;
        }

        $counts = $this->notifications->countsFor($user, $department);
        $labels = $this->notifications->formattedCounts($counts);
        $menuKeys = AcademicsSidebarNotificationService::MENU_KEYS;
        [$counts, $labels, $menuKeys] = $this->withQaTaskSidebarBadge($counts, $labels, $menuKeys, $user);
        $hub = $department->isAcademicsHub() ? $department : ($department->academicsHub() ?? $department);

        $view->with([
            'sidebarCounts' => $counts,
            'sidebarLabels' => $labels,
            'sidebarMenuLabels' => $menuKeys,
            'sidebarId' => 'academics-admin-sidebar',
            'sidebarPollUrl' => route('departments.academics.sidebar-notifications', ['department' => $department]),
            'sidebarBroadcastEnabled' => true,
            'sidebarBroadcastChannel' => "academics.sidebar.{$hub->id}",
        ]);
    }
}
