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
        meta="Safaricom Daraja STK push credentials, callback URL, and recent payment requests"
    >
        <x-slot:actions>
            <button type="button" class="tich-btn tich-btn-primary" data-open-modal="mpesa-settings-modal">
                Edit settings
            </button>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-grid tich-grid--3 tich-mt-8">
        <article class="tich-card tich-stat">
            <p class="tich-caption">Live STK push</p>
            <p class="tich-stat__value" style="font-size:1.1rem;">
                {{ $settings->is_enabled ? 'Enabled' : 'Disabled' }}
            </p>
            <p class="tich-caption tich-mt-2">
                @if ($settings->is_enabled)
                    Students pay via Safaricom STK push.
                @else
                    Portal uses simulated M-Pesa for testing.
                @endif
            </p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Environment</p>
            <p class="tich-stat__value" style="font-size:1.1rem;">{{ ucfirst($settings->environment) }}</p>
            <p class="tich-caption tich-mt-2">
                Credentials {{ $credentialsReady ? 'ready' : 'incomplete' }}
            </p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Last updated</p>
            <p class="tich-stat__value" style="font-size:1rem;">
                {{ $settings->updated_at?->format('d M Y H:i') ?? 'Never' }}
            </p>
            @if ($settings->updater)
                <p class="tich-caption tich-mt-2">By {{ $settings->updater->fullName() }}</p>
            @endif
        </article>
    </div>

    <article class="tich-card tich-mt-8">
        <h2 class="tich-h3 tich-mb-4">Current configuration</h2>
        <dl style="display:grid; gap:1rem; grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr));">
            <div>
                <dt class="tich-caption">Business shortcode / paybill</dt>
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
            <h2 class="tich-h2 tich-dept-panel__title">Recent STK push requests</h2>
        </div>
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
                        <p class="tich-caption tich-mt-1">When disabled, the student portal simulates M-Pesa payments for testing.</p>
                    </div>

                    <div class="tich-form-group" style="margin: 0;">
                        <label class="tich-label" for="mpesa-environment">Environment</label>
                        <select id="mpesa-environment" name="environment" class="tich-input" required>
                            <option value="sandbox" @selected(old('environment', $settings->environment) === 'sandbox')>Sandbox</option>
                            <option value="production" @selected(old('environment', $settings->environment) === 'production')>Production</option>
                        </select>
                    </div>

                    <div class="tich-form-group" style="margin: 0;">
                        <label class="tich-label" for="mpesa-shortcode">Business shortcode / paybill</label>
                        <input type="text" id="mpesa-shortcode" name="shortcode" class="tich-input" value="{{ old('shortcode', $settings->shortcode) }}" placeholder="174379">
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

    @if ($openEditModal)
        <script>document.body.style.overflow = 'hidden';</script>
    @endif
@endsection
