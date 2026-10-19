<?php

namespace App\Http\Controllers\Api\Employee\mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Employee\mobile\Concerns\ResolvesPortalStaff;
use App\Models\Staff;
use App\Models\StaffWeeklyTimeLog;
use App\Services\WeeklyTimeLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class TimeLogController extends Controller
{
    use ResolvesPortalStaff;

    public function __construct(
        protected WeeklyTimeLogService $timeLogs,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $staff = $this->portalStaff($request);

        if (! Schema::hasTable('staff_weekly_time_logs')) {
            return response()->json([
                'weeks' => [],
                'message' => 'Time logs unavailable.',
                'meta' => null,
            ]);
        }

        $current = $this->timeLogs->currentWeekMeta();

        $logs = StaffWeeklyTimeLog::query()
            ->where('staff_id', $staff->id)
            ->orderByDesc('log_year')
            ->orderByDesc('log_month')
            ->orderByDesc('week_number')
            ->limit(40)
            ->get();

        return response()->json([
            'weeks' => $logs->map(fn (StaffWeeklyTimeLog $log) => $this->serializeListItem($log))->values(),
            'meta' => [
                'current_year' => $current['year'],
                'current_month' => $current['month'],
                'current_week_number' => $current['week']['week_number'] ?? 1,
                'week_options' => $this->timeLogs->weeksForMonth($current['year'], $current['month']),
                'can_self_endorse' => $this->timeLogs->canSelfEndorse($staff),
            ],
        ]);
    }

    public function weeks(Request $request): JsonResponse
    {
        $year = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);

        return response()->json([
            'weeks' => $this->timeLogs->weeksForMonth($year, $month),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $staff = $this->portalStaff($request);
        $data = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'week_number' => ['required', 'integer', 'min:1', 'max:6'],
        ]);

        try {
            $log = $this->timeLogs->findOrCreateDraft(
                $staff,
                (int) $data['year'],
                (int) $data['month'],
                (int) $data['week_number'],
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $log = $this->timeLogs->pruneWeekendDays($log->load(['days', 'staff.department', 'manager']));

        return response()->json([
            'message' => 'Weekly time log opened.',
            'log' => $this->serializeLog($log, $staff),
        ], 201);
    }

    public function show(Request $request, StaffWeeklyTimeLog $timeLog): JsonResponse
    {
        $staff = $this->portalStaff($request);
        abort_unless($this->timeLogs->viewerMayAccess($timeLog, $staff, $request->user()), 403);

        $timeLog = $this->timeLogs->pruneWeekendDays(
            $timeLog->load(['days', 'staff.department', 'manager', 'hrReviewer'])
        );

        return response()->json([
            'log' => $this->serializeLog($timeLog, $staff),
        ]);
    }

    public function update(Request $request, StaffWeeklyTimeLog $timeLog): JsonResponse
    {
        $staff = $this->portalStaff($request);
        abort_unless((int) $timeLog->staff_id === (int) $staff->id, 403);

        $data = $request->validate([
            'days' => ['nullable', 'array'],
            'days.*.time_in' => ['nullable', 'string', 'max:8'],
            'days.*.time_out' => ['nullable', 'string', 'max:8'],
            'days.*.tasks_accomplished' => ['nullable', 'string', 'max:5000'],
            'days.*.initials' => ['nullable', 'string', 'max:20'],
            'days.*.department_ids' => ['nullable', 'array'],
            'days.*.department_ids.*' => ['integer'],
            'days.*.total_hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'days.*.total_units' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $this->timeLogs->saveDraft($timeLog, $staff, $data);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $timeLog = $this->timeLogs->pruneWeekendDays($timeLog->fresh(['days', 'staff.department', 'manager']));

        return response()->json([
            'message' => 'Time log saved.',
            'log' => $this->serializeLog($timeLog, $staff),
        ]);
    }

    public function submit(Request $request, StaffWeeklyTimeLog $timeLog): JsonResponse
    {
        $staff = $this->portalStaff($request);
        abort_unless((int) $timeLog->staff_id === (int) $staff->id, 403);

        $data = $request->validate([
            'days' => ['nullable', 'array'],
            'days.*.time_in' => ['nullable', 'string', 'max:8'],
            'days.*.time_out' => ['nullable', 'string', 'max:8'],
            'days.*.tasks_accomplished' => ['nullable', 'string', 'max:5000'],
            'days.*.initials' => ['nullable', 'string', 'max:20'],
            'days.*.department_ids' => ['nullable', 'array'],
            'days.*.department_ids.*' => ['integer'],
            'days.*.total_hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'days.*.total_units' => ['nullable', 'numeric', 'min:0'],
            'self_endorse' => ['nullable', 'boolean'],
        ]);

        try {
            $this->timeLogs->saveDraft($timeLog, $staff, $data);
            $this->timeLogs->submit($timeLog->fresh('days'), $staff, $request->boolean('self_endorse'));
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $timeLog = $this->timeLogs->pruneWeekendDays($timeLog->fresh(['days', 'staff.department', 'manager']));

        return response()->json([
            'message' => 'Weekly time log submitted to HR.',
            'log' => $this->serializeLog($timeLog, $staff),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeListItem(StaffWeeklyTimeLog $log): array
    {
        return [
            'id' => $log->id,
            'log_code' => $log->log_code,
            'week_ref' => $log->week_ref,
            'week_start' => $log->period_start?->format('Y-m-d'),
            'week_end' => $log->period_end?->format('Y-m-d'),
            'log_year' => $log->log_year,
            'log_month' => $log->log_month,
            'week_number' => $log->week_number,
            'status' => $log->status,
            'total_hours' => $log->total_hours !== null ? (float) $log->total_hours : null,
            'employee_signed_at' => $log->employee_signed_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeLog(StaffWeeklyTimeLog $log, Staff $viewer): array
    {
        $owner = (int) $log->staff_id === (int) $viewer->id;
        $departments = $this->timeLogs->departmentsForStaff($log->staff);

        return [
            'id' => $log->id,
            'log_code' => $log->log_code,
            'week_ref' => $log->week_ref,
            'log_year' => $log->log_year,
            'log_month' => $log->log_month,
            'week_number' => $log->week_number,
            'period_start' => $log->period_start?->format('Y-m-d'),
            'period_end' => $log->period_end?->format('Y-m-d'),
            'status' => $log->status,
            'total_hours' => $log->total_hours !== null ? (float) $log->total_hours : null,
            'total_units' => $log->total_units !== null ? (float) $log->total_units : null,
            'hr_notes' => $log->hr_notes,
            'employee_signed_at' => $log->employee_signed_at?->toIso8601String(),
            'manager_signed_at' => $log->manager_signed_at?->toIso8601String(),
            'editable' => $owner && $log->isEditableByEmployee(),
            'can_submit' => $owner
                && ! $log->manager_signed_at
                && in_array($log->status, [
                    StaffWeeklyTimeLog::STATUS_DRAFT,
                    StaffWeeklyTimeLog::STATUS_RETURNED,
                ], true),
            'can_self_endorse' => $owner && $this->timeLogs->canSelfEndorse($viewer),
            'staff_name' => $log->staff?->fullName(),
            'employee_number' => $log->staff?->employee_number,
            'departments' => $departments->map(fn ($d) => [
                'id' => $d->id,
                'code' => $d->dept_code,
                'name' => $d->dept_name,
            ])->values(),
            'days' => $log->days->map(function ($day) {
                $workDate = $day->work_date;

                return [
                    'id' => $day->id,
                    'work_date' => $workDate?->format('Y-m-d'),
                    'day_label' => $day->day_label,
                    'in_month' => (bool) $day->in_month,
                    'is_weekend' => $workDate ? $workDate->isWeekend() : false,
                    'time_in' => $day->time_in ? substr((string) $day->time_in, 0, 5) : null,
                    'time_out' => $day->time_out ? substr((string) $day->time_out, 0, 5) : null,
                    'tasks_accomplished' => $day->tasks_accomplished,
                    'initials' => $day->initials,
                    'department_ids' => is_array($day->department_ids) ? $day->department_ids : [],
                    'total_hours' => $day->total_hours !== null ? (float) $day->total_hours : null,
                    'total_units' => $day->total_units !== null ? (float) $day->total_units : null,
                ];
            })->values(),
        ];
    }
}
