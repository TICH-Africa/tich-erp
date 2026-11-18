@extends('layouts.finance')

@section('title', 'Paybill receipt')

@section('finance-content')
    @php
        $payload = is_array($transaction->payload) ? $transaction->payload : [];
        $needsReconciling = in_array($transaction->status, [
            \App\Models\MpesaC2bTransaction::STATUS_UNMATCHED,
            'received',
        ], true) && $transaction->kind === \App\Models\MpesaC2bTransaction::KIND_CONFIRMATION;
        $facts = [
            'Amount reported' => $transaction->amount !== null ? 'KES '.number_format((float) $transaction->amount, 2) : '-',
            'M-Pesa receipt' => $transaction->receipt_number ?: '-',
            'Account number typed' => $transaction->bill_ref_number ?: 'None typed',
            'Student matched' => $transaction->student?->displayName() ?? 'No student matched',
            'Payer phone' => $transaction->msisdn ?: '-',
            'Safaricom transaction id' => $transaction->transaction_id ?: '-',
            'Local transaction id' => $transaction->local_trxn_id ?: '-',
            'Invoice reference sent' => $transaction->invoice_number ?: 'None sent',
            'Callback' => ucfirst($transaction->kind),
            'Outcome' => $transaction->outcomeLabel(),
            'Allocation' => $transaction->allocationLabel(),
            'Payment' => $transaction->payment?->payment_number ?: '-',
            'Result code' => $transaction->result_code ?: '-',
            'Received' => $transaction->created_at?->format('d M Y H:i') ?? '-',
        ];
    @endphp

    <x-page-toolbar
        title="Paybill receipt"
        meta="What Safaricom reported, and what we did with it"
    >
        <x-slot:actions>
            <a href="{{ route('finance.mpesa.paybill') }}" class="tich-btn tich-btn-ghost">Back to the paybill desk</a>
        </x-slot:actions>
    </x-page-toolbar>

    <article class="tich-card tich-mt-8">
        <h2 class="tich-h3 tich-mb-4">Transaction</h2>
        <table class="tich-admin-table">
            <tbody>
                @foreach ($facts as $label => $value)
                    <tr>
                        <th>{{ $label }}</th>
                        <td>{{ $value }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @if ($transaction->result_desc)
            <p class="tich-caption tich-mt-4">{{ $transaction->result_desc }}</p>
        @endif
    </article>

    @if ($needsReconciling)
        <article class="tich-card tich-mt-8">
            <h2 class="tich-h3 tich-mb-2">Reconcile this receipt</h2>
            <p class="tich-caption">
                @if ($paybill !== '')
                    Money arrived on paybill {{ $paybill }} with an account number we could not bill
                    against. Only an officer with payment rights can post it, and the amount stays
                    exactly as Safaricom reported it.
                @else
                    Money arrived that we could not bill against. Only an officer with payment rights
                    can post it, and the amount stays exactly as Safaricom reported it.
                @endif
            </p>

            @can('finance.payments.manage')
                <form method="POST" action="{{ route('finance.mpesa.paybill.reconcile', $transaction) }}" class="tich-mt-4">
                    @csrf
                    <div class="uf-field">
                        <label for="registration_number">Admission number to bill <span class="uf-req">*</span></label>
                        <input
                            type="text"
                            id="registration_number"
                            name="registration_number"
                            value="{{ old('registration_number', $transaction->bill_ref_number ?? '') }}"
                            required
                            class="{{ $errors->has('registration_number') ? 'is-invalid' : '' }}"
                        >
                        @error('registration_number')
                            <span class="uf-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="uf-field tich-mt-4">
                        <label for="reason">Why it belongs to this student <span class="uf-req">*</span></label>
                        <textarea
                            id="reason"
                            name="reason"
                            rows="3"
                            required
                            class="{{ $errors->has('reason') ? 'is-invalid' : '' }}"
                        >{{ old('reason') }}</textarea>
                        @error('reason')
                            <span class="uf-error">{{ $message }}</span>
                        @enderror
                        <span class="uf-hint">The amount is taken exactly as Safaricom reported it.</span>
                    </div>
                    <button type="submit" class="tich-btn tich-btn-primary tich-mt-4">Post to this student</button>
                </form>
            @else
                <p class="tich-caption tich-mt-4">
                    Your Finance role can read this receipt but not post it. Ask an officer with
                    payment rights.
                </p>
            @endcan
        </article>
    @endif

    <article class="tich-card tich-mt-8">
        <h2 class="tich-h3 tich-mb-2">Officer note</h2>
        <p class="tich-caption">
            A note keeps the receipt on the desk for later. It changes no money and is audited.
        </p>
        @can('finance.payments.manage')
            <form method="POST" action="{{ route('finance.mpesa.paybill.note', $transaction) }}" class="tich-mt-4">
                @csrf
                <div class="uf-field">
                    <label for="note">What you found out <span class="uf-req">*</span></label>
                    <textarea
                        id="note"
                        name="note"
                        rows="3"
                        required
                        class="{{ $errors->has('note') ? 'is-invalid' : '' }}"
                    >{{ old('note') }}</textarea>
                    @error('note')
                        <span class="uf-error">{{ $message }}</span>
                    @enderror
                </div>
                <button type="submit" class="tich-btn tich-btn-secondary tich-mt-4">Save note</button>
            </form>
        @endcan
    </article>

    <article class="tich-card tich-mt-8">
        <h2 class="tich-h3 tich-mb-4">Exactly what Safaricom sent</h2>
        <pre class="tich-caption" style="white-space: pre-wrap; word-break: break-all;">{{ json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    </article>
@endsection
