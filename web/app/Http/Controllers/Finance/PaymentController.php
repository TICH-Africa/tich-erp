<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Finance\PaymentService;
use App\Services\Finance\StudentAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $payments,
        protected StudentAccountService $accounts,
    ) {}

    public function index(Request $request): View
    {
        $payments = Payment::query()
            ->with(['student.applicant', 'invoice'])
            ->when($request->filled('method'), fn ($query) => $query->where('payment_method', $request->string('method')))
            ->orderByDesc('created_at')
            ->orderByDesc('payment_date')
            ->paginate(25)
            ->withQueryString();

        return view('finance.payments.index', [
            'payments' => $payments,
            'paymentMethods' => config('finance.payment_methods'),
        ]);
    }

    public function create(Request $request): View
    {
        $invoice = $request->filled('invoice_id')
            ? Invoice::query()->with(['student.applicant'])->findOrFail($request->integer('invoice_id'))
            : null;

        $openInvoices = Invoice::query()
            ->with(['student.applicant'])
            ->whereIn('status', ['issued', 'partial', 'overdue'])
            ->where('balance', '>', 0)
            ->orderByDesc('issue_date')
            ->limit(100)
            ->get();

        $paymentAccounts = ChartOfAccount::query()
            ->where('account_type', 'asset')
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        return view('finance.payments.create', [
            'invoice' => $invoice,
            'openInvoices' => $openInvoices,
            'paymentMethods' => config('finance.payment_methods'),
            'paymentAccounts' => $paymentAccounts,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|in:'.implode(',', array_keys(config('finance.payment_methods'))),
            'payment_reference' => 'nullable|string|max:100',
            'transaction_channel_ref' => 'nullable|string|max:100',
            'payment_date' => 'required|date',
            'payment_account_code' => 'required|string|exists:chart_of_accounts,account_code',
        ]);

        $invoice = Invoice::query()->with('studentAccount')->findOrFail($validated['invoice_id']);
        $staffId = (int) ($request->user()->staff_id ?? \App\Models\Staff::query()->value('id'));

        // The student's own owing, not the invoice balance: an invoice can still show
        // more than the student owes once HELB, scholarship or an earlier overpayment
        // is counted, and quoting the invoice figure would name the wrong excess.
        $owed = $invoice->studentAccount
            ? $this->accounts->amountOwed($invoice->studentAccount)
            : round((float) $invoice->balance, 2);

        $payment = $this->payments->recordPayment($invoice, $validated, $staffId);

        $message = 'Payment recorded: '.$payment->payment_number;
        $carried = round((float) $payment->amount - $owed, 2);
        if ($carried > 0) {
            $message .= ' — KES '.number_format($carried, 2).' is more than the student owes, so it stays on the '
                .$payment->student?->displayName().' account as an excess payment. It carries to the next term or '
                .'semester and comes off their next invoice automatically.';
        }

        return redirect()->route('finance.payments.index')->with('success', $message);
    }
}
