@extends('layouts.department')

@section('title', 'Payment Milestones')

@section('finance-content')
    <x-page-toolbar title="Payment Milestones" meta="Student fee payment milestones: 50% registration, 75% mid-semester, 100% before final exams">
        <x-slot:actions>
            <a href="{{ route('finance.student-finance.index', ['department' => $department->id]) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Milestone</th>
                        <th>Percentage</th>
                        <th>Amount</th>
                        <th>Paid</th>
                        <th>Status</th>
                        <th>Due Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($milestones as $milestone)
                        <tr>
                            <td>
                                <strong>{{ $milestone->student->fullName() ?? 'N/A' }}</strong>
                                <p class="tich-caption">{{ $milestone->student->registration_number ?? 'N/A' }}</p>
                            </td>
                            <td class="tich-caption">{{ ucfirst(str_replace('_', ' ', $milestone->milestone_type)) }}</td>
                            <td>{{ $milestone->percentage }}%</td>
                            <td>KES {{ number_format($milestone->milestone_amount, 2) }}</td>
                            <td>KES {{ number_format($milestone->paid_amount, 2) }}</td>
                            <td>
                                <span class="tich-badge tich-badge--{{ match($milestone->status) {
                                    'pending' => 'secondary',
                                    'partial' => 'warning',
                                    'paid' => 'success',
                                    'overdue' => 'danger',
                                    default => 'secondary',
                                } }}">
                                    {{ ucfirst($milestone->status) }}
                                </span>
                            </td>
                            <td class="tich-caption">{{ $milestone->due_date?->format('d M Y') }}</td>
                            <td>
                                <a href="{{ route('finance.student-finance.milestones.show', ['department' => $department->id, 'id' => $milestone->id]) }}" class="tich-btn tich-btn-ghost">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="tich-table-empty">No payment milestones found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($milestones instanceof \Illuminate\Contracts\Pagination\Paginator && $milestones->hasPages())
            <div class="tich-mt-4">{{ $milestones->links() }}</div>
        @endif
    </div>
@endsection


