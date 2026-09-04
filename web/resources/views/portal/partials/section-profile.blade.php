@php
    $applicant = $student->applicant;
    $photoUrl = $biodata['identity']['photo_url'] ?? $student->photoUrl();
    $profileChangeRequests = $profileChangeRequests ?? collect();
    $identity = $biodata['identity'] ?? [];
    $contact = $biodata['contact'] ?? [];
    $academic = $biodata['academic'] ?? [];
    $application = $biodata['application'] ?? [];
    $nextOfKin = $biodata['next_of_kin'] ?? [];
    $emergency = $biodata['emergency'] ?? [];
    $enrollment = $biodata['enrollment'] ?? [];
    $portal = $biodata['portal'] ?? [];
@endphp

<x-page-toolbar
    title="My profile"
    meta="{{ $student->registration_number }} · {{ $academic['program'] ?? '' }}"
>
    <x-slot:actions>
        <a href="{{ route('portal.profile.edit') }}" class="tich-btn tich-btn-primary">Update profile</a>
    </x-slot:actions>
</x-page-toolbar>

<p class="tich-caption tich-mt-2">
    View your full record below. Use <strong>Update profile</strong> to change details — contact and next-of-kin save immediately; name, ID, date of birth, and photo require Academic Registrar approval.
</p>

