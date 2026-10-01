<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\WorkFromHomeRequest;
use App\Services\EmployeePortalService;
use App\Services\WorkFromHomeRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeWfhController extends Controller
{
    public function __construct(
        protected EmployeePortalService $employeePortal,
        protected WorkFromHomeRequestService $wfh,
    ) {}

    public function index(Request $request): View
    {
        $staff = $this->staff($request);
        $entitlement = $this->wfh->entitlementSnapshot($staff);

        $requests = WorkFromHomeRequest::query()
            ->where('staff_id', $staff->id)
            ->orderByDesc('work_date')
            ->orderByDesc('id')
            ->paginate(15);

        return view('employee.wfh.index', [
            'portalTitle' => 'Work from home',
            'staff' => $staff,
            'requests' => $requests,
            'entitlement' => $entitlement,
        ]);
    }

    public function create(Request $request): View
    {
        $staff = $this->staff($request);
        $staff->load(['department', 'lineManager']);
        $entitlement = $this->wfh->entitlementSnapshot($staff);

        return view('employee.wfh.create', [
            'portalTitle' => 'Apply for work from home',
            'staff' => $staff,
            'entitlement' => $entitlement,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $staff = $this->staff($request);

        $validated = $request->validate([
            'work_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'total_hours' => ['nullable', 'numeric', 'min:0.5', 'max:24'],
            'period_start' => ['nullable', 'date'],
            'period_end' => ['nullable', 'date', 'after_or_equal:period_start'],
            'remote_tasks' => ['required', 'array', 'min:1', 'max:5'],
            'remote_tasks.*' => ['nullable', 'string', 'max:500'],
        ]);

        $tasks = array_values(array_filter(
            array_map(static fn ($t) => trim((string) $t), $validated['remote_tasks'] ?? []),
            static fn ($t) => $t !== ''
        ));

        if ($tasks === []) {
            return back()->withErrors(['remote_tasks' => 'List at least one task to accomplish while working remotely.'])->withInput();
        }

        $validated['remote_tasks'] = $tasks;

        try {
            $wfhRequest = $this->wfh->submit($staff, $request->user(), $validated);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['work_date' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('employee.wfh.show', $wfhRequest)
            ->with('success', "Request {$wfhRequest->request_code} submitted to HR.");
    }

    public function show(Request $request, WorkFromHomeRequest $wfh): View
    {
        $staff = $this->staff($request);
        abort_if($wfh->staff_id !== $staff->id, 403);

        $wfh->load(['supervisor', 'hrReviewer']);

        return view('employee.wfh.show', [
            'portalTitle' => $wfh->request_code,
            'staff' => $staff,
            'wfh' => $wfh,
        ]);
    }

    public function cancel(Request $request, WorkFromHomeRequest $wfh): RedirectResponse
    {
        $staff = $this->staff($request);

        try {
            $this->wfh->cancel($wfh, $staff, $request->user());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['wfh' => $e->getMessage()]);
        }

        return redirect()
            ->route('employee.wfh.index')
            ->with('success', 'Work from home request cancelled.');
    }

    private function staff(Request $request): Staff
    {
        $staff = $request->attributes->get('portal_staff')
            ?? $this->employeePortal->staffForUser($request->user());

        abort_unless($staff, 403);

        return $staff;
    }
}
