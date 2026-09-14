<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\FinanceBudget;
use App\Models\ProcurementRequisition;
use App\Models\Staff;
use App\Services\Procurement\RequisitionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            ->paginate(25)
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
        return view('procurement.requisitions.create', [
            'departments' => Department::query()->main()->where('is_active', 1)->orderBy('dept_name')->get(['id', 'dept_name']),
            'budgets' => FinanceBudget::query()->where('status', 'active')->orderByDesc('fiscal_year')->get(['id', 'budget_code', 'budget_name', 'allocated_amount', 'fiscal_year']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'requesting_department_id' => ['required', 'exists:departments,id'],
            'request_date' => ['required', 'date_format:d/m/Y'],
            'justification' => ['required', 'string', 'max:2000'],
            'estimated_cost' => ['required', 'numeric', 'min:0'],
            'budget_code' => ['nullable', 'string', 'max:100'],
            'budget_line' => ['nullable', 'string', 'max:100'],
            'requested_item' => ['required', 'string', 'max:300'],
            'estimated_unit_cost' => ['nullable', 'numeric', 'min:0'],
            'quantity' => ['nullable', 'numeric', 'min:0.0001'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'],
        ], [
            'requesting_department_id.required' => 'Please select a requesting department.',
            'request_date.required' => 'Please enter the request date.',
            'justification.required' => 'Please provide a justification for this requisition.',
            'estimated_cost.required' => 'Please enter the estimated cost.',
            'requested_item.required' => 'Please enter the item or service being requested.',
        ]);

        $validated['request_date'] = \Carbon\Carbon::createFromFormat('d/m/Y', $validated['request_date'])->format('Y-m-d');

        if (!empty($validated['delivery_required_by'])) {
            $validated['delivery_required_by'] = \Carbon\Carbon::createFromFormat('d/m/Y', $validated['delivery_required_by'])->format('Y-m-d');
        }

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
