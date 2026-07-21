@extends('layouts.approval')

@section('approval-content')
    <a href="{{ route('admissions.applications.index') }}" class="tich-link">&larr; All applications</a>

    <div class="tich-mt-4" style="display: flex; flex-wrap: wrap; justify-content: space-between; gap: 1rem; align-items: start;">
        <div>
            <h1 class="tich-h1" style="font-size: 2rem;">{{ $applicant->fullName() }}</h1>
            <p class="tich-text">{{ $applicant->application_number }} · {{ $applicant->email }}</p>
        </div>
        <div>@include('admissions.partials.status-badge', ['applicant' => $applicant])</div>
    </div>

    @if (session('status'))
        <p class="tich-text tich-mt-4" style="color: var(--tich-green);">{{ session('status') }}</p>
    @endif

    @if (session('application_mail_error'))
        <p class="tich-text tich-mt-4" style="color: #c0392b;">
            The applicant notification email could not be sent.
            @if (config('app.debug'))
                {{ session('application_mail_error') }}
            @endif
        </p>
    @endif

    @if ($errors->any())
        <div class="tich-card tich-mt-4" style="border-color: #c0392b;">
            <ul style="margin: 0; padding-left: 1.25rem;">
                @foreach ($errors->all() as $error)
                    <li class="tich-text">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="tich-grid tich-grid--2 tich-mt-8" style="align-items: start; gap: 2rem;">
        <article class="tich-card">
            <h2 class="tich-h3">Application details</h2>
            <dl class="tich-mt-4" style="display: grid; grid-template-columns: 10rem 1fr; gap: 0.75rem 1rem; margin: 0;">
                <dt class="tich-caption">Handling department</dt>
                <dd class="tich-text" style="margin: 0;"><strong>{{ $handlingDepartment }}</strong></dd>

                <dt class="tich-caption">Programme</dt>
                <dd class="tich-text" style="margin: 0;">{{ $applicant->program?->program_name ?? '—' }} ({{ $applicant->program?->program_code }})</dd>

                <dt class="tich-caption">Preferred campus</dt>
                <dd class="tich-text" style="margin: 0;">{{ $applicant->preferredCampus?->campus_name ?? '—' }}</dd>

                <dt class="tich-caption">Entry qualification</dt>
                <dd class="tich-text" style="margin: 0;">{{ strtoupper($applicant->entry_qualification ?? '—') }}</dd>

                <dt class="tich-caption">Date of birth</dt>
                <dd class="tich-text" style="margin: 0;">{{ $applicant->date_of_birth?->format('d M Y') ?? '—' }}</dd>

                <dt class="tich-caption">Gender</dt>
                <dd class="tich-text" style="margin: 0;">{{ ucfirst(str_replace('_', ' ', $applicant->gender ?? '—')) }}</dd>

                <dt class="tich-caption">Phone</dt>
                <dd class="tich-text" style="margin: 0;">{{ $applicant->phone_number }}</dd>

                <dt class="tich-caption">County</dt>
                <dd class="tich-text" style="margin: 0;">{{ $applicant->home_county ?? '—' }}</dd>

                <dt class="tich-caption">ID / Passport</dt>
                <dd class="tich-text" style="margin: 0;">{{ $applicant->national_id_number ?? $applicant->passport_number ?? '—' }}</dd>

                <dt class="tich-caption">Submitted</dt>
                <dd class="tich-text" style="margin: 0;">{{ $applicant->created_at?->format('d M Y H:i') ?? '—' }}</dd>

                @if ($applicant->reviewed_at)
                    <dt class="tich-caption">Last reviewed</dt>
                    <dd class="tich-text" style="margin: 0;">{{ $applicant->reviewed_at->format('d M Y H:i') }}</dd>
                @endif
            </dl>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3">Review notes</h2>
            @if ($applicant->review_notes)
                <p class="tich-text tich-mt-4">{{ $applicant->review_notes }}</p>
            @else
                <p class="tich-caption tich-mt-4">No review notes yet.</p>
            @endif

            @if ($applicant->rejection_reason)
                <h3 class="tich-h3 tich-mt-6">Rejection reason</h3>
                <p class="tich-text">{{ $applicant->rejection_reason }}</p>
            @endif

            @if ($applicant->academic_review_status === 'shortlisted')
                <div class="tich-mt-6" style="padding: 1rem; background: rgba(108, 171, 51, 0.08); border-left: 3px solid var(--tich-green, #6cab33);">
                    <p class="tich-text" style="margin: 0;">
                        <strong>Shortlisted.</strong> Applicant notified to pay admission fee once Finance creates the invoice.
                    </p>
                </div>
            @endif
        </article>
    </div>

    @include('admissions.partials.document-viewer', ['applicant' => $applicant])

    @unless ($applicant->isFinalized())
        <div class="tich-grid tich-grid--3 tich-mt-8" style="align-items: start; gap: 2rem;">
            @can('admissions.write')
            <article class="tich-card">
                <h2 class="tich-h3">Shortlist</h2>
                <p class="tich-text tich-mb-4">Mark as shortlisted and notify the applicant to pay the admission fee (invoice to be created by Finance).</p>
                <form method="POST" action="{{ route('admissions.applications.shortlist', $applicant->id) }}">
                    @csrf
                    <div class="tich-form-group">
                        <label class="tich-label">Notes (optional)</label>
                        <textarea name="review_notes" class="tich-input" rows="3">{{ old('review_notes') }}</textarea>
                    </div>
                    <button type="submit" class="tich-btn tich-btn-secondary">Shortlist</button>
                </form>
            </article>
            @endcan

            @if ($canApprove)
            <article class="tich-card">
                <h2 class="tich-h3">Accept</h2>
                <p class="tich-text tich-mb-4">Approve onboarding and mark the applicant as admitted.</p>
                <form method="POST" action="{{ route('admissions.applications.approve', $applicant->id) }}">
                    @csrf
                    <div class="tich-form-group">
                        <label class="tich-label">Notes (optional)</label>
                        <textarea name="review_notes" class="tich-input" rows="3">{{ old('review_notes') }}</textarea>
                    </div>
                    <button type="submit" class="tich-btn tich-btn-primary">Accept application</button>
                </form>
            </article>

            <article class="tich-card">
                <h2 class="tich-h3">Reject</h2>
                <p class="tich-text tich-mb-4">Decline the application with a reason visible to reviewers.</p>
                <form method="POST" action="{{ route('admissions.applications.reject', $applicant->id) }}">
                    @csrf
                    <div class="tich-form-group">
                        <label class="tich-label">Rejection reason</label>
                        <input type="text" name="rejection_reason" class="tich-input" value="{{ old('rejection_reason') }}" required>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Notes (optional)</label>
                        <textarea name="review_notes" class="tich-input" rows="2">{{ old('review_notes') }}</textarea>
                    </div>
                    <button type="submit" class="tich-btn tich-btn-secondary" style="border-color: #c0392b; color: #c0392b;">Reject application</button>
                </form>
            </article>
            @endif
        </div>
    @else
        <div class="tich-card tich-mt-8">
            <p class="tich-text">This application has been finalized and can no longer be changed from this dashboard.</p>
        </div>
    @endunless
@endsection
