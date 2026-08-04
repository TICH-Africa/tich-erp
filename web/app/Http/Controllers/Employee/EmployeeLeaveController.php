<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Services\EmployeePortalService;
use App\Services\LeaveRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeLeaveController extends Controller
{
    public function __construct(
        protected EmployeePortalService $employeePortal,
        protected LeaveRequestService $leaveRequests,
    ) {}

    public function index(Request $request): View
    {
        $staff = $this->staff($request);
        $editRequest = null;

        if ($request->filled('edit')) {
            $editRequest = LeaveRequest::query()
                ->where('staff_id', $staff->id)
                ->whereKey($request->integer('edit'))
                ->firstOrFail();

            abort_unless($editRequest->isEditableByEmployee(), 403);
        }

        return view('employee.leave.index', [
            'portalTitle' => 'Leave requests',
            'staff' => $staff,
            'leaveTypes' => $this->leaveRequests->activeLeaveTypes(),
            'leaveRequests' => $this->leaveRequests->requestsForStaff($staff),
            'leaveBalances' => $this->employeePortal->leaveBalancesFor($staff),
            'editRequest' => $editRequest,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $staff = $this->staff($request);
        $data = $this->validatedLeave($request, requireFutureStart: true);

        try {
            $this->leaveRequests->submit($staff, $data, $request->file('medical_certificate'));
        } catch (\InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['end_date' => $exception->getMessage()]);
        }

        return redirect()
            ->route('employee.leave.index')
            ->with('success', 'Your leave request has been submitted to HR for review.');
    }

    public function update(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $staff = $this->staff($request);
        abort_unless($leaveRequest->staff_id === $staff->id, 403);

        $data = $this->validatedLeave($request, requireFutureStart: false);

        try {
            $this->leaveRequests->updateByEmployee($leaveRequest, $staff, $data, $request->file('medical_certificate'));
        } catch (\InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['end_date' => $exception->getMessage()]);
        }

        return redirect()
            ->route('employee.leave.index')
            ->with('success', 'Your leave request has been updated and sent back to HR.');
    }

    public function cancel(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $staff = $this->staff($request);

        $this->leaveRequests->cancelByEmployee(
            $leaveRequest,
            $staff,
            $request->input('cancellation_reason'),
        );

        return redirect()
            ->route('employee.leave.index')
            ->with('success', 'Leave request cancelled.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedLeave(Request $request, bool $requireFutureStart = true): array
    {
        $startRules = ['required', 'date'];
        if ($requireFutureStart) {
            $startRules[] = 'after_or_equal:today';
        }

        return $request->validate([
            'leave_type_id' => ['required', 'integer', 'exists:leave_types,id'],
            'start_date' => $startRules,
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:2000'],
            'handover_notes' => ['nullable', 'string', 'max:2000'],
            'is_emergency' => ['nullable', 'boolean'],
            'medical_certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);
    }

    private function staff(Request $request): \App\Models\Staff
    {
        $staff = $request->attributes->get('portal_staff')
            ?? $this->employeePortal->staffForUser($request->user());

        abort_unless($staff, 403);

        return $staff;
    }
}
