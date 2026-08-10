<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\Finance\PaymentService;
use App\Services\StudentPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FinancePaymentController extends Controller
{
    public function __construct(
        protected StudentPortalService $studentPortal,
        protected PaymentService $payments,
    ) {}

    public function store(Request $request, Invoice $invoice): RedirectResponse
    {
        $student = $this->studentPortal->studentForUser($request->user());
        abort_unless($student && (int) $invoice->student_id === (int) $student->id, 403);
        abort_unless($invoice->isPayable(), 422, 'This invoice cannot be paid.');

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|in:mpesa,bank_transfer,card',
            'phone_number' => 'required_if:payment_method,mpesa|nullable|string|max:20',
            'payment_reference' => 'nullable|string|max:100',
        ]);

        $staffId = (int) (\App\Models\Staff::query()->value('id') ?? 1);

        if ($validated['payment_method'] === 'mpesa' && ! config('finance.mpesa.enabled')) {
            $this->payments->recordPayment($invoice, [
                'amount' => (float) $validated['amount'],
                'payment_method' => 'mpesa',
                'payment_reference' => $validated['payment_reference'] ?? 'SIM-MPESA',
                'transaction_channel_ref' => 'SIM-'.strtoupper(substr(sha1((string) microtime(true)), 0, 10)),
            ], $staffId);

            return redirect()->route('portal.dashboard', ['section' => 'finance'])
                ->with('success', 'Payment recorded successfully. Your balance has been updated.');
        }

        if ($validated['payment_method'] === 'mpesa') {
            $this->payments->initiateMpesaPayment($invoice, (float) $validated['amount'], $validated['phone_number']);

            return redirect()->route('portal.dashboard', ['section' => 'finance'])
                ->with('status', 'M-Pesa payment initiated. You will receive a confirmation once cleared.');
        }

        $this->payments->recordPayment($invoice, [
            'amount' => (float) $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'payment_reference' => $validated['payment_reference'] ?? null,
        ], $staffId);

        return redirect()->route('portal.dashboard', ['section' => 'finance'])
            ->with('success', 'Payment submitted successfully.');
    }
}
