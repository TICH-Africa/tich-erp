<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AttendanceSession;
use App\Models\LessonPlan;
use App\Models\UnitAllocation;
use App\Services\AttendanceVerificationService;
use App\Services\ContinuousAssessmentService;
use App\Services\StaffPortalDashboardService;
use App\Services\StaffPortalNavigationService;
use App\Services\StaffPortalService;
use App\Services\StaffTeachingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffPortalDashboardController extends Controller
{
    public function __construct(
        protected StaffPortalService $portalService,
        protected StaffPortalNavigationService $navigation,
        protected StaffPortalDashboardService $dashboard,
        protected StaffTeachingService $teaching,
        protected ContinuousAssessmentService $assessments,
    ) {}

    public function __invoke(Request $request): View
    {
        $staff = $this->portalService->staffForUser($request->user());
        abort_if(! $staff, 404);

        $section = $this->navigation->resolveSection($request);
        $portalData = $this->dashboard->forStaff($staff);

        $attendanceSession = null;
        $rostersByAllocation = [];
        if ($request->integer('attendance_session')) {
            $attendanceSession = AttendanceSession::query()
                ->with(['records.student.applicant', 'allocation.unit', 'allocation.semester', 'allocation.staff'])
                ->find($request->integer('attendance_session'));
        }

        $gradingTerminal = null;
        if ($section === 'grading') {
            $allocationId = $request->integer('allocation');
            if ($allocationId) {
                $allocation = UnitAllocation::query()
                    ->with(['unit', 'semester', 'campus'])
                    ->where('staff_id', $staff->id)
                    ->find($allocationId);
                if ($allocation) {
                    $gradingTerminal = $this->assessments->terminalData($allocation, $staff);
                }
            }
        }

        return view('staff.dashboard', [
            'staff' => $staff,
            'portalData' => $portalData,
            'section' => $section,
            'sections' => $this->navigation->sections(),
            'sidebarNavigation' => $this->navigation->sidebarNavigation(),
            'portalTitle' => ($this->navigation->sections()[$section] ?? 'Overview').' - Staff portal',
            'attendanceSession' => $attendanceSession,
            'attendanceRiskMatrix' => AttendanceVerificationService::riskMatrix(),
            'gradingTerminal' => $gradingTerminal,
            'rostersByAllocation' => $rostersByAllocation,
        ]);
    }
}
