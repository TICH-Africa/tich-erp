@extends('layouts.finance')

@section('title', 'Financial Aid Management')

@section('finance-content')
    <x-page-toolbar title="Financial Aid Management" meta="Allocate approved aid, manage donations & sponsorships">
        <x-slot:actions>
            <a href="{{ route('finance.financial-aid.applications') }}" class="tich-btn tich-btn-primary">Approved Applications</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-grid tich-grid--3 tich-mt-6" style="gap: 1.5rem;">
        <article class="tich-card tich-stat">
            <div class="tich-card__body" style="text-align: center;">
                <p class="tich-stat__value" style="color: #3b82f6;">{{ $approvedApplications->total() }}</p>
                <p class="tich-caption">Approved Apps Pending Allocation</p>
            </div>
        </article>
        <article class="tich-card tich-stat">
            <div class="tich-card__body" style="text-align: center;">
                <p class="tich-stat__value" style="color: #16a34a;">{{ $pendingDonations->total() }}</p>
                <p class="tich-caption">Pending Donations</p>
            </div>
        </article>
        <article class="tich-card tich-stat">
            <div class="tich-card__body" style="text-align: center;">
                <p class="tich-stat__value" style="color: #9333ea;">{{ $pendingSponsorships->total() }}</p>
                <p class="tich-caption">Pending Sponsorships</p>
            </div>
        </article>
    </div>

    <div class="tich-grid tich-grid--2 tich-mt-6" style="gap: 1.5rem;">
        <article class="tich-card">
            <div class="tich-card__body">
                <div class="tich-flex tich-flex--between tich-mb-4">
                    <h3 class="tich-h3">Pending Donations</h3>
                    <a href="{{ route('finance.financial-aid.donations') }}" class="tich-link">View all</a>
                </div>
                @if ($pendingDonations->isEmpty())
                    <p class="tich-text tich-text--muted">No pending donations.</p>
                @else
                    <div class="tich-table-wrap">
                        <table class="tich-admin-table">
                            <thead>
                                <tr>
                                    <th>Donor</th>
                                    <th>Amount</th>
                                    <th>Designation</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pendingDonations as $donation)
                                    <tr>
                                        <td>{{ $donation->donor_name }}</td>
                                        <td>KES {{ number_format((float) $donation->amount, 2) }}</td>
                                        <td>{{ $donation->designation ?? 'General' }}</td>
                                        <td>{{ $donation->created_at->format('d M Y') }}</td>
                                        <td>
                                            <span class="tich-badge bg-yellow-100 text-yellow-800">{{ ucfirst($donation->status) }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </article>

        <article class="tich-card">
            <div class="tich-card__body">
                <div class="tich-flex tich-flex--between tich-mb-4">
                    <h3 class="tich-h3">Pending Sponsorships</h3>
                    <a href="{{ route('finance.financial-aid.sponsorships') }}" class="tich-link">View all</a>
                </div>
                @if ($pendingSponsorships->isEmpty())
                    <p class="tich-text tich-text--muted">No pending sponsorships.</p>
                @else
                    <div class="tich-table-wrap">
                        <table class="tich-admin-table">
                            <thead>
                                <tr>
                                    <th>Sponsor</th>
                                    <th>Type</th>
                                    <th>Field</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pendingSponsorships as $inquiry)
                                    <tr>
                                        <td>{{ $inquiry->sponsor_name }}</td>
                                        <td>{{ ucfirst(str_replace('_', ' ', $inquiry->sponsor_type)) }}</td>
                                        <td>{{ $inquiry->preferred_field ?? 'Any' }}</td>
                                        <td>{{ $inquiry->created_at->format('d M Y') }}</td>
                                        <td>
                                            <span class="tich-badge bg-yellow-100 text-yellow-800">{{ ucfirst($inquiry->status) }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </article>
    </div>

    <article class="tich-card tich-mt-6">
        <div class="tich-card__body">
            <h3 class="tich-h3 tich-mb-4">Approved Applications Requiring Allocation</h3>
            @if ($approvedApplications->isEmpty())
                <p class="tich-text tich-text--muted">No approved applications pending allocation.</p>
            @else
                <div class="tich-table-wrap">
                    <table class="tich-admin-table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Opportunity</th>
                                <th>Approved Amount</th>
                                <th>Approved</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($approvedApplications as $application)
                                <tr>
                                    <td>{{ $application->student_name }}</td>
                                    <td>{{ $application->opportunity->title }}</td>
                                    <td>KES {{ number_format((float) $application->approved_amount, 2) }}</td>
                                    <td>{{ $application->approved_at?->format('d M Y') }}</td>
                                    <td>
                                        <span class="tich-badge bg-green-100 text-green-800">{{ ucfirst($application->status) }}</span>
                                    </td>
                                    <td>
                                        <a href="{{ route('finance.financial-aid.applications.show', $application) }}" class="tich-btn tich-btn-ghost tich-btn--sm">Allocate</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    {{ $approvedApplications->links() }}
                </div>
            @endif
        </div>
    </article>
@endsection