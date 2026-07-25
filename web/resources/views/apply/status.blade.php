@extends('layouts.app')

@section('title', 'Application status')

@section('content')
<section class="tich-section">
    <div class="tich-container" style="max-width: 36rem;">
        <h1 class="tich-h1">Check application status</h1>
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
                <div class="tich-card tich-mt-6">
                    <h2 class="tich-h3">{{ $applicant->application_number }}</h2>
                    <p class="tich-text tich-mt-2">{{ trim($applicant->first_name.' '.$applicant->surname) }}</p>
                    <ul class="tich-program-card__meta tich-mt-4">
                        <li><span class="tich-caption">Programme</span> {{ $applicant->program?->program_name ?? '-' }}</li>
                        <li><span class="tich-caption">Status</span> {{ ucfirst(str_replace('_', ' ', $applicant->status)) }}</li>
                        <li><span class="tich-caption">Academic review</span> {{ ucfirst(str_replace('_', ' ', $applicant->academic_review_status)) }}</li>
                        <li><span class="tich-caption">Submitted</span> {{ $applicant->created_at?->format('M j, Y g:i A') ?? '-' }}</li>
                        @if ($applicant->reviewed_at)
                            <li><span class="tich-caption">Last updated</span> {{ $applicant->reviewed_at->format('M j, Y g:i A') }}</li>
                        @endif
                        @if ($applicant->rejection_reason)
                            <li><span class="tich-caption">Reason</span> {{ $applicant->rejection_reason }}</li>
                        @endif
                    </ul>
                </div>
            @else
                <p class="tich-field-error tich-mt-4">No application found for those details. Check your application number and email.</p>
            @endif
        @endif
    </div>
</section>
@endsection
