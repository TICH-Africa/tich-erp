<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use App\Services\RBACService;
use App\Services\Sidebar\AdminSidebarNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class SidebarNotificationController extends Controller
{
    public function __invoke(
        Request $request,
        AdminSidebarNotificationService $notifications,
        AuditService $auditService,
        RBACService $rbac,
    ): JsonResponse {
        $counts = $notifications->counts(true);

        $user = $request->user();
        if ($user && ! $rbac->canViewUnrestrictedAuditLogs($user)) {
            $counts['audit-logs'] = (
                Schema::hasTable('audit_logs') && Schema::hasColumn('audit_logs', 'status')
            )
                ? $auditService->query(['status' => 'failure'], $user)
                    ->where('created_at', '>=', now()->subDays(7))
                    ->count()
                : 0;
        }

        $labels = collect($counts)
            ->mapWithKeys(fn (int $count, string $key) => [$key => $notifications->formatCount($count)])
            ->all();

        return response()->json([
            'counts' => $counts,
            'labels' => $labels,
        ]);
    }
}
