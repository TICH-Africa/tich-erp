@php
    $finance = $portalData['finance'];
    $payableInvoices = $finance['payable_invoices'];
    $selectedInvoice = $payableInvoices->firstWhere('id', (int) request('invoice'))
        ?? $payableInvoices->first();
    $defaultPhone = $finance['default_phone'];
    $mpesaEnabled = (bool) ($finance['mpesa_enabled'] ?? false);
    $pendingStkId = session('mpesa_stk_request_id');
    $pendingStk = $finance['pending_stk_requests']->firstWhere('id', (int) $pendingStkId)
        ?? $finance['pending_stk_requests']->first();
@endphp

<x-page-toolbar title="Finance" meta="Fee accounts, invoices, payments, and M-Pesa self-pay">
    <x-slot:actions>
        @if ($payableInvoices->isNotEmpty())
            <a href="#pay-with-mpesa" class="tich-btn tich-btn-primary">Pay with M-Pesa</a>
        @endif
    </x-slot:actions>
</x-page-toolbar>

@if ($pendingStk)
    <div
        id="mpesa-payment-banner"
        class="tich-card tich-mt-4"
        data-status-url="{{ route('portal.mpesa.stk.status', $pendingStk->id) }}"
        role="status"
        style="border-left: 4px solid var(--tich-primary, #0d6efd); padding: 1rem;"
    >
        M-Pesa prompt sent to {{ $pendingStk->phone }} for
        {{ $pendingStk->invoice?->invoice_number ?? 'your invoice' }}
        (KES {{ number_format((float) $pendingStk->amount, 2) }}).
        Check your phone and enter your PIN…
    </div>
@endif

<div class="tich-grid tich-grid--4 tich-dept-stats tich-mt-8">
    <article class="tich-card tich-stat">
        <p class="tich-caption">Outstanding balance</p>
        <p class="tich-stat__value">KES {{ number_format((float) $finance['summary']['outstanding_balance'], 2) }}</p>
    </article>
    <article class="tich-card tich-stat">
        <p class="tich-caption">Total chargeable</p>
        <p class="tich-stat__value">KES {{ number_format((float) $finance['summary']['total_chargeable'], 2) }}</p>
    </article>
    <article class="tich-card tich-stat">
        <p class="tich-caption">Total paid</p>
        <p class="tich-stat__value">KES {{ number_format((float) $finance['summary']['total_paid'], 2) }}</p>
    </article>
    <article class="tich-card tich-stat">
        <p class="tich-caption">Fee clearance</p>
        <p class="tich-stat__value" style="font-size: 1.125rem;">{{ ucfirst($finance['summary']['fee_clearance_status'] ?? 'pending') }}</p>
    </article>
</div>

