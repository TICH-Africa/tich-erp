<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\MpesaC2bTransaction;
use App\Services\Finance\FinanceAuditService;
use App\Services\Finance\MpesaC2bService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The paybill desk: every M-Pesa callback we have taken, what became of it, and the
 * receipts that need a person before the money means anything.
 */
class MpesaPaybillController extends Controller
{
    public function __construct(
        protected MpesaC2bService $c2b,
        protected FinanceAuditService $audit,
    ) {}

    public function index(Request $request): View
    {
        [$rows, $filters] = $this->rows($request, paginate: true);

        return view('finance.mpesa.paybill', [
            'rows' => $rows,
            'filters' => $filters,
            'totals' => $this->totals(),
            'paybill' => (string) ($this->c2b->paybill() ?? ''),
            'available' => $this->available(),
        ]);
    }

    public function show(MpesaC2bTransaction $c2bTransaction): View
    {
        $c2bTransaction->load(['student.applicant', 'payment']);

        return view('finance.mpesa.paybill-show', [
            'transaction' => $c2bTransaction,
            'paybill' => (string) ($this->c2b->paybill() ?? ''),
        ]);
    }

    /**
     * The money is Safaricom's; only the student it belongs to is the officer's decision.
     */
    public function reconcile(Request $request, MpesaC2bTransaction $c2bTransaction): RedirectResponse
    {
        $validated = $request->validate([
            'registration_number' => ['required', 'string', 'max:60'],
            'reason' => ['required', 'string', 'min:8', 'max:500'],
        ]);

        $student = $this->c2b->findStudent($validated['registration_number']);

        if (! $student) {
            return back()
                ->withInput()
                ->withErrors(['registration_number' => 'No student on our register carries that admission number.']);
        }

        $before = [
            'status' => $c2bTransaction->status,
            'student_id' => $c2bTransaction->student_id,
            'payment_id' => $c2bTransaction->payment_id,
        ];

        $payment = $this->c2b->reconcile(
            $c2bTransaction,
            $student,
            $validated['reason'],
            (int) ($request->user()?->staff?->id ?? 1),
        );

        $this->audit->log(
            'finance.mpesa.paybill.reconciled',
            'mpesa_c2b_transactions',
            $c2bTransaction->id,
            $before,
            [
                'status' => MpesaC2bTransaction::STATUS_BOOKED,
                'student_id' => $student->id,
                'registration_number' => $student->registration_number,
                'payment_id' => $payment->id,
                'payment_number' => $payment->payment_number,
                'amount' => (float) $c2bTransaction->amount,
                'receipt' => $c2bTransaction->receipt_number,
            ],
            $validated['reason'],
        );

        return redirect()
            ->route('finance.mpesa.paybill.show', $c2bTransaction)
            ->with('success', 'Receipt '.$c2bTransaction->receipt_number.' posted to '
                .$student->registration_number.' as payment '.$payment->payment_number.'.');
    }

    public function note(Request $request, MpesaC2bTransaction $c2bTransaction): RedirectResponse
    {
        $validated = $request->validate([
            'note' => ['required', 'string', 'min:8', 'max:500'],
        ]);

        $before = $c2bTransaction->result_desc;

        $c2bTransaction->update([
            'result_desc' => 'Finance note: '.$validated['note'],
        ]);

        $this->audit->log(
            'finance.mpesa.paybill.noted',
            'mpesa_c2b_transactions',
            $c2bTransaction->id,
            ['result_desc' => $before],
            ['result_desc' => $validated['note']],
            $validated['note'],
        );

        return redirect()
            ->route('finance.mpesa.paybill.show', $c2bTransaction)
            ->with('success', 'Note saved. The receipt stays on the paybill desk until it is posted.');
    }

    public function export(Request $request): StreamedResponse
    {
        [$rows] = $this->rows($request, paginate: false);
        $filename = 'paybill-transactions-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'Admission number',
                'Student name',
                'M-Pesa receipt',
                'Amount (KES)',
                'Payment date and time',
                'Payer phone',
                'Transaction status',
                'Allocation status',
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    (string) ($row->bill_ref_number ?? ''),
                    (string) ($row->student?->displayName() ?? ''),
                    (string) ($row->receipt_number ?? ''),
                    number_format((float) $row->amount, 2),
                    $row->created_at?->format('d M Y H:i') ?? '',
                    (string) ($row->msisdn ?? ''),
                    $row->outcomeLabel(),
                    $row->allocationLabel(),
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return array{0: LengthAwarePaginator|\Illuminate\Support\Collection, 1: array<string, string>}
     */
    private function rows(Request $request, bool $paginate): array
    {
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'status' => (string) $request->query('status', 'all'),
            'callback' => (string) $request->query('callback', MpesaC2bTransaction::KIND_CONFIRMATION),
            'from' => (string) $request->query('from', ''),
            'to' => (string) $request->query('to', ''),
        ];

        if (! $this->available()) {
            return [collect(), $filters];
        }

        $query = MpesaC2bTransaction::query()
            ->with(['student.applicant', 'payment'])
            ->when(
                in_array($filters['callback'], [MpesaC2bTransaction::KIND_CONFIRMATION, MpesaC2bTransaction::KIND_VALIDATION], true),
                fn (Builder $q) => $q->where('kind', $filters['callback'])
            )
            ->when($filters['status'] !== 'all', fn (Builder $q) => $q->where('status', $filters['status']))
            ->when($filters['from'] !== '', fn (Builder $q) => $q->whereDate('created_at', '>=', $filters['from']))
            ->when($filters['to'] !== '', fn (Builder $q) => $q->whereDate('created_at', '<=', Carbon::parse($filters['to'])->toDateString()))
            ->when($filters['search'] !== '', function (Builder $q) use ($filters): void {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $filters['search']).'%';

                $q->where(function (Builder $inner) use ($like, $filters): void {
                    $inner
                        ->where('receipt_number', 'like', $like)
                        ->orWhere('bill_ref_number', 'like', $like)
                        ->orWhere('transaction_id', 'like', $like)
                        ->orWhere('msisdn', 'like', $like)
                        ->orWhereHas('payment', fn (Builder $payment) => $payment->where('payment_number', 'like', $like))
                        ->orWhereHas('student', fn (Builder $student) => $student->search($filters['search']));
                });
            })
            ->orderByDesc('id');

        return [$paginate ? $query->paginate(25)->withQueryString() : $query->get(), $filters];
    }

    /**
     * @return array<string, float|int>
     */
    private function totals(): array
    {
        return $this->c2b->paybillSummary();
    }

    private function available(): bool
    {
        return Schema::hasTable((new MpesaC2bTransaction)->getTable());
    }
}
