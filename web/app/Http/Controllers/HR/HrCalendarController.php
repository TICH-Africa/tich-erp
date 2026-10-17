<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Services\KenyaPublicHolidayService;
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
        ]);
    }
}
