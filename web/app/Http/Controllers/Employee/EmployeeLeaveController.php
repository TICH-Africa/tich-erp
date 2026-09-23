<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\LeaveCarryForwardRequest;
use App\Models\LeaveRequest;
use App\Services\EmployeePortalService;
use App\Services\Leave\LeaveCatalogService;
use App\Services\Leave\LeaveCoverageService;
use App\Services\LeaveCarryForwardService;
use App\Services\LeaveRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeLeaveController extends Controller
{
    public function __construct(
        protected EmployeePortalService $employeePortal,
        protected LeaveRequestService $leaveRequests,
        protected LeaveCarryForwardService $carryForwardService,
        protected LeaveCatalogService $catalog,
        protected LeaveCoverageService $coverages,
    ) {}

    public function index(Request $request): View
    {
        $staff = $this->staff($request);
        $staff->loadMissing('department');
        $editRequest = null;

        if ($request->filled('edit')) {
            $editRequest = LeaveRequest::query()
                ->with('coverages')
                ->where('staff_id', $staff->id)
                ->whereKey($request->integer('edit'))
                ->firstOrFail();

            abort_unless($editRequest->isEditableByEmployee(), 403);
        }

        $leaveTypes = $this->leaveRequests->activeLeaveTypes();
        $staffBalances = $this->employeePortal->leaveBalancesFor($staff);
        $coverageDepartments = $this->coverages->departmentsRequiringCoverage($staff);
        $eligibleCoverStaff = $this->coverages->eligibleCoverStaff($staff->id);
        $familyRelations = $this->catalog->familyRelations();

        $leaveTypeMeta = $leaveTypes->mapWithKeys(function ($type) use ($staffBalances) {
            $def = $this->catalog->definitionForType($type);

            return [
                $type->id => [
                    'id' => $type->id,
                    'code' => strtoupper((string) $type->leave_code),
                    'name' => $type->leave_name,
                    'calculation_type' => $def['calculation'] ?? $type->calculation_type,
                    'accrual_type' => ($def['accrual'] ?? 'none') === 'monthly' ? 'monthly' : 'none',
                    'accrual_rate' => $def['accrual_rate'] ?? $type->accrual_rate,
                    'requires_document' => ! empty($def['requires_document']),
                    'document_label' => $def['document_label'] ?? 'Supporting document',
                    'requires_family_relation' => ! empty($def['requires_family_relation']),
                    'gender_restriction' => $def['gender_restriction'] ?? 'any',
                    'days_allowed' => (int) ($def['days_allowed_per_year'] ?? 0),
                    'description' => $def['description'] ?? null,
                    'available_balance' => (float) ($staffBalances->firstWhere('leave_type_name', $type->leave_name)?->balance_days ?? 0),
                ],
            ];
        })->all();

        return view('employee.leave.index', [
            'portalTitle' => 'Leave requests',
            'staff' => $staff,
            'leaveTypes' => $leaveTypes,
            'leaveRequests' => $this->leaveRequests->requestsForStaff($staff),
            'leaveBalances' => $staffBalances,
            'editRequest' => $editRequest,
            'leaveTypeMap' => array_values($leaveTypeMeta),
            'leaveTypeMeta' => $leaveTypeMeta,
            'familyRelations' => $familyRelations,
            'coverageDepartments' => $coverageDepartments,
            'eligibleCoverStaff' => $eligibleCoverStaff,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $staff = $this->staff($request);
        $data = $this->validatedLeave($request, requireFutureStart: true);

        try {
            $this->leaveRequests->submit($staff, $data, $request->file('medical_certificate'));
        } catch (\InvalidArgumentException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
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
            return back()->withInput()->with('error', $exception->getMessage());
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

        $data = $request->validate([
            'leave_type_id' => ['required', 'integer', 'exists:leave_types,id'],
            'start_date' => $startRules,
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'return_date' => ['nullable', 'date', 'after_or_equal:end_date'],
            'reason' => ['required', 'string', 'max:2000'],
            'handover_notes' => ['nullable', 'string', 'max:2000'],
            'contact_mobile' => ['nullable', 'string', 'max:40'],
            'contact_email' => ['nullable', 'email', 'max:191'],
            'contact_postal_address' => ['nullable', 'string', 'max:255'],
            'is_emergency' => ['nullable', 'boolean'],
            'family_relation' => ['nullable', 'string', 'in:mother,father,child,spouse'],
            'cover_staff_id' => ['nullable', 'array'],
            'cover_staff_id.*' => ['nullable', 'integer', 'exists:staff,id'],
            'medical_certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $data['is_emergency'] = $request->boolean('is_emergency');
        $data['cover_staff_id'] = $data['cover_staff_id'] ?? [];

        return $data;
    }

    public function carryForwardForm(Request $request): View
    {
        $staff = $this->staff($request);
        $requests = $this->carryForwardService->forEmployee($staff);

        return view('employee.leave.carry-forward', [
            'staff' => $staff,
            'carryForwardRequests' => $requests,
            'currentYear' => now()->year,
            'teamCarryForwardPending' => $this->carryForwardService->pendingForLineManager($staff),
        ]);
    }

    public function carryForwardStore(Request $request): RedirectResponse
    {
        $staff = $this->staff($request);

        $validated = $request->validate([
            'days_requested' => ['required', 'numeric', 'min:0.5', 'max:10'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $validated['from_year'] = now()->year;

        $this->carryForwardService->submit($staff, $validated);

        return redirect()
            ->route('employee.leave.carry-forward')
            ->with('success', 'Carry-forward request submitted for line manager and HR approval.');
    }

    public function carryForwardApproveAsManager(Request $request, LeaveCarryForwardRequest $carryForwardRequest): RedirectResponse
    {
        $manager = $this->staff($request);
        $validated = $request->validate([
            'line_manager_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->carryForwardService->approveByLineManager($manager, $carryForwardRequest, $validated);

        return redirect()
            ->route('employee.leave.carry-forward')
            ->with('success', 'Carry-forward approved. It will now go to HR for final approval.');
    }

    public function carryForwardRejectAsManager(Request $request, LeaveCarryForwardRequest $carryForwardRequest): RedirectResponse
    {
        $manager = $this->staff($request);
        $validated = $request->validate([
            'line_manager_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->carryForwardService->rejectByLineManager(
            $manager,
            $carryForwardRequest,
            $validated['line_manager_notes'] ?? null
        );

        return redirect()
            ->route('employee.leave.carry-forward')
            ->with('success', 'Carry-forward request rejected.');
    }

    private function staff(Request $request): \App\Models\Staff
    {
        $staff = $request->attributes->get('portal_staff')
            ?? $this->employeePortal->staffForUser($request->user());

        abort_unless($staff, 403);

        return $staff;
    }
}
