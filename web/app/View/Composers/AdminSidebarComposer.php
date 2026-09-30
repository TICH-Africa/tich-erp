<?php

namespace App\View\Composers;

use App\Services\AuditService;
use App\Services\RBACService;
use App\Services\Sidebar\AdminSidebarNotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AdminSidebarComposer
{
    public function __construct(
        protected AdminSidebarNotificationService $notifications,
        protected AuditService $auditService,
        protected RBACService $rbac,
    ) {}

    public function compose(View $view): void
    {
        $counts = $this->notifications->counts();
        $counts = $this->scopeAuditLogCount($counts);
        $labels = collect($counts)
            ->mapWithKeys(fn (int $count, string $key) => [$key => $this->notifications->formatCount($count)])
            ->all();

        $view->with([
            'adminSidebarCounts' => $counts,
            'adminSidebarLabels' => $labels,
            'adminSidebarMenuLabels' => AdminSidebarNotificationService::MENU_KEYS,
            'sidebarCounts' => $counts,
            'sidebarLabels' => $labels,
            'sidebarMenuLabels' => AdminSidebarNotificationService::MENU_KEYS,
            'sidebarId' => 'admin-platform-sidebar',
            'sidebarPollUrl' => route('admin.sidebar-notifications'),
            'sidebarBroadcastEnabled' => true,
            'sidebarBroadcastChannel' => 'admin.sidebar',
        ]);
    }

    /**
     * @param  array<string, int>  $counts
     * @return array<string, int>
     */
    private function scopeAuditLogCount(array $counts): array
    {
        $user = Auth::user();
        if (! $user || $this->rbac->canViewUnrestrictedAuditLogs($user)) {
            return $counts;
        }

        if (! Schema::hasTable('audit_logs') || ! Schema::hasColumn('audit_logs', 'status')) {
            $counts['audit-logs'] = 0;

            return $counts;
        }

        $counts['audit-logs'] = $this->auditService
            ->query(['status' => 'failure'], $user)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        return $counts;
    }
}
