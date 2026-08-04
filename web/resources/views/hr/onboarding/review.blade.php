@extends('layouts.hr')

@section('title', 'Review Onboarding - ' . $onboarding->staff->fullName())

@section('hr-content')
    <div class="tich-mb-8">
        <a href="{{ route('hr.staff.show', $onboarding->staff) }}" class="tich-btn tich-btn-ghost">&larr; Back to staff profile</a>
    </div>

    <div class="tich-mb-8">
        <h1 class="tich-h1">Review Onboarding</h1>
        <p class="tich-text tich-mt-2">Review and approve or reject the staff member's onboarding information.</p>
    </div>

    <div class="tich-grid tich-grid--2 tich-mb-8">
        <article class="tich-card">
            <h3 class="tich-h3">Personal Information</h3>
            <div class="tich-mt-4">
                <p><strong>Employee No.:</strong> {{ $onboarding->staff->employee_number }}</p>
                <p><strong>Name:</strong> {{ $onboarding->staff->fullName() }}</p>
                <p><strong>Date of Birth:</strong> {{ $onboarding->staff->date_of_birth?->format('Y-m-d') }}</p>
                <p><strong>Gender:</strong> {{ $onboarding->staff->gender }}</p>
                <p><strong>Marital Status:</strong> {{ $onboarding->staff->marital_status ?? '—' }}</p>
                <p><strong>National ID:</strong> {{ $onboarding->staff->national_id_number ?? '—' }}</p>
            </div>
        </article>

        <article class="tich-card">
            <h3 class="tich-h3">Contact Information</h3>
            <div class="tich-mt-4">
                <p><strong>Primary Email:</strong> {{ $onboarding->staff->primary_email }}</p>
                <p><strong>Organisation Email:</strong> {{ $onboarding->staff->organisation_email }}</p>
                <p><strong>Phone:</strong> {{ $onboarding->staff->phone_number }}</p>
                <p><strong>Postal Address:</strong> {{ $onboarding->staff->postal_address ?? '—' }}</p>
                <p><strong>Physical Address:</strong> {{ $onboarding->staff->physical_address ?? '—' }}</p>
            </div>
        </article>

        <article class="tich-card">
            <h3 class="tich-h3">Emergency Contact</h3>
            <div class="tich-mt-4">
                <p><strong>Name:</strong> {{ $onboarding->staff->emergency_contact_name }}</p>
                <p><strong>Phone:</strong> {{ $onboarding->staff->emergency_contact_phone }}</p>
                <p><strong>Relationship:</strong> {{ $onboarding->staff->emergency_contact_relationship }}</p>
            </div>
        </article>

        <article class="tich-card">
            <h3 class="tich-h3">Statutory Information</h3>
            <div class="tich-mt-4">
                <p><strong>KRA PIN:</strong> {{ $onboarding->staff->kra_pin ?? '—' }}</p>
                <p><strong>NSSF:</strong> {{ $onboarding->staff->nssf_number ?? '—' }}</p>
                <p><strong>SHA:</strong> {{ $onboarding->staff->sha_number ?? '—' }}</p>
                <p><strong>HELB:</strong> {{ $onboarding->staff->helb_number ?? '—' }}</p>
            </div>
        </article>
    </div>

    @if ($onboarding->status === 'pending_hr_review')
        <div class="tich-card tich-mb-8">
            <h3 class="tich-h3">Actions</h3>
            <div class="tich-mt-4">
                <form method="POST" action="{{ route('hr.onboarding.approve', $onboarding) }}" class="tich-d-inline">
                    @csrf
                    <button type="submit" class="tich-btn tich-btn-success">Approve Biodata</button>
                </form>

                <button type="button" onclick="document.getElementById('reject-form').style.display='block'" class="tich-btn tich-btn-danger tich-ml-4">Reject</button>

                <div id="reject-form" style="display: none; margin-top: 1rem;">
                    <form method="POST" action="{{ route('hr.onboarding.reject', $onboarding) }}">
                        @csrf
                        <textarea name="rejection_reason" placeholder="Enter rejection reason..." class="tich-input" rows="3" required></textarea>
                        <button type="submit" class="tich-btn tich-btn-danger tich-mt-2">Confirm Rejection</button>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if ($onboarding->status === 'approved')
        <div class="tich-card">
            <h3 class="tich-h3">Approval Details</h3>
            <div class="tich-mt-4">
                <p><strong>Status:</strong> <span class="tich-badge tich-badge--success">Approved</span></p>
                <p><strong>Reviewed By:</strong> {{ $onboarding->reviewer?->fullName() ?? '—' }}</p>
                <p><strong>Reviewed At:</strong> {{ $onboarding->reviewed_at?->format('Y-m-d H:i') ?? '—' }}</p>
            </div>
        </div>
    @endif

    @if ($onboarding->status === 'rejected')
        <div class="tich-card">
            <h3 class="tich-h3">Rejection Details</h3>
            <div class="tich-mt-4">
                <p><strong>Status:</strong> <span class="tich-badge tich-badge--danger">Rejected</span></p>
                <p><strong>Reason:</strong> {{ $onboarding->rejection_reason }}</p>
                <p><strong>Reviewed By:</strong> {{ $onboarding->reviewer?->fullName() ?? '—' }}</p>
                <p><strong>Reviewed At:</strong> {{ $onboarding->reviewed_at?->format('Y-m-d H:i') ?? '—' }}</p>
            </div>
        </div>
    @endif
@endsection
