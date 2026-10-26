<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\StaffWeeklyTimeLog;
use App\Services\WeeklyTimeLogService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Finance reads the weekly time logs employees have submitted, for payroll proof only. Writing
 * them - endorsing, approving, returning - stays with the employee, their manager and HR.
 */
class TimeLogController extends Controller
{
    public function __construct(
        protected WeeklyTimeLogService $timeLogs,
    ) {}

    public function index(Request $request, Department $department): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:all,pending_hr,approved,rejected,returned'],
            'year' => ['nullable', 'integer', 'min:2020', 'max:2100'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'week_number' => ['nullable', 'integer', 'min:1', 'max:6'],
            'submitted_from' => ['nullable', 'date'],
            'submitted_to' => ['nullable', 'date', 'after_or_equal:submitted_from'],
        ]);

        // HR opens on its own pending queue; Finance wants the whole submitted book.
        if (($filters['status'] ?? '') === '') {
            $filters['status'] = 'all';
        }

        return view('finance.time-logs.index', [
            'department' => $department,
            'departmentParams' => ['department' => $department->id],
            'logs' => $this->timeLogs->hrIndex($filters),
            'filters' => $filters,
        ]);
    }

    public function show(Department $department, StaffWeeklyTimeLog $timeLog): View
    {
        abort_if($timeLog->status === StaffWeeklyTimeLog::STATUS_DRAFT, 404);

        $log = $this->timeLogs->pruneWeekendDays(
            $timeLog->load(['days', 'staff.department', 'manager', 'hrReviewer'])
        );

        return view('finance.time-logs.show', [
            'department' => $department,
            'departmentParams' => ['department' => $department->id],
            'log' => $log,
            'departments' => $this->timeLogs->departmentsForStaff($log->staff),
        ]);
    }
}
