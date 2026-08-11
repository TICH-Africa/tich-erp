@extends('layouts.print-document')

@section('document-content')
    <table class="tich-doc-table">
        <thead>
            <tr>
                <th>Bucket</th>
                <th class="num">Invoices</th>
                <th class="num">Outstanding (KES)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($aging['buckets'] as $bucket)
                <tr>
                    <td>{{ $bucket['label'] }}</td>
                    <td class="num">{{ $bucket['count'] }}</td>
                    <td class="num">{{ number_format($bucket['total'], 2) }}</td>
                </tr>
            @endforeach
            <tr>
                <td><strong>Total</strong></td>
                <td class="num"><strong>{{ $aging['invoice_count'] }}</strong></td>
                <td class="num"><strong>{{ number_format($aging['total_outstanding'], 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    @foreach ($aging['buckets'] as $bucket)
        @if ($bucket['invoices']->isNotEmpty())
            <h2 style="margin-top:1.5rem;font-size:1rem;">{{ $bucket['label'] }}</h2>
            <table class="tich-doc-table">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Student</th>
                        <th>Due</th>
                        <th class="num">Days</th>
                        <th class="num">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($bucket['invoices'] as $row)
                        @php($invoice = $row['invoice'])
                        <tr>
                            <td>{{ $invoice->invoice_number }}</td>
                            <td>{{ $invoice->student?->displayName() }}</td>
                            <td>{{ $invoice->due_date?->format('d M Y') }}</td>
                            <td class="num">{{ $row['days_past_due'] }}</td>
                            <td class="num">{{ number_format((float) $invoice->balance, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endforeach
@endsection
