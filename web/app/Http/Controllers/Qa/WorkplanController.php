<?php

namespace App\Http\Controllers\Qa;

use App\Http\Controllers\Controller;
use App\Models\AcademicWorkplan;
use App\Services\AcademicWorkplanService;
use App\Services\StaffPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkplanController extends Controller
{
    public function __construct(
        protected AcademicWorkplanService $workplans,
        protected StaffPortalService $staffPortal,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($this->workplans->isQaOfficer($request->user()), 403);

        $status = $request->string('status')->toString() ?: null;

        if ($status === 'all') {
            $workplans = AcademicWorkplan::query()
                ->with(['department', 'semester.academicYear', 'preparedByStaff', 'registrarStaff', 'qaStaff'])
                ->whereIn('status', [
                    AcademicWorkplan::STATUS_PENDING,
                    AcademicWorkplan::STATUS_APPROVED,
                    AcademicWorkplan::STATUS_REJECTED,
                    AcademicWorkplan::STATUS_CHANGES_REQUESTED,
                ])
                ->orderByDesc('id')
                ->get();
        } else {
            $workplans = $this->workplans->inboxForQa($status ?: null);
        }

        return view('qa.workplans.index', [
            'workplans' => $workplans,
            'selectedStatus' => $status,
        ]);
    }

    public function show(Request $request, AcademicWorkplan $workplan): View
    {
        abort_unless($this->workplans->isQaOfficer($request->user()), 403);

        $workplan->load(['activities', 'department', 'semester.academicYear', 'preparedByStaff', 'registrarStaff', 'qaStaff']);
        $staff = $this->staffPortal->staffForUser($request->user());

        return view('qa.workplans.show', [
            'workplan' => $workplan,
            'summary' => $this->workplans->approvalSummary($workplan),
            'canAct' => $staff
                && $workplan->status === AcademicWorkplan::STATUS_PENDING
                && $workplan->qa_status === AcademicWorkplan::REVIEW_PENDING,
        ]);
    }

    public function approve(Request $request, AcademicWorkplan $workplan): RedirectResponse
    {
        $staff = $this->requireQaStaff($request);
        $validated = $request->validate(['comments' => ['nullable', 'string', 'max:2000']]);
        $this->workplans->approveAsQa($staff, $workplan, $validated['comments'] ?? null);

        return back()->with('status', 'Workplan approved by QA.');
    }

    public function reject(Request $request, AcademicWorkplan $workplan): RedirectResponse
    {
        $staff = $this->requireQaStaff($request);
        $validated = $request->validate(['comments' => ['required', 'string', 'max:2000']]);
        $this->workplans->rejectAsQa($staff, $workplan, $validated['comments']);

        return back()->with('status', 'Workplan rejected by QA.');
    }

    public function requestChanges(Request $request, AcademicWorkplan $workplan): RedirectResponse
    {
        $staff = $this->requireQaStaff($request);
        $validated = $request->validate(['comments' => ['required', 'string', 'max:2000']]);
        $this->workplans->requestChangesAsQa($staff, $workplan, $validated['comments']);

        return back()->with('status', 'Changes requested on workplan.');
    }

    private function requireQaStaff(Request $request): \App\Models\Staff
    {
        abort_unless($this->workplans->isQaOfficer($request->user()), 403);
        $staff = $this->staffPortal->staffForUser($request->user());
        abort_unless($staff, 403, 'Your account is not linked to a staff record.');

        return $staff;
    }
}
