@extends('layouts.employee')

@section('title', $portalTitle)

@section('employee-content')
    <x-page-toolbar :title="$portalTitle" :meta="$staff->employee_number . ' · ' . ($staff->job_title ?? 'Staff')">
    </x-page-toolbar>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Policy</th>
                        <th>Version</th>
                        <th>Effective Date</th>
                        <th>Status</th>
                        <th>Acknowledged</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($acknowledgements as $ack)
                        <tr>
                            <td><strong>{{ $ack->policy->title ?? $ack->policy_name }}</strong></td>
                            <td class="tich-caption">v{{ $ack->policy_version }}</td>
                            <td class="tich-caption">{{ $ack->effective_date?->format('Y-m-d') ?? '-' }}</td>
                            <td>
                                <span class="tich-badge tich-badge--{{ $ack->policy->is_active ? 'success' : 'warning' }}">
                                    {{ $ack->policy->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                @if ($ack->is_acknowledged)
                                    <span class="tich-badge tich-badge--success">Acknowledged</span>
                                    <p class="tich-caption">{{ $ack->acknowledged_at?->format('d M Y, H:i') }}</p>
                                @else
                                    <span class="tich-badge tich-badge--warning">Pending</span>
                                @endif
                            </td>
                            <td>
                                @if (!$ack->is_acknowledged)
                                    <a href="{{ route('policies.acknowledge', $ack->policy_id) }}" class="tich-btn tich-btn-primary">Acknowledge</a>
                                @else
                                    <a href="{{ route('policies.view', $ack->policy_id) }}" class="tich-btn tich-btn-ghost" target="_blank">View</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="tich-table-empty">No policies assigned to you yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($acknowledgements->hasPages())
            <div class="tich-mt-4">{{ $acknowledgements->links() }}</div>
        @endif
    </div>
@endsection
