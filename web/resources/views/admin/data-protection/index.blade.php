@extends('layouts.admin')

@section('title', 'Data protection (Kenya DPA)')

@section('admin-content')
    <x-page-toolbar
        title="Data protection"
        meta="Kenya Data Protection Act, 2019 — processing register, consents, and data subject requests"
    />

    <p class="tich-text tich-mb-6">
        Policy version <strong>{{ config('data-protection.policy_version') }}</strong>.
        DPO contact: <a href="mailto:{{ config('data-protection.dpo.email') }}">{{ config('data-protection.dpo.email') }}</a>.
        @if (config('data-protection.controller.odpc_registration_number'))
            ODPC registration: {{ config('data-protection.controller.odpc_registration_number') }}.
        @else
            Set <code>ODPC_REGISTRATION_NUMBER</code> in the environment when registered.
        @endif
    </p>

    <article class="tich-card tich-mb-8">
        <h2 class="tich-h3">Record of processing activities</h2>
        <div class="tich-table-wrap tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Activity</th>
                        <th>Lawful basis</th>
                        <th>Tables</th>
                        <th>Retention</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($register as $row)
                        <tr>
                            <td>
                                <strong>{{ $row->name }}</strong>
                                <p class="tich-caption">{{ $row->description }}</p>
                            </td>
                            <td>{{ config('data-protection.lawful_bases.'.$row->lawful_basis, $row->lawful_basis) }}</td>
                            <td class="tich-caption">{{ implode(', ', $row->database_tables ?? []) }}</td>
                            <td class="tich-caption">{{ $row->retention }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No register entries. Run migrations to seed from config.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </article>

    <article class="tich-card tich-mb-8">
        <h2 class="tich-h3">Open data subject requests</h2>
        <div class="tich-table-wrap tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Requester</th>
                        <th>Type</th>
                        <th>Due</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($openRequests as $dsr)
                        <tr>
                            <td>{{ $dsr->request_number }}</td>
                            <td>
                                {{ $dsr->requester_name }}<br>
                                <span class="tich-caption">{{ $dsr->requester_email }}</span>
                            </td>
                            <td>{{ $dsr->requestTypeLabel() }}</td>
                            <td>{{ $dsr->due_at?->format('d M Y') ?? '-' }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $dsr->status)) }}</td>
                            <td>
                                @if ($dsr->status === 'received')
                                    <form method="POST" action="{{ route('admin.data-protection.dsr.progress', $dsr) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="tich-btn tich-btn-ghost">Start</button>
                                    </form>
                                @endif
                                @if (in_array($dsr->status, ['received', 'in_progress'], true))
                                    <form method="POST" action="{{ route('admin.data-protection.dsr.complete', $dsr) }}" style="display:inline; max-width: 14rem; vertical-align: top;">
                                        @csrf
                                        <textarea name="outcome_notes" class="tich-input" rows="2" placeholder="Outcome notes (optional)" style="font-size: 0.8125rem; margin-bottom: 0.35rem;"></textarea>
                                        <button type="submit" class="tich-btn tich-btn-primary">Complete</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6">No open requests.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </article>

    @if ($openBreaches->isNotEmpty())
        <article class="tich-card tich-mb-8">
            <h2 class="tich-h3">Open breach incidents</h2>
            <p class="tich-caption">Register incidents under the Kenya DPA and report to ODPC when required.</p>
            <div class="tich-table-wrap tich-mt-4">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Discovered</th>
                            <th>Affected</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($openBreaches as $incident)
                            <tr>
                                <td>{{ $incident->incident_number }}</td>
                                <td>{{ $incident->discovered_at?->format('d M Y H:i') }}</td>
                                <td>{{ $incident->affected_subjects_count ?? '—' }}</td>
                                <td>{{ ucfirst($incident->status) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </article>
    @endif

    <article class="tich-card">
        <h2 class="tich-h3">Recent consent records</h2>
        <div class="tich-table-wrap tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Subject</th>
                        <th>Purpose</th>
                        <th>Notice version</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentConsents as $consent)
                        <tr>
                            <td>{{ $consent->consented_at?->format('d M Y H:i') }}</td>
                            <td class="tich-caption">{{ $consent->subject_type }} #{{ $consent->subject_id }} · {{ $consent->subject_email }}</td>
                            <td>{{ $consent->purpose_key }}</td>
                            <td>{{ $consent->privacy_notice_version }}</td>
                            <td class="tich-caption">{{ $consent->ip_address }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No consent records yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </article>
@endsection
