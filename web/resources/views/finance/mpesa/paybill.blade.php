@extends('layouts.finance')

@section('title', 'Paybill transactions')

@section('finance-content')
    @php
        $badges = [
            \App\Models\MpesaC2bTransaction::STATUS_BOOKED => 'success',
            \App\Models\MpesaC2bTransaction::STATUS_ACCEPTED => 'info',
            \App\Models\MpesaC2bTransaction::STATUS_DUPLICATE => 'warning',
            \App\Models\MpesaC2bTransaction::STATUS_UNMATCHED => 'danger',
            \App\Models\MpesaC2bTransaction::STATUS_REFUSED => 'danger',
            'received' => 'warning',
        ];
    @endphp

    <x-page-toolbar
        title="Paybill transactions"
        meta="Every M-Pesa paybill callback Safaricom has sent - what it became and what still needs a person"
    >
        <x-slot:actions>
            <a
                href="{{ route('finance.mpesa.paybill.export', request()->query()) }}"
                class="tich-btn tich-btn-secondary"
                @unless ($available) disabled @endunless
            >Export CSV</a>
            <a href="{{ route('finance.mpesa.settings') }}" class="tich-btn tich-btn-ghost">M-Pesa settings</a>
        </x-slot:actions>
        <x-slot:filters>
            <form method="GET" action="{{ route('finance.mpesa.paybill') }}" class="tich-page-toolbar__filters-form" data-live-filter>
                @include('partials.search-field', ['placeholder' => 'Admission no., student name, receipt, phone…', 'value' => $filters['search']])
                <select name="callback" class="tich-input tich-input--compact" title="Callback" aria-label="Callback">
                    <option value="confirmation" @selected($filters['callback'] === 'confirmation')>Money reported</option>
                    <option value="validation" @selected($filters['callback'] === 'validation')>Permission checks</option>
                    <option value="all" @selected($filters['callback'] === 'all')>All callbacks</option>
                </select>
                <select name="status" class="tich-input tich-input--compact" title="Status" aria-label="Status">
                    <option value="all" @selected($filters['status'] === 'all')>Any status</option>
                    @foreach (\App\Models\MpesaC2bTransaction::OUTCOMES as $value => $label)
                        <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <input
                    type="date"
                    name="from"
                    class="tich-input tich-input--compact"
                    value="{{ $filters['from'] }}"
                    title="Received from"
                    aria-label="Received from"
                >
                <input
                    type="date"
                    name="to"
                    class="tich-input tich-input--compact"
                    value="{{ $filters['to'] }}"
                    title="Received to"
                    aria-label="Received to"
                >
                <button type="submit" class="tich-btn tich-btn-ghost">Filter</button>
            </form>
        </x-slot:filters>
    </x-page-toolbar>

    <div class="tich-grid tich-grid--4 tich-mt-8">
        <article class="tich-card tich-stat">
            <p class="tich-caption">Booked from the paybill</p>
            <p class="tich-stat__value" style="font-size:1.4rem;">KES {{ number_format($totals['booked'], 2) }}</p>
            <p class="tich-caption tich-mt-2">{{ $totals['booked_count'] }} payment{{ $totals['booked_count'] === 1 ? '' : 's' }} recorded without Finance typing anything.</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Needs reconciling</p>
            <p class="tich-stat__value" style="font-size:1.4rem;">{{ $totals['unmatched_count'] }}</p>
            <p class="tich-caption tich-mt-2">Money Safaricom reported that we could not tie to a student's open bill.</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Refused at the check</p>
            <p class="tich-stat__value" style="font-size:1.4rem;">{{ $totals['refused_count'] }}</p>
            <p class="tich-caption tich-mt-2">Bounced back before the money left the payer.</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Paybill number</p>
            <p class="tich-stat__value" style="font-size:1.4rem;">{{ $paybill !== '' ? $paybill : 'Not set' }}</p>
            <p class="tich-caption tich-mt-2">Students use their admission number as the account number.</p>
        </article>
    </div>

    <div class="tich-card tich-table-panel tich-mt-8">
        @unless ($available)
            <p class="tich-caption" style="padding:1rem;">
                The paybill log table is not in this database yet. Apply the paybill transactions table
                and every callback Safaricom sends will show here.
            </p>
        @else
            <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Admission number</th>
                    <th>Student</th>
                    <th>M-Pesa receipt</th>
                    <th>Amount</th>
                    <th>Payment date and time</th>
                    <th>Payer phone</th>
                    <th>Transaction status</th>
                    <th>Allocation status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td>{{ $row->bill_ref_number ?: '-' }}</td>
                        <td>{{ $row->student?->displayName() ?? 'Not matched' }}</td>
                        <td>{{ $row->receipt_number ?: '-' }}</td>
                        <td>{{ $row->amount !== null ? 'KES '.number_format((float) $row->amount, 2) : '-' }}</td>
                        <td>{{ $row->created_at?->format('d M Y H:i') ?? '-' }}</td>
                        <td>{{ $row->msisdn ?: '-' }}</td>
                        <td>
                            <span class="tich-badge tich-badge--{{ $badges[$row->status] ?? 'neutral' }}">{{ $row->outcomeLabel() }}</span>
                            @if ($row->result_desc)
                                <p class="tich-caption tich-mt-1">{{ $row->result_desc }}</p>
                            @endif
                        </td>
                        <td>{{ $row->allocationLabel() }}</td>
                        <td>
                            <a href="{{ route('finance.mpesa.paybill.show', $row) }}" class="tich-btn tich-btn-ghost">Review</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="tich-caption">No paybill payments have reached us yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        @endunless
    </div>

    @if ($available)
        {{ $rows->links() }}
    @endif
@endsection
