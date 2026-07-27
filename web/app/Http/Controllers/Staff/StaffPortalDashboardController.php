<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AttendanceSession;
use App\Models\Department;
use App\Models\UnitAllocation;
use App\Services\AcademicsAccessService;
use App\Services\AcademicsDashboardService;
use App\Services\AttendanceVerificationService;
use App\Services\ContinuousAssessmentService;
use App\Services\RBACService;
use App\Services\StaffPortalDashboardService;
use App\Services\StaffPortalNavigationService;
use App\Services\StaffPortalService;
use App\Services\StaffTeachingService;
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
        protected RBACService $rbac,
        protected AcademicsAccessService $academicsAccess,
        protected AcademicsDashboardService $academicsService,
    ) {}

    public function __invoke(Request $request): View
    {
        $staff = $this->portalService->staffForUser($request->user());
        abort_if(! $staff, 404);

        $isHod = $this->rbac->hasPermission($request->user(), 'academics.read');
        $defaultSection = $isHod ? 'hod-dashboard' : 'overview';
        $section = $this->navigation->resolveSection($request, $isHod, $defaultSection);
        $portalData = $this->dashboard->forStaff($staff);

        $data = [
            'staff' => $staff,
            'section' => $section,
            'sections' => $this->navigation->sections($isHod),
            'sidebarNavigation' => $this->navigation->sidebarNavigation($isHod),
            'portalTitle' => ($this->navigation->sections($isHod)[$section] ?? 'Overview').' - Staff portal',
            'portalData' => $portalData,
        ];

        $attendanceSession = null;
        $rostersByAllocation = [];
        if ($request->integer('attendance_session')) {
            $attendanceSession = AttendanceSession::query()
                ->with(['records.student.applicant', 'allocation.unit', 'allocation.semester', 'allocation.staff'])
                ->find($request->integer('attendance_session'));
        }
        $data['attendanceSession'] = $attendanceSession;

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
        $data['gradingTerminal'] = $gradingTerminal;
        $data['attendanceRiskMatrix'] = AttendanceVerificationService::riskMatrix();
        $data['rostersByAllocation'] = $rostersByAllocation;

        if ($isHod) {
            $staffDepartment = $staff->department;
            if ($staffDepartment) {
                $data['department'] = $staffDepartment;
                $data['academicsStats'] = $this->academicsService->statsForLearningDepartment($request->user(), $staffDepartment);
                $data['workloadStats'] = $this->academicsService->workloadStatsForDepartment($staffDepartment->id);
                $data['learningDepartments'] = collect([$staffDepartment]);
            }
        }

        return view('staff.dashboard', $data);
    }
}
