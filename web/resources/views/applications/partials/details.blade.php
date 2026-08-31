@php
    $entryQualifications = config('tich-application.entry_qualifications', []);
    $sponsorshipOptions = config('tich-application.sponsorship_options', []);
    $relationshipOptions = config('tich-application.next_of_kin_relationships', []);
    $documentTypes = config('tich-application.document_types', []);
    $genderLabels = [
        'male' => 'Male',
        'female' => 'Female',
        'other' => 'Other',
        'prefer_not_to_say' => 'Prefer not to say',
    ];

    $display = static fn (?string $value, string $fallback = 'Not provided') => filled($value) ? $value : $fallback;
@endphp

<div class="tich-grid tich-grid--2 tich-mt-8" style="align-items: start; gap: 2rem;">
    <article class="tich-card">
        <h2 class="tich-h3">Programme</h2>
        <dl class="tich-mt-4 tich-review-list">
            <div><dt>Application number</dt><dd><strong>{{ $applicant->application_number }}</strong></dd></div>
            <div><dt>Programme</dt><dd>{{ $applicant->program?->program_name ?? '-' }} ({{ $applicant->program?->program_code ?? '-' }})</dd></div>
            <div><dt>Target intake</dt><dd>{{ $applicant->intakeLabel() }}</dd></div>
            <div><dt>Preferred campus</dt><dd>{{ $applicant->preferredCampus?->campus_name ?? 'No preference' }}</dd></div>
            <div><dt>Handling department</dt><dd><strong>{{ $handlingDepartment }}</strong></dd></div>
            <div><dt>Application source</dt><dd>{{ ucfirst(str_replace('_', ' ', $applicant->application_source ?? 'online')) }}</dd></div>
        </dl>
    </article>

    <article class="tich-card">
        <h2 class="tich-h3">Application status</h2>
        <dl class="tich-mt-4 tich-review-list">
            <div><dt>Status</dt><dd>@include('applications.partials.status-badge', ['applicant' => $applicant])</dd></div>
            <div><dt>Submitted</dt><dd>{{ $applicant->created_at?->format('d M Y H:i') ?? '-' }}</dd></div>
            @if ($applicant->reviewed_at)
                <div><dt>Last reviewed</dt><dd>{{ $applicant->reviewed_at->format('d M Y H:i') }}</dd></div>
            @endif
            <div><dt>Application fee</dt><dd>{{ $applicant->application_fee_paid ? 'Paid' : 'Not paid' }}</dd></div>
            @if ($applicant->application_fee_paid)
                <div><dt>Fee paid at</dt><dd>{{ $applicant->application_fee_paid_at?->format('d M Y H:i') ?? '-' }}</dd></div>
                @if ($applicant->application_fee_payment_ref)
                    <div><dt>Payment reference</dt><dd>{{ $applicant->application_fee_payment_ref }}</dd></div>
                @endif
            @endif
            @if ($applicant->student?->registration_number)
                <div><dt>Registration number</dt><dd>{{ $applicant->student->registration_number }}</dd></div>
            @endif
        </dl>
    </article>
</div>

<div class="tich-grid tich-grid--2 tich-mt-8" style="align-items: start; gap: 2rem;">
    <article class="tich-card">
        <h2 class="tich-h3">Personal details</h2>
        <dl class="tich-mt-4 tich-review-list">
            <div><dt>First name</dt><dd>{{ $display($applicant->first_name, '-') }}</dd></div>
            <div><dt>Middle name</dt><dd>{{ $display($applicant->middle_name) }}</dd></div>
            <div><dt>Surname</dt><dd>{{ $display($applicant->surname, '-') }}</dd></div>
            <div><dt>Full name</dt><dd><strong>{{ $applicant->fullName() }}</strong></dd></div>
            <div><dt>Date of birth</dt><dd>{{ $applicant->date_of_birth?->format('d M Y') ?? '-' }}</dd></div>
            <div><dt>Gender</dt><dd>{{ $genderLabels[$applicant->gender ?? ''] ?? ucfirst(str_replace('_', ' ', $applicant->gender ?? '-')) }}</dd></div>
            <div><dt>National ID number</dt><dd>{{ $display($applicant->national_id_number) }}</dd></div>
            <div><dt>Passport number</dt><dd>{{ $display($applicant->passport_number) }}</dd></div>
            <div><dt>Email</dt><dd>{{ $applicant->email }}</dd></div>
            <div><dt>Phone number</dt><dd>{{ $applicant->phone_number }}</dd></div>
            <div><dt>Home county</dt><dd>{{ $display($applicant->home_county) }}</dd></div>
        </dl>
    </article>

    <article class="tich-card">
        <h2 class="tich-h3">Academic qualifications</h2>
        <dl class="tich-mt-4 tich-review-list">
            <div><dt>Entry qualification</dt><dd>{{ $entryQualifications[$applicant->entry_qualification ?? ''] ?? strtoupper($applicant->entry_qualification ?? '-') }}</dd></div>
            <div><dt>KCSE mean grade</dt><dd>{{ $display($applicant->kcse_grade) }}</dd></div>
            <div><dt>KCSE year</dt><dd>{{ $applicant->kcse_year ?: 'Not provided' }}</dd></div>
            <div><dt>Previous institution</dt><dd>{{ $display($applicant->previous_institution) }}</dd></div>
        </dl>
    </article>
