<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Services\EmployeePortalService;
use App\Services\LeaveRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveRequestController extends Controller
{
    public function __construct(
        protected LeaveRequestService $leaveRequests,
        protected EmployeePortalService $employeePortal,
    ) {}

    public function index(Request $request): View
    {
        return view('hr.leave.index', [
            'requests' => $this->leaveRequests->hrInbox(
                $request->input('status'),
                $request->input('search'),
            ),
            'pendingCount' => $this->leaveRequests->pendingHrCount(),
        ]);
    }

    public function show(LeaveRequest $leaveRequest): View
    {
        $leaveRequest->load(['staff.department', 'staff.lineManager', 'leaveType', 'hrApprovedBy']);

        return view('hr.leave.show', [
            'leaveRequest' => $leaveRequest,
            'leaveBalances' => $this->employeePortal->leaveBalancesFor($leaveRequest->staff),
        ]);
    }

    public function approve(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('hr.manage_leave'), 403);

        $data = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'handover_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $hrStaff = $this->employeePortal->staffForUser($request->user());
        abort_unless($hrStaff, 403, 'Your user account is not linked to an employee profile.');

        if (! in_array($leaveRequest->overall_status, ['pending_hr', 'returned'], true)) {
            return back()->withErrors(['leave' => 'This request is no longer pending review.']);
        }

        $this->leaveRequests->approve($leaveRequest, $hrStaff, $data);

        return redirect()
            ->route('hr.leave.show', $leaveRequest)
            ->with('success', 'Leave request approved.');
    }

    public function reject(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('hr.manage_leave'), 403);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $hrStaff = $this->employeePortal->staffForUser($request->user());
        abort_unless($hrStaff, 403);

        if (! in_array($leaveRequest->overall_status, ['pending_hr', 'returned'], true)) {
            return back()->withErrors(['leave' => 'This request is no longer pending review.']);
        }

        $this->leaveRequests->reject($leaveRequest, $hrStaff, $data['reason']);

        return redirect()
            ->route('hr.leave.index')
            ->with('success', 'Leave request rejected.');
    }

    public function returnForChanges(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('hr.manage_leave'), 403);

        $data = $request->validate([
            'notes' => ['required', 'string', 'max:2000'],
        ]);

        $hrStaff = $this->employeePortal->staffForUser($request->user());
        abort_unless($hrStaff, 403);

        if ($leaveRequest->overall_status !== 'pending_hr') {
            return back()->withErrors(['leave' => 'Only pending requests can be returned for changes.']);
        }

        $this->leaveRequests->returnForChanges($leaveRequest, $hrStaff, $data['notes']);

        return redirect()
            ->route('hr.leave.index')
            ->with('success', 'Leave request returned to employee for changes.');
    }
}
