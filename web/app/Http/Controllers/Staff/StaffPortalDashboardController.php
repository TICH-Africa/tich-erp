<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AttendanceSession;
use App\Models\LessonPlan;
use App\Models\UnitAllocation;
use App\Services\AttendanceSessionGenerationService;
use App\Services\AttendanceVerificationService;
use App\Services\ContinuousAssessmentService;
use App\Services\ObjectiveAutoGradingService;
use App\Services\StaffPortalDashboardService;
use App\Services\StaffPortalNavigationService;
use App\Services\StaffPortalService;
use App\Services\StaffExamMarksService;
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
        protected AttendanceSessionGenerationService $attendanceGeneration,
        protected StaffTeachingService $teaching,
        protected ContinuousAssessmentService $assessments,
        protected ObjectiveAutoGradingService $objectiveGrading,
        protected StaffExamMarksService $examMarks,
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
        ]);
    }
}
