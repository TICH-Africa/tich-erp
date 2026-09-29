@extends('layouts.finance')

@section('title', 'Donation Management')

@section('finance-content')
    <x-page-toolbar title="Donations" meta="Manage and confirm incoming donations">
        <x-slot:actions>
            <a href="{{ route('finance.financial-aid.applications') }}" class="tich-btn tich-btn-ghost">Applications</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if ($donations->isEmpty())
        <div class="tich-card tich-mt-6">
            <div class="tich-card__body" style="text-align: center; padding: 3rem;">
                <p class="tich-text tich-text--muted">No donations recorded.</p>
            </div>
        </div>
    @else
        <div class="tich-card tich-mt-6">
            <div class="tich-table-wrap">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Donor</th>
                            <th>Email</th>
                            <th>Amount</th>
                            <th>Designation</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($donations as $donation)
                            <tr>
                                <td>{{ $donation->donor_name }}</td>
                                <td>{{ $donation->donor_email }}</td>
                                <td>KES {{ number_format((float) $donation->amount, 2) }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $donation->designation ?? 'General')) }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $donation->donation_type)) }}</td>
                                <td>{{ $donation->created_at->format('d M Y') }}</td>
                                <td>
                                    @php
                                        $statusColors = [
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'confirmed' => 'bg-blue-100 text-blue-800',
                                            'completed' => 'bg-green-100 text-green-800',
                                            'cancelled' => 'bg-red-100 text-red-800',
                                        ];
                                    @endphp
                                    <span class="tich-badge {{ $statusColors[$donation->status] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ ucfirst($donation->status) }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('finance.financial-aid.donations.show', $donation) }}" class="tich-btn tich-btn-ghost tich-btn--sm">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $donations->links() }}
        </div>
    @endif
@endsection