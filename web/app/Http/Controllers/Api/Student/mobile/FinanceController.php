<?php

namespace App\Http\Controllers\Api\Student\mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Student\mobile\Concerns\ResolvesPortalStudent;
use App\Http\Controllers\Api\Student\mobile\Concerns\SerializesStudentPortal;
use App\Models\Invoice;
use App\Models\MpesaStkRequest;
use App\Models\Payment;
use App\Models\Staff;
use App\Services\Finance\MpesaSettingsService;
use App\Services\Finance\MpesaStkCallbackService;
use App\Services\Finance\PaymentService;
use App\Services\Sidebar\StudentSidebarNotificationService;
use App\Services\StudentPortalDashboardService;
use App\Services\StudentRecordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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
        $biodata = $this->mobileBiodata($student);
        $portalData = Cache::remember(
            'student.mobile.finance.'.$student->id.'.v1',
            20,
            fn () => $this->dashboard->financeForMobile($student),
        );

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

                $simRef = 'SIM-'.strtoupper(substr(sha1((string) microtime(true)), 0, 10));

                $payment = $this->payments->recordPayment($invoice, [
                    'amount' => (float) $validated['amount'],
                    'payment_method' => 'mpesa',
                    'payment_reference' => $validated['payment_reference'] ?? $simRef,
                    'transaction_channel_ref' => $simRef,
                ], $staffId);

                app(StudentSidebarNotificationService::class)->forget($student);

                if (! $payment->wasRecentlyCreated) {
                    return response()->json([
                        'message' => $this->duplicateMessage($payment),
                        'recorded' => false,
                        'simulated' => true,
                    ], 422);
                }

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

        $payment = $this->payments->recordPayment($invoice, [
            'amount' => (float) $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'payment_reference' => $validated['payment_reference'] ?? null,
        ], $staffId);

        app(StudentSidebarNotificationService::class)->forget($student);

        if (! $payment->wasRecentlyCreated) {
            return response()->json([
                'message' => $this->duplicateMessage($payment),
                'recorded' => false,
            ], 422);
        }

        return response()->json(['message' => 'Payment submitted successfully.']);
    }

    /**
     * The service answers a repeated submission with the payment already on file instead of
     * booking the money twice, so name that payment rather than claiming new money arrived.
     */
    private function duplicateMessage(Payment $payment): string
    {
        return 'Nothing was added: KES '.number_format((float) $payment->amount, 2)
            .' is already on your account as payment '.$payment->payment_number
            .' dated '.($payment->payment_date?->format('d M Y') ?? 'an earlier date')
            .($payment->payment_reference ? ' under reference '.$payment->payment_reference : '')
            .'. If this is different money, send it with its own reference. If the figure is '
            .'wrong, contact the finance office.';
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
