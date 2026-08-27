@extends('layouts.app')

@section('title', 'Track Application')
@section('meta_robots', 'noindex,nofollow')

@section('content')
    <x-animated-section animation="fade">
        <section class="tich-section tich-careers-page" id="track-application" aria-labelledby="track-heading">
            <div class="tich-container">
                <div class="tich-mb-8">
                    <h1 id="track-heading" class="tich-h1">Track Your Application</h1>
                    <p class="tich-text tich-mt-2">Enter your application number and email to check your status.</p>
                </div>

                <x-animated-card animation="scale">
                    <div class="tich-card" style="max-width: 600px;">
                        <form method="POST" action="{{ route('vacancies.track') }}">
                            @csrf
                            <div class="tich-mb-4">
                                <label for="application_number" class="tich-label">Application Number *</label>
                                <input type="text" id="application_number" name="application_number" value="{{ old('application_number', $applicationNumber) }}" required class="tich-input">
                            </div>
                            <div class="tich-mb-4">
                                <label for="email" class="tich-label">Email Address *</label>
                                <input type="email" id="email" name="email" value="{{ old('email', $email) }}" required class="tich-input">
                            </div>
                            <button type="submit" class="tich-btn tich-btn-primary">Track Status</button>
                        </form>
                    </div>
                </x-animated-card>

                @if ($applicant)
                    <x-animated-card animation="bottom">
                        <div class="tich-card tich-mt-8">
                            <h2 class="tich-h2">Application Status</h2>
                            <div class="tich-mt-4">
                                <p><strong>Application Number:</strong> {{ $applicant->application_number }}</p>
                                <p><strong>Position:</strong> {{ $applicant->vacancy->job_title ?? 'N/A' }}</p>
                                <p><strong>Applied On:</strong> {{ $applicant->created_at->format('M j, Y') }}</p>
                                <p><strong>Status:</strong>
                                    @if ($applicant->status == 'submitted')
                                        <span class="tich-badge tich-badge--info">Submitted</span>
                                    @elseif ($applicant->status == 'under_review')
                                        <span class="tich-badge tich-badge--warning">Under Review</span>
                                    @elseif ($applicant->status == 'shortlisted')
                                        <span class="tich-badge tich-badge--success">Shortlisted</span>
                                    @elseif ($applicant->status == 'rejected')
                                        <span class="tich-badge tich-badge--danger">Not Selected</span>
                                    @elseif ($applicant->status == 'offered')
                                        <span class="tich-badge tich-badge--success">Offer Made</span>
                                    @else
                                        <span class="tich-badge">{{ ucfirst($applicant->status) }}</span>
                                    @endif
                                </p>
                                @if ($applicant->decision_notes)
                                    <p><strong>Notes:</strong> {{ $applicant->decision_notes }}</p>
                                @endif
                            </div>
                        </div>
                    </x-animated-card>
                @endif
            </div>
        </section>
    </x-animated-section>
@endsection