</div>

<div class="tich-grid tich-grid--2 tich-mt-8" style="align-items: start; gap: 2rem;">
    <article class="tich-card">
        <h2 class="tich-h3">Sponsorship</h2>
        <dl class="tich-mt-4 tich-review-list">
            <div><dt>Sponsorship type</dt><dd>{{ $sponsorshipOptions[$applicant->sponsorship_type ?? ''] ?? ucfirst(str_replace('_', ' ', $applicant->sponsorship_type ?? '-')) }}</dd></div>
            @if (($applicant->sponsorship_type ?? '') === 'organization' || filled($applicant->sponsor_organization))
                <div><dt>Organization name</dt><dd>{{ $display($applicant->sponsor_organization) }}</dd></div>
                <div><dt>Organization phone</dt><dd>{{ $display($applicant->sponsor_phone) }}</dd></div>
                <div><dt>Organization address</dt><dd>{{ $display($applicant->sponsor_address) }}</dd></div>
            @endif
        </dl>
    </article>

    <article class="tich-card">
        <h2 class="tich-h3">Next of kin</h2>
        <dl class="tich-mt-4 tich-review-list">
            <div><dt>Full name</dt><dd>{{ $display($applicant->next_of_kin_name, '-') }}</dd></div>
            <div><dt>Relationship</dt><dd>{{ $relationshipOptions[$applicant->next_of_kin_relationship ?? ''] ?? ucfirst(str_replace('_', ' ', $applicant->next_of_kin_relationship ?? '-')) }}</dd></div>
            <div><dt>Address</dt><dd>{{ $display($applicant->next_of_kin_address) }}</dd></div>
            <div><dt>Telephone</dt><dd>{{ $display($applicant->next_of_kin_phone, '-') }}</dd></div>
        </dl>
    </article>
</div>

<article class="tich-card tich-mt-8">
    <h2 class="tich-h3">Supporting documents</h2>
    <dl class="tich-mt-4 tich-review-list">
        @php
            $uploadedDocuments = $applicant->documents->keyBy('document_type');
        @endphp
        @foreach ($documentTypes as $type => $label)
            @php($document = $uploadedDocuments->get($type))
            <div>
                <dt>{{ $label }}</dt>
                <dd>
                    @if ($document)
                        {{ $document->original_filename }}
                    @else
                        <span class="tich-caption">Not uploaded</span>
                    @endif
                </dd>
            </div>
        @endforeach
        @if ($applicant->documents->isEmpty() && $documentTypes === [])
            <p class="tich-caption">No document types configured.</p>
        @endif
    </dl>
</article>

@if ($applicant->review_notes || $applicant->rejection_reason)
    <article class="tich-card tich-mt-8">
        <h2 class="tich-h3">Review notes</h2>
        @if ($applicant->review_notes)
            <p class="tich-text tich-mt-4">{{ $applicant->review_notes }}</p>
        @endif
        @if ($applicant->rejection_reason)
            <h3 class="tich-h4 tich-mt-6">Rejection reason</h3>
            <p class="tich-text">{{ $applicant->rejection_reason }}</p>
        @endif
    </article>
@endif

<style>
    .tich-review-list {
        display: grid;
        gap: 0.75rem 1rem;
        margin: 0;
    }

    .tich-review-list > div {
        display: grid;
        grid-template-columns: 11rem 1fr;
        gap: 0.5rem 1rem;
        align-items: start;
    }

    .tich-review-list dt {
        font-size: 0.8125rem;
        color: var(--tich-muted, #6b6e72);
        margin: 0;
    }

    .tich-review-list dd {
        margin: 0;
        font-size: 0.9375rem;
        color: var(--tich-text, #494c50);
    }

    @media (max-width: 640px) {
        .tich-review-list > div {
            grid-template-columns: 1fr;
        }
    }
</style>
