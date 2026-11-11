<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Finance\MpesaSettingsService;
use App\Services\Finance\PaymentService;
use App\Services\StudentPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FinancePaymentController extends Controller
{
    public function __construct(
        protected StudentPortalService $studentPortal,
        protected PaymentService $payments,
        protected MpesaSettingsService $mpesaSettings,
    ) {}

    public function store(Request $request, Invoice $invoice): RedirectResponse
    {
        $student = $this->studentPortal->studentForUser($request->user());
        abort_unless($student && (int) $invoice->student_id === (int) $student->id, 403);
        abort_unless($invoice->isPayable(), 422, 'This invoice cannot be paid.');

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:'.((float) $invoice->balance),
            'payment_method' => 'required|string|in:mpesa,bank_transfer,card',
            'phone_number' => 'required_if:payment_method,mpesa|nullable|string|max:20',
            'payment_reference' => 'nullable|string|max:100',
        ]);

        $staffId = (int) (\App\Models\Staff::query()->value('id') ?? 1);

        if ($validated['payment_method'] === 'mpesa') {
            if (! $this->mpesaSettings->isEnabled()) {
                if (! app()->environment('local')) {
                    return redirect()->route('portal.dashboard', ['section' => 'finance'])
                        ->withErrors(['mpesa' => 'M-Pesa payments are not available right now. Please contact the finance office.']);
                }

                $simRef = 'SIM-'.strtoupper(substr(sha1((string) microtime(true)), 0, 10));

                $payment = $this->payments->recordPayment($invoice, [
                    'amount' => (float) $validated['amount'],
                    'payment_method' => 'mpesa',
                    'payment_reference' => $validated['payment_reference'] ?? $simRef,
                    'transaction_channel_ref' => $simRef,
                ], $staffId);

                if (! $payment->wasRecentlyCreated) {
                    return redirect()->route('portal.dashboard', ['section' => 'finance'])
                        ->with('error', $this->duplicateMessage($payment));
                }

                return redirect()->route('portal.dashboard', ['section' => 'finance'])
                    ->with('success', 'Payment recorded successfully. Your balance has been updated.');
            }

            try {
                $stkRequest = $this->payments->initiateMpesaPayment(
                    $invoice,
                    (float) $validated['amount'],
                    (string) $validated['phone_number'],
                    (int) $student->id,
                );
            } catch (\Throwable $e) {
                return redirect()->route('portal.dashboard', ['section' => 'finance'])
                    ->withErrors(['mpesa' => $e->getMessage()]);
            }

            return redirect()->route('portal.dashboard', ['section' => 'finance'])
                ->with('mpesa_stk_request_id', $stkRequest->id)
                ->with('status', 'M-Pesa prompt sent to '.$stkRequest->phone.'. Enter your PIN on your phone to complete payment.');
        }

        $payment = $this->payments->recordPayment($invoice, [
            'amount' => (float) $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'payment_reference' => $validated['payment_reference'] ?? null,
        ], $staffId);

        if (! $payment->wasRecentlyCreated) {
            return redirect()->route('portal.dashboard', ['section' => 'finance'])
                ->with('error', $this->duplicateMessage($payment));
        }

        return redirect()->route('portal.dashboard', ['section' => 'finance'])
            ->with('success', 'Payment submitted successfully.');
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
}
