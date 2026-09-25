<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\StaffWeeklyTimeLog;
use App\Services\StaffPortalService;
use App\Services\WeeklyTimeLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WeeklyTimeLogReviewController extends Controller
{
    public function __construct(
        protected WeeklyTimeLogService $timeLogs,
        protected StaffPortalService $staffPortal,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:pending_hr,approved,rejected,returned'],
            'year' => ['nullable', 'integer', 'min:2020', 'max:2100'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'week_number' => ['nullable', 'integer', 'min:1', 'max:6'],
            'submitted_from' => ['nullable', 'date'],
            'submitted_to' => ['nullable', 'date', 'after_or_equal:submitted_from'],
        ]);

        return view('hr.time-logs.index', [
            'logs' => $this->timeLogs->hrIndex($filters),
            'filters' => $filters,
            'weeks' => ! empty($filters['year']) && ! empty($filters['month'])
                ? $this->timeLogs->weeksForMonth((int) $filters['year'], (int) $filters['month'])
                : [],
        ]);
    }

    public function show(StaffWeeklyTimeLog $timeLog): View
    {
        $timeLog = $this->timeLogs->pruneWeekendDays(
            $timeLog->load(['days', 'staff.department', 'manager', 'hrReviewer'])
        );

        return view('hr.time-logs.show', [
            'log' => $timeLog,
            'departments' => $this->timeLogs->departmentsForStaff($timeLog->staff),
        ]);
    }

    public function approve(Request $request, StaffWeeklyTimeLog $timeLog): RedirectResponse
    {
        $hr = $this->hrStaff($request);
        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $this->timeLogs->hrApprove($timeLog, $hr, $data['notes'] ?? null);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['time_log' => $e->getMessage()]);
        }

        return redirect()->route('hr.time-logs.index')->with('status', 'Time log approved.');
    }

    public function reject(Request $request, StaffWeeklyTimeLog $timeLog): RedirectResponse
    {
        $hr = $this->hrStaff($request);
        $data = $request->validate([
            'notes' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $this->timeLogs->hrReject($timeLog, $hr, $data['notes']);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['time_log' => $e->getMessage()]);
        }

        return redirect()->route('hr.time-logs.index')->with('status', 'Time log rejected.');
    }

    public function returnLog(Request $request, StaffWeeklyTimeLog $timeLog): RedirectResponse
    {
        $hr = $this->hrStaff($request);
        $data = $request->validate([
            'notes' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $this->timeLogs->hrReturn($timeLog, $hr, $data['notes']);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['time_log' => $e->getMessage()]);
        }

        return redirect()->route('hr.time-logs.index')->with('status', 'Time log returned to the employee for revision.');
    }

    private function hrStaff(Request $request): \App\Models\Staff
    {
        $staff = $this->staffPortal->staffForUser($request->user());
        abort_unless($staff, 403, 'HR staff profile required.');

        return $staff;
    }
}
