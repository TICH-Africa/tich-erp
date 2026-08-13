<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Services\EmployeePortalService;
use App\Services\StaffAttendanceService;
use App\Services\StaffClockInLocationService;
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
            'campusGeofence' => app(\App\Services\StaffClockInLocationService::class)->geofenceForStaff($staff),
            'requireLocation' => (bool) config('hr-attendance.require_location', true),
            'maxLocationAccuracy' => (int) config('hr-attendance.max_accuracy_meters', 2000),
            'promptLocation' => (bool) session('prompt_location'),
        ]);
    }

    public function clockIn(Request $request): RedirectResponse
    {
        $staff = $this->staff($request);
        $data = $request->validate([
            'is_off_campus' => ['nullable', 'boolean'],
            'field_project_name' => ['nullable', 'string', 'max:300'],
            'location_lat_long' => ['nullable', 'string', 'max:100'],
            'clock_in_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'clock_in_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'clock_in_accuracy_m' => ['nullable', 'numeric', 'min:0', 'max:50000'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $data['is_off_campus'] = $request->boolean('is_off_campus');

        try {
            $this->attendance->clockIn($staff, $data);
        } catch (\RuntimeException $exception) {
            if ($exception->getMessage() === StaffClockInLocationService::LOCATION_REQUIRED_MESSAGE) {
                return back()->withInput()->with('prompt_location', true);
            }

            return back()->withInput()->withErrors(['attendance' => $exception->getMessage()]);
        }

        $today = $this->attendance->todayRecord($staff);
        $message = $today?->needsClockInLocationVerification()
            ? 'Location still not verified. Allow GPS access and try again.'
            : ($today?->hasVerifiedClockInLocation()
                ? 'Clock-in location verified successfully.'
                : 'Clocked in successfully.');

        return back()->with('success', $message);
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
