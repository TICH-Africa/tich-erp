@extends('layouts.finance')

@section('title', 'Financial Clearance')

@section('finance-content')
    <x-page-toolbar title="Financial Clearance" meta="Student balance clearance status">
<x-slot:actions>
            <a href="{{ route('finance.student-finance.index', ['department' => $department->id]) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-stat-row tich-mb-8">
        <div class="tich-stat">
            <p class="tich-stat__label">Cleared</p>
            <p class="tich-stat__value">{{ $clearedCount ?? 0 }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Not Cleared</p>
            <p class="tich-stat__value">{{ $notClearedCount ?? 0 }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Credit Balances</p>
            <p class="tich-stat__value">{{ $creditCount ?? 0 }}</p>
        </div>
    </div>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Registration No</th>
                        <th>Academic Year</th>
                        <th>Balance</th>
                        <th>Credit</th>
                        <th>Clearance</th>
                        <th>Last Payment</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($accounts as $account)
                        <tr>
                            <td><strong>{{ $account->student->fullName() ?? 'N/A' }}</strong></td>
                            <td class="tich-caption">{{ $account->student->registration_number ?? 'N/A' }}</td>
                            <td class="tich-caption">{{ $account->academicYear->year_label ?? 'N/A' }}</td>
                            <td><strong>KES {{ number_format($account->outstanding_balance, 2) }}</strong></td>
                            <td>KES {{ number_format($account->credit_balance, 2) }}</td>
                            <td>
                                @if ($account->is_cleared)
                                    <span class="tich-badge tich-badge--success">CLEARED</span>
                                @else
                                    <span class="tich-badge tich-badge--danger">NOT CLEARED</span>
                                @endif
                            </td>
                            <td class="tich-caption">{{ $account->last_payment_date?->format('d M Y') }}</td>
<td>
                                <a href="{{ route('finance.student-finance.accounts.show', ['department' => $department->id, 'id' => $account->id]) }}" class="tich-btn tich-btn-ghost">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="tich-table-empty">No accounts found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($accounts instanceof \Illuminate\Contracts\Pagination\Paginator && $accounts->hasPages())
            <div class="tich-mt-4">{{ $accounts->links() }}</div>
        @endif
    </div>
@endsection



