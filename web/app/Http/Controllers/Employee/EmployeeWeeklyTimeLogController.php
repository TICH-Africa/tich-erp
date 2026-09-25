<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\StaffWeeklyTimeLog;
use App\Services\EmployeePortalService;
use App\Services\WeeklyTimeLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeWeeklyTimeLogController extends Controller
{
    public function __construct(
        protected EmployeePortalService $employeePortal,
        protected WeeklyTimeLogService $timeLogs,
    ) {}

    public function index(Request $request): View
    {
        $staff = $this->staff($request);
        $current = $this->timeLogs->currentWeekMeta();

        return view('employee.time-logs.index', [
            'portalTitle' => 'Weekly Time Log',
            'staff' => $staff,
            'logs' => $this->timeLogs->logsForStaff($staff),
            'currentYear' => $current['year'],
            'currentMonth' => $current['month'],
            'currentWeek' => $current['week']['week_number'] ?? 1,
            'weeks' => $this->timeLogs->weeksForMonth($current['year'], $current['month']),
            'canSelfEndorse' => $this->timeLogs->canSelfEndorse($staff),
        ]);
    }

    public function create(Request $request): RedirectResponse
    {
        $staff = $this->staff($request);
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
            return back()->withErrors(['time_log' => $e->getMessage()]);
        }

        return redirect()->route('employee.time-logs.show', $log);
    }

    public function weeks(Request $request): \Illuminate\Http\JsonResponse
    {
        $year = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);

        return response()->json([
            'weeks' => $this->timeLogs->weeksForMonth($year, $month),
        ]);
    }

    public function show(Request $request, StaffWeeklyTimeLog $timeLog): View
    {
        $staff = $this->staff($request);
        abort_unless($this->timeLogs->viewerMayAccess($timeLog, $staff, $request->user()), 403);

        $timeLog = $this->timeLogs->pruneWeekendDays($timeLog->load(['days', 'staff.department', 'manager', 'hrReviewer']));

        return view('employee.time-logs.show', [
            'portalTitle' => 'Weekly Time Log',
            'staff' => $staff,
            'log' => $timeLog,
            'departments' => $this->timeLogs->departmentsForStaff($timeLog->staff),
            'editable' => (int) $timeLog->staff_id === (int) $staff->id && $timeLog->isEditableByEmployee(),
            'canEndorse' => $timeLog->status === StaffWeeklyTimeLog::STATUS_PENDING_HR
                && ! $timeLog->manager_signed_at
                && (
                    (int) $timeLog->staff?->line_manager_id === (int) $staff->id
                    || ((int) $timeLog->staff_id === (int) $staff->id && $this->timeLogs->canSelfEndorse($staff))
                ),
            'canSelfEndorse' => (int) $timeLog->staff_id === (int) $staff->id && $this->timeLogs->canSelfEndorse($staff),
            'isOwner' => (int) $timeLog->staff_id === (int) $staff->id,
            'canSubmit' => (int) $timeLog->staff_id === (int) $staff->id
                && ! $timeLog->manager_signed_at
                && in_array($timeLog->status, [
                    StaffWeeklyTimeLog::STATUS_DRAFT,
                    StaffWeeklyTimeLog::STATUS_RETURNED,
                ], true),
        ]);
    }

    public function update(Request $request, StaffWeeklyTimeLog $timeLog): RedirectResponse
    {
        $staff = $this->staff($request);
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
            return back()->withInput()->withErrors(['time_log' => $e->getMessage()]);
        }

        return back()->with('status', 'Time log saved.');
    }

    public function submit(Request $request, StaffWeeklyTimeLog $timeLog): RedirectResponse
    {
        $staff = $this->staff($request);
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
            return back()->withInput()->withErrors(['time_log' => $e->getMessage()]);
        }

        return redirect()
            ->route('employee.time-logs.show', $timeLog)
            ->with('status', 'Weekly time log submitted to HR.');
    }

    public function endorse(Request $request, StaffWeeklyTimeLog $timeLog): RedirectResponse
    {
        $staff = $this->staff($request);
        $data = $request->validate([
            'signature' => ['required', 'string', 'max:300'],
        ]);

        try {
            $this->timeLogs->managerEndorse($timeLog, $staff, $data['signature']);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['time_log' => $e->getMessage()]);
        }

        return back()->with('status', 'Time log endorsed. The form is now locked for the employee.');
    }

    private function staff(Request $request): \App\Models\Staff
    {
        $staff = $request->attributes->get('portal_staff')
            ?? $this->employeePortal->staffForUser($request->user());

        abort_unless($staff, 403);

        return $staff;
    }
}
