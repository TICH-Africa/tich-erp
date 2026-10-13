<?php

namespace App\Http\Controllers\Api\Employee\mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Employee\mobile\Concerns\ResolvesPortalStaff;
use App\Services\Sidebar\EmployeeSidebarNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ResolvesPortalStaff;

    /** @var array<string, string> */
    private const LABELS = [
        'profile-changes' => 'Profile changes awaiting HR',
        'leave.returned' => 'Leave returned for edits',
        'appraisals' => 'Appraisal actions needed',
        'concerns' => 'Open concerns',
        'feedback' => 'Feedback awaiting response',
        'policies' => 'Policies to acknowledge',
        'qa.tasks' => 'QA tasks',
    ];

    public function __invoke(
        Request $request,
        EmployeeSidebarNotificationService $notifications,
    ): JsonResponse {
        $staff = $this->portalStaff($request);
        $counts = $notifications->countsFor($staff, true, $request->user());
        $labels = $notifications->formattedCountsFor($staff, true, $request->user());

        $items = [];
        $total = 0;
        foreach ($counts as $key => $count) {
            $n = (int) $count;
            if ($n <= 0) {
                continue;
            }
            $total += $n;
            $items[] = [
                'key' => $key,
                'title' => self::LABELS[$key] ?? str_replace(['.', '_', '-'], ' ', (string) $key),
                'count' => $n,
                'badge' => $labels[$key] ?? (string) $n,
            ];
        }

        return response()->json([
            'total' => $total,
            'counts' => $counts,
            'labels' => $labels,
            'items' => $items,
        ]);
    }
}