@if ($payableInvoices->isNotEmpty())
    <section id="pay-with-mpesa" class="tich-dept-panel tich-mt-8">
        <div class="tich-dept-panel__head">
            <h2 class="tich-h2 tich-dept-panel__title">Pay with M-Pesa</h2>
            <p class="tich-text">Choose an invoice, enter your M-Pesa number, and approve the STK prompt on your phone.</p>
        </div>

        <article class="tich-card tich-mt-4" style="padding: 1.25rem;">
            @if (! $mpesaEnabled)
                <p class="tich-text" style="margin: 0 0 1rem; color: var(--tich-danger, #b42318);">
                    Online M-Pesa payments are not active yet. Contact the finance office or try again later.
                </p>
            @endif

            <form
                id="portal-mpesa-pay-form"
                method="post"
                action="{{ $selectedInvoice ? route('portal.invoices.pay', $selectedInvoice->id) : '#' }}"
                class="tich-grid tich-grid--2"
                style="gap: 1rem; align-items: end;"
                @disabled(! $mpesaEnabled || ! $selectedInvoice)
            >
                @csrf

                <div>
                    <label for="portal-pay-invoice" class="tich-caption">Invoice</label>
                    <select id="portal-pay-invoice" class="tich-input tich-mt-2" required>
                        @foreach ($payableInvoices as $invoice)
                            <option
                                value="{{ $invoice->id }}"
                                data-balance="{{ $invoice->balance }}"
                                data-action="{{ route('portal.invoices.pay', $invoice->id) }}"
                                @selected($selectedInvoice && (int) $selectedInvoice->id === (int) $invoice->id)
                            >
                                {{ $invoice->invoice_number }}
                                · {{ ucwords(str_replace('_', ' ', $invoice->invoice_type)) }}
                                · balance KES {{ number_format((float) $invoice->balance, 2) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="portal-pay-amount" class="tich-caption">Amount (KES)</label>
                    <input
                        id="portal-pay-amount"
                        type="number"
                        name="amount"
                        class="tich-input tich-mt-2"
                        min="1"
                        step="0.01"
                        value="{{ $selectedInvoice ? number_format((float) $selectedInvoice->balance, 2, '.', '') : '' }}"
                        max="{{ $selectedInvoice ? number_format((float) $selectedInvoice->balance, 2, '.', '') : '' }}"
                        required
                    >
                    <p class="tich-caption tich-mt-2">You can pay the full balance or a partial amount.</p>
                </div>

                <div>
                    <label for="portal-pay-phone" class="tich-caption">M-Pesa phone number</label>
                    <input
                        id="portal-pay-phone"
                        type="tel"
                        name="phone_number"
                        class="tich-input tich-mt-2"
                        placeholder="07XXXXXXXX or 2547XXXXXXXX"
                        inputmode="tel"
                        pattern="^(?:\+?254|0)?[17]\d{8}$"
                        title="Kenyan mobile number"
                        value="{{ old('phone_number', $defaultPhone) }}"
                        required
                    >
                </div>

                <div>
                    <input type="hidden" name="payment_method" value="mpesa">
                    <button type="submit" class="tich-btn tich-btn-primary" style="width: 100%;" @disabled(! $mpesaEnabled)>
                        Send M-Pesa prompt
                    </button>
                </div>
            </form>
        </article>
    </section>
@elseif ((float) $finance['summary']['outstanding_balance'] > 0)
    <article class="tich-card tich-mt-8">
        <h2 class="tich-h3">Balance on account</h2>
        <p class="tich-text tich-mt-2">
            You have an outstanding balance of
            <strong>KES {{ number_format((float) $finance['summary']['outstanding_balance'], 2) }}</strong>.
            A payable invoice has not been posted to your portal yet. Contact the finance office if you need to pay now.
        </p>
    </article>
@endif

@if ($finance['accounts']->isNotEmpty())
    <section class="tich-dept-panel tich-mt-8">
        <div class="tich-dept-panel__head">
            <h2 class="tich-h2 tich-dept-panel__title">Fee accounts</h2>
        </div>
        <div class="tich-card tich-table-panel tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Academic year</th>
                        <th>Chargeable</th>
                        <th>Paid</th>
                        <th>Balance</th>
                        <th>Cleared</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($finance['accounts'] as $account)
                        <tr>
                            <td>{{ $account->year_label }}</td>
                            <td>KES {{ number_format((float) $account->total_chargeable, 2) }}</td>
                            <td>KES {{ number_format((float) $account->total_paid, 2) }}</td>
                            <td>KES {{ number_format((float) $account->outstanding_balance, 2) }}</td>
                            <td>{{ $account->is_cleared ? 'Yes' : 'No' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif

@if (($finance['credits']['total'] ?? 0) > 0)
    <section class="tich-dept-panel tich-mt-8">
        <div class="tich-dept-panel__head">
            <h2 class="tich-h2 tich-dept-panel__title">Credits &amp; waivers</h2>
        </div>
        <div class="tich-grid tich-grid--4 tich-mt-4" style="gap: 1rem;">
            @foreach (['scholarship' => 'Scholarship', 'helb' => 'HELB', 'sponsor' => 'Sponsor', 'work_study' => 'Work-study'] as $key => $label)
                @if (($finance['credits'][$key] ?? 0) > 0)
                    <article class="tich-card tich-stat">
                        <p class="tich-caption">{{ $label }}</p>
                        <p class="tich-stat__value" style="font-size: 1.125rem;">KES {{ number_format((float) $finance['credits'][$key], 2) }}</p>
                    </article>
                @endif
            @endforeach
        </div>
    </section>
@endif

@if (! empty($finance['statement']))
    <section class="tich-dept-panel tich-mt-8">
        <div class="tich-dept-panel__head">
            <h2 class="tich-h2 tich-dept-panel__title">Account statement</h2>
            <p class="tich-text">Chronological record of charges and payments on your account.</p>
        </div>
        <div class="tich-card tich-table-panel tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Reference</th>
                        <th>Description</th>
                        <th>Debit</th>
                        <th>Credit</th>
                        <th>Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($finance['statement'] as $entry)
                        <tr>
                            <td>{{ $entry['date'] ? \Illuminate\Support\Carbon::parse($entry['date'])->format('d M Y') : '-' }}</td>
                            <td>{{ $entry['reference'] }}</td>
                            <td>{{ $entry['description'] }}</td>
                            <td>{{ $entry['debit'] > 0 ? 'KES '.number_format($entry['debit'], 2) : '-' }}</td>
                            <td>{{ $entry['credit'] > 0 ? 'KES '.number_format($entry['credit'], 2) : '-' }}</td>
                            <td>KES {{ number_format((float) $entry['running_balance'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif

@if ($finance['invoices']->isNotEmpty())
    <section class="tich-dept-panel tich-mt-8">
        <div class="tich-dept-panel__head">
            <h2 class="tich-h2 tich-dept-panel__title">Invoices</h2>
        </div>
        <div class="tich-card tich-table-panel tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Paid</th>
                        <th>Balance</th>
                        <th>Due</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($finance['invoices'] as $invoice)
                        @php($isPayable = (float) $invoice->balance > 0 && in_array($invoice->status, ['issued', 'partial', 'overdue'], true))
                        <tr>
                            <td>{{ $invoice->invoice_number }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $invoice->invoice_type)) }}</td>
                            <td>{{ $invoice->description ?: '-' }}</td>
                            <td>KES {{ number_format((float) $invoice->amount, 2) }}</td>
                            <td>KES {{ number_format((float) $invoice->amount_paid, 2) }}</td>
                            <td>KES {{ number_format((float) $invoice->balance, 2) }}</td>
                            <td>{{ $invoice->due_date ? \Illuminate\Support\Carbon::parse($invoice->due_date)->format('d M Y') : '-' }}</td>
                            <td>{{ ucfirst($invoice->status) }}</td>
                            <td>
                                @if ($isPayable && $mpesaEnabled)
                                    <a href="{{ route('portal.dashboard', ['section' => 'finance', 'invoice' => $invoice->id]) }}#pay-with-mpesa" class="tich-link">Pay</a>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif

@if ($finance['payments']->isNotEmpty())
    <section class="tich-dept-panel tich-mt-8">
        <div class="tich-dept-panel__head">
            <h2 class="tich-h2 tich-dept-panel__title">Payment history</h2>
        </div>
        <div class="tich-card tich-table-panel tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Receipt</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Reference</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($finance['payments'] as $payment)
                        <tr>
                            <td>{{ $payment->payment_number }}</td>
                            <td>{{ $payment->payment_date ? \Illuminate\Support\Carbon::parse($payment->payment_date)->format('d M Y') : '-' }}</td>
                            <td>KES {{ number_format((float) $payment->amount, 2) }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</td>
                            <td>{{ $payment->payment_reference ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@elseif ($finance['accounts']->isEmpty() && $finance['invoices']->isEmpty())
    @include('partials.states.empty', [
        'title' => 'No financial records yet',
        'description' => 'Your fee account, invoices, and receipts will appear here once the finance office posts them. Current balance: KES ' . number_format((float) $finance['summary']['outstanding_balance'], 2) . '.',
        'icon' => 'inbox',
    ])
@endif

@if ($pendingStk || $payableInvoices->isNotEmpty())
    <script src="{{ asset('js/tich-portal-finance.js') }}" defer></script>
@endif

@if ($pendingStk)
    <script src="{{ asset('js/tich-mpesa-payment.js') }}" defer></script>
@endif
