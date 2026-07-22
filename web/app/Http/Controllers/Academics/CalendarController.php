<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Semester;
use App\Services\AcademicCalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function __construct(protected AcademicCalendarService $calendar) {}

    public function index(): View
    {
        return view('academics.calendar.index', [
            'years' => $this->calendar->listYears(),
            'defaultTrimesters' => config('tich-academics.default_trimester_count'),
            'intakeMonths' => config('tich-academics.default_intake_months'),
        ]);
    }

    public function storeYear(Request $request): RedirectResponse
    {
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

    public function updateSemester(Request $request, Semester $semester): RedirectResponse
    {
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
