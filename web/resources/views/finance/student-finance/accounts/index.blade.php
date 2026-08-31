@extends('layouts.finance')

@section('title', 'Student Accounts')

@section('finance-content')
    <x-page-toolbar title="Student Accounts" meta="Automatic financial accounts for active students">
        <x-slot:actions>
            <a href="{{ route('finance.student-finance.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Registration No</th>
                        <th>Academic Year</th>
                        <th>Total Chargeable</th>
                        <th>Total Paid</th>
                        <th>Outstanding</th>
                        <th>Credit</th>
                        <th>Clearance</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($accounts as $account)
                        <tr>
                            <td><strong>{{ $account->student->fullName() ?? 'N/A' }}</strong></td>
                            <td class="tich-caption">{{ $account->student->registration_number ?? 'N/A' }}</td>
                            <td class="tich-caption">{{ $account->academicYear->year_label ?? 'N/A' }}</td>
                            <td>KES {{ number_format($account->total_chargeable, 2) }}</td>
                            <td>KES {{ number_format($account->total_paid, 2) }}</td>
                            <td><strong>KES {{ number_format($account->outstanding_balance, 2) }}</strong></td>
                            <td>KES {{ number_format($account->credit_balance, 2) }}</td>
                            <td>
                                @if ($account->is_cleared)
                                    <span class="tich-badge tich-badge--success">CLEARED</span>
                                @else
                                    <span class="tich-badge tich-badge--warning">NOT CLEARED</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('finance.student-finance.accounts.show', ['id' => $account->id]) }}" class="tich-btn tich-btn-ghost">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="tich-table-empty">No student accounts found.</td>
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



