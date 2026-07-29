<header class="tich-dept-header">
    <p class="tich-caption">My services</p>
    <h1 class="tich-h1 tich-dept-header__title">Enrolment</h1>
    <p class="tich-text tich-dept-header__meta">Campus placement, admission, and clearance details.</p>
</header>

<div class="tich-grid tich-grid--2 tich-mt-8" style="gap: 1.5rem; align-items: start;">
    <article class="tich-card">
        <h2 class="tich-h3">Enrolment details</h2>
        <dl style="display: grid; grid-template-columns: 10rem 1fr; gap: 0.75rem 1rem; margin: 1rem 0 0;">
            <dt class="tich-caption">Registration number</dt>
            <dd>{{ $student->registration_number }}</dd>
            <dt class="tich-caption">Programme</dt>
            <dd>{{ $biodata['academic']['program'] ?? '-' }}</dd>
            <dt class="tich-caption">Department</dt>
            <dd>{{ $biodata['academic']['department'] ?? '-' }}</dd>
            <dt class="tich-caption">Campus</dt>
            <dd>{{ $biodata['enrollment']['campus'] ?? '-' }}</dd>
            <dt class="tich-caption">Cohort / intake</dt>
            <dd>{{ $biodata['academic']['cohort_intake'] ?? '-' }}</dd>
            <dt class="tich-caption">Entry pathway</dt>
            <dd>{{ ucwords(str_replace('_', ' ', (string) ($biodata['academic']['entry_pathway'] ?? ''))) ?: '-' }}</dd>
            <dt class="tich-caption">Date of admission</dt>
            <dd>{{ $biodata['enrollment']['date_of_admission'] ?? '-' }}</dd>
            <dt class="tich-caption">Enrolment status</dt>
            <dd>{{ ucfirst($biodata['enrollment']['enrollment_status'] ?? '-') }}</dd>
        </dl>
    </article>

    <article class="tich-card">
        <h2 class="tich-h3">Clearance &amp; finance snapshot</h2>
        <dl style="display: grid; grid-template-columns: 10rem 1fr; gap: 0.75rem 1rem; margin: 1rem 0 0;">
            <dt class="tich-caption">Fee clearance</dt>
            <dd>{{ ucfirst($biodata['enrollment']['fee_clearance_status'] ?? 'pending') }}</dd>
            <dt class="tich-caption">Outstanding balance</dt>
            <dd>KES {{ number_format((float) ($portalData['finance']['summary']['outstanding_balance'] ?? 0), 2) }}</dd>
            <dt class="tich-caption">Portal activated</dt>
            <dd>{{ $biodata['enrollment']['portal_activated_at'] ?? '-' }}</dd>
        </dl>
        <a href="{{ route('portal.dashboard', ['section' => 'finance']) }}" class="tich-link tich-mt-4" style="display:inline-block;">View full finance</a>
    </article>
</div>

@if ($portalData['academics']['registrations']->isNotEmpty())
    <section class="tich-dept-panel tich-mt-8">
        <div class="tich-dept-panel__head">
            <h2 class="tich-h2 tich-dept-panel__title">Semester registrations</h2>
        </div>
        <div class="tich-card tich-table-panel tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Semester</th>
                        <th>Academic year</th>
                        <th>Units</th>
                        <th>Registered</th>
                        <th>Status</th>
                        <th>Fee cleared</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($portalData['academics']['registrations'] as $registration)
                        <tr>
                            <td>{{ $registration->semester_label ?? ('Semester '.$registration->semester_number) }}</td>
                            <td>{{ $registration->year_label ?? '-' }}</td>
                            <td>{{ $registration->unit_count }}</td>
                            <td>{{ $registration->registration_date ? \Illuminate\Support\Carbon::parse($registration->registration_date)->format('d M Y') : '-' }}</td>
                            <td>{{ ucfirst($registration->status ?? 'registered') }}</td>
                            <td>{{ $registration->is_fee_cleared ? 'Yes' : 'No' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif
