<?php

namespace App\Http\Controllers\Api\Procurement\mobile;

use App\Http\Controllers\Controller;
use App\Models\Discrepancy;
use App\Models\MpesaStkRequest;
use App\Models\ProcurementInvoice;
use App\Models\ProcurementPayment;
use App\Services\Procurement\PaymentVerificationService;
use App\Services\Procurement\ProcurementMobilePortalService;
use Illuminate\Http\JsonResponse;

class PaymentVerificationController extends Controller
{
    public function __construct(
        protected ProcurementMobilePortalService $portal,
        protected PaymentVerificationService $paymentService,
    ) {}

    public function index(): JsonResponse
    {
        $summary = $this->paymentService->getPaymentSummary();

        return response()->json([
            'stats' => [
                'pending_invoices' => ProcurementInvoice::pendingMatching()->count(),
                'matched' => ProcurementInvoice::where('status', 'matched')->count(),
                'discrepancies' => ProcurementInvoice::withDiscrepancies()->count(),
                'queued_for_finance' => ProcurementInvoice::readyForPayment()->count(),
                'pending_stk' => MpesaStkRequest::where('status', 'pending')->count(),
            ],
            'payment_summary' => [
                'pending' => (float) ($summary['total_pending'] ?? 0),
                'success' => (float) ($summary['total_success'] ?? 0),
                'mpesa' => (float) ($summary['total_mpesa'] ?? 0),
                'bank' => (float) ($summary['total_bank'] ?? 0),
            ],
            'invoices' => ProcurementInvoice::query()
                ->with('supplier')
                ->whereIn('status', ['pending_matching', 'discrepancy_flagged', 'partial_match', 'matched', 'payment_processing'])
                ->orderByDesc('created_at')
                ->limit(25)
                ->get()
                ->map(fn (ProcurementInvoice $i) => $this->portal->serializeInvoice($i))
                ->values(),
            'discrepancies' => Discrepancy::query()
                ->with(['invoice', 'supplier'])
                ->whereIn('status', ['open', 'flagged', 'escalated'])
                ->orderByDesc('created_at')
                ->limit(25)
                ->get()
                ->map(fn (Discrepancy $d) => $this->portal->serializeDiscrepancy($d))
                ->values(),
            'payments' => ProcurementPayment::query()
                ->with(['invoice', 'supplier'])
                ->orderByDesc('created_at')
                ->limit(25)
                ->get()
                ->map(fn (ProcurementPayment $p) => $this->portal->serializePayment($p))
                ->values(),
        ]);
    }
}
