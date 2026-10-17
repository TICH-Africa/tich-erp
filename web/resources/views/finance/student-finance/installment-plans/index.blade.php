@extends('layouts.finance')

@section('title', 'Installment Plans')

@section('finance-content')
    <x-page-toolbar title="Installment Plans" meta="Student installment payment plans and milestones">
        <x-slot:actions>
            <a href="{{ route('finance.student-finance.installment-plans.create') }}" class="tich-btn tich-btn-primary">+ New plan</a>
            <a href="{{ route('finance.student-finance.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    @include('finance.student-finance.partials.list-filters', [
        'action' => route('finance.student-finance.installment-plans.index'),
        'search' => $search,
        'semesterId' => $semesterId,
        'academicYearId' => $academicYearId,
        'allSemesters' => $allSemesters,
        'allAcademicYears' => $allAcademicYears,
    ])

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
                                <a href="{{ route('finance.student-finance.installment-plans.show', ['id' => $plan->id]) }}" class="tich-btn tich-btn-ghost">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="tich-table-empty">No payment plans found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($plans instanceof \Illuminate\Contracts\Pagination\Paginator && $plans->hasPages())
            <div class="tich-mt-4">{{ $plans->links() }}</div>
        @endif
    </div>
@endsection
