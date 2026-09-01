@extends('layouts.administration')

@section('title', 'Application '.$applicant->application_number)

@section('administration-content')
    <a href="{{ route('administration.applications.index') }}" class="tich-link">&larr; All applications</a>

    <x-page-toolbar
        :title="$applicant->fullName()"
        :meta="$applicant->application_number . ' · ' . $applicant->email"
        class="tich-mt-4"
    >
        <x-slot:actions>
            @include('applications.partials.status-badge', ['applicant' => $applicant])
        </x-slot:actions>
    </x-page-toolbar>

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

    @include('applications.partials.details', ['applicant' => $applicant, 'handlingDepartment' => $handlingDepartment])

    @include('applications.partials.document-viewer', [
        'applicant' => $applicant,
        'documentRoutePrefix' => $documentRoutePrefix,
    ])

    @if ($approvalPackageEmail)
        <article class="tich-card tich-mt-8">
            <h2 class="tich-h3">Approval package email</h2>
            <dl class="tich-mt-4" style="display: grid; grid-template-columns: 12rem 1fr; gap: 0.75rem 1rem; margin: 0;">
                <dt class="tich-caption">Package email</dt>
                <dd class="tich-text" style="margin: 0;">
                    @if ($approvalPackageEmail['email_sent'])
                        <strong style="color: var(--tich-green);">Sent</strong>
                        @if ($approvalPackageEmail['last_recipient'])
                            to {{ $approvalPackageEmail['last_recipient'] }}
                        @endif
                        @if ($approvalPackageEmail['last_sent_at'])
                            · {{ $approvalPackageEmail['last_sent_at']->format('d M Y H:i') }}
                        @endif
                    @else
                        <strong style="color: #c0392b;">Not sent</strong>
                    @endif
                </dd>
            </dl>

            @if ($approvalPackageEmail['can_resend'])
                <form method="POST" action="{{ route('administration.applications.resend-approval-package', $applicant->id) }}" class="tich-mt-6">
                    @csrf
                    <p class="tich-text tich-mb-4">
                        Resend the approval package (application letter, fee structure, and payment link) to <strong>{{ $applicant->email }}</strong>.
                    </p>
                    <button type="submit" class="tich-btn tich-btn-secondary">Resend approval package email</button>
                </form>
            @endif
        </article>
    @endif

    @if ($admissionConfirmationEmail)
        <article class="tich-card tich-mt-8">
            <h2 class="tich-h3">Payment & admission confirmation email</h2>
            <dl class="tich-mt-4" style="display: grid; grid-template-columns: 12rem 1fr; gap: 0.75rem 1rem; margin: 0;">
                <dt class="tich-caption">Confirmation email</dt>
                <dd class="tich-text" style="margin: 0;">
                    @if ($admissionConfirmationEmail['email_sent'])
                        <strong style="color: var(--tich-green);">Sent</strong>
                        @if ($admissionConfirmationEmail['last_recipient'])
                            to {{ $admissionConfirmationEmail['last_recipient'] }}
                        @endif
                        @if ($admissionConfirmationEmail['last_sent_at'])
                            · {{ $admissionConfirmationEmail['last_sent_at']->format('d M Y H:i') }}
                        @endif
                    @else
                        <strong style="color: #c0392b;">Not sent</strong>
                    @endif
                </dd>

                @if ($admissionConfirmationEmail['registration_number'])
                    <dt class="tich-caption">Registration no.</dt>
                    <dd class="tich-text" style="margin: 0;">{{ $admissionConfirmationEmail['registration_number'] }}</dd>
                @endif

                <dt class="tich-caption">Portal status</dt>
                <dd class="tich-text" style="margin: 0;">
                    @if ($admissionConfirmationEmail['portal_activated'])
                        <strong style="color: var(--tich-green);">Activated</strong>
                    @elseif ($admissionConfirmationEmail['invite_pending'])
                        <strong>Pending activation</strong>
                    @elseif ($admissionConfirmationEmail['is_admitted'])
                        <strong>Invite expired or missing</strong>
                    @else
                        <strong>Awaiting admission</strong>
                    @endif
                </dd>
            </dl>

            @if ($admissionConfirmationEmail['can_resend'])
                <form method="POST" action="{{ route('administration.applications.resend-admission-confirmation', $applicant->id) }}" class="tich-mt-6">
                    @csrf
                    <p class="tich-text tich-mb-4">
                        Resend the payment confirmation email to <strong>{{ $applicant->email }}</strong>.
                        @if ($admissionConfirmationEmail['is_admitted'] && ! $admissionConfirmationEmail['portal_activated'])
                            Includes the student portal activation link and admission letter when applicable.
                        @endif
                    </p>
                    <button type="submit" class="tich-btn tich-btn-secondary">Resend confirmation email</button>
                </form>
            @endif
        </article>
    @endif

    @if (in_array($applicant->status, ['submitted_admin', 'submitted'], true))
        <article class="tich-card tich-mt-8">
            <h2 class="tich-h3">Forward to academics</h2>
            <p class="tich-text tich-mb-4">Review the application details and documents, then forward to academics for qualification review. Administration does not approve or reject applications here.</p>
            <form method="POST" action="{{ route('administration.applications.handoff-to-academics', $applicant->id) }}">
                @csrf
                <div class="tich-form-group">
                    <label class="tich-label">Notes for academics (optional)</label>
                    <textarea name="review_notes" class="tich-input" rows="3">{{ old('review_notes') }}</textarea>
                </div>
                <button type="submit" class="tich-btn tich-btn-primary">Forward to academics</button>
            </form>
        </article>
    @elseif ($applicant->isFinalized())
        <div class="tich-card tich-mt-8">
            <p class="tich-text">This application has been finalized.</p>
        </div>
    @else
        <div class="tich-card tich-mt-8">
            <p class="tich-text">This application is with academics or awaiting applicant payment. Status updates appear here when academics complete their review.</p>
        </div>
    @endif
@endsection
