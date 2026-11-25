@php
    $finance = $portalData['finance'];
    $payableInvoices = $finance['payable_invoices'];
    $paybill = (string) ($finance['paybill'] ?? '');
    $businessName = (string) ($finance['business_name'] ?? '');
    $admissionNumber = (string) ($finance['admission_number'] ?? '');
    $outstanding = (float) $finance['summary']['outstanding_balance'];
    $excess = (float) ($finance['summary']['excess_paid'] ?? 0);
    $cover = (float) ($finance['credits']['total'] ?? 0);
    // A student who owes money must always get the paybill instructions, even before an invoice row is posted.
    $canPay = $payableInvoices->isNotEmpty() || $outstanding > 0;
@endphp

<x-page-toolbar title="Finance" meta="Fee accounts, invoices, payments, and M-Pesa paybill">
    <x-slot:actions>
        <a href="{{ route('portal.fee-statement.print') }}" target="_blank" rel="noopener" class="tich-btn tich-btn-secondary">Print statement</a>
        <a href="{{ route('portal.fee-statement.pdf') }}" class="tich-btn tich-btn-secondary">Download statement</a>
        <a href="{{ route('portal.fee-structure.pdf') }}" class="tich-btn tich-btn-secondary">Download fees structure</a>
        @if ($canPay)
            <a href="#pay-with-mpesa" class="tich-btn tich-btn-primary">Pay by M-Pesa paybill</a>
        @endif
    </x-slot:actions>
</x-page-toolbar>

@if ($errors->any())
    <div class="tich-alert tich-alert--error tich-mt-4" role="alert">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<div class="tich-grid tich-grid--4 tich-dept-stats tich-mt-8">
    <article class="tich-card tich-stat">
        <p class="tich-caption">Outstanding balance</p>
        <p class="tich-stat__value">KES {{ number_format((float) $finance['summary']['outstanding_balance'], 2) }}</p>
        @if ($cover > 0)
            <p class="tich-caption" style="margin: .25rem 0 0;">Counting KES {{ number_format($cover, 2) }} in credits and approved waivers.</p>
        @endif
        @if ($excess > 0)
            <p class="tich-caption" style="margin: .25rem 0 0;">KES {{ number_format($excess, 2) }} paid above the bills stays on your account and comes off the next one.</p>
        @endif
    </article>
    <article class="tich-card tich-stat">
        <p class="tich-caption">Total chargeable</p>
        <p class="tich-stat__value">KES {{ number_format((float) $finance['summary']['total_chargeable'], 2) }}</p>
        <p class="tich-caption" style="margin: .25rem 0 0;">Every bill on your account, this and earlier terms.</p>
    </article>
    <article class="tich-card tich-stat">
        <p class="tich-caption">Total paid</p>
        <p class="tich-stat__value">KES {{ number_format((float) $finance['summary']['total_paid'], 2) }}</p>
        <p class="tich-caption" style="margin: .25rem 0 0;">Cash the finance office has taken, including paybill.</p>
    </article>
    <article class="tich-card tich-stat">
        <p class="tich-caption">Fee clearance</p>
        <p class="tich-stat__value" style="font-size: 1.125rem;">{{ ucfirst($finance['summary']['fee_clearance_status'] ?? 'pending') }}</p>
    </article>
</div>

