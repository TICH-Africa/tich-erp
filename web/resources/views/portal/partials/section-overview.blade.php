@php($stats = $portalData['overview_stats'])

<x-page-toolbar
    title="Welcome, {{ $biodata['identity']['full_name'] }}"
    meta="{{ $student->registration_number }} · {{ $biodata['academic']['program'] }} · {{ $stats['enrollment_status'] }}"
/>

<div class="tich-grid tich-grid--4 tich-dept-stats tich-mt-8">
    <article class="tich-card tich-stat">
        <p class="tich-caption">Fee balance</p>
        <p class="tich-stat__value">KES {{ number_format($stats['outstanding_balance'], 2) }}</p>
    </article>
    <article class="tich-card tich-stat">
        <p class="tich-caption">Fee clearance</p>
        <p class="tich-stat__value" style="font-size: 1.125rem;">{{ $stats['fee_clearance'] }}</p>
    </article>
    <article class="tich-card tich-stat">
        <p class="tich-caption">Registered units</p>
        <p class="tich-stat__value">{{ $stats['registered_unit_count'] }}</p>
    </article>
    <article class="tich-card tich-stat">
        <p class="tich-caption">Documents</p>
        <p class="tich-stat__value">{{ $stats['document_count'] }}</p>
    </article>
</div>

<section class="tich-dept-panel tich-mt-8">
    <div class="tich-dept-panel__head">
        <h2 class="tich-h2 tich-dept-panel__title">At a glance</h2>
    </div>

    <div class="tich-grid tich-grid--2 tich-mt-4" style="gap: 1.5rem;">
        <article class="tich-card">
            <h3 class="tich-h3">Application</h3>
            <p class="tich-text tich-mt-2">
                <strong>{{ $biodata['application']['application_number'] ?? '-' }}</strong><br>
                Status: {{ $stats['application_status'] ?: '-' }}<br>
                Review: {{ ucwords(str_replace('_', ' ', (string) ($biodata['application']['academic_review_status'] ?? ''))) ?: '-' }}
            </p>
            <a href="{{ route('portal.dashboard', ['section' => 'application']) }}" class="tich-link tich-mt-4" style="display:inline-block;">View application</a>
        </article>

        <article class="tich-card">
            <h3 class="tich-h3">Enrolment</h3>
            <p class="tich-text tich-mt-2">
                Campus: {{ $biodata['enrollment']['campus'] ?? '-' }}<br>
                Admitted: {{ $biodata['enrollment']['date_of_admission'] ?? '-' }}<br>
                Intake: {{ $biodata['academic']['cohort_intake'] ?? '-' }}
            </p>
            <a href="{{ route('portal.dashboard', ['section' => 'enrolment']) }}" class="tich-link tich-mt-4" style="display:inline-block;">View enrolment</a>
        </article>

        <article class="tich-card">
            <h3 class="tich-h3">Academics</h3>
            <p class="tich-text tich-mt-2">
                Programme units: {{ $stats['curriculum_unit_count'] }}<br>
                Registered: {{ $stats['registered_unit_count'] }}<br>
                Grades on record: {{ $stats['grade_count'] }}
            </p>
            <a href="{{ route('portal.dashboard', ['section' => 'academics']) }}" class="tich-link tich-mt-4" style="display:inline-block;">View academics</a>
        </article>

        <article class="tich-card">
            <h3 class="tich-h3">Finance</h3>
            <p class="tich-text tich-mt-2">
                Outstanding: KES {{ number_format($stats['outstanding_balance'], 2) }}<br>
                Clearance: {{ $stats['fee_clearance'] }}
            </p>
            <a href="{{ route('portal.dashboard', ['section' => 'finance']) }}" class="tich-link tich-mt-4" style="display:inline-block;">View finance</a>
            @if ($stats['outstanding_balance'] > 0)
                <a href="{{ route('portal.dashboard', ['section' => 'finance']) }}#pay-with-mpesa" class="tich-link tich-mt-2" style="display:inline-block;">Pay with M-Pesa</a>
            @endif
        </article>
    </div>
</section>

<section class="tich-dept-panel tich-mt-8">
    <div class="tich-dept-panel__head">
        <h2 class="tich-h2 tich-dept-panel__title">Your modules</h2>
        <p class="tich-text">Open a service from the sidebar or choose a module below.</p>
    </div>

    <div class="tich-grid tich-grid--3 tich-dept-cards tich-mt-4">
        @foreach ($modules as $module)
            <article class="tich-card tich-dept-card">
                <h3 class="tich-h3">{{ $module['label'] }}</h3>
                <p class="tich-text tich-mt-2">{{ $module['description'] }}</p>
                <a href="{{ route('portal.dashboard', ['section' => $module['section']]) }}" class="tich-btn tich-btn-secondary tich-mt-4">Open</a>
            </article>
        @endforeach
    </div>
</section>
