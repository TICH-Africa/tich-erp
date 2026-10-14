<?php

namespace App\Http\Controllers\Api\Student\mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Student\mobile\Concerns\ResolvesPortalStudent;
use App\Http\Controllers\Api\Student\mobile\Concerns\SerializesStudentPortal;
use App\Models\StudentNotification;
use App\Services\StudentPortalDashboardService;
use App\Services\StudentRecordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    use ResolvesPortalStudent;
    use SerializesStudentPortal;

    public function __construct(
        protected StudentPortalDashboardService $dashboard,
        protected StudentRecordService $studentRecords,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $student = $this->portalStudent($request);
        $biodata = $this->studentRecords->biodata360($student);
        $portalData = $this->dashboard->forStudent($student, $biodata);

        $notifications = [];
        $unread = 0;
        if (Schema::hasTable('student_notifications')) {
            $notifications = StudentNotification::query()
                ->where('student_id', $student->id)
                ->orderByDesc('created_at')
                ->limit(8)
                ->get()
                ->map(fn (StudentNotification $n) => [
                    'id' => $n->id,
                    'title' => $n->title,
                    'body' => $n->body,
                    'category' => $n->category,
                    'action_url' => $n->action_url,
                    'read_at' => optional($n->read_at)?->toIso8601String(),
                    'created_at' => optional($n->created_at)?->toIso8601String(),
                ])
                ->all();
            $unread = StudentNotification::query()
                ->where('student_id', $student->id)
                ->whereNull('read_at')
                ->count();
        }

        return response()->json([
            'student' => $this->serializeStudent($student, $biodata),
            'overview_stats' => $portalData['overview_stats'],
            'finance_summary' => $portalData['finance']['summary'] ?? [],
            'transcript' => $portalData['transcript'] ?? [],
            'notifications_preview' => $notifications,
            'unread_notifications' => $unread,
        ]);
    }
}
