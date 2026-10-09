@extends('layouts.employee')

@section('employee-content')
    <x-page-toolbar title="Finances" meta="Your payslips for current and previous months">
    </x-page-toolbar>

    <div class="tich-card tich-table-panel tich-mt-6">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Period</th>
                        <th>Payslip no.</th>
                        <th>Gross (KES)</th>
                        <th>Deductions (KES)</th>
                        <th>Net (KES)</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payslips as $item)
                        @php
                            $period = $item->run?->periodLabel()
                                ?? (\Carbon\Carbon::createFromDate((int) $item->pay_period_year, (int) $item->pay_period_month, 1)->format('F Y'));
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $period }}</strong>
                            </td>
                            <td class="tich-caption">{{ $item->payslip_number ?: '-' }}</td>
                            <td>{{ number_format((float) $item->gross_salary, 2) }}</td>
                            <td>{{ number_format((float) $item->total_deductions, 2) }}</td>
                            <td><strong>{{ number_format((float) $item->net_salary, 2) }}</strong></td>
                            <td>
                                <span class="tich-badge {{ $item->run?->status === 'posted' ? 'tich-badge--success' : 'tich-badge--info' }}">
                                    {{ ucfirst((string) $item->run?->status) }}
                                </span>
                            </td>
                            <td>
                                <div class="tich-flex tich-gap-2" style="flex-wrap:wrap; justify-content:flex-end;">
                                    <a
                                        href="{{ route('employee.finances.payslips.show', $item) }}"
                                        class="tich-btn tich-btn-secondary tich-btn-sm"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >View</a>
                                    <a
                                        href="{{ route('employee.finances.payslips.pdf', $item) }}"
                                        class="tich-btn tich-btn-primary tich-btn-sm"
                                    >Download PDF</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', [
                            'colspan' => 7,
                            'title' => 'No payslips available yet',
                            'icon' => 'inbox',
                        ])
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($payslips->hasPages())
            <div class="tich-mt-4">{{ $payslips->links() }}</div>
        @endif
    </div>
@endsection
