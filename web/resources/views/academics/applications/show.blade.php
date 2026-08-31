@extends('layouts.academics')

@section('title', 'Application '.$applicant->application_number)

@section('academics-content')
    @php($hub = \App\Support\AcademicsRouteParams::fromRequest(request()))

    <a href="{{ route('departments.academics.applications.index', $hub) }}" class="tich-link">&larr; Application review</a>

    <x-page-toolbar
        :title="$applicant->fullName()"
        :meta="$applicant->application_number . ' · ' . $applicant->email"
        class="tich-mt-4"
    >
        <x-slot:actions>
            @include('applications.partials.status-badge', ['applicant' => $applicant])
        </x-slot:actions>
    </x-page-toolbar>

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

    <div class="tich-mt-8">
        @include('applications.partials.details', ['applicant' => $applicant, 'handlingDepartment' => $handlingDepartment])
    </div>

    @include('applications.partials.document-viewer', [
        'applicant' => $applicant,
        'documentRoutePrefix' => $documentRoutePrefix,
    ])

    @unless ($applicant->isFinalized())
        @if ($applicant->status === 'academic_review' && $canApprove)
            <div class="tich-grid tich-grid--2 tich-mt-8" style="align-items: start; gap: 2rem;">
                <article class="tich-card">
                    <h2 class="tich-h3">Approve academically</h2>
                    <p class="tich-text tich-mb-4">Approve this application. The applicant will receive their application letter, fee structure, and a link to pay the application fee.</p>
                    <form method="POST" action="{{ route('departments.academics.applications.approve', array_merge($hub, ['id' => $applicant->id])) }}">
                        @csrf
                        <div class="tich-form-group">
                            <label class="tich-label">Notes (optional)</label>
                            <textarea name="review_notes" class="tich-input" rows="3">{{ old('review_notes') }}</textarea>
                        </div>
                        <button type="submit" class="tich-btn tich-btn-primary">Approve application</button>
                    </form>
                </article>

                <article class="tich-card">
                    <h2 class="tich-h3">Reject</h2>
                    <p class="tich-text tich-mb-4">Reject with a reason that will be shared with the applicant.</p>
                    <form method="POST" action="{{ route('departments.academics.applications.reject', array_merge($hub, ['id' => $applicant->id])) }}">
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
            </div>
        @elseif (! $canApprove)
            <div class="tich-card tich-mt-8">
                <p class="tich-text">You can view this application but do not have permission to approve or reject it.</p>
            </div>
        @endif
    @else
        <div class="tich-card tich-mt-8">
            <p class="tich-text">This application has been finalized.</p>
        </div>
    @endunless
@endsection
