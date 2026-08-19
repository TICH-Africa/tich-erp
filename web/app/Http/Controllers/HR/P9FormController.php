<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Services\P9FormService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class P9FormController extends Controller
{
    public function __construct(
        protected P9FormService $p9Service,
    ) {}

    public function index(Request $request): View
    {
        $year = $request->integer('year', now()->year - 1);
        $staff = $this->p9Service->staffWithPayrollData($year);

        $availableYears = range(now()->year, now()->year - 5);

        return view('hr.p9-forms.index', [
            'staff' => $staff,
            'year' => $year,
            'availableYears' => $availableYears,
        ]);
    }

    public function show(Request $request, Staff $staff): View
    {
        $year = $request->integer('year', now()->year - 1);
        $monthlyData = $this->p9Service->getMonthlyData($staff, $year);

        return view('hr.p9-forms.show', [
            'employee' => $staff,
            'year' => $year,
            'monthlyData' => $monthlyData,
        ]);
    }

    public function download(Request $request, Staff $staff): StreamedResponse
    {
        $year = $request->integer('year', now()->year - 1);

        return $this->p9Service->download($staff, $year);
    }
}
