<?php

namespace App\Http\Controllers\Academics;

use App\Models\AcademicWorkplan;
use App\Models\Department;
use App\Services\AcademicWorkplanService;
use App\Services\StaffPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkplanController extends DepartmentAcademicsController
{
    public function __construct(
        protected AcademicWorkplanService $workplans,
        protected StaffPortalService $staffPortal,
        \App\Services\AcademicsAccessService $access,
        \App\Services\DepartmentDashboardService $departmentDashboard,
    ) {
        parent::__construct($access, $departmentDashboard);
    }

    public function index(Request $request, Department $department): View
    {
        $hub = $this->authorizeRegistrar($request, $department);
        $status = $request->string('status')->toString() ?: null;

        $workplans = $status === 'all'
            ? AcademicWorkplan::query()
                ->with(['department', 'semester.academicYear', 'preparedByStaff', 'registrarStaff', 'qaStaff'])
                ->orderByDesc('id')
                ->get()
            : $this->workplans->inboxForRegistrar($status ?: null);

        return view('academics.workplans.index', [
            'department' => $hub,
            'workplans' => $workplans,
            'selectedStatus' => $status,
        ]);
    }

    public function show(Request $request, Department $department, AcademicWorkplan $workplan): View
    {
        $hub = $this->authorizeRegistrar($request, $department);
        $workplan->load(['activities', 'department', 'semester.academicYear', 'preparedByStaff', 'registrarStaff', 'qaStaff']);
        $staff = $this->staffPortal->staffForUser($request->user());

        return view('academics.workplans.show', [
            'department' => $hub,
            'workplan' => $workplan,
            'summary' => $this->workplans->approvalSummary($workplan),
            'canAct' => $staff
                && $workplan->status === AcademicWorkplan::STATUS_PENDING
                && $workplan->registrar_status === AcademicWorkplan::REVIEW_PENDING,
            'hubParams' => \App\Support\AcademicsRouteParams::fromRequest($request),
        ]);
    }

    public function approve(Request $request, Department $department, AcademicWorkplan $workplan): RedirectResponse
    {
        $this->authorizeRegistrar($request, $department);
        $staff = $this->requireRegistrarStaff($request);
        $validated = $request->validate(['comments' => ['nullable', 'string', 'max:2000']]);
        $this->workplans->approveAsRegistrar($staff, $workplan, $validated['comments'] ?? null);

        return back()->with('status', 'Workplan approved by Academic Registrar.');
    }

    public function reject(Request $request, Department $department, AcademicWorkplan $workplan): RedirectResponse
    {
        $this->authorizeRegistrar($request, $department);
        $staff = $this->requireRegistrarStaff($request);
        $validated = $request->validate(['comments' => ['required', 'string', 'max:2000']]);
        $this->workplans->rejectAsRegistrar($staff, $workplan, $validated['comments']);

        return back()->with('status', 'Workplan rejected by Academic Registrar.');
    }

    public function requestChanges(Request $request, Department $department, AcademicWorkplan $workplan): RedirectResponse
    {
        $this->authorizeRegistrar($request, $department);
        $staff = $this->requireRegistrarStaff($request);
        $validated = $request->validate(['comments' => ['required', 'string', 'max:2000']]);
        $this->workplans->requestChangesAsRegistrar($staff, $workplan, $validated['comments']);

        return back()->with('status', 'Changes requested on workplan.');
    }

    protected function authorizeRegistrar(Request $request, Department $department): Department
    {
        $hub = $this->authorizeHub($request, $department);
        abort_unless(
            $this->workplans->isRegistrar($request->user()),
            403,
            'Only the Academic Registrar can review semester workplans.'
        );

        return $hub;
    }

    private function requireRegistrarStaff(Request $request): \App\Models\Staff
    {
        $staff = $this->staffPortal->staffForUser($request->user());
        abort_unless($staff, 403, 'Your account is not linked to a staff record.');

        return $staff;
    }
}
