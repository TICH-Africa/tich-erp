<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Finance\PaymentService;
use App\Services\Finance\StudentAccountService;
use Illuminate\Database\Eloquent\Builder;
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

        $search = trim($request->string('search')->toString());

        $open = Invoice::query()
            ->whereIn('status', ['issued', 'partial', 'overdue'])
            ->where('balance', '>', 0);

        // A bill from an earlier term is still owing, so it has to be reachable from
        // this form. Without a search the list is capped, and the officer would only
        // ever see the newest bills.
        if ('' !== $search) {
            $open->where(function (Builder $query) use ($search) {
                $query->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('student', fn (Builder $studentQuery) => $this->searchStudents($studentQuery, $search));
            });
        }

        $openCount = (clone $open)->count();
        $openInvoices = $open->with(['student.applicant'])
            ->orderByDesc('issue_date')
            ->limit(200)
            ->get();

        $paymentAccounts = ChartOfAccount::query()
            ->where('account_type', 'asset')
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        return view('finance.payments.create', [
            'invoice' => $invoice,
            'openInvoices' => $openInvoices,
            'openCount' => $openCount,
            'search' => $search,
            'paymentMethods' => config('finance.payment_methods'),
            'paymentAccounts' => $paymentAccounts,
        ]);
    }

    /** Name, registration number or programme, the way Finance searches a student elsewhere. */
    private function searchStudents(Builder $query, string $search): void
    {
        $query->where(function (Builder $inner) use ($search) {
            $inner->where('registration_number', 'like', "%{$search}%")
                ->orWhere('first_name', 'like', "%{$search}%")
                ->orWhere('surname', 'like', "%{$search}%")
                ->orWhereHas('applicant', function (Builder $applicantQuery) use ($search) {
                    $applicantQuery->where('first_name', 'like', "%{$search}%")
                        ->orWhere('surname', 'like', "%{$search}%")
                        ->orWhere('application_number', 'like', "%{$search}%");
                });
        });
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

        // The service answers a repeated submission with the payment already on file
        // instead of booking the money twice, so say which payment it found rather
        // than claiming a new one was recorded.
        if (! $payment->wasRecentlyCreated) {
            return redirect()->route('finance.payments.index')->with('error', 'Nothing was added: KES '
                .number_format((float) $payment->amount, 2).' is already booked as payment '
                .$payment->payment_number.' on '.($payment->payment_date?->format('d M Y') ?? 'an earlier date')
                .($payment->payment_reference ? ' under reference '.$payment->payment_reference : '')
                .'. If this is different money, record it with its own reference. If the figure is wrong, edit '
                .$payment->payment_number.' instead.');
        }

        $message = 'Payment recorded: '.$payment->payment_number;
        $carried = round((float) $payment->amount - $owed, 2);
        if ($carried > 0) {
            $message .= ' — KES '.number_format($carried, 2).' is more than the student owes, so it stays on the '
                .$payment->student?->displayName().' account as an excess payment. It carries to the next term or '
                .'semester and comes off their next invoice automatically.';
        }

        return redirect()->route('finance.payments.index')->with('success', $message);
    }

    public function edit(Payment $payment): View
    {
        $payment->load(['invoice.student.applicant', 'student.applicant', 'studentAccount.academicYear']);

        return view('finance.payments.edit', [
            'payment' => $payment,
            'paymentMethods' => config('finance.payment_methods'),
        ]);
    }

    public function update(Request $request, Payment $payment): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|in:'.implode(',', array_keys(config('finance.payment_methods'))),
            'payment_reference' => 'nullable|string|max:100',
            'payment_date' => 'required|date',
        ]);

        $staffId = (int) ($request->user()->staff_id ?? \App\Models\Staff::query()->value('id'));
        $amended = $this->payments->amendPayment($payment, $validated, $staffId);
        $invoice = $amended->invoice;

        $message = 'Payment '.$amended->payment_number.' corrected to KES '.number_format((float) $amended->amount, 2).'.';
        if ($invoice) {
            $message .= ' KES '.number_format((float) $invoice->balance, 2).' now remains on invoice '.$invoice->invoice_number.'.';
        }

        $account = $invoice?->studentAccount ?? $amended->studentAccount;
        $credit = (float) ($account?->credit_balance ?? 0);
        if ($credit > 0) {
            $message .= ' KES '.number_format($credit, 2).' is on the student account as an excess payment; '
                .'it carries to the next term or semester and comes off their next invoice.';
        }

        return $invoice
            ? redirect()->route('finance.invoices.show', $invoice)->with('success', $message)
            : redirect()->route('finance.payments.index')->with('success', $message);
    }
}
