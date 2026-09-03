<?php

namespace App\Http\Controllers\Academics;

use App\Models\Department;
use App\Services\AcademicsAccessService;
use App\Services\AcademicsDashboardService;
use App\Services\AcademicsIntegrationRegistry;
use App\Services\DepartmentDashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends DepartmentAcademicsController
{
    public function __construct(
        protected AcademicsDashboardService $dashboard,
        AcademicsAccessService $access,
        DepartmentDashboardService $departmentDashboard,
        protected AcademicsIntegrationRegistry $integrations,
    ) {
        parent::__construct($access, $departmentDashboard);
    }

    public function __invoke(Request $request, Department $department): View|RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department, allowSuggestionsOnly: true);

        if ($this->access->isSuggestionsOnly($request->user())) {
            return redirect()->route(
                'departments.academics.suggestions.index',
                \App\Support\AcademicsRouteParams::fromRequest($request)
            );
        }

        return view('academics.dashboard', [
            'department' => $hub,
            'stats' => $this->dashboard->stats($request->user(), $hub),
            'canApproveRegistry' => $this->access->canApproveRegistry($request->user()),
            'canApproveCeo' => $this->access->canApproveCeo($request->user()),
            'integrationHooks' => config('tich-academics.integration_hooks'),
            'learningDepartments' => $this->access->learningDepartmentsInScope($request->user(), $hub),
        ]);
    }
}
