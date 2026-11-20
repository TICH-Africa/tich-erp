@extends('layouts.app')

@section('title', 'Pay application fee')
@section('meta_robots', 'noindex,nofollow')

@section('content')
<section class="tich-section" aria-labelledby="pay-heading">
    <div class="tich-container" style="max-width: 36rem;">
        <h1 id="pay-heading" class="tich-h1">Pay application fee</h1>
        <p class="tich-text tich-mt-4">Use your application number as the M-Pesa account number. Payment is confirmed automatically when Safaricom reports the paybill receipt.</p>

        @if (! $applicant)
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
        @endif

        @if (! empty($lookedUp))
            @if (! $applicant)
                <p class="tich-field-error tich-mt-4">No application found for those details.</p>
            @else
                @php
                    $paybill = (string) ($instructions['paybill'] ?? '');
                    $paybillName = (string) ($instructions['paybill_name'] ?? '');
                    $accountReference = (string) ($instructions['account_reference'] ?? $applicant->application_number);
                @endphp

                <div class="tich-card tich-mt-6">
                    <h2 class="tich-h3">{{ $applicant->application_number }}</h2>
                    <p class="tich-text tich-mt-2">{{ $applicant->fullName() }}</p>
                    <ul class="tich-program-card__meta tich-mt-4">
                        <li><span class="tich-caption">Programme</span> {{ $applicant->program?->program_name ?? '-' }}</li>
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
                        <p class="tich-text tich-mt-4">Your application fee is verified. You will receive further updates by email.</p>
                        <a href="{{ route('apply.status', ['application_number' => $applicant->application_number, 'email' => $applicant->email]) }}" class="tich-btn tich-btn-primary tich-mt-4">Check application status</a>
                    @elseif (! $applicant->canPayApplicationFee())
                        <p class="tich-text tich-mt-4">
                            @if ($applicant->status === 'academic_review')
                                Your application is still under academic review. You can pay the application fee after it is approved.
                            @elseif (in_array($applicant->status, ['submitted_admin', 'submitted'], true))
                                Your application is with Administration. Payment opens after academics approve your application.
                            @else
                                This application is not at the payment stage yet.
                            @endif
                        </p>
                        <a href="{{ route('apply.status', ['application_number' => $applicant->application_number, 'email' => $applicant->email]) }}" class="tich-btn tich-btn-secondary tich-mt-4">Check application status</a>
                    @else
                        <div class="tich-mt-6" id="pay-with-mpesa">
                            <h3 class="tich-h4">Pay the application fee by M-Pesa paybill</h3>
                            <p class="tich-text tich-mt-2">You send the money yourself from M-Pesa. No prompt comes to your phone and you never type your PIN on this site.</p>

                            @if ($paybill === '')
                                <p class="tich-text tich-mt-4" style="color: var(--tich-danger, #b42318);">
                                    The finance office has not published the paybill number yet. Please pay at the finance office for now.
                                </p>
                            @endif

                            <div class="tich-grid tich-grid--2 tich-mt-4" style="gap: 1rem;">
                                <div class="tich-card" style="padding: 1rem;">
                                    <p class="tich-caption" style="margin: 0;">School Paybill Number</p>
                                    <p class="tich-stat__value" id="paybill-number" style="font-size: 1.5rem; margin: .25rem 0 .75rem;">{{ $paybill !== '' ? $paybill : 'Not set' }}</p>
                                    @if ($paybill !== '')
                                        <button type="button" class="tich-btn tich-btn-secondary tich-btn-sm" data-copy-target="paybill-number">Copy paybill number</button>
                                    @endif
                                </div>

                                <div class="tich-card" style="padding: 1rem;">
                                    <p class="tich-caption" style="margin: 0;">Business / Account Name</p>
                                    <p class="tich-stat__value" id="paybill-business-name" style="font-size: 1.125rem; margin: .25rem 0 0;">{{ $paybillName !== '' ? $paybillName : 'The school name given by the finance office' }}</p>
                                </div>

                                <div class="tich-card" style="padding: 1rem;">
                                    <p class="tich-caption" style="margin: 0;">Account Number · your application number</p>
                                    <p class="tich-stat__value" id="paybill-account" style="font-size: 1.5rem; margin: .25rem 0 .75rem;">{{ $accountReference }}</p>
                                    <button type="button" class="tich-btn tich-btn-secondary tich-btn-sm" data-copy-target="paybill-account">Copy account number</button>
                                </div>

                                <div class="tich-card" style="padding: 1rem;">
                                    <p class="tich-caption" style="margin: 0;">Amount</p>
                                    <p class="tich-stat__value" style="font-size: 1.5rem; margin: .25rem 0 0;">KES {{ number_format((float) ($instructions['amount'] ?? 0), 2) }}</p>
                                </div>
                            </div>

                            <ol class="tich-list tich-mt-4" style="padding-left: 1.25rem; line-height: 1.9;">
                                <li>Open M-Pesa on your phone.</li>
                                <li>Select Lipa na M-Pesa.</li>
                                <li>Select Pay Bill.</li>
                                <li>Enter the school's Paybill number.</li>
                                <li>Enter your application number as the account number.</li>
                                <li>Enter the application fee amount.</li>
                                <li>Enter your M-Pesa PIN to authorize the payment.</li>
                                <li>Wait for the M-Pesa confirmation message.</li>
                            </ol>

                            <p class="tich-text tich-mt-4">
                                <strong>Important:</strong> the account number must be your application number
                                exactly as shown above. Payments with any other account number cannot be matched
                                to your application and are returned by Safaricom.
                            </p>

                            <p class="tich-text tich-mt-4" style="margin: 0;">
                                Safaricom reports the receipt to us and your fee is verified on its own - you do not
                                have to send a message or a screenshot. Check this page again a few minutes after you pay.
                            </p>

                            @if ($canSimulate)
                                <form method="POST" action="{{ route('apply.pay.store') }}" class="tich-mt-6">
                                    @csrf
                                    <input type="hidden" name="application_number" value="{{ $applicant->application_number }}">
                                    <input type="hidden" name="email" value="{{ $applicant->email }}">
                                    <button type="submit" class="tich-btn tich-btn-secondary">Record test payment</button>
                                </form>
                            @endif
                        </div>
                    @endif
                </div>
            @endif
        @endif
    </div>
</section>
@if (! empty($instructions))
    <script src="{{ asset('js/tich-copy-value.js') }}" defer></script>
@endif
@endsection
