@extends('layouts.finance')

@section('title', 'Student Directory')

@section('finance-content')
    <x-page-toolbar title="Student Directory" meta="All students with their financial summary">
        <x-slot:actions>
            <a href="{{ route('finance.students.index') }}" class="tich-btn tich-btn-secondary">Refresh</a>
        </x-slot:actions>
    </x-page-toolbar>

    <form method="GET" class="tich-mb-4">
        @include('partials.search-field', ['placeholder' => 'Name, reg no...', 'value' => request('search') ?? ''])
        <select name="program_id" class="tich-input tich-input--compact">
            <option value="">All programmes</option>
            @foreach ($programs as $program)
                <option value="{{ $program->id }}" @selected((string)(request('program_id') ?? '') === (string) $program->id)>
                    {{ $program->program_code }}
                </option>
            @endforeach
        </select>
    </form>

    <div class="tich-card tich-table-panel tich-mt-6">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Reg. Number</th>
                    <th>Student</th>
                    <th>Programme</th>
                    <th>Total Chargeable</th>
                    <th>Total Paid</th>
                    <th>Balance</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($students as $student)
                    <tr>
                        <td>{{ $student->registration_number }}</td>
                        <td>
                            {{ $student->applicant?->fullName() ?? 'Student' }}
                            <span class="tich-caption">{{ $student->applicant?->email }}</span>
                        </td>
                        <td>{{ $student->program?->program_name ?? '-' }}</td>
                        <td>KES {{ number_format($student->studentAccounts->sum('total_chargeable'), 2) }}</td>
                        <td>
                            @php
                                $paid = $student->studentAccounts->sum('total_paid');
                            @endphp
                            KES {{ number_format($paid, 2) }}
                        </td>
                        <td>
                            @php
                                $balance = $student->studentAccounts->sum('outstanding_balance');
                            @endphp
                            <span class="{{ $balance > 0 ? 'tich-status--warning' : 'tich-status--success' }}">
                                KES {{ number_format(max(0, $balance), 2) }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('finance.students.show', $student->id) }}" class="tich-link">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="tich-caption">No students found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="tich-mt-4">{{ $students->links() }}</div>

    @if(session('success'))
        <div class="tich-alert tich-alert--success tich-mt-6">{{ session('success') }}</div>
    @endif
@endsection
