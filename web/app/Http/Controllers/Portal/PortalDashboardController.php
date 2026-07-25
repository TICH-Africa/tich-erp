<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\StudentPortalDashboardService;
use App\Services\StudentPortalNavigationService;
use App\Services\StudentPortalService;
use App\Services\StudentRecordService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalDashboardController extends Controller
{
    public function __construct(
        protected StudentPortalService $portalService,
        protected StudentRecordService $studentRecords,
        protected StudentPortalNavigationService $navigation,
        protected StudentPortalDashboardService $dashboard,
    ) {}

    public function __invoke(Request $request): View
    {
        $student = $this->portalService->studentForUser($request->user());

        abort_if(! $student, 404);

        $biodata = $this->studentRecords->biodata360($student);
        $section = $this->navigation->resolveSection($request);
        $portalData = $this->dashboard->forStudent($student, $biodata);

        return view('portal.dashboard', [
            'student' => $student,
            'biodata' => $biodata,
            'portalData' => $portalData,
            'section' => $section,
            'sections' => $this->navigation->sections(),
            'sidebarNavigation' => $this->navigation->sidebarNavigation($student),
            'modules' => $this->navigation->modules(),
            'portalTitle' => ($this->navigation->sections()[$section] ?? 'Overview').' - Student portal',
        ]);
    }
}
