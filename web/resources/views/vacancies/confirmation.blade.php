@extends('layouts.app')

@section('title', 'Application Submitted')

@section('content')
<section class="tich-section">
    <div class="tich-container">
        <div class="tich-card tich-text-center" style="padding: 3rem;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">✓</div>
            <h1 class="tich-h1">Application Submitted Successfully!</h1>
            <p class="tich-text tich-mt-2">Thank you for applying for the <strong>{{ $application->vacancy->job_title }}</strong> position.</p>

            <div class="tich-card tich-mt-6" style="background: #f3f4f6; text-align: left;">
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
