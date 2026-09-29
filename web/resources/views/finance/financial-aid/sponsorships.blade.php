@extends('layouts.finance')

@section('title', 'Sponsorship Inquiries')

@section('finance-content')
    <x-page-toolbar title="Sponsorship Inquiries" meta="Manage and follow up on sponsorship requests">
        <x-slot:actions>
            <a href="{{ route('finance.financial-aid.applications') }}" class="tich-btn tich-btn-ghost">Applications</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if ($sponsorships->isEmpty())
        <div class="tich-card tich-mt-6">
            <div class="tich-card__body" style="text-align: center; padding: 3rem;">
                <p class="tich-text tich-text--muted">No sponsorship inquiries.</p>
            </div>
        </div>
    @else
        <div class="tich-card tich-mt-6">
            <div class="tich-table-wrap">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Sponsor</th>
                            <th>Email</th>
                            <th>Type</th>
                            <th>Duration</th>
                            <th>Field</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sponsorships as $inquiry)
                            <tr>
                                <td>{{ $inquiry->sponsor_name }}</td>
                                <td>{{ $inquiry->sponsor_email }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $inquiry->sponsor_type)) }}</td>
                                <td>{{ $inquiry->duration === 'ongoing' ? 'Ongoing' : $inquiry->duration . ' Year(s)' }}</td>
                                <td>{{ $inquiry->preferred_field ?? 'Any' }}</td>
                                <td>{{ $inquiry->created_at->format('d M Y') }}</td>
                                <td>
                                    @php
                                        $statusColors = [
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'contacted' => 'bg-blue-100 text-blue-800',
                                            'in_progress' => 'bg-purple-100 text-purple-800',
                                            'completed' => 'bg-green-100 text-green-800',
                                            'declined' => 'bg-red-100 text-red-800',
                                        ];
                                    @endphp
                                    <span class="tich-badge {{ $statusColors[$inquiry->status] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ ucfirst($inquiry->status) }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('finance.financial-aid.sponsorships.show', $inquiry) }}" class="tich-btn tich-btn-ghost tich-btn--sm">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $sponsorships->links() }}
        </div>
    @endif
@endsection