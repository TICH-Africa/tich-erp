@extends('layouts.app')

@section('title', 'Pay application fee')
@section('meta_robots', 'noindex,nofollow')

@section('content')
<section class="tich-section" aria-labelledby="pay-heading">
    <div class="tich-container" style="max-width: 36rem;">
        <h1 id="pay-heading" class="tich-h1">Pay application fee</h1>
        <p class="tich-text tich-mt-4">Use your application number as the M-Pesa account reference. Payment is confirmed automatically after a successful STK prompt.</p>

        <div class="tich-card tich-mt-8">
            <form method="GET" action="{{ route('apply.pay') }}">
                <div class="tich-form-group">
                    <label for="application_number" class="tich-label">Application number</label>
                    <input type="text" id="application_number" name="application_number" class="tich-input" value="{{ $applicationNumber ?? old('application_number') }}" placeholder="APP-2026-00001" required>
                </div>
                <div class="tich-form-group">
                    <label for="email" class="tich-label">Email address</label>
                    <input type="email" id="email" name="email" class="tich-input" value="{{ $email ?? old('email') }}" required>
                </div>
                <button type="submit" class="tich-btn tich-btn-secondary">Look up application</button>
            </form>
        </div>

        @if (! empty($lookedUp))
            @if (! $applicant)
                <p class="tich-field-error tich-mt-4">No application found for those details.</p>
            @else
                @php
                    $canPay = in_array($applicant->status, ['fee_pending'], true)
                        && $applicant->academic_review_status === 'shortlisted'
                        && ! $applicant->application_fee_paid;
                @endphp

                <div class="tich-card tich-mt-6">
                    <h2 class="tich-h3">{{ $applicant->application_number }}</h2>
                    <p class="tich-text tich-mt-2">{{ $applicant->fullName() }}</p>
                    <ul class="tich-program-card__meta tich-mt-4">
                        <li><span class="tich-caption">Programme</span> {{ $applicant->program?->program_name ?? '-' }}</li>
                        <li><span class="tich-caption">Amount</span> KES {{ number_format((float) ($instructions['amount'] ?? 0), 2) }}</li>
                        <li><span class="tich-caption">Account reference</span> {{ $instructions['account_reference'] ?? $applicant->application_number }}</li>
                        <li><span class="tich-caption">Fee status</span> {{ $applicant->application_fee_paid ? 'Paid' : 'Pending' }}</li>
                        @if ($applicant->application_fee_payment_ref)
                            <li><span class="tich-caption">Receipt</span> {{ $applicant->application_fee_payment_ref }}</li>
                        @endif
                    </ul>

                    @if (session('status'))
                        <p class="tich-text tich-mt-4">{{ session('status') }}</p>
                    @endif

                    @if ($errors->any())
                        <p class="tich-field-error tich-mt-4">{{ $errors->first() }}</p>
                    @endif

                    @if ($applicant->application_fee_paid || $applicant->status === 'paid')
                        <p class="tich-text tich-mt-4">Application fee is verified. Admissions can now complete onboarding.</p>
                        <a href="{{ route('apply.status', ['application_number' => $applicant->application_number, 'email' => $applicant->email]) }}" class="tich-btn tich-btn-primary tich-mt-4">Check application status</a>
                    @elseif (! $canPay)
                        <p class="tich-text tich-mt-4">This application is not yet at the payment stage.</p>
                    @else
                        @if ($stkRequest)
                            <div
                                id="mpesa-payment-banner"
                                class="tich-card tich-mt-4"
                                data-status-url="{{ route('apply.pay.stk.status', $stkRequest->id) }}"
                                data-redirect-url="{{ route('apply.pay', ['application_number' => $applicant->application_number, 'email' => $applicant->email]) }}"
                                role="status"
                                style="border-left: 4px solid var(--tich-primary, #0d6efd); padding: 1rem;"
                            >
                                M-Pesa prompt sent to {{ $stkRequest->phone }} for KES {{ number_format((float) $stkRequest->amount, 2) }}. Check your phone and enter your PIN…
                            </div>
                        @endif

                        <form method="POST" action="{{ route('apply.pay.store') }}" class="tich-mt-6">
                            @csrf
                            <input type="hidden" name="application_number" value="{{ $applicant->application_number }}">
                            <input type="hidden" name="email" value="{{ $applicant->email }}">
                            <div class="tich-form-group">
                                <label for="phone_number" class="tich-label">M-Pesa phone number</label>
                                <input type="text" id="phone_number" name="phone_number" class="tich-input" value="{{ old('phone_number', $applicant->phone_number) }}" required>
                            </div>
                            @if (! $mpesaEnabled && ! app()->environment('local'))
                                <p class="tich-caption">Online M-Pesa is not active yet. Pay using the account reference above, then wait for Finance to confirm.</p>
                            @endif
                            <button type="submit" class="tich-btn tich-btn-primary" @disabled(! $mpesaEnabled && ! app()->environment('local'))>
                                {{ $mpesaEnabled ? 'Pay with M-Pesa' : (app()->environment('local') ? 'Record test payment' : 'M-Pesa unavailable') }}
                            </button>
                        </form>
                    @endif
                </div>
            @endif
        @endif
    </div>
</section>

@if (! empty($stkRequest))
    <script src="{{ asset('js/tich-mpesa-payment.js') }}" defer></script>
@endif
@endsection