<div class="tich-grid tich-grid--2 tich-mt-8" style="align-items: start; gap: 1.5rem;">
    <article class="tich-card">
        <h2 class="tich-h3">Identity</h2>
        <div class="tich-mt-4" style="display:flex; gap:1rem; align-items:flex-start;">
            <div style="width:5.5rem; height:5.5rem; border-radius:0.75rem; overflow:hidden; background:var(--tich-neutral-100, #f1f5f9); flex-shrink:0; display:flex; align-items:center; justify-content:center;">
                @if ($photoUrl)
                    <img src="{{ $photoUrl }}" alt="Student photo" style="width:100%; height:100%; object-fit:cover;">
                @else
                    <span class="tich-caption">{{ $student->initials() }}</span>
                @endif
            </div>
            <dl style="display: grid; grid-template-columns: 8rem 1fr; gap: 0.4rem 0.75rem; margin: 0; flex:1;">
                <dt class="tich-caption">Full name</dt>
                <dd>{{ $identity['full_name'] ?? '-' }}</dd>
                <dt class="tich-caption">Date of birth</dt>
                <dd>{{ $identity['date_of_birth'] ?? '-' }}</dd>
                <dt class="tich-caption">Gender</dt>
                <dd>{{ $identity['gender'] ?? '-' }}</dd>
                <dt class="tich-caption">Nationality</dt>
                <dd>{{ $identity['nationality'] ?? $contact['nationality'] ?? '-' }}</dd>
                <dt class="tich-caption">National ID</dt>
                <dd>{{ $identity['national_id_number'] ?? '-' }}</dd>
                <dt class="tich-caption">Passport</dt>
                <dd>{{ $identity['passport_number'] ?? '-' }}</dd>
            </dl>
        </div>
    </article>

    <article class="tich-card">
        <h2 class="tich-h3">Contact</h2>
        <dl style="display: grid; grid-template-columns: 9rem 1fr; gap: 0.5rem 1rem; margin: 1rem 0 0;">
            <dt class="tich-caption">Email</dt>
            <dd>{{ $contact['email'] ?? '-' }}</dd>
            <dt class="tich-caption">Phone</dt>
            <dd>{{ $contact['phone_number'] ?? '-' }}</dd>
            <dt class="tich-caption">Home county</dt>
            <dd>{{ $contact['home_county'] ?? '-' }}</dd>
            <dt class="tich-caption">Postal address</dt>
            <dd>{{ $contact['postal_address'] ?? '-' }}</dd>
            <dt class="tich-caption">Nationality</dt>
            <dd>{{ $contact['nationality'] ?? '-' }}</dd>
        </dl>
    </article>

    <article class="tich-card">
        <h2 class="tich-h3">Academic profile</h2>
        <dl style="display: grid; grid-template-columns: 9rem 1fr; gap: 0.5rem 1rem; margin: 1rem 0 0;">
            <dt class="tich-caption">Registration no.</dt>
            <dd>{{ $academic['registration_number'] ?? '-' }}</dd>
            <dt class="tich-caption">Programme</dt>
            <dd>{{ $academic['program'] ?? '-' }}</dd>
            <dt class="tich-caption">Department</dt>
            <dd>{{ $academic['department'] ?? '-' }}</dd>
            <dt class="tich-caption">Year of study</dt>
            <dd>{{ $academic['year_of_study'] ?? '-' }}</dd>
            <dt class="tich-caption">Current semester</dt>
            <dd>{{ $academic['current_semester'] ?? '-' }}</dd>
            <dt class="tich-caption">Cohort / intake</dt>
            <dd>{{ $academic['cohort_intake'] ?? '-' }}</dd>
            <dt class="tich-caption">Entry pathway</dt>
            <dd>{{ $academic['entry_pathway'] ?? '-' }}</dd>
            <dt class="tich-caption">Entry qualification</dt>
            <dd>{{ $academic['entry_qualification'] ?? '-' }}</dd>
        </dl>
    </article>

    <article class="tich-card">
        <h2 class="tich-h3">Enrolment</h2>
        <dl style="display: grid; grid-template-columns: 9rem 1fr; gap: 0.5rem 1rem; margin: 1rem 0 0;">
            <dt class="tich-caption">Status</dt>
            <dd>{{ ucfirst((string) ($enrollment['enrollment_status'] ?? '-')) }}</dd>
            <dt class="tich-caption">Campus</dt>
            <dd>{{ $enrollment['campus'] ?? '-' }}</dd>
            <dt class="tich-caption">Date of admission</dt>
            <dd>{{ $enrollment['date_of_admission'] ?? '-' }}</dd>
            <dt class="tich-caption">Fee clearance</dt>
            <dd>{{ ucfirst((string) ($enrollment['fee_clearance_status'] ?? '-')) }}</dd>
            <dt class="tich-caption">Outstanding balance</dt>
            <dd>{{ number_format((float) ($enrollment['overall_balance'] ?? 0), 2) }}</dd>
            <dt class="tich-caption">Portal activated</dt>
            <dd>{{ $enrollment['portal_activated_at'] ?? '-' }}</dd>
        </dl>
    </article>

    <article class="tich-card">
        <h2 class="tich-h3">Application</h2>
        <dl style="display: grid; grid-template-columns: 9rem 1fr; gap: 0.5rem 1rem; margin: 1rem 0 0;">
            <dt class="tich-caption">Application no.</dt>
            <dd>{{ $application['application_number'] ?? '-' }}</dd>
            <dt class="tich-caption">Status</dt>
            <dd>{{ ucwords(str_replace('_', ' ', (string) ($application['status'] ?? '-'))) }}</dd>
            <dt class="tich-caption">Academic review</dt>
            <dd>{{ ucwords(str_replace('_', ' ', (string) ($application['academic_review_status'] ?? '-'))) }}</dd>
            <dt class="tich-caption">Preferred campus</dt>
            <dd>{{ $application['preferred_campus'] ?? '-' }}</dd>
            <dt class="tich-caption">Intake</dt>
            <dd>
                @if (! empty($application['intake_month']) || ! empty($application['intake_year']))
                    {{ $application['intake_month'] ?? '' }}{{ ! empty($application['intake_month']) && ! empty($application['intake_year']) ? ' ' : '' }}{{ $application['intake_year'] ?? '' }}
                @else
                    -
                @endif
            </dd>
            <dt class="tich-caption">Sponsorship</dt>
            <dd>{{ $application['sponsorship_type'] ?? '-' }}</dd>
            <dt class="tich-caption">Submitted</dt>
            <dd>{{ $application['submitted_at'] ?? '-' }}</dd>
            <dt class="tich-caption">Reviewed</dt>
            <dd>{{ $application['reviewed_at'] ?? '-' }}</dd>
        </dl>
    </article>

    <article class="tich-card">
        <h2 class="tich-h3">Portal account</h2>
        <dl style="display: grid; grid-template-columns: 9rem 1fr; gap: 0.5rem 1rem; margin: 1rem 0 0;">
            <dt class="tich-caption">Account</dt>
            <dd>{{ ! empty($portal['has_account']) ? 'Active' : 'Not linked' }}</dd>
            <dt class="tich-caption">Login name</dt>
            <dd>{{ $portal['name'] ?? '-' }}</dd>
            <dt class="tich-caption">Login email</dt>
            <dd>{{ $portal['email'] ?? '-' }}</dd>
            <dt class="tich-caption">Last login</dt>
            <dd>{{ $portal['last_login_at'] ?? '-' }}</dd>
        </dl>
    </article>

    <article class="tich-card">
        <h2 class="tich-h3">Next of kin</h2>
        <dl style="display: grid; grid-template-columns: 9rem 1fr; gap: 0.5rem 1rem; margin: 1rem 0 0;">
            <dt class="tich-caption">Name</dt>
            <dd>{{ $nextOfKin['name'] ?? '-' }}</dd>
            <dt class="tich-caption">Relationship</dt>
            <dd>{{ $nextOfKin['relationship'] ?? '-' }}</dd>
            <dt class="tich-caption">Phone</dt>
            <dd>{{ $nextOfKin['phone'] ?? '-' }}</dd>
            <dt class="tich-caption">Address</dt>
            <dd>{{ $nextOfKin['address'] ?? '-' }}</dd>
        </dl>
    </article>

    <article class="tich-card">
        <h2 class="tich-h3">Emergency contact</h2>
        <dl style="display: grid; grid-template-columns: 9rem 1fr; gap: 0.5rem 1rem; margin: 1rem 0 0;">
            <dt class="tich-caption">Name</dt>
            <dd>{{ $emergency['name'] ?? '-' }}</dd>
            <dt class="tich-caption">Phone</dt>
            <dd>{{ $emergency['phone'] ?? '-' }}</dd>
            <dt class="tich-caption">Relationship</dt>
            <dd>{{ $emergency['relationship'] ?? '-' }}</dd>
        </dl>
    </article>
</div>

@if ($profileChangeRequests->isNotEmpty())
    <section class="tich-portal-panel tich-mt-8">
        <div class="tich-portal-panel__head">
            <h2 class="tich-h3">Pending / recent change requests</h2>
        </div>
        <div class="tich-card tich-table-panel tich-mt-4">
            <div class="tich-table-wrap">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($profileChangeRequests as $req)
                            <tr>
                                <td>{{ ucfirst(str_replace('_', ' ', $req->request_type)) }}</td>
                                <td>{{ ucfirst($req->status) }}</td>
                                <td>{{ $req->created_at?->format('d M Y H:i') }}</td>
                                <td class="tich-caption">
                                    {{ $req->rejection_reason ?: ($req->reviewer_notes ?: ($req->student_notes ?: '-')) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endif
