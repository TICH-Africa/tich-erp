@extends('layouts.finance')

@section('title', 'M-Pesa settings')

@section('finance-content')
    @php
        $transactionLabels = [
            'CustomerPayBillOnline' => 'Paybill (CustomerPayBillOnline)',
            'CustomerBuyGoodsOnline' => 'Till (CustomerBuyGoodsOnline)',
        ];
        $secretStatus = fn (?string $value) => filled($value) ? 'Configured (hidden)' : 'Not set';
    @endphp

    <x-page-toolbar
        title="M-Pesa payment settings"
        meta="Student fee paybill, Daraja credentials, paybill callback URLs, and the M-Pesa transaction logs"
    >
        <x-slot:actions>
            <button type="button" class="tich-btn tich-btn-primary" data-open-modal="mpesa-settings-modal">
                Edit settings
            </button>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-grid tich-grid--3 tich-mt-8">
        <article class="tich-card tich-stat">
            <p class="tich-caption">Paybill for student fees</p>
            <p class="tich-stat__value" style="font-size:1.4rem;">
                {{ $settings->shortcode ?: 'Not set' }}
            </p>
            <p class="tich-caption tich-mt-2">
                Students type this number themselves and use their admission number as the account
                number. No prompt goes to their phone.
            </p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Live M-Pesa</p>
            <p class="tich-stat__value" style="font-size:1.1rem;">
                {{ $settings->is_enabled ? 'Enabled' : 'Disabled' }}
            </p>
            <p class="tich-caption tich-mt-2">
                @if ($settings->is_enabled)
                    Paybill money is recorded as Safaricom confirms it, and application fees still
                    send an STK push.
                @else
                    Application fees cannot send an STK push. Paybill payments still land, because
                    Safaricom does not ask us first.
                @endif
            </p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Environment</p>
            <p class="tich-stat__value" style="font-size:1.1rem;">{{ ucfirst($settings->environment) }}</p>
            <p class="tich-caption tich-mt-2">
                Credentials {{ $credentialsReady ? 'ready' : 'incomplete' }}
            </p>
            <p class="tich-caption tich-mt-1">
                Updated {{ $settings->updated_at?->format('d M Y H:i') ?? 'never' }}
                @if ($settings->updater)
                    by {{ $settings->updater->fullName() }}
                @endif
            </p>
        </article>
    </div>

    <article class="tich-card tich-mt-8">
        <h2 class="tich-h3 tich-mb-4">Current configuration</h2>
        <dl style="display:grid; gap:1rem; grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr));">
            <div>
                <dt class="tich-caption">Paybill students type</dt>
                <dd>{{ $settings->shortcode ?: '-' }}</dd>
            </div>
            <div>
                <dt class="tich-caption">Lipa na M-Pesa passkey</dt>
                <dd>{{ $secretStatus($settings->passkey) }}</dd>
            </div>
            <div>
                <dt class="tich-caption">Consumer key</dt>
                <dd>{{ $settings->consumer_key ? \Illuminate\Support\Str::mask($settings->consumer_key, '*', 4) : '-' }}</dd>
            </div>
            <div>
                <dt class="tich-caption">Consumer secret</dt>
                <dd>{{ $secretStatus($settings->consumer_secret) }}</dd>
            </div>
            <div>
                <dt class="tich-caption">Transaction type</dt>
                <dd>{{ $transactionLabels[$settings->transaction_type] ?? $settings->transaction_type }}</dd>
            </div>
            <div>
                <dt class="tich-caption">Account reference prefix</dt>
                <dd><code>{{ $settings->account_reference_prefix }}</code></dd>
                <p class="tich-caption tich-mt-1">
                    Only STK push puts this in front of an account number. Student fee payments arrive
                    with the admission number on its own, which is how we tie the money to the right bill.
                </p>
            </div>
            <div style="grid-column: 1 / -1;">
                <dt class="tich-caption">Callback URL</dt>
                <dd>
                    <code style="word-break: break-all;">{{ $callbackUrl }}</code>
                </dd>
                @if ($settings->callback_url_override)
                    <p class="tich-caption tich-mt-2">Override: <code>{{ $settings->callback_url_override }}</code></p>
                @else
                    <p class="tich-caption tich-mt-2">Register this URL in the Safaricom Daraja portal for STK push callbacks.</p>
                @endif
            </div>
        </dl>
    </article>

    <section class="tich-dept-panel tich-mt-8">
        <div class="tich-dept-panel__head">
            <h2 class="tich-h2 tich-dept-panel__title">Paybill callback URLs</h2>
            @can('finance.payments.manage')
                <form method="POST" action="{{ route('finance.mpesa.settings.register-callbacks') }}" class="tich-inline">
                    @csrf
                    <button type="submit" class="tich-btn tich-btn-secondary tich-btn-sm">Send both to Safaricom</button>
                </form>
            @endcan
        </div>
        <p class="tich-caption">
            Sending them hands both URLs to Safaricom's C2B registration against the shortcode above, so
            nothing has to be typed into the Daraja portal. Copy them and paste them under C2B Admin →
            URL Registration instead if you prefer.
            @if ($callbackSecretSet)
                Sending them appends the configured <code>token</code> secret to each URL itself, so the
                copy below is the URL without it.
            @endif
        </p>
        <div class="tich-card tich-mt-4" style="display:grid; gap:1rem;">
            @foreach ($c2bCallbacks as $callback)
                <div>
                    <p class="tich-caption">{{ $callback['label'] }}</p>
                    <p><code id="c2b-{{ \Illuminate\Support\Str::slug($callback['label']) }}" style="word-break: break-all;">{{ $callback['url'] }}</code></p>
                    <p class="tich-caption tich-mt-1">{{ $callback['hint'] }}</p>
                    <button
                        type="button"
                        class="tich-btn tich-btn-secondary tich-btn-sm tich-mt-2"
                        data-copy-target="c2b-{{ \Illuminate\Support\Str::slug($callback['label']) }}"
                    >Copy {{ \Illuminate\Support\Str::lower($callback['label']) }}</button>
                </div>
            @endforeach
        </div>
    </section>

    <section class="tich-dept-panel tich-mt-8">
        <div class="tich-dept-panel__head">
            <h2 class="tich-h2 tich-dept-panel__title">Paybill transactions</h2>
            <a href="{{ route('finance.mpesa.paybill') }}" class="tich-btn tich-btn-secondary tich-btn-sm">Open the paybill desk</a>
        </div>
        <p class="tich-caption">
            Every question Safaricom asked us, answered. A refused validation bounced the money back to
            the payer; an unmatched confirmation holds money we could not tie to an open bill.
            Search, filter and export them on the paybill desk.
        </p>
        <div class="tich-grid tich-grid--4 tich-mt-4">
            <div class="tich-card tich-stat">
                <p class="tich-caption">Booked</p>
                <p class="tich-stat__value" style="font-size:1.4rem;">KES {{ number_format($paybillSummary['booked'], 2) }}</p>
                <p class="tich-caption tich-mt-2">{{ $paybillSummary['booked_count'] }} payment{{ $paybillSummary['booked_count'] === 1 ? '' : 's' }} posted without anyone typing them.</p>
            </div>
            <div class="tich-card tich-stat">
                <p class="tich-caption">Needs reconciling</p>
                <p class="tich-stat__value" style="font-size:1.4rem;">{{ $paybillSummary['unmatched_count'] }}</p>
                <p class="tich-caption tich-mt-2">Receipts we could not match to a student with an open bill.</p>
            </div>
            <div class="tich-card tich-stat">
                <p class="tich-caption">Already recorded</p>
                <p class="tich-stat__value" style="font-size:1.4rem;">{{ $paybillSummary['duplicate_count'] }}</p>
                <p class="tich-caption tich-mt-2">Safaricom repeated a receipt we had taken; the student was billed once.</p>
            </div>
            <div class="tich-card tich-stat">
                <p class="tich-caption">Refused</p>
                <p class="tich-stat__value" style="font-size:1.4rem;">{{ $paybillSummary['refused_count'] }}</p>
                <p class="tich-caption tich-mt-2">Money returned to the payer before it reached us.</p>
            </div>
        </div>
        @if ($recentPaybill->isNotEmpty())
            <div class="tich-card tich-table-panel tich-mt-4">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Account number</th>
                            <th>Student</th>
                            <th>Receipt</th>
                            <th>Amount</th>
                            <th>Outcome</th>
                            <th>When</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentPaybill as $transaction)
                            <tr>
                                <td><code>{{ $transaction->bill_ref_number ?: '-' }}</code></td>
                                <td>{{ $transaction->student?->displayName() ?? 'Not matched' }}</td>
                                <td>{{ $transaction->receipt_number ?: '-' }}</td>
                                <td>KES {{ number_format((float) $transaction->amount, 2) }}</td>
                                <td>
                                    {{ $transaction->outcomeLabel() }}
                                    @if ($transaction->result_desc)
                                        <p class="tich-caption tich-mt-1">{{ $transaction->result_desc }}</p>
                                    @endif
                                </td>
                                <td>{{ $transaction->created_at?->format('d M Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="tich-dept-panel tich-mt-8">
        <div class="tich-dept-panel__head">
            <h2 class="tich-h2 tich-dept-panel__title">Application fee STK push requests</h2>
        </div>
        <p class="tich-caption">
            Student fees no longer use STK push, so these are the prompts sent to applicants paying the
            application fee.
        </p>
        <div class="tich-card tich-table-panel tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Invoice</th>
                        <th>Student</th>
                        <th>Amount</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Receipt</th>
                        <th>When</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentRequests as $request)
                        <tr>
                            <td>{{ $request->id }}</td>
                            <td>{{ $request->invoice?->invoice_number ?? '-' }}</td>
                            <td>{{ $request->student?->displayName() ?? '-' }}</td>
                            <td>KES {{ number_format((float) $request->amount, 2) }}</td>
                            <td>{{ $request->phone }}</td>
                            <td>{{ ucfirst($request->status) }}</td>
                            <td>{{ $request->mpesa_receipt_number ?? '-' }}</td>
                            <td>{{ $request->created_at?->format('d M Y H:i') }}</td>
                            <td>
                                @if ($request->isPending())
                                    <form method="post" action="{{ route('finance.mpesa.stk.reconcile', $request) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="tich-btn tich-btn-ghost" style="font-size:0.8125rem;">Refresh</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="tich-table-empty">No STK push requests yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div
        id="mpesa-settings-modal"
        class="tich-modal{{ $openEditModal ? ' is-open' : '' }}"
        aria-hidden="{{ $openEditModal ? 'false' : 'true' }}"
        role="dialog"
        aria-modal="true"
        aria-labelledby="mpesa-settings-modal-title"
    >
        <div class="tich-modal__backdrop" data-close-modal="mpesa-settings-modal"></div>
        <div class="tich-modal__dialog" style="max-width: 36rem;">
            <header class="tich-modal__header">
                <h2 id="mpesa-settings-modal-title" class="tich-h3" style="margin: 0;">Edit M-Pesa settings</h2>
                <button type="button" class="tich-modal__close" data-close-modal="mpesa-settings-modal" aria-label="Close">&times;</button>
            </header>
            <form method="post" action="{{ route('finance.mpesa.settings.update') }}" class="tich-modal__body">
                @csrf
                @method('PUT')

                @if ($errors->any())
                    <div class="tich-modal__errors tich-mb-4">
                        <ul style="margin: 0; padding-left: 1.25rem;">
                            @foreach ($errors->all() as $error)
                                <li class="tich-text">{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div style="display: grid; gap: 1rem;">
                    <div class="tich-form-group" style="margin: 0;">
                        <label class="tich-label">
                            <input type="checkbox" name="is_enabled" value="1" @checked(old('is_enabled', $settings->is_enabled))>
                            Enable live M-Pesa STK push payments
                        </label>
                        <p class="tich-caption tich-mt-1">
                            This only drives the application-fee prompt. Student fees go to the paybill
                            number below and are booked from Safaricom's confirmation, switch or not.
                        </p>
                    </div>

                    <div class="tich-form-group" style="margin: 0;">
                        <label class="tich-label" for="mpesa-environment">Environment</label>
                        <select id="mpesa-environment" name="environment" class="tich-input" required>
                            <option value="sandbox" @selected(old('environment', $settings->environment) === 'sandbox')>Sandbox</option>
                            <option value="production" @selected(old('environment', $settings->environment) === 'production')>Production</option>
                        </select>
                    </div>

                    <div class="tich-form-group" style="margin: 0;">
                        <label class="tich-label" for="mpesa-shortcode">Paybill / till students type</label>
                        <input type="text" id="mpesa-shortcode" name="shortcode" class="tich-input" value="{{ old('shortcode', $settings->shortcode) }}" placeholder="174379">
                        <p class="tich-caption tich-mt-1">
                            This is the number shown on the student portal and the fees structure. Students pay
                            fees with their admission number as the account number, so keep it filled.
                        </p>
                    </div>

                    <div class="tich-form-group" style="margin: 0;">
                        <label class="tich-label" for="mpesa-passkey">Lipa na M-Pesa passkey</label>
                        <input type="password" id="mpesa-passkey" name="passkey" class="tich-input" placeholder="{{ filled($settings->passkey) ? 'Leave blank to keep current value' : 'Enter passkey' }}" autocomplete="new-password">
                    </div>

                    <div class="tich-form-group" style="margin: 0;">
                        <label class="tich-label" for="mpesa-consumer-key">Consumer key</label>
                        <input type="text" id="mpesa-consumer-key" name="consumer_key" class="tich-input" value="{{ old('consumer_key', $settings->consumer_key) }}">
                    </div>

                    <div class="tich-form-group" style="margin: 0;">
                        <label class="tich-label" for="mpesa-consumer-secret">Consumer secret</label>
                        <input type="password" id="mpesa-consumer-secret" name="consumer_secret" class="tich-input" placeholder="{{ filled($settings->consumer_secret) ? 'Leave blank to keep current value' : 'Enter consumer secret' }}" autocomplete="new-password">
                    </div>

                    <div class="tich-form-group" style="margin: 0;">
                        <label class="tich-label" for="mpesa-transaction-type">Transaction type</label>
                        <select id="mpesa-transaction-type" name="transaction_type" class="tich-input" required>
                            <option value="CustomerPayBillOnline" @selected(old('transaction_type', $settings->transaction_type) === 'CustomerPayBillOnline')>Paybill (CustomerPayBillOnline)</option>
                            <option value="CustomerBuyGoodsOnline" @selected(old('transaction_type', $settings->transaction_type) === 'CustomerBuyGoodsOnline')>Till (CustomerBuyGoodsOnline)</option>
                        </select>
                    </div>

                    <div class="tich-form-group" style="margin: 0;">
                        <label class="tich-label" for="mpesa-account-prefix">Account reference prefix</label>
                        <input type="text" id="mpesa-account-prefix" name="account_reference_prefix" class="tich-input" value="{{ old('account_reference_prefix', $settings->account_reference_prefix) }}" maxlength="30" required>
                        <p class="tich-caption tich-mt-1">
                            Only STK push prepends this. Student fee payments arrive with the admission
                            number alone, which is what we match the bill against.
                        </p>
                    </div>

                    <div class="tich-form-group" style="margin: 0;">
                        <label class="tich-label" for="mpesa-callback-override">Callback URL override (optional)</label>
                        <input type="url" id="mpesa-callback-override" name="callback_url_override" class="tich-input" value="{{ old('callback_url_override', $settings->callback_url_override) }}" placeholder="{{ $callbackUrl }}">
                        <p class="tich-caption tich-mt-1">Default: <code>{{ $callbackUrl }}</code></p>
                    </div>
                </div>

                <footer class="tich-modal__footer">
                    <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="mpesa-settings-modal">Cancel</button>
                    <button type="submit" class="tich-btn tich-btn-primary">Save settings</button>
                </footer>
            </form>
        </div>
    </div>

    @include('admin.partials.tich-modal-assets')
    <script src="{{ asset('js/tich-copy-value.js') }}" defer></script>

    @if ($openEditModal)
        <script>document.body.style.overflow = 'hidden';</script>
    @endif
@endsection
