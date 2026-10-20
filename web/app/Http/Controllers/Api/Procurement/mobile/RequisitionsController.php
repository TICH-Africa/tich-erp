<?php

namespace App\Http\Controllers\Api\Procurement\mobile;

use App\Http\Controllers\Api\Procurement\mobile\Concerns\BroadcastsProcurementCounts;
use App\Http\Controllers\Controller;
use App\Models\ProcurementRequisition;
use App\Services\Procurement\ProcurementMobilePortalService;
use App\Services\Procurement\RequisitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RequisitionsController extends Controller
{
    use BroadcastsProcurementCounts;

    public function __construct(
        protected ProcurementMobilePortalService $portal,
        protected RequisitionService $requisitionService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();

        $query = ProcurementRequisition::query()
            ->with(['department', 'requester'])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('requisition_number', 'like', "%{$search}%")
                        ->orWhere('requested_item', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->limit(50);

        $items = $query->get()->map(
            fn (ProcurementRequisition $r) => $this->portal->serializeRequisition($r, $request->user())
        )->values();

        return response()->json(['items' => $items]);
    }

    public function show(Request $request, ProcurementRequisition $requisition): JsonResponse
    {
        return response()->json(
            $this->portal->serializeRequisition($requisition, $request->user())
        );
    }

    public function approve(Request $request, ProcurementRequisition $requisition): JsonResponse
    {
        $user = $request->user();
        abort_unless($this->portal->canApproveRequisition($user, $requisition), 403);

        $staff = $this->portal->staffForUser($user);
        abort_unless($staff, 403, 'Linked employee profile required to approve requisitions.');

        $budget = $this->requisitionService->verifyBudget($requisition);
        if (! ($budget['passed'] ?? false)) {
            return response()->json(['message' => $budget['message'] ?? 'Budget check failed.'], 422);
        }

        $validated = $request->validate([
            'comments' => ['nullable', 'string', 'max:1000'],
        ]);

        $level = $requisition->currentApprovalLevel();
        if (! in_array($level, ['hod', 'finance', 'ceo'], true)) {
            return response()->json(['message' => 'This requisition is not awaiting approval.'], 422);
        }

        $this->requisitionService->approve(
            $requisition,
            $staff,
            $level,
            $validated['comments'] ?? null
        );

        $this->broadcastProcurementCounts();

        return response()->json([
            'message' => 'Requisition approved.',
            'item' => $this->portal->serializeRequisition($requisition->fresh(), $user),
        ]);
    }

    public function reject(Request $request, ProcurementRequisition $requisition): JsonResponse
    {
        $user = $request->user();
        abort_unless($this->portal->canApproveRequisition($user, $requisition), 403);

        $staff = $this->portal->staffForUser($user);
        abort_unless($staff, 403, 'Linked employee profile required to reject requisitions.');

        $level = $requisition->currentApprovalLevel();
        if (! in_array($level, ['hod', 'finance', 'ceo'], true)) {
            return response()->json(['message' => 'This requisition is not awaiting approval.'], 422);
        }

        $validated = $request->validate([
            'comments' => ['required', 'string', 'max:1000'],
        ], [
            'comments.required' => 'Please provide a reason for rejection.',
        ]);

        $this->requisitionService->reject(
            $requisition,
            $staff,
            $level,
            $validated['comments']
        );

        $this->broadcastProcurementCounts();

        return response()->json([
            'message' => 'Requisition rejected.',
            'item' => $this->portal->serializeRequisition($requisition->fresh(), $user),
        ]);
    }

    public function submit(Request $request, ProcurementRequisition $requisition): JsonResponse
    {
        if (! $requisition->isDraft()) {
            return response()->json(['message' => 'Only draft requisitions can be submitted.'], 422);
        }

        $budget = $this->requisitionService->verifyBudget($requisition);
        if (! ($budget['passed'] ?? false)) {
            return response()->json(['message' => $budget['message'] ?? 'Budget check failed.'], 422);
        }

        $staff = $this->portal->staffForUser($request->user());
        abort_unless($staff, 403, 'Linked employee profile required.');

        $this->requisitionService->addAuditTrail($requisition, 'submit', 'Requisition submitted for approval');

        $requisition->update([
            'status' => 'submitted',
            'hod_approval_status' => 'pending',
        ]);

        $this->broadcastProcurementCounts();

        return response()->json([
            'message' => 'Requisition submitted for approval.',
            'item' => $this->portal->serializeRequisition($requisition->fresh(), $request->user()),
        ]);
    }
}
