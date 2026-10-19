@extends('layouts.finance')

@section('title', 'Payments')

@section('finance-content')
    <x-page-toolbar title="Payments" meta="Student payment records">
        <x-slot:actions>
            <a href="{{ route('finance.payments.create') }}" class="tich-btn tich-btn-primary">+ Record payment</a>
            <a href="{{ route('finance.student-finance.invoices.index') }}" class="tich-btn tich-btn-ghost">Back to invoices</a>
        </x-slot:actions>
    </x-page-toolbar>

    @include('finance.student-finance.partials.list-filters', [
        'action' => route('finance.student-finance.payments.index'),
        'search' => $search,
        'semesterId' => $semesterId,
        'academicYearId' => $academicYearId,
        'programId' => $programId ?? 0,
        'allSemesters' => $allSemesters,
        'allAcademicYears' => $allAcademicYears,
        'allPrograms' => $allPrograms,
        'showProgram' => true,
        'searchPlaceholder' => 'Search payment, name, reg no, programme…',
    ])

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
                            $program = data_get($student, 'program');
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
                                <a href="{{ route('finance.student-finance.payments.show', ['id' => $payment->id]) }}" class="tich-btn tich-btn-ghost">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            @include('partials.states.table-empty', ['colspan' => 9, 'title' => 'No payments found.', 'icon' => 'inbox'])
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
