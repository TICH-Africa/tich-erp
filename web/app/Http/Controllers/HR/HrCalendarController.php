<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Services\KenyaPublicHolidayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HrCalendarController extends Controller
{
    public function __construct(protected KenyaPublicHolidayService $holidays) {}

    public function index(Request $request): View
    {
        $year = (int) $request->input('year', now()->year);
        $year = max(1970, min(2100, $year));

        return view('hr.calendar.index', [
            'year' => $year,
            'yearOptions' => $this->holidays->selectableYears(),
            'months' => $this->holidays->byMonthForYear($year),
            'canManageGazetteHolidays' => $request->user()?->can('hr.manage_leave') ?? false,
        ]);
    }

    public function storeGazetteHoliday(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:1970', 'max:2100'],
            'holiday_date' => ['required', 'date'],
            'holiday_name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $date = (string) $validated['holiday_date'];
        if ((int) substr($date, 0, 4) !== (int) $validated['year']) {
            return back()->withErrors(['holiday_date' => 'The date must fall in the selected calendar year.'])->withInput();
        }

        $this->holidays->upsertGazettedHoliday(
            $date,
            $validated['holiday_name'],
            $validated['description'] ?? null,
        );

        return redirect()
            ->route('hr.calendar.index', ['year' => $validated['year']])
            ->with('success', 'Gazetted public holiday saved.');
    }

    public function destroyGazetteHoliday(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:1970', 'max:2100'],
            'holiday_date' => ['required', 'date'],
        ]);

        $deleted = $this->holidays->deleteGazettedHoliday($validated['holiday_date']);

        return redirect()
            ->route('hr.calendar.index', ['year' => $validated['year']])
            ->with($deleted ? 'success' : 'error', $deleted
                ? 'Gazetted public holiday removed.'
                : 'Only gazetted holidays added by HR can be removed from here.');
    }
}
