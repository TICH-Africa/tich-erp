<?php

namespace App\Http\Controllers\Api\Student\mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Student\mobile\Concerns\ResolvesPortalStudent;
use App\Http\Controllers\Api\Student\mobile\Concerns\SerializesStudentPortal;
use App\Models\Invoice;
use App\Models\MpesaStkRequest;
use App\Models\Staff;
use App\Services\Finance\MpesaSettingsService;
use App\Services\Finance\MpesaStkCallbackService;
use App\Services\Finance\PaymentService;
use App\Services\StudentPortalDashboardService;
use App\Services\StudentRecordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    use ResolvesPortalStudent;
    use SerializesStudentPortal;

    public function __construct(
        protected StudentPortalDashboardService $dashboard,
        protected StudentRecordService $studentRecords,
        protected PaymentService $payments,
        protected MpesaSettingsService $mpesaSettings,
        protected MpesaStkCallbackService $callbackService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $student = $this->portalStudent($request);
        $biodata = $this->studentRecords->biodata360($student);
        $portalData = $this->dashboard->forStudent($student, $biodata);

        return response()->json([
            'student' => $this->serializeStudent($student, $biodata),
            'finance' => $this->serializeFinance($portalData),
        ]);
    }

    public function pay(Request $request, Invoice $invoice): JsonResponse
    {
        $student = $this->portalStudent($request);
        abort_unless((int) $invoice->student_id === (int) $student->id, 403);
        abort_unless($invoice->isPayable(), 422, 'This invoice cannot be paid.');

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:'.((float) $invoice->balance),
            'payment_method' => 'required|string|in:mpesa,bank_transfer,card',
            'phone_number' => 'required_if:payment_method,mpesa|nullable|string|max:20',
            'payment_reference' => 'nullable|string|max:100',
        ]);

        $staffId = (int) (Staff::query()->value('id') ?? 1);

        if ($validated['payment_method'] === 'mpesa') {
            if (! $this->mpesaSettings->isEnabled()) {
                if (! app()->environment('local')) {
                    return response()->json([
                        'message' => 'M-Pesa payments are not available right now. Please contact the finance office.',
                    ], 422);
                }

                $this->payments->recordPayment($invoice, [
                    'amount' => (float) $validated['amount'],
                    'payment_method' => 'mpesa',
                    'payment_reference' => $validated['payment_reference'] ?? 'SIM-MPESA',
                    'transaction_channel_ref' => 'SIM-'.strtoupper(substr(sha1((string) microtime(true)), 0, 10)),
                ], $staffId);

                return response()->json([
                    'message' => 'Payment recorded successfully. Your balance has been updated.',
                    'simulated' => true,
                ]);
            }

            try {
                $stkRequest = $this->payments->initiateMpesaPayment(
                    $invoice,
                    (float) $validated['amount'],
                    (string) $validated['phone_number'],
                    (int) $student->id,
                );
            } catch (\Throwable $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return response()->json([
                'message' => 'M-Pesa prompt sent to '.$stkRequest->phone.'. Enter your PIN on your phone to complete payment.',
                'stk_request_id' => $stkRequest->id,
                'phone' => $stkRequest->phone,
            ]);
        }

        $this->payments->recordPayment($invoice, [
            'amount' => (float) $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'payment_reference' => $validated['payment_reference'] ?? null,
        ], $staffId);

        return response()->json(['message' => 'Payment submitted successfully.']);
    }

    public function stkStatus(Request $request, MpesaStkRequest $stkRequest): JsonResponse
    {
        $student = $this->portalStudent($request);
        abort_unless((int) $stkRequest->student_id === (int) $student->id, 403);

        if ($stkRequest->isPending() && $stkRequest->created_at?->lt(now()->subSeconds(45))) {
            $stkRequest = $this->callbackService->reconcilePending($stkRequest);
        }

        return response()->json([
            'status' => $stkRequest->status,
            'result_code' => $stkRequest->result_code,
            'result_desc' => $stkRequest->result_desc,
            'mpesa_receipt_number' => $stkRequest->mpesa_receipt_number,
            'amount' => (float) $stkRequest->amount,
            'invoice_number' => $stkRequest->invoice?->invoice_number,
            'is_complete' => $stkRequest->isTerminal(),
            'is_success' => $stkRequest->status === MpesaStkRequest::STATUS_SUCCESS,
        ]);
    }
}
