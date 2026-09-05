<?php

namespace App\Http\Controllers\Academics;

use App\Models\AttendanceSession;
use App\Models\Department;
use App\Services\AttendanceVerificationService;
use App\Services\StaffPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceLedgerController extends DepartmentAcademicsController
{
    public function __construct(
        protected AttendanceVerificationService $verification,
        protected StaffPortalService $staffPortal,
        \App\Services\AcademicsAccessService $access,
        \App\Services\DepartmentDashboardService $departmentDashboard,
    ) {
        parent::__construct($access, $departmentDashboard);
    }

    public function index(Request $request, Department $department): View
    {
        $hub = $this->authorizeHub($request, $department);
        $learningDepartmentId = $request->integer('learning_department') ?: null;
        $departmentId = $learningDepartmentId ?: (int) ($hub->academicsScopeDepartmentIds()[0] ?? $hub->id);
        $status = $request->string('status')->toString() ?: null;

        return view('academics.attendance-ledger.index', [
            'department' => $hub,
            'learningDepartment' => $learningDepartmentId ? Department::query()->find($learningDepartmentId) : null,
            'learningDepartments' => $this->access->learningDepartmentsInScope($request->user(), $hub),
            'sessions' => $this->verification->ledgerForDepartment($departmentId, $status),
            'selectedStatus' => $status,
            'canVerifyHod' => $request->user()->hasAnyRole(['HOD', 'Dean of Students', 'Super Admin']),
            'canVerifyRegistrar' => $request->user()->hasAnyRole(['Academic Registrar', 'Super Admin']),
        ]);
    }

    public function show(Request $request, Department $department, AttendanceSession $session): View
    {
        $hub = $this->authorizeHub($request, $department);
        $this->assertSessionInHub($hub, $session);

        $sheet = $this->verification->sheetData($session);
        $session->loadMissing(['hodVerifier', 'registrarVerifier', 'rosterVerifier']);

        $presentCount = $session->records->where('is_present', true)->count();
        $totalCount = $session->records->count();

        return view('academics.attendance-ledger.show', [
            'department' => $hub,
            'learningDepartment' => $request->integer('learning_department')
                ? Department::query()->find($request->integer('learning_department'))
                : null,
            'session' => $session,
            'allocation' => $sheet['allocation'],
            'unit' => $sheet['unit'],
            'tutor' => $sheet['tutor'],
            'records' => $sheet['records'],
            'trackingId' => $sheet['tracking_id'],
            'intakeLabel' => $sheet['intake_label'],
            'presentCount' => $presentCount,
            'totalCount' => $totalCount,
            'canVerifyHod' => $request->user()->hasAnyRole(['HOD', 'Dean of Students', 'Super Admin']),
            'canVerifyRegistrar' => $request->user()->hasAnyRole(['Academic Registrar', 'Super Admin']),
        ]);
    }

    public function verifyHod(Request $request, Department $department, AttendanceSession $session): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $this->assertSessionInHub($hub, $session);
        abort_unless($request->user()->hasAnyRole(['HOD', 'Dean of Students', 'Super Admin']), 403);

        $staff = $this->staffPortal->staffForUser($request->user());
        abort_unless($staff, 403);

        $this->verification->verifyAsHod($session, $staff);

        return redirect()
            ->route('departments.academics.attendance-ledger.show', array_merge(
                \App\Support\AcademicsRouteParams::fromRequest($request),
                ['session' => $session->id]
            ))
            ->with('status', 'Attendance session verified by HOD.');
    }

    public function verifyRegistrar(Request $request, Department $department, AttendanceSession $session): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $this->assertSessionInHub($hub, $session);
        abort_unless($request->user()->hasAnyRole(['Academic Registrar', 'Super Admin']), 403);

        $staff = $this->staffPortal->staffForUser($request->user());
        abort_unless($staff, 403);

        $this->verification->verifyAsRegistrar($session, $staff);

        return redirect()
            ->route('departments.academics.attendance-ledger.show', array_merge(
                \App\Support\AcademicsRouteParams::fromRequest($request),
                ['session' => $session->id]
            ))
            ->with('status', 'Attendance session verified by Academic Registrar.');
    }

    public function verifyRoster(Request $request, Department $department, AttendanceSession $session): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $this->assertSessionInHub($hub, $session);
        abort_unless($request->user()->hasAnyRole(['HOD', 'Dean of Students', 'Academic Registrar', 'Super Admin']), 403);

        $staff = $this->staffPortal->staffForUser($request->user());
        abort_unless($staff, 403);

        $this->verification->verifyRoster($session, $staff);

        return back()->with('status', 'Roster verified. Tutors may now record attendance for this session.');
    }

    public function examEligibilityCheck(Request $request, Department $department, AttendanceSession $session): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $this->assertSessionInHub($hub, $session);
        abort_unless($request->user()->hasAnyRole(['HOD', 'Dean of Students', 'Academic Registrar', 'Super Admin']), 403);

        $staff = $this->staffPortal->staffForUser($request->user());
        abort_unless($staff, 403);

        $session = $this->verification->examEligibilityCheck($session, $staff);

        $blockedCount = isset($session->exam_blocked_students) ? $session->exam_blocked_students->count() : 0;

        return back()->with('status', $blockedCount > 0 ? "Exam eligibility check complete. {$blockedCount} student(s) blocked due to attendance." : 'Exam eligibility check complete. All students eligible.');
    }

    protected function assertSessionInHub(Department $hub, AttendanceSession $session): void
    {
        $session->loadMissing('allocation.unit');
        $unitDepartmentId = (int) ($session->allocation?->unit?->department_id ?? 0);
        $scopeIds = $this->access->scopeDepartmentIds($hub);

        abort_unless($unitDepartmentId > 0 && in_array($unitDepartmentId, $scopeIds, true), 404);
    }
}
