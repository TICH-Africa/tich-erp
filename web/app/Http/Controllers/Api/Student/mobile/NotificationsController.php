<?php

namespace App\Http\Controllers\Api\Student\mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Student\mobile\Concerns\ResolvesPortalStudent;
use App\Models\StudentNotification;
use App\Services\Sidebar\StudentSidebarNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class NotificationsController extends Controller
{
    use ResolvesPortalStudent;

    public function __construct(
        protected StudentSidebarNotificationService $sidebarNotifications,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $student = $this->portalStudent($request);

        if (! Schema::hasTable('student_notifications')) {
            return response()->json(['total' => 0, 'unread' => 0, 'items' => []]);
        }

        $rows = StudentNotification::query()
            ->where('student_id', $student->id)
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        $unread = $rows->whereNull('read_at')->count();
        $items = $rows->map(fn (StudentNotification $n) => [
            'id' => $n->id,
            'title' => $n->title,
            'body' => $n->body,
            'category' => $n->category,
            'action_url' => $n->action_url,
            'read_at' => optional($n->read_at)?->toIso8601String(),
            'created_at' => optional($n->created_at)?->toIso8601String(),
        ]);

        return response()->json([
            'total' => $items->count(),
            'unread' => $unread,
            'items' => $items->values()->all(),
        ]);
    }

    public function markRead(Request $request, StudentNotification $notification): JsonResponse
    {
        $student = $this->portalStudent($request);
        abort_unless((int) $notification->student_id === (int) $student->id, 403);

        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
            $this->sidebarNotifications->forget($student);
        }

        return response()->json([
            'message' => 'Notification marked as read.',
            'id' => $notification->id,
            'read_at' => optional($notification->read_at)?->toIso8601String(),
        ]);
    }
}
