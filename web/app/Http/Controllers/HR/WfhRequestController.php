<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\WorkFromHomeRequest;
use App\Services\StaffPortalService;
use App\Services\WorkFromHomeRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WfhRequestController extends Controller
{
    public function __construct(
        protected WorkFromHomeRequestService $wfh,
        protected StaffPortalService $staffPortal,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:pending_hr,approved,rejected,returned,cancelled,all'],
            'year' => ['nullable', 'integer', 'min:2020', 'max:2100'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        if (! isset($filters['status'])) {
            $filters['status'] = 'pending_hr';
        }

        return view('hr.wfh.index', [
            'requests' => $this->wfh->hrIndex($filters),
            'filters' => $filters,
        ]);
    }

    public function show(WorkFromHomeRequest $wfh): View
    {
        $wfh->load(['staff.department', 'staff.lineManager', 'supervisor', 'hrReviewer']);

        $entitlement = null;
        if ($wfh->staff && $wfh->work_date) {
            try {
                $entitlement = $this->wfh->entitlementFor($wfh->staff, $wfh->work_date);
                // Recompute used excluding this pending request's consumption for display clarity
            } catch (\InvalidArgumentException) {
                $entitlement = null;
            }
        }

        return view('hr.wfh.show', [
            'wfh' => $wfh,
            'entitlement' => $entitlement,
            'considerationLabels' => WorkFromHomeRequest::CONSIDERATION_LABELS,
        ]);
    }

    public function approve(Request $request, WorkFromHomeRequest $wfh): RedirectResponse
    {
        $hr = $this->hrStaff($request);
        $data = $request->validate([
            'considerations' => ['required', 'array'],
            'considerations.department_open_hours' => ['required', 'in:1,0,true,false'],
            'considerations.no_adverse_effect' => ['required', 'in:1,0,true,false'],
            'considerations.position_conducive' => ['required', 'in:1,0,true,false'],
            'considerations.performance_plan' => ['required', 'in:1,0,true,false'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $this->wfh->approve($wfh, $hr, $data['considerations'], $data['notes'] ?? null);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['wfh' => $e->getMessage()]);
        }

        return redirect()->route('hr.wfh.index')->with('status', 'Work from home request approved.');
    }

    public function reject(Request $request, WorkFromHomeRequest $wfh): RedirectResponse
    {
        $hr = $this->hrStaff($request);
        $data = $request->validate([
            'notes' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $this->wfh->reject($wfh, $hr, $data['notes']);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['wfh' => $e->getMessage()]);
        }

        return redirect()->route('hr.wfh.index')->with('status', 'Work from home request rejected.');
    }

    public function returnRequest(Request $request, WorkFromHomeRequest $wfh): RedirectResponse
    {
        $hr = $this->hrStaff($request);
        $data = $request->validate([
            'notes' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $this->wfh->returnToEmployee($wfh, $hr, $data['notes']);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['wfh' => $e->getMessage()]);
        }

        return redirect()->route('hr.wfh.index')->with('status', 'Work from home request returned to the employee.');
    }

    private function hrStaff(Request $request): \App\Models\Staff
    {
        $staff = $this->staffPortal->staffForUser($request->user());
        abort_unless($staff, 403, 'HR staff profile required.');

        return $staff;
    }
}
