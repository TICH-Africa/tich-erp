@extends('layouts.finance')

@section('title', 'M-Pesa settings')

@section('finance-content')
    <x-page-toolbar
        title="M-Pesa payment settings"
        meta="Safaricom Daraja STK push credentials, callback URL, and recent payment requests"
    />

    <form method="post" action="{{ route('finance.mpesa.settings.update') }}" class="tich-card tich-form-grid tich-mt-8">
        @csrf
        @method('PUT')

        <div class="tich-form-row">
            <label class="tich-label">
                <input type="checkbox" name="is_enabled" value="1" @checked(old('is_enabled', $settings->is_enabled))>
                Enable live M-Pesa STK push payments
            </label>
            <p class="tich-caption tich-mt-2">When disabled, the student portal simulates M-Pesa payments for testing.</p>
        </div>

        <div class="tich-form-row">
            <label class="tich-label" for="environment">Environment</label>
            <select id="environment" name="environment" class="tich-input" required>
                <option value="sandbox" @selected(old('environment', $settings->environment) === 'sandbox')>Sandbox</option>
                <option value="production" @selected(old('environment', $settings->environment) === 'production')>Production</option>
            </select>
        </div>

        <div class="tich-form-row">
            <label class="tich-label" for="shortcode">Business shortcode / paybill</label>
            <input type="text" id="shortcode" name="shortcode" class="tich-input" value="{{ old('shortcode', $settings->shortcode) }}" placeholder="174379">
        </div>

        <div class="tich-form-row">
            <label class="tich-label" for="passkey">Lipa na M-Pesa passkey</label>
            <input type="password" id="passkey" name="passkey" class="tich-input" placeholder="Leave blank to keep current value">
        </div>

        <div class="tich-form-row">
            <label class="tich-label" for="consumer_key">Consumer key</label>
            <input type="text" id="consumer_key" name="consumer_key" class="tich-input" value="{{ old('consumer_key', $settings->consumer_key) }}">
        </div>

        <div class="tich-form-row">
            <label class="tich-label" for="consumer_secret">Consumer secret</label>
            <input type="password" id="consumer_secret" name="consumer_secret" class="tich-input" placeholder="Leave blank to keep current value">
        </div>

        <div class="tich-form-row">
            <label class="tich-label" for="transaction_type">Transaction type</label>
            <select id="transaction_type" name="transaction_type" class="tich-input" required>
                <option value="CustomerPayBillOnline" @selected(old('transaction_type', $settings->transaction_type) === 'CustomerPayBillOnline')>Paybill (CustomerPayBillOnline)</option>
                <option value="CustomerBuyGoodsOnline" @selected(old('transaction_type', $settings->transaction_type) === 'CustomerBuyGoodsOnline')>Till (CustomerBuyGoodsOnline)</option>
            </select>
        </div>

        <div class="tich-form-row">
            <label class="tich-label" for="account_reference_prefix">Account reference prefix</label>
            <input type="text" id="account_reference_prefix" name="account_reference_prefix" class="tich-input" value="{{ old('account_reference_prefix', $settings->account_reference_prefix) }}" maxlength="30" required>
        </div>

        <div class="tich-form-row">
            <label class="tich-label" for="callback_url_override">Callback URL override (optional)</label>
            <input type="url" id="callback_url_override" name="callback_url_override" class="tich-input" value="{{ old('callback_url_override', $settings->callback_url_override) }}" placeholder="{{ $callbackUrl }}">
            <p class="tich-caption tich-mt-2">Default callback: <code>{{ $callbackUrl }}</code></p>
            <p class="tich-caption">Register this URL in the Safaricom Daraja portal for STK push callbacks.</p>
        </div>

        <div>
            <button type="submit" class="tich-btn tich-btn-primary">Save M-Pesa settings</button>
        </div>
    </form>

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
                            <td>{{ $request->invoice?->invoice_number ?? '—' }}</td>
                            <td>{{ $request->student?->displayName() ?? '—' }}</td>
                            <td>KES {{ number_format((float) $request->amount, 2) }}</td>
                            <td>{{ $request->phone }}</td>
                            <td>{{ ucfirst($request->status) }}</td>
                            <td>{{ $request->mpesa_receipt_number ?? '—' }}</td>
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
                            <td colspan="9">No STK push requests yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
