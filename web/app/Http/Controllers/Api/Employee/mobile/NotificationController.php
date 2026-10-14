<?php

namespace App\Http\Controllers\Api\Employee\mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Employee\mobile\Concerns\ResolvesPortalStaff;
use App\Models\InAppNotification;
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
        'attendance_clock_in' => 'Clock-in reminders',
    ];

    public function __invoke(
        Request $request,
        EmployeeSidebarNotificationService $notifications,
    ): JsonResponse {
        $staff = $this->portalStaff($request);
        $user = $request->user();
        $counts = $notifications->countsFor($staff, true, $user);
        $labels = $notifications->formattedCountsFor($staff, true, $user);

        $inbox = InAppNotification::forUser((int) $user->id, 80);
        $unreadInbox = $inbox->filter(fn (InAppNotification $n) => $n->isUnread())->count();

        $actionItems = [];
        $actionTotal = 0;
        foreach ($counts as $key => $count) {
            $n = (int) $count;
            if ($n <= 0) {
                continue;
            }
            $actionTotal += $n;
            $actionItems[] = [
                'key' => $key,
                'title' => self::LABELS[$key] ?? str_replace(['.', '_', '-'], ' ', (string) $key),
                'count' => $n,
                'badge' => $labels[$key] ?? (string) $n,
            ];
        }

        $items = $inbox->map(fn (InAppNotification $n) => [
            'id' => $n->id,
            'title' => $n->title,
            'body' => $n->body,
            'priority' => $n->priority,
            'action_url' => $n->action_url,
            'is_read' => (bool) $n->is_read,
            'created_at' => optional($n->created_at)?->toIso8601String(),
            'related_entity_type' => $n->related_entity_type,
        ])->values()->all();

        return response()->json([
            'total' => $unreadInbox + $actionTotal,
            'unread' => $unreadInbox,
            'counts' => $counts,
            'labels' => $labels,
            'action_items' => $actionItems,
            'items' => $items,
        ]);
    }

    public function markRead(Request $request, InAppNotification $notification): JsonResponse
    {
        abort_unless((int) $notification->user_id === (int) $request->user()->id, 403);
        $notification->markRead('mobile');

        return response()->json([
            'message' => 'Notification marked as read.',
            'id' => $notification->id,
        ]);
    }
}
