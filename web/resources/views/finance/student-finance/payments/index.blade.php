@extends('layouts.finance')

@section('title', 'Payments')

@section('finance-content')
    <x-page-toolbar title="Payments" meta="Student payment records">
        <x-slot:actions>
            <a href="{{ route('finance.payments.create') }}" class="tich-btn tich-btn-primary">+ Record payment</a>
            <a href="{{ route('finance.student-finance.invoices.index', ['department' => $department->id]) }}" class="tich-btn tich-btn-ghost">Back to invoices</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel tich-mb-8">
        <form method="get" action="{{ route('finance.student-finance.payments.index', $department) }}" class="tich-flex tich-items-center tich-gap-2 flex-wrap">
            <div class="tich-flex-1" style="max-width: 260px;">
                <div class="relative">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search student or payment..." class="tich-input pl-8" style="padding: 8px 12px 8px 32px; font-size: 14px;">
                    <svg class="absolute left-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>
            <select name="semester_id" class="tich-input" style="padding: 8px 12px; font-size: 14px; max-width: 180px;">
                <option value="0">All Semesters</option>
                @foreach ($allSemesters as $sem)
                    <option value="{{ $sem->id }}" {{ $semesterId == $sem->id ? 'selected' : '' }}>{{ $sem->displayLabel() }}</option>
                @endforeach
            </select>
            <select name="academic_year_id" class="tich-input" style="padding: 8px 12px; font-size: 14px; max-width: 180px;">
                <option value="0">All Academic Years</option>
                @foreach ($allAcademicYears as $year)
                    <option value="{{ $year->id }}" {{ $academicYearId == $year->id ? 'selected' : '' }}>{{ $year->year_label }}</option>
                @endforeach
            </select>
            <select name="program_id" class="tich-input" style="padding: 8px 12px; font-size: 14px; max-width: 220px;">
                <option value="0">All Programmes</option>
                @foreach ($allPrograms as $program)
                    <option value="{{ $program->id }}" {{ ($programId ?? 0) == $program->id ? 'selected' : '' }}>
                        {{ $program->program_code }} - {{ $program->program_name }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="tich-btn tich-btn-secondary" style="padding: 8px 16px; font-size: 14px;">
                <svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 010 2H4a1 1 0 01-1-1zM3 12a1 1 0 011-1h10a1 1 0 010 2H4a1 1 0 01-1-1zM3 20a1 1 0 011-1h16a1 1 0 010 2H4a1 1 0 01-1-1z"></path>
                </svg>
                Filter
            </button>
            @if ($search !== '' || $semesterId > 0 || $academicYearId > 0 || ($programId ?? 0) > 0)
                <a href="{{ route('finance.student-finance.payments.index', $department) }}" class="tich-btn tich-btn-ghost" style="padding: 8px 16px; font-size: 14px;">
                    <svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Clear
                </a>
            @endif
        </form>
    </div>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Payment</th>
                        <th>Student</th>
                        <th>Programme</th>
                        <th>Invoice</th>
                        <th>Date</th>
                        <th>Method</th>
                        <th>Amount</th>
                        <th>Reference</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        @php
                            $student = $payment->student ?? $payment->invoice?->student;
                            $program = $student?->program;
                        @endphp
                        <tr>
                            <td><strong>{{ $payment->payment_number }}</strong></td>
                            <td>
                                <strong>{{ $student?->fullName() ?? 'N/A' }}</strong>
                                <p class="tich-caption">{{ $student?->registration_number ?? 'N/A' }}</p>
                            </td>
                            <td class="tich-caption">
                                {{ $program?->program_code ?? '-' }}
                                @if ($program?->program_name)
                                    · {{ $program->program_name }}
                                @endif
                            </td>
                            <td class="tich-caption">{{ $payment->invoice->invoice_number ?? 'N/A' }}</td>
                            <td class="tich-caption">{{ $payment->payment_date?->format('d M Y') }}</td>
                            <td class="tich-caption">{{ ucfirst($payment->payment_method) }}</td>
                            <td>KES {{ number_format($payment->amount, 2) }}</td>
                            <td class="tich-caption">{{ $payment->payment_reference ?? '-' }}</td>
                            <td>
                                <a href="{{ route('finance.student-finance.payments.show', ['department' => $department->id, 'id' => $payment->id]) }}" class="tich-btn tich-btn-ghost">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="tich-table-empty">No payments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($payments instanceof \Illuminate\Contracts\Pagination\Paginator && $payments->hasPages())
            <div class="tich-mt-4">{{ $payments->links() }}</div>
        @endif
    </div>
@endsection



