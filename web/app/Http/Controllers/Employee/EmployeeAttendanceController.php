<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Services\EmployeePortalService;
use App\Services\StaffAttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeAttendanceController extends Controller
{
    public function __construct(
        protected EmployeePortalService $employeePortal,
        protected StaffAttendanceService $attendance,
    ) {}

    public function index(Request $request): View
    {
        $staff = $this->staff($request);

        return view('employee.attendance.index', [
            'portalTitle' => 'Clock in / out',
            'staff' => $staff,
            'todayRecord' => $this->attendance->todayRecord($staff),
            'recentRecords' => $this->attendance->recentRecords($staff),
        ]);
    }

    public function clockIn(Request $request): RedirectResponse
    {
        $staff = $this->staff($request);
        $data = $request->validate([
            'is_off_campus' => ['nullable', 'boolean'],
            'field_project_name' => ['nullable', 'string', 'max:300'],
            'location_lat_long' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->attendance->clockIn($staff, $data);
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['attendance' => $exception->getMessage()]);
        }

        return back()->with('success', 'Clocked in successfully.');
    }

    public function clockOut(Request $request): RedirectResponse
    {
        $staff = $this->staff($request);

        try {
            $this->attendance->clockOut($staff, $request->input('notes'));
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['attendance' => $exception->getMessage()]);
        }

        return back()->with('success', 'Clocked out successfully.');
    }

    private function staff(Request $request): \App\Models\Staff
    {
        $staff = $request->attributes->get('portal_staff')
            ?? $this->employeePortal->staffForUser($request->user());

        abort_unless($staff, 403);

        return $staff;
    }
}
