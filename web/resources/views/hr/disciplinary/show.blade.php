@extends('layouts.hr')

@section('title', 'Disciplinary case ' . $case->case_number)

@section('hr-content')
    <div class="tich-mb-6">
        <a href="{{ route('hr.employee-relations.disciplinary.index') }}" class="tich-btn tich-btn-ghost">← Back to cases</a>
    </div>

    <section class="tich-leave-hero tich-mb-8">
        <div>
            <p class="tich-caption">Disciplinary case</p>
            <h1 class="tich-leave-hero__title">{{ $case->case_number }}</h1>
            <div class="tich-leave-hero__meta">
                <span class="tich-badge tich-badge--{{ match($case->status) {
                    'open' => 'warning',
                    'under_investigation' => 'info',
                    'hearing_scheduled' => 'info',
                    'decided' => 'success',
                    'appealed' => 'warning',
                    'closed' => 'secondary',
                    default => 'secondary',
                } }}">
                    {{ ucfirst(str_replace('_', ' ', $case->status)) }}
                </span>
                <span class="tich-caption">Incident: {{ $case->incident_date->format('d M Y') }}</span>
            </div>
        </div>
        <div class="tich-leave-hero__actions">
            <a href="{{ route('hr.employee-relations.disciplinary.edit', $case) }}" class="tich-btn tich-btn-secondary">Edit case</a>
        </div>
    </section>

    <div class="tich-grid tich-grid--2 tich-mb-8" style="align-items:start; gap:1.5rem;">
        <article class="tich-card">
            <h2 class="tich-h3">Employee</h2>
            <div class="tich-kv-grid tich-mt-4">
                <div>
                    <span class="tich-kv-grid__label">Name</span>
                    <span class="tich-kv-grid__value">{{ $case->staff->fullName() }}</span>
                </div>
                <div>
                    <span class="tich-kv-grid__label">Employee no.</span>
                    <span class="tich-kv-grid__value">{{ $case->staff->employee_number }}</span>
                </div>
                <div>
                    <span class="tich-kv-grid__label">Department</span>
                    <span class="tich-kv-grid__value">{{ $case->staff->department?->dept_name ?? '-' }}</span>
                </div>
                <div>
                    <span class="tich-kv-grid__label">Job title</span>
                    <span class="tich-kv-grid__value">{{ $case->staff->job_title ?? '-' }}</span>
                </div>
            </div>
            <a href="{{ route('hr.staff.show', $case->staff) }}" class="tich-btn tich-btn-ghost tich-mt-4">View staff profile</a>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3">Case details</h2>
            <div class="tich-kv-grid tich-mt-4">
                <div>
                    <span class="tich-kv-grid__label">Incident date</span>
                    <span class="tich-kv-grid__value">{{ $case->incident_date->format('d M Y') }}</span>
                </div>
                <div>
                    <span class="tich-kv-grid__label">Hearing date</span>
                    <span class="tich-kv-grid__value">{{ $case->hearing_date?->format('d M Y') ?? '-' }}</span>
                </div>
                <div>
                    <span class="tich-kv-grid__label">Assigned to</span>
                    <span class="tich-kv-grid__value">{{ $case->assignedTo?->fullName() ?? '-' }}</span>
                </div>
                <div>
                    <span class="tich-kv-grid__label">Action type</span>
                    <span class="tich-kv-grid__value">{{ ucfirst($case->action_type ?? '-') }}</span>
                </div>
                @if ($case->action_start_date)
                    <div>
                        <span class="tich-kv-grid__label">Action period</span>
                        <span class="tich-kv-grid__value">
                            {{ $case->action_start_date->format('d M Y') }}
                            {{ $case->action_end_date ? ' → ' . $case->action_end_date->format('d M Y') : '' }}
                        </span>
                    </div>
                @endif
            </div>
        </article>
    </div>

    <article class="tich-card tich-mb-8">
        <h2 class="tich-h3">Incident description</h2>
        <p class="tich-text tich-mt-4" style="white-space: pre-wrap;">{{ $case->incident_description }}</p>
    </article>

    @if ($case->investigation_notes)
        <article class="tich-card tich-mb-8">
            <h2 class="tich-h3">Investigation notes</h2>
            <p class="tich-text tich-mt-4" style="white-space: pre-wrap;">{{ $case->investigation_notes }}</p>
        </article>
    @endif

    @if ($case->witness_information)
        <article class="tich-card tich-mb-8">
            <h2 class="tich-h3">Witness information</h2>
            <p class="tich-text tich-mt-4" style="white-space: pre-wrap;">{{ $case->witness_information }}</p>
        </article>
    @endif

    @if ($case->committee_members)
        <article class="tich-card tich-mb-8">
            <h2 class="tich-h3">Committee members</h2>
            <p class="tich-text tich-mt-4" style="white-space: pre-wrap;">{{ $case->committee_members }}</p>
        </article>
    @endif

    @if ($case->decision)
        <article class="tich-card tich-mb-8">
            <h2 class="tich-h3">Decision</h2>
            <p class="tich-text tich-mt-4" style="white-space: pre-wrap;">{{ $case->decision }}</p>
        </article>
    @endif

    @if ($case->hr_comments)
        <article class="tich-card tich-mb-8">
            <h2 class="tich-h3">HR comments</h2>
            <p class="tich-text tich-mt-4" style="white-space: pre-wrap;">{{ $case->hr_comments }}</p>
        </article>
    @endif

    <article class="tich-card tich-mb-8" id="disciplinary-correspondence">
        <h2 class="tich-h3">Message employee</h2>
        <p class="tich-caption tich-mt-2">
            Draft and send from the HR mailbox ({{ \App\Support\ModuleMail::from(\App\Support\ModuleMail::HR)['address'] }}).
            The employee receives email and an in-app notification.
        </p>
        @if ($recipientEmail)
            <p class="tich-caption tich-mt-2">Recipient: <strong>{{ $case->staff->fullName() }}</strong> &lt;{{ $recipientEmail }}&gt;</p>
        @else
            <p class="tich-text tich-mt-4" style="color:#b45309;">No email on file for this employee. Update their profile before sending.</p>
        @endif

        @error('correspondence')
            <p class="tich-text tich-mt-4" style="color:#b91c1c;">{{ $message }}</p>
        @enderror

        <form method="POST" action="{{ route('hr.employee-relations.disciplinary.correspondence.send', $case) }}" class="tich-form-stack tich-mt-4">
            @csrf
            <div>
                <label for="correspondence_subject" class="tich-label">Subject</label>
                <input type="text" id="correspondence_subject" name="subject" class="tich-input" maxlength="200" required
                    value="{{ old('subject', 'Disciplinary matter — ' . $case->case_number) }}"
                    @disabled(! $recipientEmail)>
            </div>
            <div>
                <label for="correspondence_body" class="tich-label">Message</label>
                <textarea id="correspondence_body" name="body" class="tich-input" rows="10" maxlength="10000" required
                    placeholder="Write the notice or instructions for the employee…"
                    @disabled(! $recipientEmail)>{{ old('body') }}</textarea>
            </div>
            <div>
                <button type="submit" class="tich-btn tich-btn-primary" @disabled(! $recipientEmail)
                    onclick="return confirm('Send this message to {{ $case->staff->fullName() }} from HR?');">
                    Send from HR
                </button>
            </div>
        </form>

        @if ($case->correspondences->isNotEmpty())
            <h3 class="tich-h4 tich-mt-8">Sent messages</h3>
            <ul class="tich-mt-4" style="margin:0;padding-left:0;list-style:none;">
                @foreach ($case->correspondences as $message)
                    <li class="tich-card tich-mb-4" style="padding:1rem;background:#f9fafb;">
                        <p class="tich-caption">
                            {{ $message->created_at->format('d M Y H:i') }}
                            · {{ $message->sentBy?->name ?? 'HR' }}
                            @if ($message->emailed_at)
                                · Email sent
                            @endif
                            @if ($message->notified_at)
                                · Notified in portal
                            @endif
                        </p>
                        <p class="tich-text" style="font-weight:600;margin:0.5rem 0 0;">{{ $message->subject }}</p>
                        <p class="tich-text tich-mt-2" style="white-space:pre-wrap;margin:0;">{{ $message->body }}</p>
                    </li>
                @endforeach
            </ul>
        @endif
    </article>

    <article class="tich-card tich-mb-8">
        <h2 class="tich-h3">Attachments</h2>
        @if ($case->documents->isNotEmpty())
            <ul class="tich-mt-4" style="margin: 0; padding-left: 1.1rem;">
                @foreach ($case->documents as $document)
                    <li class="tich-mb-2">
                        <a href="{{ route('hr.employee-relations.disciplinary.documents.download', [$case, $document]) }}" class="tich-link">
                            {{ $document->document_name }}
                        </a>
                        @if ($document->created_at)
                            <span class="tich-caption"> - {{ $document->created_at->format('d M Y') }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @else
            <p class="tich-caption tich-mt-4">No documents attached to this case.</p>
        @endif
    </article>
@endsection
