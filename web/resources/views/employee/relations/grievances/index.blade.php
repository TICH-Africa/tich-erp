@extends('layouts.employee')

@section('title', $portalTitle)

@section('employee-content')
    <x-page-toolbar :title="$portalTitle" :meta="$staff->employee_number . ' · ' . ($staff->job_title ?? 'Staff')">
        <x-slot:actions>
            <a href="{{ route('employee.relations.grievances.create') }}" class="tich-btn tich-btn-primary">+ New grievance</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($grievances as $grievance)
                        <tr>
                            <td class="tich-caption">{{ $grievance->grievance_type ?? '-' }}</td>
                            <td>
                                <span class="tich-badge tich-badge--{{ match($grievance->status) {
                                    'open' => 'warning',
                                    'under_review' => 'info',
                                    'resolved' => 'success',
                                    'closed' => 'secondary',
                                    default => 'secondary',
                                } }}">
                                    {{ ucfirst(str_replace('_', ' ', $grievance->status)) }}
                                </span>
                            </td>
                            <td class="tich-caption">{{ $grievance->created_at?->format('d M Y') }}</td>
                            <td>
                                <a href="{{ route('employee.relations.grievances.show', $grievance) }}" class="tich-btn tich-btn-ghost">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="tich-table-empty">No grievances found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($grievances->hasPages())
            <div class="tich-mt-4">{{ $grievances->links() }}</div>
        @endif
    </div>
@endsection
