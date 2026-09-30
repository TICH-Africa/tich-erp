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
                        <th>Photo</th>
                        <th>Staff</th>
                        <th>Type</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Renewal status</th>
                        <th>Signed</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($contracts as $contract)
                        <tr>
                            <td>{{ $contract->contract_number }}</td>
                            <td>
                                @if ($contract->staff)
                                    @include('hr.staff.partials.table-avatar', ['member' => $contract->staff])
                                @else
                                    <div class="tich-staff-table-avatar" aria-hidden="true">
                                        <span>?</span>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $contract->staff->fullName() ?? '-' }}</strong>
                                <p class="tich-caption">{{ $contract->staff->employee_number ?? '' }}</p>
                            </td>
                            <td class="tich-caption">{{ ucfirst($contract->contract_type) }}</td>
                            <td class="tich-caption">{{ $contract->start_date?->format('Y-m-d') }}</td>
                            <td class="tich-caption">{{ $contract->end_date?->format('Y-m-d') ?? 'Ongoing' }}</td>
                            <td>
                                @php
                                    $renewalLabel = match ($contract->renewal_status) {
                                        'pending' => 'Active',
                                        'renewed' => 'Renewed',
                                        'terminated' => 'Terminated',
                                        'expired' => 'Expired',
                                        default => ucfirst((string) $contract->renewal_status),
                                    };
                                    $renewalBadge = match ($contract->renewal_status) {
                                        'pending' => 'success',
                                        'renewed' => 'info',
                                        'terminated', 'expired' => 'danger',
                                        default => 'warning',
                                    };
                                @endphp
                                <span class="tich-badge tich-badge--{{ $renewalBadge }}">
                                    {{ $renewalLabel }}
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
                        @include('partials.states.table-empty', ['colspan' => 9, 'title' => 'No contracts found', 'icon' => 'inbox'])
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
