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
use Illuminate\Support\Facades\Cache;
use App\Support\SchemaProbe;

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
        $biodata = $this->mobileBiodata($student);
        $cacheKey = 'student.mobile.dashboard.'.$student->id.'.v1';
        $overview = Cache::remember(
            $cacheKey,
            20,
            fn () => $this->dashboard->overviewForMobile($student, $biodata),
        );

        $notifications = [];
        $unread = 0;
        if (SchemaProbe::hasTable('student_notifications')) {
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
            'overview_stats' => $overview['overview_stats'],
            'finance_summary' => $overview['finance_summary'],
            'transcript' => $overview['transcript'] ?? [],
            'notifications_preview' => $notifications,
            'unread_notifications' => $unread,
        ]);
    }
}
