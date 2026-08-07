@extends('layouts.hr')

@section('title', 'Review profile change')

@section('hr-content')
    @php
        $staffMember = $changeRequest->staff;
        $canReview = $changeRequest->isPending();
    @endphp

    <div class="tich-mb-6 tich-flex" style="gap:0.5rem; flex-wrap:wrap;">
        <a href="{{ route('hr.profile-changes.index', ['status' => 'pending']) }}" class="tich-btn tich-btn-ghost">← Profile changes inbox</a>
        <a href="{{ route('hr.dashboard') }}#profile-changes-inbox" class="tich-btn tich-btn-ghost">HR dashboard</a>
    </div>

    <section class="tich-leave-hero tich-mb-8">
        <div>
            <p class="tich-caption">Profile change request</p>
            <h1 class="tich-leave-hero__title">{{ $changeRequest->typeLabel() }}</h1>
            <div class="tich-leave-hero__meta">
                @if ($changeRequest->status === 'pending')
                    <span class="tich-badge tich-badge--warning">Pending HR review</span>
                @elseif ($changeRequest->status === 'approved')
                    <span class="tich-badge tich-badge--success">Approved</span>
                @else
                    <span class="tich-badge tich-badge--danger">{{ ucfirst($changeRequest->status) }}</span>
                @endif
                <span class="tich-caption">Submitted {{ $changeRequest->created_at->format('d M Y H:i') }}</span>
            </div>
        </div>
    </section>

    <div class="tich-grid tich-grid--2 tich-mb-8" style="align-items:start; gap:1.5rem;">
        <article class="tich-card">
            <h2 class="tich-h3">Employee</h2>
            <div class="tich-kv-grid tich-mt-4">
                <div><span class="tich-kv-grid__label">Name</span><span class="tich-kv-grid__value">{{ $staffMember->fullName() }}</span></div>
                <div><span class="tich-kv-grid__label">Employee no.</span><span class="tich-kv-grid__value">{{ $staffMember->employee_number }}</span></div>
                <div><span class="tich-kv-grid__label">Department</span><span class="tich-kv-grid__value">{{ $staffMember->department?->dept_name ?? '—' }}</span></div>
                <div><span class="tich-kv-grid__label">Job title</span><span class="tich-kv-grid__value">{{ $staffMember->job_title ?? '—' }}</span></div>
            </div>
            <a href="{{ route('hr.staff.show', $staffMember) }}" class="tich-btn tich-btn-ghost tich-mt-4">View full staff record</a>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3">Employee notes</h2>
            <p class="tich-text tich-mt-4">{{ $changeRequest->employee_notes ?: '—' }}</p>
        </article>
    </div>

    <article class="tich-card tich-mb-8">
        <h2 class="tich-h3">Proposed changes</h2>

        @if ($changeRequest->request_type === 'photo' && $attachmentUrl)
            <div class="tich-mt-4" style="display:flex; gap:2rem; flex-wrap:wrap; align-items:flex-start;">
                <div>
                    <p class="tich-caption">Current photo</p>
                    @if ($staffMember->photoUrl())
                        <img src="{{ $staffMember->photoUrl() }}" alt="Current" style="width:8rem; height:8rem; object-fit:cover; border-radius:50%; border:2px solid var(--tich-neutral-border);">
                    @else
                        <p class="tich-text tich-mt-2">No photo on file</p>
                    @endif
                </div>
                <div>
                    <p class="tich-caption">Proposed photo</p>
                    <img src="{{ $attachmentUrl }}" alt="Proposed" style="width:8rem; height:8rem; object-fit:cover; border-radius:50%; border:2px solid var(--tich-blue);">
                </div>
            </div>
        @elseif ($changeRequest->request_type === 'qualification')
            <div class="tich-kv-grid tich-mt-4">
                @foreach ($changeRequest->proposed_changes ?? [] as $field => $value)
                    @if ($value !== null && $value !== '')
                        <div>
                            <span class="tich-kv-grid__label">{{ ucwords(str_replace('_', ' ', $field)) }}</span>
                            <span class="tich-kv-grid__value">{{ $value }}</span>
                        </div>
                    @endif
                @endforeach
            </div>
            @if ($attachmentUrl)
                <p class="tich-mt-4"><a href="{{ $attachmentUrl }}" target="_blank" rel="noopener" class="tich-btn tich-btn-ghost">View certificate file</a></p>
            @endif
        @else
            <div class="tich-table-wrap tich-mt-4">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Field</th>
                            <th>Current</th>
                            <th>Proposed</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($changeRequest->proposed_changes ?? [] as $field => $proposed)
                            <tr>
                                <td>{{ ucwords(str_replace('_', ' ', $field)) }}</td>
                                <td>{{ $changeRequest->current_snapshot[$field] ?? '—' }}</td>
                                <td><strong>{{ $proposed ?? '—' }}</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </article>

    @if ($changeRequest->status === 'rejected')
        <article class="tich-card tich-mb-8" style="border-left:4px solid #dc2626;">
            <h2 class="tich-h3">Rejection reason</h2>
            <p class="tich-text tich-mt-4">{{ $changeRequest->rejection_reason }}</p>
        </article>
    @endif

    @if ($canReview)
        @if ($errors->has('form'))
            <div class="tich-alert tich-alert--danger tich-mb-4">{{ $errors->first('form') }}</div>
        @endif

        <div class="tich-grid tich-grid--2" style="gap:1.5rem; align-items:start;">
            <article class="tich-card">
                <h2 class="tich-h3">Approve</h2>
                <form method="POST" action="{{ route('hr.profile-changes.approve', $changeRequest) }}" class="tich-mt-4">
                    @csrf
                    <label for="approve_hr_notes" class="tich-label">HR notes (optional)</label>
                    <textarea id="approve_hr_notes" name="hr_notes" rows="3" class="tich-input tich-mt-2"></textarea>
                    <button type="submit" class="tich-btn tich-btn-primary tich-mt-4">Approve &amp; apply changes</button>
                </form>
            </article>

            <article class="tich-card">
                <h2 class="tich-h3">Reject</h2>
                <form method="POST" action="{{ route('hr.profile-changes.reject', $changeRequest) }}" class="tich-mt-4">
                    @csrf
                    <label for="rejection_reason" class="tich-label">Reason for rejection *</label>
                    <textarea id="rejection_reason" name="rejection_reason" rows="3" class="tich-input tich-mt-2 @error('rejection_reason') tich-input--error @enderror" required></textarea>
                    @error('rejection_reason')<p class="tich-form-error">{{ $message }}</p>@enderror
                    <label for="reject_hr_notes" class="tich-label tich-mt-4">HR notes (optional)</label>
                    <textarea id="reject_hr_notes" name="hr_notes" rows="2" class="tich-input tich-mt-2"></textarea>
                    <button type="submit" class="tich-btn tich-btn-ghost tich-mt-4" style="color:#b91c1c; border-color:#fecaca;">Reject request</button>
                </form>
            </article>
        </div>
    @elseif ($changeRequest->reviewed_at)
        <article class="tich-card">
            <p class="tich-caption">Reviewed {{ $changeRequest->reviewed_at->format('d M Y H:i') }} by {{ $changeRequest->reviewedBy?->fullName() ?? 'HR' }}</p>
            @if ($changeRequest->hr_notes)
                <p class="tich-text tich-mt-2">{{ $changeRequest->hr_notes }}</p>
            @endif
        </article>
    @endif
@endsection
