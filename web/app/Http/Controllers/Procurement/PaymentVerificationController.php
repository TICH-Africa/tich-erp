<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Models\ProcurementInvoice;
use App\Models\ProcurementPayment;
use App\Models\ThreeWayMatch;
use App\Models\Discrepancy;
use App\Models\CreditNote;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Staff;
use App\Models\MpesaStkRequest;
use App\Services\Procurement\MatchingEngineService;
use App\Services\Procurement\PaymentVerificationService;
use App\Services\Procurement\DiscrepancyService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class PaymentVerificationController extends Controller
{
    public function __construct(
        private MatchingEngineService $matchingEngine,
        private PaymentVerificationService $paymentService,
        private DiscrepancyService $discrepancyService,
    ) {}

    public function index(): View
    {
        $stats = [
            ['label' => 'Pending Invoices', 'value' => ProcurementInvoice::pendingMatching()->count(), 'tone' => 'neutral'],
            ['label' => 'Fully Matched', 'value' => ProcurementInvoice::where('status', 'matched')->count(), 'tone' => 'success'],
            ['label' => 'Discrepancies', 'value' => ProcurementInvoice::withDiscrepancies()->count(), 'tone' => 'warning'],
            ['label' => 'Queued for Finance', 'value' => ProcurementInvoice::readyForPayment()->count(), 'tone' => 'info'],
        ];

        $paymentSummary = $this->paymentService->getPaymentSummary();
        $summary = [
            ['label' => 'Fully matched', 'value' => $stats[1]['value'], 'tone' => 'success'],
            ['label' => 'Discrepancies', 'value' => $stats[2]['value'], 'tone' => 'warning'],
            ['label' => 'Queued for finance', 'value' => $stats[3]['value'], 'tone' => 'neutral'],
            ['label' => 'M-Pesa STK prompts', 'value' => MpesaStkRequest::where('status', 'pending')->count(), 'tone' => 'info'],
        ];

        $workflowStages = [
            'Invoice received and logged',
            'PO and quotation retrieved',
            'Automated field comparison',
            'Discrepancy review and supplier response',
            'Matched invoice routed to Finance',
            'M-Pesa STK confirmation and ledger update',
        ];

        return view('procurement.payment-verification.index', compact('summary', 'workflowStages', 'paymentSummary', 'stats'));
    }

    public function matching(): View
    {
        $records = ThreeWayMatch::with(['invoice', 'purchaseOrder', 'rfqQuotation', 'discrepancies'])->latest()->paginate(25);
        $suppliers = Supplier::active()->get();
        $purchaseOrders = PurchaseOrder::with('supplier')->where('status', 'approved')->get();

        return view('procurement.payment-verification.matching', compact('records', 'suppliers', 'purchaseOrders'));
    }

    public function discrepancies(): View
    {
        $discrepancies = Discrepancy::with(['invoice', 'supplier', 'threeWayMatch', 'raisedBy', 'resolvedBy'])->latest()->paginate(25);
        $invoices = ProcurementInvoice::with('supplier')->whereIn('status', ['pending_matching', 'discrepancy_flagged', 'partial_match'])->get();
        $suppliers = Supplier::active()->get();

        return view('procurement.payment-verification.discrepancies', compact('discrepancies', 'invoices', 'suppliers'));
    }

    public function payments(): View
    {
        $payments = ProcurementPayment::with(['invoice', 'supplier', 'recordedBy'])->latest()->paginate(25);
        $invoices = ProcurementInvoice::with('supplier')->whereIn('status', ['matched', 'partial_match', 'payment_processing'])->get();
        $suppliers = Supplier::active()->get();

        return view('procurement.payment-verification.payments', compact('payments', 'invoices', 'suppliers'));
    }

    public function mpesaStk(): View
    {
        $stkPushes = MpesaStkRequest::with(['procurementInvoice', 'payment'])->latest()->paginate(25);
        $workflowSteps = [
            'Finance officer selects M-Pesa as payment method',
            'System validates supplier phone number and amount',
            'STK push is sent to the supplier',
            'Daraja webhook confirms payment and updates the ledger',
        ];

        return view('procurement.payment-verification.mpesa-stk', compact('stkPushes', 'workflowSteps'));
    }

    public function auditTrail(): View
    {
        $events = \App\Models\PaymentAudit::latest()->paginate(50);
        if ($events->isEmpty()) {
            $events = collect([
                ['time' => now()->format('H:i'), 'actor' => 'System', 'action' => 'Module initialized', 'result' => 'Payment verification schema active'],
            ]);
        }

        return view('procurement.payment-verification.audit-trail', compact('events'));
    }

    public function createInvoice(): View
    {
        $suppliers = Supplier::limit(20)->get();
        $purchaseOrders = PurchaseOrder::with('supplier')->limit(20)->get();
        $rfqs = \App\Models\RfqQuotation::with('rfq')->where('status', 'locked')->limit(20)->get();

        return view('procurement.payment-verification.invoice-form', compact('suppliers', 'purchaseOrders', 'rfqs'));
    }

    public function storeInvoice(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'invoice_number' => 'required|string|max:50|unique:procurement_invoices,invoice_number',
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'rfq_id' => 'nullable|exists:rfq_quotations,id',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date',
            'subtotal' => 'required|numeric|min:0',
            'tax_amount' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'retention_percent' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
        ]);

        $retentionPercent = $validated['retention_percent'] ?? 0;
        $retentionAmount = round($validated['total_amount'] * ($retentionPercent / 100), 2);

        $invoice = ProcurementInvoice::create([
            'invoice_number' => $validated['invoice_number'],
            'supplier_id' => $validated['supplier_id'],
            'purchase_order_id' => $validated['purchase_order_id'],
            'rfq_id' => $validated['rfq_id'] ?? null,
            'requisition_id' => PurchaseOrder::find($validated['purchase_order_id'])->requisition_id ?? null,
            'invoice_date' => $validated['invoice_date'],
            'due_date' => $validated['due_date'] ?? null,
            'subtotal' => $validated['subtotal'],
            'tax_amount' => $validated['tax_amount'],
            'total_amount' => $validated['total_amount'],
            'balance' => $validated['total_amount'],
            'status' => 'pending_matching',
            'retention_percent' => $retentionPercent,
            'retention_amount' => $retentionAmount,
            'notes' => $validated['notes'] ?? null,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('procurement.payment-verification.matching')->with('success', "Invoice {$invoice->invoice_number} created and pending matching.");
    }

    public function updateInvoice(Request $request, ProcurementInvoice $invoice): RedirectResponse
    {
        $validated = $request->validate([
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date',
            'subtotal' => 'required|numeric|min:0',
            'tax_amount' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'retention_percent' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
        ]);

        $retentionPercent = $validated['retention_percent'] ?? $invoice->retention_percent;
        $retentionAmount = round($validated['total_amount'] * ($retentionPercent / 100), 2);

        $invoice->update([
            'invoice_date' => $validated['invoice_date'],
            'due_date' => $validated['due_date'] ?? null,
            'subtotal' => $validated['subtotal'],
            'tax_amount' => $validated['tax_amount'],
            'total_amount' => $validated['total_amount'],
            'retention_percent' => $retentionPercent,
            'retention_amount' => $retentionAmount,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('procurement.payment-verification.matching')->with('success', "Invoice {$invoice->invoice_number} updated.");
    }

    public function matchInvoice(ProcurementInvoice $invoice): RedirectResponse
    {
        if ($invoice->status !== 'pending_matching') {
            return redirect()->back()->with('error', 'Only pending invoices can be matched.');
        }

        $match = $this->matchingEngine->run($invoice);

        if ($match->status === 'discrepancy') {
            $this->discrepancyService->raise($invoice, $match, [
                'type' => 'general',
                'deviation_amount' => $match->deviation_amount,
            ]);
        }

        $this->logAudit($invoice, Auth::user()?->name ?? 'System', 'Three-way match executed', "Status: {$match->status}, Deviation: KES {$match->deviation_amount}");

        return redirect()->route('procurement.payment-verification.matching')->with('success', "Matching complete for {$invoice->invoice_number}: {$match->status}");
    }

    public function resolveDiscrepancy(Request $request, Discrepancy $discrepancy): RedirectResponse
    {
        $this->authorize('manage_matching');

        $this->discrepancyService->resolve($discrepancy, $request->only(['resolution_note', 'resolution_type', 'supplier_response']));

        $invoice = $discrepancy->invoice;
        $this->logAudit($invoice, Auth::user()?->name ?? 'System', 'Discrepancy resolved', "Resolution: {$request->input('resolution_type')}, Note: {$request->input('resolution_note')}");

        return redirect()->route('procurement.payment-verification.discrepancies')->with('success', 'Discrepancy resolved.');
    }

    public function flagDiscrepancy(Request $request, Discrepancy $discrepancy): RedirectResponse
    {
        $this->authorize('manage_matching');

        $this->discrepancyService->flagForReview($discrepancy);

        $invoice = $discrepancy->invoice;
        $this->logAudit($invoice, Auth::user()?->name ?? 'System', 'Discrepancy flagged for review', 'Suspicion noted');

        return redirect()->route('procurement.payment-verification.discrepancies')->with('success', 'Discrepancy flagged for review.');
    }

    public function supersedeInvoice(Request $request, ProcurementInvoice $invoice): RedirectResponse
    {
        $this->authorize('manage_invoices');

        $invoice->update(['status' => 'superseded']);

        $this->logAudit($invoice, Auth::user()?->name ?? 'System', 'Invoice superseded', $request->input('notes', 'Superseded by new invoice'));

        return redirect()->route('procurement.payment-verification.matching')->with('success', "Invoice {$invoice->invoice_number} superseded.");
    }

    public function acceptDiscrepancy(Discrepancy $discrepancy): RedirectResponse
    {
        $this->authorize('manage_matching');

        $this->discrepancyService->resolve($discrepancy, [
            'resolution_type' => 'accepted',
            'resolution_note' => 'Supplier explanation accepted by procurement officer.',
            'supplier_response' => 'Accepted',
        ]);

        $invoice = $discrepancy->invoice;
        $this->logAudit($invoice, Auth::user()?->name ?? 'System', 'Accepted supplier explanation', 'Resolution documented');

        return redirect()->route('procurement.payment-verification.discrepancies')->with('success', 'Supplier explanation accepted.');
    }

    public function rejectDiscrepancy(Discrepancy $discrepancy): RedirectResponse
    {
        $this->authorize('manage_matching');

        $this->discrepancyService->escalate($discrepancy, [
            'escalation_note' => 'Supplier explanation rejected.',
            'escalated_to' => Auth::user()?->name ?? 'Finance',
        ]);

        $invoice = $discrepancy->invoice;
        $this->logAudit($invoice, Auth::user()?->name ?? 'System', 'Rejected supplier explanation', 'Escalated for investigation');

        return redirect()->route('procurement.payment-verification.discrepancies')->with('success', 'Discrepancy rejected and escalated.');
    }

    public function escalateDiscrepancy(Request $request, Discrepancy $discrepancy): RedirectResponse
    {
        $this->authorize('manage_matching');

        $this->discrepancyService->escalate($discrepancy, $request->only(['escalated_to', 'escalation_note']));

        $invoice = $discrepancy->invoice;
        $this->logAudit($invoice, Auth::user()?->name ?? 'System', 'Discrepancy escalated', $request->input('escalation_note', 'Escalated'));

        return redirect()->route('procurement.payment-verification.discrepancies')->with('success', 'Discrepancy escalated.');
    }

    public function processPayment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:procurement_invoices,id',
            'payment_method' => 'required|in:mpesa,bank_transfer,cheque,flutterwave',
            'payment_reference' => 'nullable|string|max:100',
        ]);

        $invoice = ProcurementInvoice::findOrFail($validated['invoice_id']);
        $payment = $this->paymentService->routeToFinance($invoice, $validated['payment_method'], $validated['payment_reference']);

        $this->logAudit($invoice, Auth::user()?->name ?? 'System', 'Payment routed to Finance', "Method: {$validated['payment_method']}, Amount: KES {$payment->amount}, Ref: {$payment->payment_number}");

        if ($payment->status === 'stk_pending') {
            return redirect()->route('procurement.payment-verification.mpesa-stk')->with('success', "M-Pesa STK push initiated for {$invoice->invoice_number}.");
        }

        return redirect()->route('procurement.payment-verification.payments')->with('success', "Payment initiated for {$invoice->invoice_number}.");
    }

    public function mpesaStkCallback(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'checkout_request_id' => 'required|string',
            'result_code' => 'required|string',
            'result_desc' => 'required|string',
            'merchant_request_id' => 'nullable|string',
            'mpesa_receipt_number' => 'nullable|string',
        ]);

        $stkRequest = MpesaStkRequest::where('checkout_request_id', $validated['checkout_request_id'])->first();

        if (! $stkRequest) {
            return redirect()->back()->with('error', 'STK request not found.');
        }

        $success = $this->paymentService->processCallback($stkRequest, $validated['result_code'], $validated['result_desc']);

        $invoice = $stkRequest->procurementInvoice;
        if ($invoice) {
            $this->logAudit($invoice, Auth::user()?->name ?? 'System', 'M-Pesa callback processed', "Result: {$validated['result_code']} - {$validated['result_desc']}");
        }

        if ($success) {
            return redirect()->route('procurement.payment-verification.mpesa-stk')->with('success', 'Payment confirmed via M-Pesa.');
        }

        return redirect()->route('procurement.payment-verification.mpesa-stk')->with('error', 'M-Pesa payment failed.');
    }

    public function releaseHoldback(Request $request, ProcurementPayment $payment): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        $this->paymentService->releaseHoldback($payment, $validated['amount']);

        $invoice = $payment->invoice;
        $this->logAudit($invoice, Auth::user()?->name ?? 'System', 'Holdback released', "Amount: KES {$validated['amount']}");

        return redirect()->route('procurement.payment-verification.payments')->with('success', 'Holdback released successfully.');
    }

    private function logAudit(ProcurementInvoice $invoice, string $actor, string $action, string $result): void
    {
        \App\Models\PaymentAudit::create([
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'actor' => $actor,
            'action' => $action,
            'result' => $result,
            'created_at' => now(),
        ]);
    }
}
