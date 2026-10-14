<?php

namespace App\Http\Controllers\Api\Employee\mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Employee\mobile\Concerns\ResolvesPortalStaff;
use App\Models\Feedback;
use App\Models\Grievance;
use App\Models\WorkFromHomeRequest;
use App\Services\EmployeeConcernService;
use App\Services\HrPerformanceAppraisalService;
use App\Services\WorkFromHomeRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Lightweight list endpoints for secondary employee portal modules.
 */
class PortalListsController extends Controller
{
    use ResolvesPortalStaff;

    public function wfh(Request $request, WorkFromHomeRequestService $wfh): JsonResponse
    {
        $staff = $this->portalStaff($request);
        $requests = WorkFromHomeRequest::query()
            ->where('staff_id', $staff->id)
            ->orderByDesc('work_date')
            ->limit(50)
            ->get();

        return response()->json([
            'entitlement' => $wfh->entitlementSnapshot($staff),
            'requests' => $requests->map(fn ($r) => [
                'id' => $r->id,
                'work_date' => optional($r->work_date)?->format('Y-m-d'),
                'status' => $r->status,
                'reason' => $r->reason,
            ])->values(),
        ]);
    }

    public function storeWfh(Request $request, WorkFromHomeRequestService $wfh): JsonResponse
    {
        $staff = $this->portalStaff($request);
        $validated = $request->validate([
            'work_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'remote_tasks' => ['required', 'array', 'min:1', 'max:5'],
            'remote_tasks.*' => ['nullable', 'string', 'max:500'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $validated['start_time'] = $validated['start_time'] ?? '08:00';
        $validated['end_time'] = $validated['end_time'] ?? '17:00';
        $tasks = array_values(array_filter(
            array_map(static fn ($t) => trim((string) $t), $validated['remote_tasks'] ?? []),
            static fn ($t) => $t !== ''
        ));
        if ($tasks === [] && filled($validated['reason'] ?? null)) {
            $tasks = [trim((string) $validated['reason'])];
        }
        if ($tasks === []) {
            return response()->json(['message' => 'List at least one remote task.'], 422);
        }
        $validated['remote_tasks'] = $tasks;

        try {
            $created = $wfh->submit($staff, $request->user(), $validated);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'WFH request submitted.',
            'request' => [
                'id' => $created->id,
                'work_date' => optional($created->work_date)?->format('Y-m-d'),
                'status' => $created->status,
            ],
        ], 201);
    }

    public function concerns(Request $request): JsonResponse
    {
        $staff = $this->portalStaff($request);
        $items = Grievance::query()
            ->where('staff_id', $staff->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return response()->json([
            'categories' => EmployeeConcernService::CATEGORIES,
            'concerns' => $items->map(fn ($c) => [
                'id' => $c->id,
                'subject' => $c->subject,
                'category' => $c->concern_category ?? $c->category,
                'status' => $c->status,
                'created_at' => optional($c->created_at)?->toIso8601String(),
            ])->values(),
        ]);
    }

    public function storeConcern(Request $request, EmployeeConcernService $concerns): JsonResponse
    {
        $staff = $this->portalStaff($request);
        $validated = $request->validate([
            'concern_category' => 'required|string|in:'.implode(',', array_keys(EmployeeConcernService::CATEGORIES)),
            'subject' => 'required|string|max:300',
            'description' => 'required|string|max:5000',
        ]);

        try {
            $created = $concerns->submit($staff, $request->user(), $validated);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Concern submitted.',
            'concern' => ['id' => $created->id ?? null],
        ], 201);
    }

    public function appraisals(Request $request, HrPerformanceAppraisalService $appraisals): JsonResponse
    {
        $staff = $this->portalStaff($request);

        try {
            $list = method_exists($appraisals, 'forEmployee')
                ? $appraisals->forEmployee($staff)
                : $staff->performanceReviews()->with('cycle')->orderByDesc('id')->limit(20)->get();
        } catch (\Throwable) {
            $list = $staff->performanceReviews()->with('cycle')->orderByDesc('id')->limit(20)->get();
        }

        return response()->json([
            'appraisals' => collect($list)->map(fn ($a) => [
                'id' => $a->id,
                'cycle' => $a->cycle?->name ?? $a->cycle?->title,
                'status' => $a->status ?? $a->overall_status,
                'period' => $a->cycle?->period_label ?? null,
            ])->values(),
        ]);
    }

    public function timeLogs(Request $request): JsonResponse
    {
        $staff = $this->portalStaff($request);

        if (! \Illuminate\Support\Facades\Schema::hasTable('staff_weekly_time_logs')
            && ! \Illuminate\Support\Facades\Schema::hasTable('weekly_time_logs')) {
            return response()->json(['weeks' => [], 'message' => 'Time logs unavailable.']);
        }

        $table = \Illuminate\Support\Facades\Schema::hasTable('staff_weekly_time_logs')
            ? 'staff_weekly_time_logs'
            : 'weekly_time_logs';
        $columns = \Illuminate\Support\Facades\Schema::getColumnListing($table);
        $periodColumn = in_array('period_start', $columns, true)
            ? 'period_start'
            : (in_array('week_start', $columns, true) ? 'week_start' : null);

        $query = \Illuminate\Support\Facades\DB::table($table)
            ->where('staff_id', $staff->id);
        if ($periodColumn !== null) {
            $query->orderByDesc($periodColumn);
        } elseif (in_array('log_year', $columns, true)
            && in_array('log_month', $columns, true)
            && in_array('week_number', $columns, true)) {
            $query->orderByDesc('log_year')
                ->orderByDesc('log_month')
                ->orderByDesc('week_number');
        } elseif (in_array('created_at', $columns, true)) {
            $query->orderByDesc('created_at');
        } else {
            $query->orderByDesc('id');
        }
        $weeks = $query->limit(24)->get();

        return response()->json([
            'weeks' => $weeks->map(fn ($w) => [
                'id' => $w->id,
                'week_start' => $w->period_start ?? $w->week_start ?? null,
                'week_end' => $w->period_end ?? $w->week_end ?? null,
                'status' => $w->status ?? null,
                'total_hours' => isset($w->total_hours) ? (float) $w->total_hours : null,
            ])->values(),
        ]);
    }

    public function feedback(Request $request): JsonResponse
    {
        $staff = $this->portalStaff($request);

        return response()->json([
            'items' => Feedback::query()
                ->where('staff_id', $staff->id)
                ->orderByDesc('created_at')
                ->limit(50)
                ->get()
                ->map(fn (Feedback $feedback) => [
                    'id' => $feedback->id,
                    'feedback_type' => $feedback->feedback_type,
                    'description' => $feedback->description,
                    'status' => $feedback->status,
                    'response' => $feedback->response,
                    'hr_comments' => $feedback->hr_comments,
                    'created_at' => optional($feedback->created_at)?->toIso8601String(),
                ])
                ->values(),
        ]);
    }

    public function storeFeedback(Request $request): JsonResponse
    {
        $staff = $this->portalStaff($request);
        $validated = $request->validate([
            'feedback_type' => ['nullable', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:5000'],
        ]);

        $feedback = Feedback::query()->create([
            'staff_id' => $staff->id,
            'feedback_type' => $validated['feedback_type'] ?? null,
            'description' => $validated['description'],
            'status' => 'open',
        ]);

        return response()->json([
            'message' => 'Feedback submitted to HR.',
            'item' => [
                'id' => $feedback->id,
                'feedback_type' => $feedback->feedback_type,
                'description' => $feedback->description,
                'status' => $feedback->status,
                'response' => $feedback->response,
                'hr_comments' => $feedback->hr_comments,
                'created_at' => optional($feedback->created_at)?->toIso8601String(),
            ],
        ], 201);
    }
}
