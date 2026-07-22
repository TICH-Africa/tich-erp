<?php

namespace App\Http\Controllers\Academics;

use App\Models\Department;
use App\Models\Semester;
use App\Services\AcademicCalendarService;
use App\Services\AcademicsAccessService;
use App\Services\DepartmentDashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CalendarController extends DepartmentAcademicsController
{
    public function __construct(
        protected AcademicCalendarService $calendar,
        AcademicsAccessService $access,
        DepartmentDashboardService $departmentDashboard,
    ) {
        parent::__construct($access, $departmentDashboard);
    }

    public function index(Request $request, Department $department): View
    {
        $hub = $this->authorizeHub($request, $department);

        return view('academics.calendar.index', [
            'department' => $hub,
            'years' => $this->calendar->listYears(),
            'defaultTrimesters' => config('tich-academics.default_trimester_count'),
            'intakeMonths' => config('tich-academics.default_intake_months'),
        ]);
    }

    public function storeYear(Request $request, Department $department): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);

        $validated = $request->validate([
            'year_label' => ['required', 'string', 'max:20', 'unique:academic_years,year_label'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'is_current' => ['nullable', 'boolean'],
            'term_count' => ['nullable', 'integer', 'min:1', 'max:6'],
        ]);

        $this->calendar->createYear($request->user(), $validated, $request);

        return back()->with('status', 'Academic year and terms created.');
    }

    public function updateSemester(Request $request, Department $department, Semester $semester): RedirectResponse
    {
        $this->authorizeHub($request, $department);

        $validated = $request->validate([
            'semester_label' => ['required', 'string', 'max:20'],
            'semester_number' => ['required', 'integer', 'min:1', 'max:12'],
            'intake_month' => ['nullable', 'string', 'max:20'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'registration_open_date' => ['nullable', 'date'],
            'registration_close_date' => ['nullable', 'date'],
            'is_current' => ['nullable', 'boolean'],
        ]);

        $this->calendar->updateSemester($request->user(), $semester, $validated, $request);

        return back()->with('status', 'Term updated.');
    }
}
