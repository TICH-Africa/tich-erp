@php
    $intakeMonth = $biodata['application']['intake_month'] ?? null;
    $intakeLabel = ($biodata['application']['intake_year'] ?? null) && $intakeMonth
        ? (\App\Models\CurriculumVersion::intakeMonths()[(int) $intakeMonth] ?? $intakeMonth).' '.$biodata['application']['intake_year']
        : null;
@endphp

<x-page-toolbar title="Application" meta="Admission application and review trail" />

<article class="tich-card tich-mt-8">
    <dl style="display: grid; grid-template-columns: 11rem 1fr; gap: 0.75rem 1rem; margin: 0;">
        <dt class="tich-caption">Application number</dt>
        <dd><strong>{{ $biodata['application']['application_number'] ?? '-' }}</strong></dd>

        <dt class="tich-caption">Status</dt>
        <dd>{{ ucwords(str_replace('_', ' ', (string) ($biodata['application']['status'] ?? ''))) ?: '-' }}</dd>

        <dt class="tich-caption">Academic review</dt>
        <dd>{{ ucwords(str_replace('_', ' ', (string) ($biodata['application']['academic_review_status'] ?? ''))) ?: '-' }}</dd>

        <dt class="tich-caption">Submitted</dt>
        <dd>{{ $biodata['application']['submitted_at'] ?? '-' }}</dd>

        <dt class="tich-caption">Reviewed</dt>
        <dd>{{ $biodata['application']['reviewed_at'] ?? '-' }}</dd>

        <dt class="tich-caption">Preferred intake</dt>
        <dd>{{ $intakeLabel ?? '-' }}</dd>

        <dt class="tich-caption">Preferred campus</dt>
        <dd>{{ $biodata['application']['preferred_campus'] ?? '-' }}</dd>

        <dt class="tich-caption">Entry qualification</dt>
        <dd>{{ $biodata['application']['entry_qualification'] ?? '-' }}</dd>

        <dt class="tich-caption">Sponsorship</dt>
        <dd>{{ ucwords(str_replace('_', ' ', (string) ($biodata['application']['sponsorship_type'] ?? ''))) ?: '-' }}</dd>

        @if (! empty($biodata['application']['review_notes']))
            <dt class="tich-caption">Review notes</dt>
            <dd>{{ $biodata['application']['review_notes'] }}</dd>
        @endif

        @if (! empty($biodata['application']['rejection_reason']))
            <dt class="tich-caption">Decision notes</dt>
            <dd>{{ $biodata['application']['rejection_reason'] }}</dd>
        @endif
    </dl>

    @if (! empty($biodata['application']['application_number']))
        <a href="{{ route('apply.status', ['application_number' => $biodata['application']['application_number'], 'email' => $biodata['contact']['email']]) }}" class="tich-btn tich-btn-secondary tich-mt-6">Check public application status</a>
    @endif
</article>
