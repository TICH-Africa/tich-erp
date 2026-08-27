@extends('layouts.app')

@section('title', 'Application Submitted')
@section('meta_robots', 'noindex,nofollow')

@section('content')
<section class="tich-section tich-careers-page" aria-labelledby="vacancy-confirmation-heading">
    <div class="tich-container">
        <div class="tich-card tich-text-center tich-careers-confirmation">
            <div class="tich-careers-confirmation__icon" aria-hidden="true">✓</div>
            <h1 id="vacancy-confirmation-heading" class="tich-h1">Application Submitted Successfully!</h1>
            <p class="tich-text tich-mt-2">Thank you for applying for the <strong>{{ $application->vacancy->job_title }}</strong> position.</p>

            <div class="tich-form-callout tich-form-callout--muted tich-mt-6 tich-careers-confirmation__summary">
                <p><strong>Application Number:</strong> {{ $application->application_number }}</p>
                <p><strong>Position:</strong> {{ $application->vacancy->job_title }}</p>
                <p><strong>Department:</strong> {{ $application->vacancy->department->dept_name ?? 'General' }}</p>
                <p><strong>Status:</strong> <span class="tich-badge tich-badge--info">Submitted</span></p>
            </div>

            <div class="tich-mt-6">
                <p class="tich-text tich-text--secondary">
                    A confirmation email has been sent to <strong>{{ $application->email }}</strong>.<br>
                    Please save your application number for future reference.
                </p>
            </div>

            <div class="tich-grid tich-grid--2 tich-mt-6">
                <a href="{{ route('vacancies.track') }}" class="tich-btn tich-btn-primary">Track Application Status</a>
                <a href="{{ route('careers.index') }}" class="tich-btn tich-btn-ghost">Browse More Vacancies</a>
            </div>
        </div>
    </div>
</section>
@endsection
