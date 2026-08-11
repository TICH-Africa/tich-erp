@extends('layouts.finance')

@section('title', 'Installment Plans')

@section('finance-content')
    <x-page-toolbar title="Installment Plans" meta="Student installment payment plans">
<x-slot:actions>
            <a href="{{ route('finance.student-finance.installment-plans.create', ['department' => $department->id]) }}" class="tich-btn tich-btn-primary">+ New plan</a>
            <a href="{{ route('finance.student-finance.index', ['department' => $department->id]) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Plan</th>
                        <th>Student</th>
                        <th>Invoice</th>
                        <th>Total</th>
                        <th>Paid</th>
                        <th>Remaining</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($plans as $plan)
                        <tr>
                            <td><strong>{{ $plan->plan_number }}</strong></td>
                            <td>
                                <strong>{{ $plan->student->fullName() ?? 'N/A' }}</strong>
                                <p class="tich-caption">{{ $plan->student->registration_number ?? 'N/A' }}</p>
                            </td>
                            <td class="tich-caption">{{ $plan->invoice->invoice_number ?? 'N/A' }}</td>
                            <td>KES {{ number_format($plan->total_amount, 2) }}</td>
                            <td>KES {{ number_format($plan->paid_amount, 2) }}</td>
                            <td>KES {{ number_format($plan->remaining_amount, 2) }}</td>
                            <td>
                                <span class="tich-badge tich-badge--{{ match($plan->status) {
                                    'active' => 'info',
                                    'completed' => 'success',
                                    'defaulted' => 'danger',
                                    'cancelled' => 'secondary',
                                    default => 'secondary',
                                } }}">
                                    {{ ucfirst($plan->status) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('finance.student-finance.installment-plans.index', $department) }}" class="tich-btn tich-btn-ghost">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="tich-table-empty">No installment plans found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($plans instanceof \Illuminate\Contracts\Pagination\Paginator && $plans->hasPages())
            <div class="tich-mt-4">{{ $plans->links() }}</div>
        @endif
    </div>

    @if ($milestones->count() > 0)
        <div class="tich-card tich-mb-8">
            <h3 class="tich-h4 tich-mb-4">Payment Milestones (50% / 75% / 100%)</h3>
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
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($milestones ?? [] as $milestone)
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
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="tich-table-empty">No payment milestones found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection



