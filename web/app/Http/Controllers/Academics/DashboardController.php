<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Services\AcademicsAccessService;
use App\Services\AcademicsDashboardService;
use App\Services\AcademicsIntegrationRegistry;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected AcademicsDashboardService $dashboard,
        protected AcademicsAccessService $access,
        protected AcademicsIntegrationRegistry $integrations,
    ) {}

    public function __invoke(Request $request): View
    {
        return view('academics.dashboard', [
            'stats' => $this->dashboard->stats($request->user()),
            'canApproveRegistry' => $this->access->canApproveRegistry($request->user()),
            'canApproveCeo' => $this->access->canApproveCeo($request->user()),
            'integrationHooks' => config('tich-academics.integration_hooks'),
        ]);
    }
}
