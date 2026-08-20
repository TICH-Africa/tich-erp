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
        $year = $request->filled('year')
            ? $request->integer('year')
            : $this->p9Service->defaultYear();

        $staff = $this->p9Service->staffWithPayrollData($year);

        return view('hr.p9-forms.index', [
            'staff' => $staff,
            'year' => $year,
            'availableYears' => $this->p9Service->availableYears(),
        ]);
    }

    public function show(Request $request, Staff $staff): View
    {
        $year = $request->filled('year')
            ? $request->integer('year')
            : $this->p9Service->defaultYear();

        $monthlyData = $this->p9Service->getMonthlyData($staff, $year);

        return view('hr.p9-forms.show', [
            'employee' => $staff,
            'year' => $year,
            'monthlyData' => $monthlyData,
        ]);
    }

    public function download(Request $request, Staff $staff): StreamedResponse
    {
        $year = $request->filled('year')
            ? $request->integer('year')
            : $this->p9Service->defaultYear();

        return $this->p9Service->download($staff, $year);
    }
}
