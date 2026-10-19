<?php

namespace App\Http\Controllers\Api\Employee\mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Employee\mobile\Concerns\ResolvesPortalStaff;
use App\Models\LeaveRequest;
use App\Models\LeaveRequestCoverage;
use App\Services\EmployeePortalService;
use App\Services\Leave\LeaveCatalogService;
use App\Services\Leave\LeaveCoverageService;
use App\Services\LeaveRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    use ResolvesPortalStaff;

    public function __construct(
        protected EmployeePortalService $employeePortal,
        protected LeaveRequestService $leaveRequests,
        protected LeaveCatalogService $catalog,
        protected LeaveCoverageService $coverages,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $staff = $this->portalStaff($request);
        $leaveTypes = $this->leaveRequests->activeLeaveTypes();
        $balances = $this->employeePortal->leaveBalancesFor($staff);

        $typeMeta = $leaveTypes->map(function ($type) use ($balances) {
            $def = $this->catalog->definitionForType($type);

            return [
                'id' => $type->id,
                'code' => strtoupper((string) $type->leave_code),
                'name' => $type->leave_name,
                'calculation_type' => $def['calculation'] ?? $type->calculation_type,
                'requires_document' => ! empty($def['requires_document']),
                'document_label' => $def['document_label'] ?? 'Supporting document',
                'requires_family_relation' => ! empty($def['requires_family_relation']),
                'gender_restriction' => $def['gender_restriction'] ?? 'any',
                'days_allowed' => (int) ($def['days_allowed_per_year'] ?? 0),
                'description' => $def['description'] ?? null,
                'available_balance' => (float) ($balances->firstWhere('leave_type_name', $type->leave_name)?->balance_days ?? 0),
            ];
        })->values();

        $requests = $this->leaveRequests->requestsForStaff($staff);
        $requests->loadMissing(['leaveType', 'coverages.coverStaff', 'coverages.department']);

        return response()->json([
            'balances' => $balances->map(fn ($b) => [
                'leave_type_name' => $b->leave_type_name,
                'entitled_days' => (float) $b->entitled_days,
                'days_taken' => (float) $b->days_taken,
                'days_pending' => (float) $b->days_pending,
                'balance_days' => (float) $b->balance_days,
            ])->values(),
            'leave_types' => $typeMeta,
            'family_relations' => $this->catalog->familyRelations(),
            'eligible_cover_staff' => $this->coverages->eligibleCoverStaff($staff->id)->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->fullName(),
                'job_title' => $s->job_title,
            ])->values(),
            'coverage_departments' => $this->coverages->departmentsRequiringCoverage($staff)->map(fn ($d) => [
                'id' => $d->id,
                'name' => $d->dept_name,
            ])->values(),
            'requests' => $requests->map(fn (LeaveRequest $r) => $this->serializeRequest($r))->values(),
            'pending_coverages' => $this->coverages->pendingForCoverStaff($staff)->map(fn ($c) => [
                'id' => $c->id,
                'leave_request_id' => $c->leave_request_id,
                'applicant' => $c->leaveRequest?->staff?->fullName(),
                'leave_type' => $c->leaveRequest?->leaveType?->leave_name,
                'start_date' => optional($c->leaveRequest?->start_date)?->format('Y-m-d'),
                'end_date' => optional($c->leaveRequest?->end_date)?->format('Y-m-d'),
                'department' => $c->department?->dept_name,
                'status' => $c->status,
            ])->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $staff = $this->portalStaff($request);
        $data = $this->validatedLeave($request);

        try {
            $leave = $this->leaveRequests->submit($staff, $data, $request->file('medical_certificate'));
        } catch (\InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Leave request submitted.',
            'request' => $this->serializeRequest($leave->fresh(['leaveType', 'coverages'])),
        ], 201);
    }

    public function cancel(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $staff = $this->portalStaff($request);

        try {
            $this->leaveRequests->cancelByEmployee(
                $leaveRequest,
                $staff,
                $request->input('cancellation_reason'),
            );
        } catch (\InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['message' => 'Leave request cancelled.']);
    }

    public function acceptCoverage(Request $request, LeaveRequestCoverage $coverage): JsonResponse
    {
        $staff = $this->portalStaff($request);
        $validated = $request->validate([
            'response_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->coverages->acceptCoverage($coverage, $staff, $validated['response_notes'] ?? null);
        } catch (\InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['message' => 'Coverage accepted.']);
    }

    public function declineCoverage(Request $request, LeaveRequestCoverage $coverage): JsonResponse
    {
        $staff = $this->portalStaff($request);
        $validated = $request->validate([
            'response_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->coverages->declineCoverage($coverage, $staff, $validated['response_notes'] ?? null);
        } catch (\InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['message' => 'Coverage declined.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedLeave(Request $request): array
    {
        $data = $request->validate([
            'leave_type_id' => ['required', 'integer', 'exists:leave_types,id'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'return_date' => ['nullable', 'date', 'after_or_equal:end_date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
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

        // Persist optional times in handover notes (leave days still use calendar dates).
        $startTime = $data['start_time'] ?? null;
        $endTime = $data['end_time'] ?? null;
        unset($data['start_time'], $data['end_time']);
        if ($startTime || $endTime) {
            $timeNote = trim(sprintf(
                'Requested times: %s – %s',
                $startTime ?? '—',
                $endTime ?? '—'
            ));
            $existing = trim((string) ($data['handover_notes'] ?? ''));
            $data['handover_notes'] = $existing === '' ? $timeNote : ($existing."\n".$timeNote);
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeRequest(LeaveRequest $r): array
    {
        return [
            'id' => $r->id,
            'leave_number' => $r->leave_number,
            'leave_type_id' => $r->leave_type_id,
            'leave_type_name' => $r->leaveType?->leave_name,
            'start_date' => optional($r->start_date)?->format('Y-m-d'),
            'end_date' => optional($r->end_date)?->format('Y-m-d'),
            'return_date' => optional($r->return_date)?->format('Y-m-d'),
            'days_requested' => (float) $r->days_requested,
            'reason' => $r->reason,
            'overall_status' => $r->overall_status,
            'is_emergency' => (bool) $r->is_emergency,
            'editable' => $r->isEditableByEmployee(),
            'coverage_editable' => $r->isCoverageEditableByEmployee(),
            'coverages' => $r->coverages->map(fn ($c) => [
                'id' => $c->id,
                'cover_staff' => $c->coverStaff?->fullName(),
                'department' => $c->department?->dept_name,
                'status' => $c->status,
            ])->values(),
        ];
    }
}
