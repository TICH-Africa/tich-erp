<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Models\ProcurementRequisition;
use App\Models\Rfq;
use App\Models\RfqEvaluation;
use App\Models\RfqQuotation;
use App\Models\RfqSupplier;
use App\Models\Supplier;
use App\Services\Procurement\RfqService;
use App\Services\Procurement\SupplierService;
use Illuminate\Http\RedirectResponse;
use App\Support\Pagination;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class RfqController extends Controller
{
    public function __construct(
        protected SupplierService $supplierService,
        protected RfqService $rfqService,
    ) {}

    public function index(Request $request): View
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();

        $rfqs = Rfq::query()
            ->with(['requisition', 'awardedSupplier'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($builder) use ($search) {
                    $builder->where('rfq_number', 'like', "%{$search}%")
                        ->orWhere('item_description', 'like', "%{$search}%")
                        ->orWhereHas('requisition', fn ($req) => $req->where('requisition_number', 'like', "%{$search}%"));
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(Pagination::perPage($request))
            ->withQueryString();

        $stats = [
            'total' => Rfq::query()->count(),
            'draft' => Rfq::query()->where('status', 'draft')->count(),
            'published' => Rfq::query()->where('status', 'published')->count(),
            'closed' => Rfq::query()->where('status', 'closed')->count(),
            'evaluated' => Rfq::query()->where('status', 'evaluated')->count(),
            'awarded' => Rfq::query()->where('status', 'awarded')->count(),
        ];

        return view('procurement.rfqs.index', compact('rfqs', 'stats', 'search', 'status'));
    }

    public function create(Request $request): View
    {
        $requisitionId = $request->get('requisition_id');

        return view('procurement.rfqs.create', [
            'requisition' => $requisitionId ? ProcurementRequisition::query()->findOrFail($requisitionId) : null,
            'requisitions' => ProcurementRequisition::query()
                ->whereIn('status', ['hod_approved', 'finance_approved', 'ceo_approved', 'completed'])
                ->orderByDesc('created_at')
                ->get(['id', 'requisition_number', 'estimated_cost', 'request_date']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'requisition_id' => ['required', 'exists:procurement_requisitions,id'],
            'item_description' => ['required', 'string', 'max:2000'],
            'quantity' => ['required', 'numeric', 'min:0.0001'],
            'specifications' => ['nullable', 'string', 'max:5000'],
            'delivery_timeline' => ['nullable', 'string', 'max:100'],
            'delivery_location' => ['nullable', 'string', 'max:500'],
            'submission_deadline' => ['required', 'date_format:d/m/Y'],
            'minimum_suppliers' => ['required', 'integer', 'min:3'],
            'minimum_categories' => ['nullable', 'array'],
            'preferred_categories' => ['nullable', 'array'],
        ], [
            'requisition_id.required' => 'Please select a requisition.',
            'item_description.required' => 'Please enter the item description.',
            'quantity.required' => 'Please enter the quantity.',
            'submission_deadline.required' => 'Please set a submission deadline.',
        ]);

        $validated['submission_deadline'] = \Carbon\Carbon::createFromFormat('d/m/Y', $validated['submission_deadline'])->format('Y-m-d');
        $validated['created_by'] = auth()->id();
        $validated['rfq_number'] = $this->generateRfqNumber();
        $validated['status'] = 'draft';

        $rfq = Rfq::query()->create($validated);

        return redirect()
            ->route('procurement.rfqs.show', $rfq)
            ->with('status', 'RFQ created successfully.');
    }

    public function show(Rfq $rfq): View
    {
        $rfq->load([
            'requisition',
            'suppliers.supplier',
            'quotations.supplier',
            'evaluations.supplier',
            'evaluations.evaluator',
        ]);

        $eligibleSuppliers = [];
        if ($rfq->isDraft()) {
            $eligibleSuppliers = $this->supplierService->getEligibleSuppliersForRfq($rfq);
        }

        $compiledScores = [];
        if ($rfq->isClosed()) {
            $compiledScores = $this->rfqService->compileEvaluationScores($rfq);
        }

        return view('procurement.rfqs.show', compact('rfq', 'eligibleSuppliers', 'compiledScores'));
    }

    public function publish(Rfq $rfq): RedirectResponse
    {
        if (!$rfq->isDraft()) {
            return back()->withErrors(['status' => 'Only draft RFQs can be published.']);
        }

        $invitedCount = RfqSupplier::query()->where('rfq_id', $rfq->id)->count();

        if ($invitedCount < ($rfq->minimum_suppliers ?? 3)) {
            return back()->withErrors(['status' => 'Please invite at least ' . ($rfq->minimum_suppliers ?? 3) . ' suppliers before publishing.']);
        }

        $rfq->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        return back()->with('status', 'RFQ published successfully.');
    }

    public function inviteSuppliers(Request $request, Rfq $rfq): RedirectResponse
    {
        if (!$rfq->isDraft()) {
            return back()->withErrors(['status' => 'Cannot invite suppliers to a published or closed RFQ.']);
        }

        $validated = $request->validate([
            'supplier_ids' => ['required', 'array', 'min:1'],
            'supplier_ids.*' => ['exists:suppliers,id'],
        ]);

        $this->supplierService->inviteSuppliers($rfq, $validated['supplier_ids']);

        return back()->with('status', 'Suppliers invited successfully.');
    }

    public function close(Rfq $rfq): RedirectResponse
    {
        try {
            $this->supplierService->closeRfq($rfq);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', 'RFQ closed. Quotations are now locked.');
    }

    public function submitEvaluation(Request $request, Rfq $rfq): RedirectResponse
    {
        if (!$rfq->isClosed()) {
            return back()->withErrors(['status' => 'Evaluations can only be submitted for closed RFQs.']);
        }

        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'technical_score' => ['required', 'numeric', 'min:0', 'max:30'],
            'comments' => ['nullable', 'string', 'max:2000'],
            'has_conflict_of_interest' => ['required', 'in:0,1'],
            'conflict_of_interest_details' => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['evaluated_by'] = auth()->id();
        $validated['has_conflict_of_interest'] = (bool) $validated['has_conflict_of_interest'];

        try {
            $this->rfqService->submitEvaluation($validated);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', 'Evaluation submitted successfully.');
    }

    public function award(Rfq $rfq): RedirectResponse
    {
        if (!$rfq->isClosed()) {
            return back()->withErrors(['status' => 'RFQ must be closed before award.']);
        }

        $validated = request()->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
        ]);

        try {
            $this->supplierService->awardRfq($rfq, Supplier::query()->findOrFail($validated['supplier_id']));
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', 'Award recommendation created. Pending approval.');
    }

    public function approveAward(Request $request, Rfq $rfq): RedirectResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->rfqService->approveAward($rfq, $validated['notes'] ?? null);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', 'Award approved successfully.');
    }

    public function rejectAward(Request $request, Rfq $rfq): RedirectResponse
    {
        $validated = $request->validate([
            'notes' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $this->rfqService->rejectAward($rfq, $validated['notes']);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', 'Award rejected.');
    }

    private function generateRfqNumber(): string
    {
        $year = now()->year;
        $prefix = "RFQ-{$year}-";

        $lastNumber = Rfq::query()
            ->where('rfq_number', 'like', $prefix . '%')
            ->orderByDesc('rfq_number')
            ->value('rfq_number');

        if ($lastNumber) {
            $parts = explode('-', $lastNumber);
            $lastSeq = (int) end($parts);
        } else {
            $lastSeq = 0;
        }

        $nextSeq = $lastSeq + 1;

        return $prefix . str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
    }
}
