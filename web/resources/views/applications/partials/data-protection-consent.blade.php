@php
    $consentRecord = $applicant->relationLoaded('personalDataConsents')
        ? $applicant->personalDataConsents->first()
        : null;
@endphp

<article class="tich-card tich-mt-8">
    <h2 class="tich-h3">Data protection (Kenya DPA)</h2>
    <dl class="tich-mt-4 tich-review-list">
        <div>
            <dt>Privacy notice consent</dt>
            <dd>
                @if ($applicant->privacy_consented_at)
                    <strong style="color: var(--tich-green);">Recorded</strong>
                    · {{ $applicant->privacy_consented_at->timezone(config('app.timezone'))->format('d M Y H:i T') }}
                @else
                    <span class="tich-caption">Not recorded (application submitted before consent tracking or via another channel).</span>
                @endif
            </dd>
        </div>
        @if ($applicant->privacy_notice_version)
            <div>
                <dt>Notice version</dt>
                <dd>{{ $applicant->privacy_notice_version }}</dd>
            </div>
        @endif
        @if ($consentRecord)
            <div>
                <dt>Consent method</dt>
                <dd>{{ ucfirst(str_replace('_', ' ', $consentRecord->consent_method ?? 'unknown')) }} ({{ $consentRecord->channel ?? 'web' }})</dd>
            </div>
            <div>
                <dt>Lawful basis</dt>
                <dd>{{ config('data-protection.lawful_bases.'.$consentRecord->lawful_basis, $consentRecord->lawful_basis) }}</dd>
            </div>
            @if ($consentRecord->ip_address)
                <div>
                    <dt>Submission IP</dt>
                    <dd class="tich-caption">{{ $consentRecord->ip_address }}</dd>
                </div>
            @endif
        @endif
    </dl>
    <p class="tich-caption tich-mt-4">
        RoPA activity: admissions online application · purpose key <code>admissions_application</code>.
        @can('audit_logs.read')
            <a href="{{ route('admin.data-protection.index') }}" class="tich-link">Data protection dashboard</a>
        @endcan
    </p>
</article>
