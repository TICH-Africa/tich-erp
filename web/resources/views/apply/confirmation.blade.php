@extends('layouts.app')

@section('title', 'Application submitted')
@section('meta_robots', 'noindex,nofollow')

@section('content')
<section class="tich-section" aria-labelledby="confirmation-heading">
    <div class="tich-container" style="max-width: 36rem; text-align: center;">
        <div class="tich-card">
            <h1 id="confirmation-heading" class="tich-h1">Application submitted</h1>
            <p class="tich-text tich-mt-4">Thank you. Your application has been received and queued for academic department review.</p>

            <p class="tich-stat tich-mt-8">
                <span class="tich-stat__label">Application number</span>
                <span class="tich-stat__value" style="font-size: 1.5rem;">{{ $applicationNumber }}</span>
            </p>

            @if ($email)
                @if (session('application_mail_error'))
                    <p class="tich-caption tich-mt-4" style="color: #c0392b;">
                        We could not send a confirmation email right now. Please save your application number and check status using the link below.
                        @if (config('app.debug'))
                            <br>{{ session('application_mail_error') }}
                        @endif
                    </p>
                @else
                    <p class="tich-caption tich-mt-4">A confirmation email with your application number and status link has been sent to {{ $email }}.</p>
                @endif
            @endif

            <div class="tich-flex-wrap tich-mt-8" style="justify-content: center;">
                <a href="{{ route('apply.status', ['application_number' => $applicationNumber, 'email' => $email]) }}" class="tich-btn tich-btn-primary">Check application status</a>
                <a href="{{ route('programs.index') }}" class="tich-btn tich-btn-blue">Back to programmes</a>
            </div>
        </div>
    </div>
</section>
@endsection
