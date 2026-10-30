<?php

namespace App\Http\Controllers\Api\Procurement\mobile;

use App\Http\Controllers\Api\Procurement\mobile\Concerns\BroadcastsProcurementCounts;
use App\Http\Controllers\Controller;
use App\Models\Administration\BudgetRequest;
use App\Models\Department;
use App\Models\FinanceBudget;
use App\Models\ProcurementRequisition;
use App\Services\Procurement\ProcurementMobilePortalService;
use App\Services\Procurement\RequisitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class RequisitionsController extends Controller
{
    use BroadcastsProcurementCounts;

    public function __construct(
        protected ProcurementMobilePortalService $portal,
        protected RequisitionService $requisitionService,
    ) {}

    public function formOptions(): JsonResponse
    {
        $financeBudgets = FinanceBudget::query()
            ->where('status', 'active')
            ->orderByDesc('fiscal_year')
            ->orderBy('budget_name')
            ->get(['id', 'budget_code', 'budget_name', 'allocated_amount', 'spent_amount', 'committed_amount', 'fiscal_year', 'department_id', 'notes']);

        $budgetRequests = Schema::hasTable('admin_budget_requests')
            ? BudgetRequest::query()
                ->with('department:id,dept_name')
                ->whereNotNull('standard_line_items')
                ->whereNotIn('status', ['draft', 'rejected', 'returned', 'cancelled'])
                ->orderByDesc('submitted_at')
                ->orderByDesc('id')
                ->get(['id', 'request_code', 'title', 'department_id', 'requested_amount', 'status', 'standard_line_items'])
            : collect();

        $budgetOptions = collect();

        foreach ($financeBudgets as $budget) {
            $budgetOptions->push([
                'key' => 'finance:'.$budget->id,
                'source' => 'finance',
                'id' => $budget->id,
                'code' => $budget->budget_code,
                'label' => sprintf(
                    '%s — %s (FY%s · avail. KES %s)',
                    $budget->budget_code,
                    $budget->budget_name,
                    $budget->fiscal_year,
                    number_format($budget->availableAmount(), 0)
                ),
                'department_id' => $budget->department_id,
            ]);
        }

        foreach ($budgetRequests as $budgetRequest) {
            $budgetOptions->push([
                'key' => 'request:'.$budgetRequest->id,
                'source' => 'request',
                'id' => $budgetRequest->id,
                'code' => $budgetRequest->request_code,
                'label' => sprintf(
                    '%s — %s (%s · %s · %d items · KES %s)',
                    $budgetRequest->request_code,
                    $budgetRequest->title,
                    $budgetRequest->department?->dept_name ?? 'Department',
                    str_replace('_', ' ', $budgetRequest->status),
                    $budgetRequest->expenditureLineCount(),
                    number_format((float) $budgetRequest->requested_amount, 0)
                ),
                'department_id' => $budgetRequest->department_id,
            ]);
        }

        $departments = Department::query()
            ->main()
            ->where('is_active', 1)
            ->orderBy('dept_name')
            ->get(['id', 'dept_name'])
            ->map(fn (Department $d) => ['id' => $d->id, 'name' => $d->dept_name])
            ->values();

        return response()->json([
            'departments' => $departments,
            'budgets' => $budgetOptions->values(),
        ]);
    }

    public function budgetLines(Request $request): JsonResponse
    {
        $source = $request->string('source')->toString();
        $id = (int) $request->input('id');

        if ($source === 'finance') {
            $budget = FinanceBudget::query()->findOrFail($id);

            return response()->json([
                'source' => 'finance',
                'id' => $budget->id,
                'budget_code' => $budget->budget_code,
                'budget_name' => $budget->budget_name,
                'department_id' => $budget->department_id,
                'available' => $budget->availableAmount(),
                'lines' => $budget->lineItems(),
            ]);
        }

        if ($source === 'request') {
            $budgetRequest = BudgetRequest::query()->findOrFail($id);
            $lines = collect($budgetRequest->expenditureLines())
                ->filter(fn ($line) => is_array($line) && trim((string) ($line['item'] ?? '')) !== '')
                ->map(function (array $line) {
                    $quantity = (float) ($line['quantity'] ?? 1);
                    $unitPrice = (float) ($line['unit_price'] ?? 0);

                    return [
                        'item' => trim((string) $line['item']),
                        'quantity' => $quantity > 0 ? $quantity : 1.0,
                        'description' => trim((string) ($line['description'] ?? '')),
                        'unit_price' => $unitPrice,
                        'unit_of_measure' => ($line['unit_of_measure'] ?? null) !== null && trim((string) $line['unit_of_measure']) !== ''
                            ? trim((string) $line['unit_of_measure'])
                            : null,
                        'total' => isset($line['total']) ? (float) $line['total'] : round($quantity * $unitPrice, 2),
                    ];
                })
                ->values()
                ->all();

            return response()->json([
                'source' => 'request',
                'id' => $budgetRequest->id,
                'budget_code' => $budgetRequest->request_code,
                'budget_name' => $budgetRequest->title,
                'department_id' => $budgetRequest->department_id,
                'available' => (float) ($budgetRequest->approved_amount ?: $budgetRequest->verified_amount ?: $budgetRequest->requested_amount),
                'lines' => $lines,
            ]);
        }

        return response()->json(['message' => 'Invalid budget source.'], 422);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'requesting_department_id' => ['required', 'exists:departments,id'],
            'request_date' => ['required', 'date_format:d/m/Y'],
            'justification' => ['required', 'string', 'max:2000'],
            'budget_code' => ['nullable', 'string', 'max:100'],
            'budget_line' => ['nullable', 'string', 'max:100'],
            'requested_item' => ['nullable', 'string', 'max:300'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.description' => ['nullable', 'string', 'max:2000'],
            'lines.*.unit_of_measure' => ['nullable', 'string', 'max:50'],
            'lines.*.unit_price' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'],
        ], [
            'requesting_department_id.required' => 'Please select a requesting department.',
            'request_date.required' => 'Please enter the request date.',
            'justification.required' => 'Please provide a justification for this requisition.',
            'lines.required' => 'Please add at least one requisition line item.',
            'lines.*.item.required' => 'Each line needs an item name.',
        ]);

        // The department files its own estimate; Procurement may regulate the prices later.
        $lineItems = $this->requisitionService->estimatedLines($validated['lines']);

        $itemNames = collect($lineItems)->pluck('item')->filter()->values();
        $validated['requested_item'] = ($validated['requested_item'] ?? null)
            ?: ($itemNames->count() === 1
                ? $itemNames->first()
                : $itemNames->take(2)->implode(', ').($itemNames->count() > 2 ? ' +'.($itemNames->count() - 2).' more' : ''));
        $validated['estimated_cost'] = $this->requisitionService->estimatedCost($lineItems);
        $validated['estimated_unit_cost'] = $lineItems[0]['unit_price'] ?? null;
        $validated['quantity'] = collect($lineItems)->sum('quantity');
        $validated['budget_line'] = ($validated['budget_line'] ?? null) ?: ($lineItems[0]['item'] ?? null);
        $validated['line_items'] = $lineItems;

        $validated['request_date'] = \Carbon\Carbon::createFromFormat('d/m/Y', $validated['request_date'])->format('Y-m-d');

        unset($validated['lines']);

        $validated['requested_by'] = Auth::id();
        $validated['requisition_number'] = $this->requisitionService->generateRequisitionNumber();
        $validated['status'] = 'draft';

        if ($request->hasFile('attachments')) {
            $storedPaths = [];
            foreach ($request->file('attachments') as $file) {
                $storedPaths[] = $file->store('procurement/attachments', 'public');
            }
            $validated['attachments'] = $storedPaths;
        }

        if (! Schema::hasColumn('procurement_requisitions', 'line_items')) {
            unset($validated['line_items']);
        }

        $requisition = ProcurementRequisition::query()->create($validated);
        $requisition->load(['department', 'requester']);

        $this->broadcastProcurementCounts();

        return response()->json([
            'message' => 'Requisition draft created successfully.',
            'item' => $this->portal->serializeRequisition($requisition, $request->user()),
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();

        $query = ProcurementRequisition::query()
            ->with(['department', 'requester'])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('requisition_number', 'like', "%{$search}%")
                        ->orWhere('requested_item', 'like', "%{$search}%")
                        ->orWhere('budget_code', 'like', "%{$search}%")
                        ->orWhereHas('department', fn ($dept) => $dept->where('dept_name', 'like', "%{$search}%"));
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

        // Nothing to verify while Procurement still owes the prices; approvals are gated later.
        if ($requisition->isPriced()) {
            $budget = $this->requisitionService->verifyBudget($requisition);
            if (! ($budget['passed'] ?? false)) {
                return response()->json(['message' => $budget['message'] ?? 'Budget check failed.'], 422);
            }
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
