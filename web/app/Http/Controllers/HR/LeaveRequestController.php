<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\Staff;
use App\Services\EmployeePortalService;
use App\Services\HrLeaveBalanceService;
use App\Services\HrLeaveOverviewService;
use App\Services\Leave\LeaveCatalogService;
use App\Services\Leave\LeaveCoverageService;
use App\Services\LeaveRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveRequestController extends Controller
{
    public function __construct(
        protected LeaveRequestService $leaveRequests,
        protected EmployeePortalService $employeePortal,
        protected HrLeaveOverviewService $leaveOverview,
        protected HrLeaveBalanceService $leaveBalances,
        protected LeaveCatalogService $catalog,
        protected LeaveCoverageService $coverages,
    ) {}

    public function overview(Request $request): View
    {
        $records = $this->leaveOverview->currentlyOnLeave($request->input('search'));

        return view('hr.leave.overview', [
            'records' => $records,
            'onLeaveCount' => $records->count(),
        ]);
    }

    public function employees(Request $request): View
    {
        $records = $this->leaveOverview->allEmployeesLeaveSummary($request->input('search'));

        return view('hr.leave.employees', [
            'records' => $records,
            'onLeaveCount' => $records->where('on_leave', true)->count(),
            'employeeCount' => $records->count(),
        ]);
    }

    public function index(Request $request): View
    {
        return view('hr.leave.index', [
            'requests' => $this->leaveRequests->hrInbox(
                $request->input('status'),
                $request->input('search'),
            ),
            'pendingCount' => $this->leaveRequests->pendingHrCount(),
            'canManageLeave' => $request->user()?->hasPermission('hr.manage_leave') ?? false,
        ]);
    }

    public function createOnBehalf(Request $request): View
    {
        abort_unless($request->user()?->hasPermission('hr.manage_leave'), 403);

        $staffId = $request->integer('staff_id') ?: null;
        $selectedStaff = $staffId
            ? Staff::query()->excludePlatformOperators()->find($staffId)
            : null;

        $employees = Staff::query()
            ->excludePlatformOperators()
            ->whereIn('employment_status', ['active', 'on_leave', 'onboarding'])
            ->orderBy('first_name')
            ->orderBy('surname')
            ->get(['id', 'first_name', 'surname', 'employee_number', 'job_title', 'department_id']);

        $leaveTypes = $this->leaveRequests->activeLeaveTypes();
        $leaveTypeMeta = [];
        $coverageDepartments = collect();
        $eligibleCoverStaff = collect();

        if ($selectedStaff) {
            $selectedStaff->loadMissing('department');
            $staffBalances = $this->employeePortal->leaveBalancesFor($selectedStaff);
            $coverageDepartments = $this->coverages->departmentsRequiringCoverage($selectedStaff);
            $eligibleCoverStaff = $this->coverages->eligibleCoverStaff($selectedStaff->id);

            $leaveTypeMeta = $leaveTypes->mapWithKeys(function ($type) use ($staffBalances) {
                $def = $this->catalog->definitionForType($type);

                return [
                    $type->id => [
                        'id' => $type->id,
                        'code' => strtoupper((string) $type->leave_code),
                        'name' => $type->leave_name,
                        'calculation_type' => $def['calculation'] ?? $type->calculation_type,
                        'requires_document' => ! empty($def['requires_document']),
                        'document_label' => $def['document_label'] ?? 'Supporting document',
                        'requires_family_relation' => ! empty($def['requires_family_relation']),
                        'gender_restriction' => $def['gender_restriction'] ?? 'any',
                        'available_balance' => (float) ($staffBalances->firstWhere('leave_type_name', $type->leave_name)?->balance_days ?? 0),
                    ],
                ];
            })->all();
        }

        return view('hr.leave.apply', [
            'employees' => $employees,
            'selectedStaff' => $selectedStaff,
            'leaveTypes' => $leaveTypes,
            'leaveTypeMeta' => $leaveTypeMeta,
            'familyRelations' => $this->catalog->familyRelations(),
            'coverageDepartments' => $coverageDepartments,
            'eligibleCoverStaff' => $eligibleCoverStaff,
        ]);
    }

    public function storeOnBehalf(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('hr.manage_leave'), 403);

        $data = $request->validate([
            'staff_id' => ['required', 'integer', 'exists:staff,id'],
            'leave_type_id' => ['required', 'integer', 'exists:leave_types,id'],
            'start_date' => ['required', 'date'],
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
            'approve_immediately' => ['nullable', 'boolean'],
        ]);

        $employee = Staff::query()->excludePlatformOperators()->findOrFail($data['staff_id']);
        $hrStaff = $this->employeePortal->staffForUser($request->user());
        abort_unless($hrStaff, 403, 'Your user account is not linked to an employee profile.');

        $data['is_emergency'] = $request->boolean('is_emergency');
        $data['cover_staff_id'] = $data['cover_staff_id'] ?? [];
        $approveImmediately = $request->boolean('approve_immediately', true);

        try {
            $leaveRequest = $this->leaveRequests->submitOnBehalfByHr(
                $employee,
                $hrStaff,
                $data,
                $request->file('medical_certificate'),
                $approveImmediately,
            );
        } catch (\InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['leave' => $exception->getMessage()]);
        }

        $message = $approveImmediately
            ? 'Leave recorded and approved for '.$employee->fullName().'.'
            : 'Leave submitted for '.$employee->fullName().' (awaiting HR approval).';

        return redirect()
            ->route('hr.leave.show', $leaveRequest)
            ->with('success', $message);
    }

    public function editBalances(Request $request, Staff $staff): View
    {
        abort_unless($request->user()?->hasPermission('hr.manage_leave'), 403);
        abort_unless($staff->exists, 404);

        $staff->loadMissing('department');
        $year = $request->integer('year') ?: now()->year;

        return view('hr.leave.balances', [
            'staff' => $staff,
            'year' => $year,
            'balances' => $this->leaveBalances->balancesForStaff($staff, $year),
        ]);
    }

    public function updateBalances(Request $request, Staff $staff): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('hr.manage_leave'), 403);

        $hrStaff = $this->employeePortal->staffForUser($request->user());
        abort_unless($hrStaff, 403);

        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'adjustment_note' => ['nullable', 'string', 'max:500'],
            'balances' => ['required', 'array'],
            'balances.*.entitled_days' => ['required', 'numeric', 'min:0', 'max:365'],
            'balances.*.days_taken' => ['required', 'numeric', 'min:0', 'max:365'],
            'balances.*.days_pending' => ['nullable', 'numeric', 'min:0', 'max:365'],
            'balances.*.carried_forward_days' => ['nullable', 'numeric', 'min:0', 'max:365'],
        ]);

        $rows = [];
        foreach ($validated['balances'] as $leaveTypeId => $fields) {
            $rows[(int) $leaveTypeId] = [
                'entitled_days' => (float) $fields['entitled_days'],
                'days_taken' => (float) $fields['days_taken'],
                'days_pending' => (float) ($fields['days_pending'] ?? 0),
                'carried_forward_days' => (float) ($fields['carried_forward_days'] ?? 0),
            ];
        }

        try {
            $this->leaveBalances->updateManualBalances(
                $staff,
                (int) $validated['year'],
                $rows,
                $hrStaff,
                $validated['adjustment_note'] ?? null,
            );
        } catch (\InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['leave' => $exception->getMessage()]);
        }

        return redirect()
            ->route('hr.leave.balances.edit', ['staff' => $staff, 'year' => $validated['year']])
            ->with('success', 'Leave balances updated for '.$staff->fullName().'.');
    }

    public function show(LeaveRequest $leaveRequest): View
    {
        $leaveRequest->load([
            'staff.department',
            'staff.lineManager',
            'leaveType',
            'hrApprovedBy',
            'coverages.coverStaff',
            'coverages.department',
        ]);

        $catalogDef = app(\App\Services\Leave\LeaveCatalogService::class)
            ->definitionForType($leaveRequest->leaveType);

        return view('hr.leave.show', [
            'leaveRequest' => $leaveRequest,
            'leaveBalances' => $this->employeePortal->leaveBalancesFor($leaveRequest->staff),
            'catalogDef' => $catalogDef,
            'catalogTypes' => config('tich-leave.types', []),
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

        try {
            $this->leaveRequests->approve($leaveRequest, $hrStaff, $data);
        } catch (\InvalidArgumentException $exception) {
            return back()->withErrors(['leave' => $exception->getMessage()]);
        }

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

    public function downloadDocument(LeaveRequest $leaveRequest): \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\Response
    {
        abort_unless(auth()->user()?->hasPermission('hr.manage_leave'), 403);

        $path = $leaveRequest->supporting_document_path ?: $leaveRequest->medical_certificate_path;
        abort_unless($path && \Illuminate\Support\Facades\Storage::disk('local')->exists($path), 404);

        $name = $leaveRequest->supporting_document_name
            ?: basename($path);

        return \Illuminate\Support\Facades\Storage::disk('local')->download($path, $name);
    }
}
