<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AttendanceSession;
use App\Models\LessonPlan;
use App\Models\Semester;
use App\Models\Staff;
use App\Models\UnitAllocation;
use App\Services\AttendanceSessionGenerationService;
use App\Services\AttendanceVerificationService;
use App\Services\ContinuousAssessmentService;
use App\Services\DepartmentPerformanceService;
use App\Services\LessonPlanApprovalService;
use App\Services\ObjectiveAutoGradingService;
use App\Services\StaffPortalDashboardService;
use App\Services\StaffPortalNavigationService;
use App\Services\StaffPortalService;
use App\Services\StaffExamMarksService;
use App\Services\StaffTeachingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StaffPortalDashboardController extends Controller
{
    public function __construct(
        protected StaffPortalService $portalService,
        protected StaffPortalNavigationService $navigation,
        protected StaffPortalDashboardService $dashboard,
        protected AttendanceSessionGenerationService $attendanceGeneration,
        protected StaffTeachingService $teaching,
        protected ContinuousAssessmentService $assessments,
        protected ObjectiveAutoGradingService $objectiveGrading,
        protected StaffExamMarksService $examMarks,
        protected LessonPlanApprovalService $lessonPlanApprovals,
        protected DepartmentPerformanceService $performance,
    ) {}

    public function __invoke(Request $request): View
    {
        $staff = $this->portalService->staffForUser($request->user());
        abort_if(! $staff, 404);

        $section = $this->navigation->resolveSection($request);
        $portalData = $this->dashboard->forStaff($staff);
        $attendanceSync = null;
        $upcomingAttendanceSessions = collect();
        $attendanceStep = 1;

        if ($section === 'attendance') {
            $attendanceSync = $this->attendanceGeneration->syncForStaff($staff);
            $portalData = $this->dashboard->forStaff($staff);
            $upcomingAttendanceSessions = $this->dashboard->upcomingAttendanceSessions($staff);
        }

        $attendanceSession = null;
        $rostersByAllocation = [];
        $sessionId = $request->integer('attendance_session');

        if ($section === 'attendance' && ! $sessionId && $upcomingAttendanceSessions->isNotEmpty()) {
            $sessionId = (int) ($upcomingAttendanceSessions->firstWhere(fn ($s) => $s->session_date?->isToday())
                ?? $upcomingAttendanceSessions->first())->id;
        }

        if ($sessionId) {
            $attendanceSession = AttendanceSession::query()
                ->with([
                    'records.student.applicant',
                    'allocation.unit',
                    'allocation.semester.academicYear',
                    'allocation.staff',
                    'timetableSession.timetable.curriculumVersion',
                    'timetableSession.timetable.program',
                ])
                ->whereHas('allocation', fn ($query) => $query->where('staff_id', $staff->id))
                ->find($sessionId);
        }

        if ($attendanceSession) {
            $attendanceStep = 1;
            if ($attendanceSession->records->isNotEmpty()) {
                $attendanceStep = 2;
            }
            if ($attendanceSession->records->where('is_present', true)->isNotEmpty()) {
                $attendanceStep = 3;
            }
            if ($attendanceSession->signed_sheet_image_path) {
                $attendanceStep = 4;
            }
            if ($attendanceSession->class_photo_image_path) {
                $attendanceStep = 5;
            }
            if ($attendanceSession->is_locked) {
                $attendanceStep = 6;
            }
        }

        $gradingTerminal = null;
        $examMarksSheet = null;
        $objectiveTerminal = null;
        if ($section === 'grading') {
            $allocationId = $request->integer('allocation');
            if ($allocationId) {
                $allocation = UnitAllocation::query()
                    ->with(['unit', 'semester', 'campus'])
                    ->where('staff_id', $staff->id)
                    ->find($allocationId);
                if ($allocation) {
                    $gradingTerminal = $this->assessments->terminalData($allocation, $staff);
                    $examMarksSheet = $this->examMarks->sheet($allocation, $staff);
                    $objectiveTerminal = $this->objectiveGrading->terminalData($allocation);
                }
            }
        }

        $hodManagement = null;
        if ($section === 'hod-management' || $request->user()->hasAnyRole(['HOD', 'Dean of Students', 'Academic Registrar', 'Super Admin'])) {
            $hodManagement = [
                'lesson_plans' => $this-> hodLessonPlans($staff),
                'allocations' => $this-> hodUnitAllocations($staff),
                'attendance' => $this-> hodAttendance($staff),
                'performance' => $this-> hodPerformance($staff),
                'leave' => $this-> hodDepartmentLeave($staff),
            ];
        }

        $staffDocuments = null;
        if ($section === 'documents') {
            $staffDocuments = $staff->documents()
                ->orderByDesc('created_at')
                ->get(['id', 'document_type', 'document_name', 'original_filename', 'mime_type', 'issue_date', 'expiry_date', 'is_verified', 'created_at']);
        }

        $leaveBalances = collect();
        $leaveRequests = collect();
        $leaveTypes = collect();
        if ($section === 'leave') {
            $leaveTypes = app(\App\Services\LeaveRequestService::class)->activeLeaveTypes();
            $leaveBalances = app(\App\Services\EmployeePortalService::class)->leaveBalancesFor($staff);
            $leaveRequests = app(\App\Services\LeaveRequestService::class)->requestsForStaff($staff);
        }

        return view('staff.dashboard', [
            'staff' => $staff,
            'portalData' => $portalData,
            'section' => $section,
            'sections' => $this->navigation->sections(),
            'sidebarNavigation' => $this->navigation->sidebarNavigation(),
            'portalTitle' => ($this->navigation->sections()[$section] ?? 'Overview').' - Staff portal',
            'attendanceSession' => $attendanceSession,
            'attendanceSync' => $attendanceSync,
            'attendanceStep' => $attendanceStep,
            'upcomingAttendanceSessions' => $upcomingAttendanceSessions,
            'attendanceSessionIntake' => $attendanceSession
                ? $this->dashboard->intakeLabelForAttendanceSession($attendanceSession)
                : null,
            'attendanceRiskMatrix' => AttendanceVerificationService::riskMatrix(),
            'gradingTerminal' => $gradingTerminal,
            'examMarksSheet' => $examMarksSheet,
            'objectiveTerminal' => $objectiveTerminal,
            'rostersByAllocation' => $rostersByAllocation,
            'hodManagement' => $hodManagement,
            'staffDocuments' => $staffDocuments,
            'leaveBalances' => $leaveBalances,
            'leaveRequests' => $leaveRequests,
            'leaveTypes' => $leaveTypes,
        ]);
    }

    /**
     * @return Collection<int, object>
     */
    private function hodLessonPlans(Staff $staff): Collection
    {
        $departmentId = (int) ($staff->department_id ?? 0);

        return DB::table('lesson_plans as lp')
            ->join('unit_allocations as ua', 'ua.id', '=', 'lp.unit_allocation_id')
            ->join('units as u', 'u.id', '=', 'ua.unit_id')
            ->join('staff as st', 'st.id', '=', 'lp.prepared_by')
            ->where('st.department_id', $departmentId)
            ->whereIn('lp.status', ['submitted', 'modified'])
            ->orderByDesc('lp.updated_at')
            ->select([
                'lp.id',
                'lp.status',
                'lp.planned_date',
                'lp.contact_hours',
                'lp.hod_comments',
                'u.unit_code',
                'u.unit_name',
                DB::raw("CONCAT(COALESCE(st.first_name,''), ' ', COALESCE(st.surname,'')) as tutor_name"),
            ])
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    private function hodUnitAllocations(Staff $staff): Collection
    {
        $departmentId = (int) ($staff->department_id ?? 0);

        return UnitAllocation::query()
            ->with(['unit', 'staff'])
            ->whereHas('staff', fn ($query) => $query->where('department_id', $departmentId))
            ->where('is_active', 1)
            ->orderByDesc('semester_id')
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    private function hodAttendance(Staff $staff): Collection
    {
        $departmentId = (int) ($staff->department_id ?? 0);

        return DB::table('attendance_sessions as s')
            ->join('unit_allocations as ua', 'ua.id', '=', 's.unit_allocation_id')
            ->join('units as u', 'u.id', '=', 'ua.unit_id')
            ->join('staff as tutor', 'tutor.id', '=', 's.recorded_by')
            ->where('tutor.department_id', $departmentId)
            ->where('s.is_locked', 1)
            ->orderByDesc('s.session_date')
            ->select([
                's.id',
                's.session_number',
                's.session_date',
                's.verification_status',
                's.signed_sheet_image_path',
                's.hod_verified_at',
                's.registrar_verified_at',
                'u.unit_code',
                'u.unit_name',
                DB::raw("CONCAT(COALESCE(tutor.first_name,''), ' ', COALESCE(tutor.surname,'')) as tutor_name"),
            ])
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function hodPerformance(Staff $staff): array
    {
        $departmentId = (int) ($staff->department_id ?? 0);
        $semesterId = null;

        $semester = Semester::query()->orderByDesc('id')->first();
        if ($semester) {
            $semesterId = (int) $semester->id;
        }

        return app(DepartmentPerformanceService::class)->dashboard($departmentId, $semesterId);
    }

    /**
     * @return Collection<int, object>
     */
    private function hodDepartmentLeave(Staff $staff): Collection
    {
        $departmentId = (int) ($staff->department_id ?? 0);
        if ($departmentId <= 0) {
            return collect();
        }

        return DB::table('leave_requests as lr')
            ->join('staff as s', 's.id', '=', 'lr.staff_id')
            ->join('leave_types as lt', 'lt.id', '=', 'lr.leave_type_id')
            ->where('s.department_id', $departmentId)
            ->where('lr.is_cancelled', false)
            ->orderByDesc('lr.start_date')
            ->select([
                'lr.id',
                'lr.leave_number',
                'lr.start_date',
                'lr.end_date',
                'lr.return_date',
                'lr.days_requested',
                'lr.overall_status',
                'lr.hod_approval_status',
                'lr.hr_approval_status',
                'lr.is_completed',
                's.employee_number',
                's.job_title',
                DB::raw("CONCAT(COALESCE(s.first_name,''), ' ', COALESCE(s.surname,'')) as tutor_name"),
                'lt.leave_name as leave_type_name',
            ])
            ->get();
    }
}
