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
            'canVerifyHod' => $request->user()->hasAnyRole(['HOD', 'Dean', 'Super Admin']),
            'canVerifyRegistrar' => $request->user()->hasAnyRole(['Academic Registrar', 'Super Admin']),
        ]);
    }

    public function verifyHod(Request $request, Department $department, AttendanceSession $session): RedirectResponse
    {
        $this->authorizeHub($request, $department);
        abort_unless($request->user()->hasAnyRole(['HOD', 'Dean', 'Super Admin']), 403);

        $staff = $this->staffPortal->staffForUser($request->user());
        abort_unless($staff, 403);

        $this->verification->verifyAsHod($session, $staff);

        return back()->with('status', 'Attendance session verified by HOD.');
    }

    public function verifyRegistrar(Request $request, Department $department, AttendanceSession $session): RedirectResponse
    {
        $this->authorizeHub($request, $department);
        abort_unless($request->user()->hasAnyRole(['Academic Registrar', 'Super Admin']), 403);

        $staff = $this->staffPortal->staffForUser($request->user());
        abort_unless($staff, 403);

        $this->verification->verifyAsRegistrar($session, $staff);

        return back()->with('status', 'Attendance session verified by Academic Registrar.');
    }
}
