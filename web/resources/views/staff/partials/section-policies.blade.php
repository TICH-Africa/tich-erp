@extends('layouts.staff')

@section('staff-content')
    <x-page-toolbar title="HR Policies" meta="Policies assigned to you by HR" />

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
                    @forelse ($policies as $ack)
                        @php $policy = $ack->policy @endphp
                        <tr>
                            <td>
                                <strong>{{ $policy->title ?? $ack->policy_name }}</strong>
                                <p class="tich-caption">{{ $policy->original_filename ?? '' }}</p>
                            </td>
                            <td class="tich-caption">v{{ $ack->policy_version }}</td>
                            <td class="tich-caption">{{ $policy->effective_date?->format('Y-m-d') ?? '—' }}</td>
                            <td>
                                <span class="tich-badge tich-badge--{{ $policy->is_active ? 'success' : 'warning' }}">
                                    {{ $policy->is_active ? 'Active' : 'Inactive' }}
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
                                    <a href="{{ route('staff.policies.acknowledge', $ack->policy_id) }}" class="tich-btn tich-btn-primary tich-btn--sm">Acknowledge</a>
                                @else
                                    <a href="{{ route('staff.policies.view', $ack->policy_id) }}" class="tich-btn tich-btn-ghost tich-btn--sm" target="_blank">View</a>
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
    </div>
@endsection
