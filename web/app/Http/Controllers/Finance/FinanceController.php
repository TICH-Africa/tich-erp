<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Services\DepartmentDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinanceController extends Controller
{
    public function __construct(protected DepartmentDashboardService $departmentDashboard)
    {
    }

    protected function departmentView(Request $request, string $view, Department $department, array $data = []): View
    {
        $sidebarNavigation = $this->departmentDashboard->sidebarNavigation($request->user(), $department);

        return view($view, array_merge([
            'department' => $department,
            'categoryLabel' => fn (Department $dept) => $this->departmentDashboard->categoryLabel($dept),
            'sidebarNavigation' => $sidebarNavigation,
        ], $data));
    }

    public function studentFinanceIndex(Request $request, Department $department): View
    {
        return $this->departmentView($request, 'finance.student-finance.index', $department);
    }

    public function studentFinanceCreate(Request $request, Department $department): View
    {
        return $this->departmentView($request, 'finance.student-finance.create', $department);
    }

    public function studentFinanceStore(Request $request, Department $department)
    {
        //
    }

    public function studentFinanceShow(Request $request, Department $department, $id): View
    {
        return $this->departmentView($request, 'finance.student-finance.show', $department);
    }

    public function arIndex(Request $request, Department $department): View
    {
        return $this->departmentView($request, 'finance.ar.index', $department);
    }

    public function arCreate(Request $request, Department $department): View
    {
        return $this->departmentView($request, 'finance.ar.create', $department);
    }

    public function arStore(Request $request, Department $department)
    {
        //
    }

    public function arShow(Request $request, Department $department, $id): View
    {
        return $this->departmentView($request, 'finance.ar.show', $department);
    }

    public function apIndex(Request $request, Department $department): View
    {
        return $this->departmentView($request, 'finance.ap.index', $department);
    }

    public function apCreate(Request $request, Department $department): View
    {
        return $this->departmentView($request, 'finance.ap.create', $department);
    }

    public function apStore(Request $request, Department $department)
    {
        //
    }

    public function apShow(Request $request, Department $department, $id): View
    {
        return $this->departmentView($request, 'finance.ap.show', $department);
    }

    public function glIndex(Request $request, Department $department): View
    {
        return $this->departmentView($request, 'finance.gl.index', $department);
    }

    public function glJournalCreate(Request $request, Department $department): View
    {
        return $this->departmentView($request, 'finance.gl.create', $department);
    }

    public function glJournalStore(Request $request, Department $department)
    {
        //
    }

    public function glShow(Request $request, Department $department, $id): View
    {
        return $this->departmentView($request, 'finance.gl.show', $department);
    }

    public function budgetingIndex(Request $request, Department $department): View
    {
        return $this->departmentView($request, 'finance.budgeting.index', $department);
    }

    public function budgetingCreate(Request $request, Department $department): View
    {
        return $this->departmentView($request, 'finance.budgeting.create', $department);
    }

    public function budgetingStore(Request $request, Department $department)
    {
        //
    }

    public function budgetingShow(Request $request, Department $department, $id): View
    {
        return $this->departmentView($request, 'finance.budgeting.show', $department);
    }

    public function projectsDonorsIndex(Request $request, Department $department): View
    {
        return $this->departmentView($request, 'finance.projects-donors.index', $department);
    }

    public function projectsDonorsCreate(Request $request, Department $department): View
    {
        return $this->departmentView($request, 'finance.projects-donors.create', $department);
    }

    public function projectsDonorsStore(Request $request, Department $department)
    {
        //
    }

    public function projectsDonorsShow(Request $request, Department $department, $id): View
    {
        return $this->departmentView($request, 'finance.projects-donors.show', $department);
    }

    public function payrollIntegrationIndex(Request $request, Department $department): View
    {
        return $this->departmentView($request, 'finance.payroll-integration.index', $department);
    }

    public function payrollIntegrationSync(Request $request, Department $department): View
    {
        return $this->departmentView($request, 'finance.payroll-integration.create', $department);
    }

    public function payrollIntegrationStore(Request $request, Department $department)
    {
        //
    }

    public function payrollIntegrationShow(Request $request, Department $department, $id): View
    {
        return $this->departmentView($request, 'finance.payroll-integration.show', $department);
    }
}
