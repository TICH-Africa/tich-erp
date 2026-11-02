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
use Illuminate\Support\Facades\Cache;

class PaymentVerificationController extends Controller
{
    public function __construct(
        protected ProcurementMobilePortalService $portal,
        protected PaymentVerificationService $paymentService,
    ) {}

    public function index(): JsonResponse
    {
        $payload = Cache::remember('procurement.mobile.payment_verification.v1', 20, function () {
            $summary = $this->paymentService->getPaymentSummary();

            return [
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
                    ->with('supplier:id,supplier_name')
                    ->select([
                        'id', 'invoice_number', 'supplier_id', 'total_amount',
                        'balance', 'status', 'invoice_date', 'created_at',
                    ])
                    ->whereIn('status', ['pending_matching', 'discrepancy_flagged', 'partial_match', 'matched', 'payment_processing'])
                    ->orderByDesc('created_at')
                    ->limit(20)
                    ->get()
                    ->map(fn (ProcurementInvoice $i) => $this->portal->serializeInvoice($i))
                    ->values()
                    ->all(),
                'discrepancies' => Discrepancy::query()
                    ->with(['invoice:id,invoice_number', 'supplier:id,supplier_name'])
                    ->select(['id', 'status', 'field_name', 'deviation_amount', 'invoice_id', 'supplier_id', 'created_at'])
                    ->whereIn('status', ['open', 'flagged', 'escalated'])
                    ->orderByDesc('created_at')
                    ->limit(15)
                    ->get()
                    ->map(fn (Discrepancy $d) => $this->portal->serializeDiscrepancy($d))
                    ->values()
                    ->all(),
                'payments' => ProcurementPayment::query()
                    ->with(['invoice:id,invoice_number', 'supplier:id,supplier_name'])
                    ->select([
                        'id', 'payment_number', 'amount', 'status', 'payment_method',
                        'invoice_id', 'supplier_id', 'payment_date', 'created_at',
                    ])
                    ->orderByDesc('created_at')
                    ->limit(15)
                    ->get()
                    ->map(fn (ProcurementPayment $p) => $this->portal->serializePayment($p))
                    ->values()
                    ->all(),
            ];
        });

        return response()->json($payload);
    }
}
