@extends('layouts.app')

@section('title', 'Application status')
@section('meta_robots', 'noindex,nofollow')

@section('content')
<section class="tich-section" aria-labelledby="status-heading">
    <div class="tich-container" style="max-width: 36rem;">
        <h1 id="status-heading" class="tich-h1">Check application status</h1>
        <p class="tich-text tich-mt-4">Enter your application number and email address used during submission.</p>

        <div class="tich-card tich-mt-8">
            <form method="POST" action="{{ route('apply.status') }}">
                @csrf
                <div class="tich-form-group">
                    <label for="application_number" class="tich-label">Application number</label>
                    <input type="text" id="application_number" name="application_number" class="tich-input" value="{{ $applicationNumber ?? old('application_number') }}" placeholder="APP-2026-00001" required>
                </div>
                <div class="tich-form-group">
                    <label for="email" class="tich-label">Email address</label>
                    <input type="email" id="email" name="email" class="tich-input" value="{{ $email ?? old('email') }}" required>
                </div>
                <button type="submit" class="tich-btn tich-btn-primary">Look up status</button>
            </form>
        </div>

        @if (!empty($lookedUp))
            @if ($applicant)
                @php
                    $stageLabel = match ($applicant->status) {
                        'submitted_admin', 'submitted' => 'Stage 1: Intake received by Administration',
                        'academic_review' => 'Stage 2: Academic qualification review',
                        'fee_pending' => 'Stage 3: Payment instructions issued',
                        'paid' => 'Stage 4: Fee payment verified',
                        'admitted' => 'Stage 5: Onboarding and admission package issued',
                        'rejected' => 'Application closed (not admitted)',
                        default => ucfirst(str_replace('_', ' ', $applicant->status)),
                    };
                @endphp
                <div class="tich-card tich-mt-6">
                    <h2 class="tich-h3">{{ $applicant->application_number }}</h2>
                    <p class="tich-text tich-mt-2">{{ trim($applicant->first_name.' '.$applicant->surname) }}</p>
                    <ul class="tich-program-card__meta tich-mt-4">
                        <li><span class="tich-caption">Programme</span> {{ $applicant->program?->program_name ?? '-' }}</li>
                        <li><span class="tich-caption">Workflow stage</span> {{ $stageLabel }}</li>
                        <li><span class="tich-caption">Academic review</span> {{ ucfirst(str_replace('_', ' ', $applicant->academic_review_status)) }}</li>
                        <li><span class="tich-caption">Submitted</span> {{ $applicant->created_at?->format('M j, Y g:i A') ?? '-' }}</li>
                        @if ($applicant->reviewed_at)
                            <li><span class="tich-caption">Last updated</span> {{ $applicant->reviewed_at->format('M j, Y g:i A') }}</li>
                        @endif
                        @if ($applicant->rejection_reason)
                            <li><span class="tich-caption">Reason</span> {{ $applicant->rejection_reason }}</li>
                        @endif
                        @if ($applicant->application_fee_paid)
                            <li><span class="tich-caption">Application fee</span> Paid{{ $applicant->application_fee_payment_ref ? ' · '.$applicant->application_fee_payment_ref : '' }}</li>
                        @endif
                    </ul>
                    @if ($applicant->canPayApplicationFee())
                        <a href="{{ route('apply.pay', ['application_number' => $applicant->application_number, 'email' => $applicant->email]) }}" class="tich-btn tich-btn-primary tich-mt-4">Pay application fee</a>
                    @endif
                </div>
            @else
                <p class="tich-field-error tich-mt-4">No application found for those details. Check your application number and email.</p>
            @endif
        @endif
    </div>
</section>
@endsection
