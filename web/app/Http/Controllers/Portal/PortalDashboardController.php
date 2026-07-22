<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\StudentPortalService;
use App\Services\StudentRecordService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalDashboardController extends Controller
{
    public function __construct(
        protected StudentPortalService $portalService,
        protected StudentRecordService $studentRecords,
    ) {}

    public function __invoke(Request $request): View
    {
        $student = $this->portalService->studentForUser($request->user());

        abort_if(! $student, 404);

        return view('portal.dashboard', [
            'student' => $student,
            'biodata' => $this->studentRecords->biodata360($student),
        ]);
    }
}
