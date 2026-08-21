@extends('layouts.administration')

@section('title', 'Automated lifecycle')

@section('administration-content')
    <x-page-toolbar title="Student admission lifecycle" meta="5-step workflow from submission to admission package dispatch">
        <x-slot:actions>
            <a href="{{ $admissionsUrl }}" class="tich-btn tich-btn-secondary">Admissions dashboard</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-stat-row tich-stat-row--5 tich-mt-8">
        <div class="tich-stat">
            <p class="tich-stat__label">1. Submission</p>
            <p class="tich-stat__value">{{ $stats['submission'] }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">2. Academic verification</p>
            <p class="tich-stat__value">{{ $stats['academic_verification'] }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">3. Payment</p>
            <p class="tich-stat__value">{{ $stats['payment'] }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">4. Admin approval</p>
            <p class="tich-stat__value">{{ $stats['admin_approval'] }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">5. Letter generation</p>
            <p class="tich-stat__value">{{ $stats['letter_generation'] }}</p>
        </div>
    </div>

    <article class="tich-card tich-mt-8">
        <h2 class="tich-h3">Workflow model</h2>
        <p class="tich-text tich-mt-3" style="margin-bottom: 0;">
            This lifecycle is coordinated by assigned departments and modules. Wherever department ownership is needed, the same flow applies through the corresponding assigned module in ERP.
        </p>
        <p class="tich-caption tich-mt-3">Total tracked applications: {{ $stats['total'] }}</p>
    </article>

    <article class="tich-card tich-mt-6">
        <h3 class="tich-h3">Stage 1 - Application submission & unique ID generation</h3>
        <p class="tich-text tich-mt-3">
            The process starts when a prospective student submits through the dual-access portal using a multi-step wizard that captures bio-data, academic history, program choices, and supporting documents.
            For non-tech or rural applicants, manual/physical submission support is handled at Administration intake.
        </p>
        <p class="tich-caption tich-mt-3">Roles and ownership</p>
        <ul class="tich-program-card__meta tich-mt-2">
            <li><span class="tich-caption">Applicants</span> submit digital or guided manual applications.</li>
            <li><span class="tich-caption">Administration</span> receives and processes physical intake before forwarding to Academic review.</li>
            <li><span class="tich-caption">ERP</span> auto-generates and logs the unique application ID.</li>
        </ul>
    </article>

    <article class="tich-card tich-mt-6">
        <h3 class="tich-h3">Stage 2 - Academic qualification review & validation</h3>
        <p class="tich-text tich-mt-3">
            Submitted qualifications and uploaded documents move into academic validation. Automated registration filters enforce minimum entry requirements (for example KCSE cluster thresholds), while non-traditional entrants can be evaluated through RPL-equivalent review paths.
        </p>
        <p class="tich-caption tich-mt-3">Roles and ownership</p>
        <ul class="tich-program-card__meta tich-mt-2">
            <li><span class="tich-caption">Academic staff / module reviewers</span> verify entry qualifications.</li>
            <li><span class="tich-caption">Academic Registrar</span> oversees certificate validation and admissions quality control.</li>
            <li><span class="tich-caption">HODs</span> confirm program-fit, waitlist/deferral handling, and RPL alignment.</li>
            <li><span class="tich-caption">Admissions desk / Admin reps</span> cross-check intake packs and compliance completeness.</li>
        </ul>
    </article>

    <article class="tich-card tich-mt-6">
        <h3 class="tich-h3">Stage 3 - Issuing payment instructions</h3>
        <p class="tich-text tich-mt-3">
            Once academically approved, the applicant is moved to billing for application fee collection. ERP automatically issues payment instructions and binds the Stage 1 application ID as the unique reference/account number for payment tracking.
        </p>
        <p class="tich-caption tich-mt-3">Roles and ownership</p>
        <ul class="tich-program-card__meta tich-mt-2">
            <li><span class="tich-caption">ERP</span> transitions the record from academic clearance to payment staging.</li>
            <li><span class="tich-caption">Finance</span> controls fee configuration and invoice generation.</li>
            <li><span class="tich-caption">Administration</span> shares approved program fee structure documents where required.</li>
        </ul>
    </article>

    <article class="tich-card tich-mt-6">
        <h3 class="tich-h3">Stage 4 - Fee payment & verification</h3>
        <p class="tich-text tich-mt-3">
            Applicants pay using the assigned reference, with M-Pesa as the primary gateway. Verification is either automatic through live Daraja settlement updates (priority) or manual reconciliation by Finance officers where required.
        </p>
        <p class="tich-caption tich-mt-3">Roles and ownership</p>
        <ul class="tich-program-card__meta tich-mt-2">
            <li><span class="tich-caption">Finance officers</span> perform manual clearance/reconciliation and receivables checks.</li>
            <li><span class="tich-caption">ERP</span> posts real-time payment status and triggers confirmation notices.</li>
        </ul>
    </article>

    <article class="tich-card tich-mt-6">
        <h3 class="tich-h3">Stage 5 - Official onboarding & admission letter generation</h3>
        <p class="tich-text tich-mt-3">
            After payment confirmation, Administration executes final onboarding. ERP allocates permanent registration numbers using institutional coding parameters and assembles the admission package (course details, center/campus, reporting date, fee structures, bank details, and approved medical forms) for dispatch.
        </p>
        <p class="tich-caption tich-mt-3">Roles and ownership</p>
        <ul class="tich-program-card__meta tich-mt-2">
            <li><span class="tich-caption">Administration</span> authorizes admission package generation and release.</li>
            <li><span class="tich-caption">Admissions desk / Admin reps</span> complete checklist verification and review generated registration numbers.</li>
            <li><span class="tich-caption">ERP</span> compiles, formats, and dispatches letters, attachments, and alerts.</li>
        </ul>
    </article>
@endsection
