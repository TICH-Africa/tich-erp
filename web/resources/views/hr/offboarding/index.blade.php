@extends('layouts.hr')

@section('title', 'Offboarding')

@section('hr-content')
    <div class="tich-mb-8">
        <div class="tich-flex tich-flex--between tich-flex--start">
            <div>
                <h1 class="tich-h1">Offboarding</h1>
                <p class="tich-text tich-mt-2">Manage employee separations and exit clearances.</p>
            </div>
            <a href="{{ route('hr.offboarding.create') }}" class="tich-btn tich-btn-primary">+ New Offboarding</a>
        </div>
    </div>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Staff</th>
                        <th>Exit Type</th>
                        <th>Exit Date</th>
                        <th>Status</th>
                        <th>Initiated</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($offboardings as $offboarding)
                        <tr>
                            <td>
                                <strong>{{ $offboarding->staff->fullName() ?? '—' }}</strong>
                                <p class="tich-caption">{{ $offboarding->staff->employee_number ?? '' }}</p>
                            </td>
                            <td class="tich-caption">{{ ucfirst(str_replace('_', ' ', $offboarding->exit_type)) }}</td>
                            <td class="tich-caption">{{ $offboarding->exit_date?->format('Y-m-d') }}</td>
                            <td>
                                <span class="tich-badge tich-badge--{{ $offboarding->status === 'completed' ? 'success' : ($offboarding->status === 'rejected' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($offboarding->status) }}
                                </span>
                            </td>
                            <td class="tich-caption">{{ $offboarding->initiator?->fullName() ?? '—' }}</td>
                            <td>
                                <a href="{{ route('hr.offboarding.show', $offboarding) }}" class="tich-btn tich-btn-ghost">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="tich-table-empty">No offboarding records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($offboardings->hasPages())
            <div class="tich-mt-6">
                {{ $offboardings->links() }}
            </div>
        @endif
    </div>
@endsection
