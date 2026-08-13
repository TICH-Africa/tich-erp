@extends('layouts.hr')

@section('title', 'Contracts')

@section('hr-content')
    <x-page-toolbar title="Contracts" meta="Employment contracts, renewals, and expiry alerts">
        <x-slot:actions>
            <a href="{{ route('hr.contracts.create') }}" class="tich-btn tich-btn-primary">+ New Contract</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Contract No.</th>
                        <th>Staff</th>
                        <th>Type</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th>Signed</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($contracts as $contract)
                        <tr>
                            <td>{{ $contract->contract_number }}</td>
                            <td>
                                <strong>{{ $contract->staff->fullName() ?? '-' }}</strong>
                                <p class="tich-caption">{{ $contract->staff->employee_number ?? '' }}</p>
                            </td>
                            <td class="tich-caption">{{ ucfirst($contract->contract_type) }}</td>
                            <td class="tich-caption">{{ $contract->start_date?->format('Y-m-d') }}</td>
                            <td class="tich-caption">{{ $contract->end_date?->format('Y-m-d') ?? 'Ongoing' }}</td>
                            <td>
                                <span class="tich-badge tich-badge--{{ $contract->renewal_status === 'pending' ? 'warning' : ($contract->renewal_status === 'renewed' ? 'info' : 'success') }}">
                                    {{ ucfirst($contract->renewal_status) }}
                                </span>
                            </td>
                            <td>
                                @if ($contract->is_signed)
                                    <span class="tich-badge tich-badge--success">Yes</span>
                                @else
                                    <span class="tich-badge tich-badge--warning">No</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('hr.contracts.show', $contract) }}" class="tich-btn tich-btn-ghost">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="tich-table-empty">No contracts found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($contracts->hasPages())
            <div class="tich-mt-6">
                {{ $contracts->links() }}
            </div>
        @endif
    </div>
@endsection
