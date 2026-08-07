@extends('layouts.hr')

@section('title', 'Policy acknowledgements')

@section('hr-content')
    <x-page-toolbar title="Policy acknowledgements" :meta="$policy->title ?? 'All policies'">
        @if (!$policy)
            <x-slot:filters>
                <form method="GET" action="{{ route('hr.policies.acknowledgements.index') }}" class="tich-page-toolbar__filters-form">
                    <select name="status" class="tich-input tich-input--compact">
                        <option value="">All statuses</option>
                        <option value="1" @selected(request('status') === '1')>Acknowledged</option>
                        <option value="0" @selected(request('status') === '0')>Pending</option>
                    </select>
                </form>
            </x-slot:filters>
        @endif
    </x-page-toolbar>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Policy</th>
                        <th>Staff</th>
                        <th>Version</th>
                        <th>Status</th>
                        <th>Acknowledged at</th>
                        <th>Method</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($acknowledgements as $ack)
                        <tr>
                            <td><strong>{{ $ack->policy->title ?? $ack->policy_name }}</strong></td>
                            <td>
                                <strong>{{ $ack->staff->fullName() }}</strong>
                                <p class="tich-caption">{{ $ack->staff->employee_number }}</p>
                            </td>
                            <td class="tich-caption">v{{ $ack->policy_version }}</td>
                            <td>
                                <span class="tich-badge tich-badge--{{ $ack->is_acknowledged ? 'success' : 'warning' }}">
                                    {{ $ack->is_acknowledged ? 'Acknowledged' : 'Pending' }}
                                </span>
                            </td>
                            <td class="tich-caption">{{ $ack->acknowledged_at?->format('d M Y, H:i') ?? '—' }}</td>
                            <td class="tich-caption">{{ ucfirst($ack->acknowledgement_method) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="tich-table-empty">No acknowledgements found.</td>
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
