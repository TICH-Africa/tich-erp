@extends('layouts.finance')

@section('title', 'Accounts Receivable')

@section('finance-content')
    @php($dept = $departmentParams)

    <x-page-toolbar title="Accounts Receivable" meta="Student invoices, ageing (14/30/60/90 days), reminders, and collections">
        <x-slot:actions>
            <a href="{{ route('finance.ar.credit-memos.create', $dept) }}" class="tich-btn tich-btn-secondary">Issue credit memo</a>
            <a href="{{ route('finance.ar.aging.export.pdf', $dept) }}" class="tich-btn tich-btn-secondary" target="_blank" rel="noopener">Export ageing PDF</a>
            <form method="post" action="{{ route('finance.ar.remind.bulk', $dept) }}" style="display:inline;">
                @csrf
                <button type="submit" class="tich-btn tich-btn-primary">Send due reminders</button>
            </form>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-grid tich-grid--5 tich-dept-stats tich-mt-8">
        @foreach ($aging['buckets'] as $key => $bucket)
            <a href="{{ route('finance.ar.index', array_merge($dept, request('search') ? ['search' => request('search')] : [], ['bucket' => $key])) }}"
               class="tich-card tich-stat {{ request('bucket') === $key ? 'tich-card--active' : '' }}"
               style="text-decoration:none;color:inherit;">
                <p class="tich-caption">{{ $bucket['label'] }}</p>
                <p class="tich-stat__value" style="font-size:1rem;">KES {{ number_format($bucket['total'], 2) }}</p>
                <p class="tich-caption">{{ $bucket['count'] }} invoice(s)</p>
            </a>
        @endforeach
    </div>

    @if (request('bucket'))
        <p class="tich-mt-4">
            <a href="{{ route('finance.ar.index', array_merge($dept, request('search') ? ['search' => request('search')] : [])) }}" class="tich-link">Clear bucket filter</a>
        </p>
    @endif

    <article class="tich-card tich-stat tich-mt-4">
        <p class="tich-caption">Total outstanding · {{ $aging['invoice_count'] }} open invoice(s)</p>
        <p class="tich-stat__value">KES {{ number_format($aging['total_outstanding'], 2) }}</p>
    </article>

    <section class="tich-dept-panel tich-mt-8">
        <div class="tich-dept-panel__head">
            <h2 class="tich-h2 tich-dept-panel__title">Open invoices</h2>
        </div>

        <form method="get" class="tich-flex tich-mb-4" style="gap:0.5rem; flex-wrap:wrap;">
            <input type="search" name="search" value="{{ request('search') }}" class="tich-input" placeholder="Search invoice or registration…">
            <button type="submit" class="tich-btn tich-btn-secondary">Search</button>
        </form>

        <form method="post" action="{{ route('finance.ar.statements.export.pdf', $dept) }}" id="ar-statements-form">
            @csrf
            <div class="tich-card tich-table-panel">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="ar-select-all" aria-label="Select all"></th>
                            <th>Invoice</th>
                            <th>Student</th>
                            <th>Balance</th>
                            <th>Due</th>
                            <th>Age</th>
                            <th>Bucket</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            @php($invoice = $row['invoice'])
                            <tr>
                                <td>
                                    <input type="checkbox" name="student_ids[]" value="{{ $invoice->student_id }}" class="ar-student-check">
                                </td>
                                <td>
                                    <a href="{{ route('finance.student-finance.invoices.show', array_merge($dept, ['id' => $invoice->id])) }}">{{ $invoice->invoice_number }}</a>
                                </td>
                                <td>{{ $invoice->student?->displayName() ?? '-' }}<br><span class="tich-caption">{{ $invoice->student?->registration_number }}</span></td>
                                <td>KES {{ number_format((float) $invoice->balance, 2) }}</td>
                                <td>{{ $invoice->due_date?->format('d M Y') ?? '-' }}</td>
                                <td>{{ $row['days_past_due'] }} days</td>
                                <td>{{ app(\App\Services\Finance\AccountsReceivableService::class)->bucketLabel($row['bucket']) }}</td>
                                <td>{{ ucfirst($invoice->status) }}</td>
                                <td>
                                    <form method="post" action="{{ route('finance.ar.invoices.remind', array_merge($dept, ['invoice' => $invoice->id])) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="tich-btn tich-btn-ghost" style="font-size:0.8125rem;">Remind</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="tich-table-empty">No open receivables.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="tich-mt-4">
                <button type="submit" class="tich-btn tich-btn-secondary">Export selected statements (PDF)</button>
            </div>
        </form>

        <div class="tich-mt-4">{{ $invoices->links() }}</div>
    </section>

    <script>
        document.getElementById('ar-select-all')?.addEventListener('change', function (event) {
            document.querySelectorAll('.ar-student-check').forEach(function (checkbox) {
                checkbox.checked = event.target.checked;
            });
        });
    </script>
@endsection
