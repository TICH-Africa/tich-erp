@extends('layouts.finance')

@section('title', 'Invoices')

@section('finance-content')
    <x-page-toolbar title="Invoices" meta="Student invoices for tuition, application, supplementary, graduation, hostel, and other charges">
<x-slot:actions>
            <a href="{{ route('finance.student-finance.invoices.create') }}" class="tich-btn tich-btn-primary">+ New invoice</a>
            <a href="{{ route('finance.student-finance.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    @include('finance.student-finance.partials.student-search-filters', [
        'action' => route('finance.student-finance.invoices.index'),
        'search' => $search ?? '',
        'programId' => $programId ?? 0,
        'programs' => $programs ?? collect(),
        'preserve' => ['view' => ($grouped ?? null) === null ? 'invoices' : 'students'],
    ])

    @php
        $modeLink = static function (string $mode) use ($search, $programId): string {
            $params = ['view' => $mode];
            if (($search ?? '') !== '') {
                $params['search'] = $search;
            }
            if ((int) ($programId ?? 0) > 0) {
                $params['program_id'] = $programId;
            }

            return route('finance.student-finance.invoices.index', $params);
        };
        $currentMode = ($grouped ?? null) === null ? 'invoices' : 'students';
    @endphp

    <nav class="tich-tabs__nav" aria-label="Invoice list view">
        <a href="{{ $modeLink('students') }}" class="tich-tabs__btn @if ($currentMode === 'students') is-active @endif">By student</a>
        <a href="{{ $modeLink('invoices') }}" class="tich-tabs__btn @if ($currentMode === 'invoices') is-active @endif">All invoices</a>
    </nav>

    @if ($grouped !== null)
        <div class="tich-card tich-table-panel">
            <div class="tich-table-wrap">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Bills</th>
                            <th>Amount</th>
                            <th>Paid</th>
                            <th>Balance</th>
                            <th>Status</th>
                            <th>Due Date</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($grouped as $row)
                            <tr>
                                <td>
                                    <strong>{{ $row->name }}</strong>
                                    <p class="tich-caption">{{ $row->registration_number ?? 'N/A' }}</p>
                                    @if ($row->program_name)
                                        <p class="tich-caption">{{ $row->program_name }}</p>
                                    @endif
                                </td>
                                <td>{{ $row->bill_count }}</td>
                                <td>KES {{ number_format($row->billed_total, 2) }}</td>
                                <td>KES {{ number_format($row->paid_total, 2) }}</td>
                                <td><strong>KES {{ number_format($row->balance_total, 2) }}</strong></td>
                                <td>
                                    <span class="tich-badge tich-badge--{{ $row->open_count > 0 ? 'warning' : 'success' }}">
                                        {{ $row->open_count > 0 ? $row->open_count.' open' : 'Settled' }}
                                    </span>
                                </td>
                                <td class="tich-caption">{{ $row->next_due ?? $row->last_billed }}</td>
                                <td>
                                    @php
                                        $rowLink = $row->account_id
                                            ? route('finance.student-finance.accounts.show', ['id' => $row->account_id])
                                            : route('finance.student-finance.invoices.index', array_filter([
                                                'view' => 'invoices',
                                                'search' => $row->registration_number,
                                            ]));
                                    @endphp
                                    <a href="{{ $rowLink }}" class="tich-btn tich-btn-ghost">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="tich-table-empty">No invoices found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($grouped instanceof \Illuminate\Contracts\Pagination\Paginator && $grouped->hasPages())
                <div class="tich-mt-4">{{ $grouped->links() }}</div>
            @endif
        </div>
    @else
    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Student</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Paid</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th>Due Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoices as $invoice)
                        <tr>
                            <td><strong>{{ $invoice->invoice_number }}</strong></td>
                            <td>
                                <strong>{{ $invoice->student->fullName() ?? 'N/A' }}</strong>
                                <p class="tich-caption">{{ $invoice->student->registration_number ?? 'N/A' }}</p>
                            </td>
                            <td class="tich-caption">{{ ucfirst($invoice->invoice_type) }}</td>
                            <td>KES {{ number_format($invoice->amount, 2) }}</td>
                            <td>KES {{ number_format($invoice->amount_paid, 2) }}</td>
                            <td><strong>KES {{ number_format($invoice->balance, 2) }}</strong></td>
                            <td>
                                <span class="tich-badge tich-badge--{{ match($invoice->status) {
                                    'issued' => 'info',
                                    'partial' => 'warning',
                                    'paid' => 'success',
                                    'overdue' => 'danger',
                                    'waived' => 'secondary',
                                    default => 'secondary',
                                } }}">
                                    {{ ucfirst($invoice->status) }}
                                </span>
                            </td>
                            <td class="tich-caption">{{ $invoice->due_date?->format('d M Y') }}</td>
                            <td>
                                <a href="{{ route('finance.student-finance.invoices.show', ['id' => $invoice->id]) }}" class="tich-btn tich-btn-ghost">View</a>
                                @can('finance.invoices.manage')
                                    <a href="{{ route('finance.invoices.edit', ['invoice' => $invoice->id]) }}" class="tich-btn tich-btn-ghost">Edit</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="tich-table-empty">No invoices found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($invoices instanceof \Illuminate\Contracts\Pagination\Paginator && $invoices->hasPages())
            <div class="tich-mt-4">{{ $invoices->links() }}</div>
        @endif
    </div>
    @endif
@endsection



