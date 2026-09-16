<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Models\Administration\BudgetRequest;
use App\Models\Department;
use App\Models\FinanceBudget;
use App\Models\ProcurementRequisition;
use App\Models\Staff;
use App\Services\Procurement\RequisitionService;
use App\Support\Pagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use InvalidArgumentException;

class RequisitionController extends Controller
{
    public function __construct(protected RequisitionService $requisitionService) {}

    public function index(Request $request): View
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();

        $requisitions = ProcurementRequisition::query()
            ->with(['department', 'requester'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($builder) use ($search) {
                    $builder->where('requisition_number', 'like', "%{$search}%")
                        ->orWhere('requested_item', 'like', "%{$search}%")
                        ->orWhereHas('requester', fn ($staff) => $staff->where('full_name', 'like', "%{$search}%"))
                        ->orWhereHas('department', fn ($dept) => $dept->where('dept_name', 'like', "%{$search}%"));
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(Pagination::perPage($request))
            ->withQueryString();

        $stats = [
            'total' => ProcurementRequisition::query()->count(),
            'draft' => ProcurementRequisition::query()->where('status', 'draft')->count(),
            'pending' => ProcurementRequisition::query()->whereIn('status', ['submitted', 'hod_approved', 'finance_approved', 'ceo_approved'])->count(),
            'completed' => ProcurementRequisition::query()->where('status', 'completed')->count(),
            'rejected' => ProcurementRequisition::query()->whereIn('status', ['rejected', 'cancelled'])->count(),
        ];

        return view('procurement.requisitions.index', compact('requisitions', 'stats', 'search', 'status'));
    }

    public function create(Request $request): View
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
                'line_count' => count($budget->lineItems()),
            ]);
        }

        foreach ($budgetRequests as $budgetRequest) {
            $lines = is_array($budgetRequest->standard_line_items) ? $budgetRequest->standard_line_items : [];
            $budgetOptions->push([
                'source' => 'request',
                'id' => $budgetRequest->id,
                'code' => $budgetRequest->request_code,
                'label' => sprintf(
                    '%s — %s (%s · %s · %d items · KES %s)',
                    $budgetRequest->request_code,
                    $budgetRequest->title,
                    $budgetRequest->department?->dept_name ?? 'Department',
                    str_replace('_', ' ', $budgetRequest->status),
                    count($lines),
                    number_format((float) $budgetRequest->requested_amount, 0)
                ),
                'department_id' => $budgetRequest->department_id,
                'line_count' => count($lines),
            ]);
        }

        return view('procurement.requisitions.create', [
            'departments' => Department::query()->main()->where('is_active', 1)->orderBy('dept_name')->get(['id', 'dept_name']),
            'budgetOptions' => $budgetOptions,
            'budgetLinesUrl' => route('procurement.api.budget-lines'),
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
            $lines = collect(is_array($budgetRequest->standard_line_items) ? $budgetRequest->standard_line_items : [])
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

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'requesting_department_id' => ['required', 'exists:departments,id'],
            'request_date' => ['required', 'date_format:d/m/Y'],
            'justification' => ['required', 'string', 'max:2000'],
            'budget_code' => ['nullable', 'string', 'max:100'],
            'budget_line' => ['nullable', 'string', 'max:100'],
            'requested_item' => ['nullable', 'string', 'max:300'],
            'estimated_unit_cost' => ['nullable', 'numeric', 'min:0'],
            'quantity' => ['nullable', 'numeric', 'min:0.0001'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.description' => ['nullable', 'string', 'max:2000'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.unit_of_measure' => ['nullable', 'string', 'max:50'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'],
        ], [
            'requesting_department_id.required' => 'Please select a requesting department.',
            'request_date.required' => 'Please enter the request date.',
            'justification.required' => 'Please provide a justification for this requisition.',
            'lines.required' => 'Please add at least one requisition line item.',
            'lines.*.item.required' => 'Each line needs an item name.',
        ]);

        $lineItems = [];
        $grandTotal = 0.0;
        foreach ($validated['lines'] as $line) {
            $quantity = round((float) $line['quantity'], 4);
            $unitPrice = round((float) $line['unit_price'], 2);
            $total = round($quantity * $unitPrice, 2);
            $grandTotal += $total;

            $lineItems[] = [
                'item' => trim((string) $line['item']),
                'quantity' => $quantity,
                'description' => trim((string) ($line['description'] ?? '')),
                'unit_price' => $unitPrice,
                'unit_of_measure' => trim((string) ($line['unit_of_measure'] ?? '')) ?: null,
                'total' => $total,
            ];
        }

        if ($grandTotal <= 0) {
            return back()
                ->withInput()
                ->withErrors(['lines' => 'The estimated total cost must be greater than zero.']);
        }

        $itemNames = collect($lineItems)->pluck('item')->filter()->values();
        $validated['requested_item'] = $validated['requested_item']
            ?: ($itemNames->count() === 1
                ? $itemNames->first()
                : $itemNames->take(2)->implode(', ').($itemNames->count() > 2 ? ' +'.($itemNames->count() - 2).' more' : ''));
        $validated['estimated_cost'] = $grandTotal;
        $validated['estimated_unit_cost'] = $lineItems[0]['unit_price'] ?? null;
        $validated['quantity'] = collect($lineItems)->sum('quantity');
        $validated['budget_line'] = $validated['budget_line'] ?: ($lineItems[0]['item'] ?? null);
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

        return redirect()
            ->route('procurement.requisitions.show', $requisition)
            ->with('status', 'Requisition draft created successfully.');
    }

    public function show(Request $request, ProcurementRequisition $requisition): View
    {
        $requisition->load(['department', 'requester', 'hodApprover', 'financeApprover', 'ceoApprover']);

        $budgetCheck = $this->requisitionService->verifyBudget($requisition);

        return view('procurement.requisitions.show', [
            'requisition' => $requisition,
            'budgetCheck' => $budgetCheck,
            'nextLevel' => $this->requisitionService->getApprovalLevel((float) $requisition->estimated_cost),
        ]);
    }

    public function approve(Request $request, ProcurementRequisition $requisition, string $level): RedirectResponse
    {
        $validLevels = ['hod', 'finance', 'ceo'];

        if (!in_array($level, $validLevels, true)) {
            throw new InvalidArgumentException("Invalid approval level: {$level}");
        }

        if ($requisition->status === 'rejected' || $requisition->status === 'cancelled') {
            return back()->withErrors(['status' => 'Cannot approve a rejected or cancelled requisition.']);
        }

        $budgetCheck = $this->requisitionService->verifyBudget($requisition);

        if (!$budgetCheck['passed']) {
            return back()->withErrors(['budget' => $budgetCheck['message']]);
        }

        $validated = $request->validate([
            'comments' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->requisitionService->approve(
            $requisition,
            Auth::user()->staff,
            $level,
            $validated['comments'] ?? null
        );

        return back()->with('status', 'Requisition approved successfully.');
    }

    public function reject(Request $request, ProcurementRequisition $requisition, string $level): RedirectResponse
    {
        $validLevels = ['hod', 'finance', 'ceo'];

        if (!in_array($level, $validLevels, true)) {
            throw new InvalidArgumentException("Invalid rejection level: {$level}");
        }

        if ($requisition->status === 'rejected' || $requisition->status === 'cancelled') {
            return back()->withErrors(['status' => 'Cannot reject a requisition that is already rejected or cancelled.']);
        }

        $validated = $request->validate([
            'comments' => ['required', 'string', 'max:1000'],
        ], [
            'comments.required' => 'Please provide a reason for rejection.',
        ]);

        $this->requisitionService->reject(
            $requisition,
            Auth::user()->staff,
            $level,
            $validated['comments']
        );

        return back()->with('status', 'Requisition rejected successfully.');
    }

    public function submit(Request $request, ProcurementRequisition $requisition): RedirectResponse
    {
        if (!$requisition->isDraft()) {
            return back()->withErrors(['status' => 'Only draft requisitions can be submitted.']);
        }

        $budgetCheck = $this->requisitionService->verifyBudget($requisition);

        if (!$budgetCheck['passed']) {
            return back()->withErrors(['budget' => $budgetCheck['message']]);
        }

        $this->requisitionService->addAuditTrail($requisition, 'submit', 'Requisition submitted for approval');

        $requisition->update([
            'status' => 'submitted',
            'hod_approval_status' => 'pending',
        ]);

        return back()->with('status', 'Requisition submitted for approval.');
    }

    public function cancel(Request $request, ProcurementRequisition $requisition): RedirectResponse
    {
        if ($requisition->isRejected() || $requisition->isCompleted()) {
            return back()->withErrors(['status' => 'Cannot cancel a completed or rejected requisition.']);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $this->requisitionService->cancel($requisition, $validated['reason']);

        return back()->with('status', 'Requisition cancelled successfully.');
    }
}
