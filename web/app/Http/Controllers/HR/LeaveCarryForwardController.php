<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\LeaveCarryForwardRequest;
use App\Services\EmployeePortalService;
use App\Services\LeaveCarryForwardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveCarryForwardController extends Controller
{
    public function __construct(
        protected LeaveCarryForwardService $service,
        protected EmployeePortalService $employeePortal,
    ) {}

    public function index(Request $request): View
    {
        $filter = $request->get('status', 'pending');

        $query = LeaveCarryForwardRequest::query()
            ->with(['staff', 'leaveType', 'reviewer'])
            ->orderByDesc('created_at');

        if ($filter !== 'all') {
            $query->where('status', $filter);
        }

        $requests = $query->paginate(25);

        return view('hr.leave.carry-forward', [
            'requests' => $requests,
            'filter' => $filter,
        ]);
    }

    public function approve(Request $request, LeaveCarryForwardRequest $carryForwardRequest): RedirectResponse
    {
        $staff = $this->employeePortal->staffForUser($request->user());
        abort_unless($staff, 403);

        $validated = $request->validate([
            'days_approved' => ['required', 'numeric', 'min:0.5', 'max:10'],
            'review_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->service->approve($staff, $carryForwardRequest, $validated);

        return redirect()
            ->route('hr.leave.carry-forward.index')
            ->with('success', 'Carry-forward request approved.');
    }

    public function reject(Request $request, LeaveCarryForwardRequest $carryForwardRequest): RedirectResponse
    {
        $staff = $this->employeePortal->staffForUser($request->user());
        abort_unless($staff, 403);

        $validated = $request->validate([
            'review_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->service->reject($staff, $carryForwardRequest, $validated['review_notes'] ?? null);

        return redirect()
            ->route('hr.leave.carry-forward.index')
            ->with('success', 'Carry-forward request rejected.');
    }
}
