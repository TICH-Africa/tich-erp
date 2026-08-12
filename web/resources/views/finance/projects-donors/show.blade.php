@extends('layouts.finance')

@section('title', 'Projects & Donors')

@section('finance-content')
    <x-page-toolbar title="{{ $project->project_name }}" meta="{{ $project->project_code }} · {{ $project->donor_name }}">
        <x-slot:actions>
            <a href="{{ route('finance.projects-donors.index', $department) }}" class="tich-btn tich-btn-ghost">Back to projects</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-grid tich-grid--3 tich-mt-6">
        <article class="tich-card tich-stat">
            <p class="tich-caption">Total grant</p>
            <p class="tich-stat__value" style="font-size:1.1rem;">{{ $project->currency }} {{ number_format((float) $project->total_grant_amount, 2) }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Disbursed (record)</p>
            <p class="tich-stat__value" style="font-size:1.1rem;">{{ $project->currency }} {{ number_format((float) $project->disbursed_amount, 2) }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Received (KES)</p>
            <p class="tich-stat__value">KES {{ number_format($project->disbursedKesTotal(), 2) }}</p>
        </article>
    </div>

    <div class="tich-grid tich-grid--2 tich-mt-6">
        <article class="tich-card">
            <h3 class="tich-h4">Project profile</h3>
            <dl class="tich-mt-4" style="display:grid; gap:0.75rem;">
                <div><dt class="tich-caption">Donor type</dt><dd>{{ ucfirst(str_replace('_', ' ', $project->donor_type)) }}</dd></div>
                <div><dt class="tich-caption">Project leader</dt><dd>{{ $project->leader?->fullName() ?? '-' }}</dd></div>
                <div><dt class="tich-caption">Start date</dt><dd>{{ $project->start_date?->format('d M Y') ?? '-' }}</dd></div>
                <div><dt class="tich-caption">End date</dt><dd>{{ $project->end_date?->format('d M Y') ?? '-' }}</dd></div>
                <div><dt class="tich-caption">Status</dt><dd>{{ ucfirst($project->status) }}</dd></div>
                <div><dt class="tich-caption">KES equivalent (grant)</dt><dd>KES {{ number_format((float) $project->kes_equivalent, 2) }}</dd></div>
            </dl>
        </article>

        <article class="tich-card">
            <h3 class="tich-h4">Disbursements</h3>
            <div class="tich-table-wrap tich-mt-4">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Ref</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>KES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($project->disbursements as $disbursement)
                            <tr>
                                <td>{{ $disbursement->disbursement_number }}</td>
                                <td>{{ $disbursement->receipt_date?->format('d M Y') ?? '-' }}</td>
                                <td>{{ $disbursement->currency_received }} {{ number_format((float) $disbursement->amount_received, 2) }}</td>
                                <td>KES {{ number_format((float) $disbursement->kes_amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="tich-table-empty">No disbursements recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </div>
@endsection
