<?php

namespace App\Http\Controllers\Api\Student\mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Student\mobile\Concerns\ResolvesPortalStudent;
use App\Http\Controllers\Api\Student\mobile\Concerns\SerializesStudentPortal;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Staff;
use App\Models\Student;
use App\Services\Finance\MpesaC2bService;
use App\Services\Finance\PaymentService;
use App\Services\Sidebar\PortalSidebarBroadcastService;
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
        protected MpesaC2bService $c2b,
        protected PortalSidebarBroadcastService $sidebarBroadcast,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $student = $this->portalStudent($request);
        $biodata = $this->mobileBiodata($student);
        $portalData = Cache::remember(
            'student.mobile.finance.'.$student->id.'.v2',
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
            'payment_reference' => 'nullable|string|max:100',
        ]);

        $staffId = (int) (Staff::query()->value('id') ?? 1);

        if ($validated['payment_method'] === 'mpesa') {
            return response()->json(['message' => $this->paybillMessage($student)], 422);
        }

        $payment = $this->payments->recordPayment($invoice, [
            'amount' => (float) $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'payment_reference' => $validated['payment_reference'] ?? null,
        ], $staffId);

        if (! $payment->wasRecentlyCreated) {
            return response()->json([
                'message' => $this->duplicateMessage($payment),
                'recorded' => false,
            ], 422);
        }

        $this->sidebarBroadcast->broadcastStudentFinance($student);

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

    /**
     * M-Pesa fees no longer come back as a prompt, so tell the student exactly how to send
     * the money and which account number Safaricom must see for us to accept it.
     */
    private function paybillMessage(Student $student): string
    {
        $paybill = (string) ($this->c2b->paybill() ?? '');

        if ($paybill === '') {
            return 'M-Pesa fees are paid through the paybill number given by the finance office. '
                .'Use your admission number '.$student->registration_number.' as the account number.';
        }

        return 'Open M-Pesa, choose Lipa na M-Pesa then Paybill, enter '.$paybill
            .' and use your admission number '.$student->registration_number
            .' as the account number. Enter the amount and your PIN. Nothing is prompted to your '
            .'phone and you never type your PIN on this app. The payment shows here once '
            .'Safaricom confirms it.';
    }
}
