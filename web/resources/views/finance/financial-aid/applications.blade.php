@extends('layouts.finance')

@section('title', 'Approved Financial Aid Applications')

@section('finance-content')
    <x-page-toolbar title="Approved Applications" meta="Allocate approved financial aid to student fee accounts">
        <x-slot:actions>
            <a href="{{ route('finance.financial-aid.donations') }}" class="tich-btn tich-btn-ghost">Donations</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if ($applications->isEmpty())
        <div class="tich-card tich-mt-6">
            <div class="tich-card__body" style="text-align: center; padding: 3rem;">
                <p class="tich-text tich-text--muted">No approved applications pending allocation.</p>
            </div>
        </div>
    @else
        <div class="tich-card tich-mt-6">
            <div class="tich-table-wrap">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Email</th>
                            <th>Opportunity</th>
                            <th>Type</th>
                            <th>Approved Amount</th>
                            <th>Approved</th>
                            <th>Allocation Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($applications as $application)
                            <tr>
                                <td>{{ $application->student_name }}</td>
                                <td>{{ $application->student_email }}</td>
                                <td>{{ $application->opportunity->title }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $application->opportunity->funding_type)) }}</td>
                                <td>KES {{ number_format((float) $application->approved_amount, 2) }}</td>
                                <td>{{ $application->approved_at?->format('d M Y') }}</td>
                                <td>
                                    @php
                                        $allocColors = [
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'allocated' => 'bg-green-100 text-green-800',
                                            'partial' => 'bg-blue-100 text-blue-800',
                                        ];
                                    @endphp
                                    <span class="tich-badge {{ $allocColors[$application->allocation_status] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ ucfirst($application->allocation_status) }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('finance.financial-aid.applications.show', $application) }}" class="tich-btn tich-btn-ghost tich-btn--sm">
                                        {{ $application->allocation_status === 'allocated' ? 'View' : 'Allocate' }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $applications->links() }}
        </div>
    @endif
@endsection