@extends('layouts.finance')

@section('title', 'Student accounts')

@section('finance-content')
    <x-page-toolbar title="Student accounts" meta="Accounts receivable by registration number, class, and programme" />

    <form method="get" class="tich-mb-4">
        @include('partials.search-field', ['placeholder' => 'Registration number or student name...', 'value' => request('search')])
    </form>

    <div class="tich-card tich-table-panel">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Registration</th>
                    <th>Student</th>
                    <th>Programme</th>
                    <th>Year</th>
                    <th>Chargeable</th>
                    <th>Paid</th>
                    <th>Balance</th>
                    <th>Cleared</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($accounts as $account)
                    <tr>
                        <td><a href="{{ route('finance.student-accounts.show', $account) }}">{{ $account->student?->registration_number }}</a></td>
                        <td>{{ $account->student?->displayName() }}</td>
                        <td>{{ $account->student?->program?->program_name }}</td>
                        <td>{{ $account->academicYear?->year_label }}</td>
                        <td>KES {{ number_format((float) $account->total_chargeable, 2) }}</td>
                        <td>KES {{ number_format((float) $account->total_paid, 2) }}</td>
                        <td>KES {{ number_format((float) $account->outstanding_balance, 2) }}</td>
                        <td>{{ $account->is_cleared ? 'Yes' : 'No' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="tich-caption">No student accounts found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