@if ($canPay)
    <section id="pay-with-mpesa" class="tich-dept-panel tich-mt-8">
        <div class="tich-dept-panel__head">
            <h2 class="tich-h2 tich-dept-panel__title">Pay fees by M-Pesa paybill</h2>
            <p class="tich-text">You send the money yourself from M-Pesa. No prompt comes to your phone and you never type your PIN on this site.</p>
        </div>

        <article class="tich-card tich-mt-4" style="padding: 1.25rem;">
            @if ($paybill === '')
                <p class="tich-text" style="margin: 0 0 1rem; color: var(--tich-danger, #b42318);">
                    The finance office has not published the paybill number yet. Please pay at the finance office for now.
                </p>
            @endif

            <div class="tich-grid tich-grid--2" style="gap: 1rem;">
                <div class="tich-card" style="padding: 1rem;">
                    <p class="tich-caption" style="margin: 0;">School Paybill Number</p>
                    <p class="tich-stat__value" id="paybill-number" style="font-size: 1.5rem; margin: .25rem 0 .75rem;">{{ $paybill !== '' ? $paybill : 'Not set' }}</p>
                    @if ($paybill !== '')
                        <button type="button" class="tich-btn tich-btn-secondary tich-btn-sm" data-copy-target="paybill-number">Copy paybill number</button>
                    @endif
                </div>

                <div class="tich-card" style="padding: 1rem;">
                    <p class="tich-caption" style="margin: 0;">Business / Account Name</p>
                    <p class="tich-stat__value" id="paybill-business-name" style="font-size: 1.125rem; margin: .25rem 0 .75rem;">{{ $businessName !== '' ? $businessName : 'The school name given by the finance office' }}</p>
                </div>

                <div class="tich-card" style="padding: 1rem;">
                    <p class="tich-caption" style="margin: 0;">Account Number · your admission number</p>
                    <p class="tich-stat__value" id="paybill-account" style="font-size: 1.5rem; margin: .25rem 0 .75rem;">{{ $admissionNumber !== '' ? $admissionNumber : 'Contact the finance office' }}</p>
                    @if ($admissionNumber !== '')
                        <button type="button" class="tich-btn tich-btn-secondary tich-btn-sm" data-copy-target="paybill-account">Copy account number</button>
                    @endif
                </div>

                <div class="tich-card" style="padding: 1rem;">
                    <p class="tich-caption" style="margin: 0;">Payment Method</p>
                    <p class="tich-stat__value" style="font-size: 1.125rem; margin: .25rem 0 0;">Safaricom M-Pesa Paybill</p>
                </div>
            </div>

            <ol class="tich-list tich-mt-4" style="padding-left: 1.25rem; line-height: 1.9;">
                <li>Open M-Pesa on your phone.</li>
                <li>Select Lipa na M-Pesa.</li>
                <li>Select Pay Bill.</li>
                <li>Enter the school's Paybill number.</li>
                <li>Enter your student admission number as the account number.</li>
                <li>Enter the amount you wish to pay.</li>
                <li>Enter your M-Pesa PIN to authorize the payment.</li>
                <li>Wait for the M-Pesa confirmation message.</li>
            </ol>

            <p class="tich-text tich-mt-4">
                <strong>Important:</strong> The account number must always be your actual admission
                number, exactly as shown above. Payments with any other account number cannot be
                matched to you and are returned by Safaricom.
            </p>

            <p class="tich-text tich-mt-4" style="margin: 0;">
                You still owe <strong>KES {{ number_format($outstanding, 2) }}</strong>.
                Pay the whole figure or part of it. Anything you send above what has been billed stays on your account and comes off the next bill.
            </p>

            @if ($payableInvoices->isNotEmpty())
                <div class="tich-grid tich-grid--2 tich-mt-4" style="gap: 1rem;">
                    @foreach ($payableInvoices as $invoice)
                        <p class="tich-caption" style="margin: 0;">
                            {{ $invoice->invoice_number }} · {{ ucwords(str_replace('_', ' ', $invoice->invoice_type)) }} · balance KES {{ number_format((float) $invoice->balance, 2) }}
                        </p>
                    @endforeach
                </div>
            @else
                <p class="tich-caption tich-mt-4" style="margin: 0;">
                    The finance office has not posted a bill for this term yet. The amount above is the balance on your account.
                </p>
            @endif

            <a href="{{ route('portal.fee-structure.pdf') }}" class="tich-btn tich-btn-primary tich-mt-4">Download fees structure</a>
        </article>
    </section>
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
            @foreach (['scholarship' => 'Scholarship', 'helb' => 'HELB', 'sponsor' => 'Sponsor', 'work_study' => 'Work-study', 'waivers' => 'Approved waivers'] as $key => $label)
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
                                @if ($isPayable)
                                    <a href="{{ route('portal.dashboard', ['section' => 'finance']) }}#pay-with-mpesa" class="tich-link">How to pay</a>
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

@if ($canPay && $paybill !== '')
    <script src="{{ asset('js/tich-copy-value.js') }}" defer></script>
@endif
