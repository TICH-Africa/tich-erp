@extends('layouts.finance')

@section('title', 'Financial Adjustments')

@section('finance-content')
    <x-page-toolbar title="Financial Adjustments" meta="Scholarships, bursaries, and waivers">
<x-slot:actions>
            <a href="{{ route('finance.student-finance.adjustments.create', ['department' => $department->id]) }}" class="tich-btn tich-btn-primary">+ New adjustment</a>
            <a href="{{ route('finance.student-finance.index', ['department' => $department->id]) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Requested By</th>
                            <th>Date</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($adjustments as $adjustment)
                            <tr>
                                <td>
                                    <strong>{{ $adjustment->student->fullName() ?? 'N/A' }}</strong>
                                    <p class="tich-caption">{{ $adjustment->student->registration_number ?? 'N/A' }}</p>
                                </td>
                                <td class="tich-caption">{{ ucfirst($adjustment->adjustment_type) }}</td>
                                <td>KES {{ number_format($adjustment->amount, 2) }}</td>
                                <td>{{ $adjustment->reason }}</td>
                                <td>
                                    <span class="tich-badge tich-badge--{{ match($adjustment->status) {
                                        'pending' => 'warning',
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        default => 'secondary',
                                    } }}">
                                        {{ ucfirst($adjustment->status) }}
                                    </span>
                                </td>
                                <td class="tich-caption">{{ $adjustment->requestedBy?->fullName() ?? ($adjustment->requested_by ?? 'N/A') }}</td>
                                <td class="tich-caption">{{ $adjustment->created_at?->format('d M Y') }}</td>
                                <td>
                                    <a href="{{ route('finance.student-finance.adjustments.show', ['department' => $department->id, 'id' => $adjustment->id]) }}" class="tich-btn tich-btn-ghost">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="tich-table-empty">No adjustments found.</td>
                            </tr>
                        @endforelse
                    </tbody>
            </table>
        </div>

        @if ($adjustments instanceof \Illuminate\Contracts\Pagination\Paginator && $adjustments->hasPages())
            <div class="tich-mt-4">{{ $adjustments->links() }}</div>
        @endif
    </div>
@endsection



