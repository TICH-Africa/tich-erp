<div class="{{ ($tableClass ?? '') === 'tich-doc-table' ? '' : 'tich-card tich-table-panel' }}">
    @php($tableClass = $tableClass ?? 'tich-admin-table tich-fin-report-table')

    <div class="tich-grid tich-grid--5 tich-dept-stats tich-mb-4">
        @foreach ($data['buckets'] as $bucket)
            <article class="tich-card tich-stat">
                <p class="tich-caption">{{ $bucket['label'] }}</p>
                <p class="tich-stat__value" style="font-size:1rem;">KES {{ number_format($bucket['total'], 2) }}</p>
                <p class="tich-caption">{{ $bucket['count'] }} invoice(s)</p>
            </article>
        @endforeach
    </div>

    <p class="tich-caption tich-mb-4">
        Total outstanding: <strong>KES {{ number_format($data['total_outstanding'], 2) }}</strong>
        · {{ $data['invoice_count'] ?? 0 }} open vendor invoice(s) from {{ $data['vendor_count'] }} supplier(s)
    </p>

    <table class="{{ $tableClass }}">
        <thead>
            <tr>
                <th>Vendor invoice</th>
                <th>Supplier</th>
                <th>Invoiced</th>
                <th>Due</th>
                <th class="num">Days</th>
                <th>Bucket</th>
                <th class="num">Total (KES)</th>
                <th class="num">Balance (KES)</th>
                <th>Payment</th>
                <th>3-way match</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data['rows'] as $row)
                <tr>
                    <td>{{ $row['invoice_number'] }}</td>
                    <td>{{ $row['supplier_name'] }}</td>
                    <td>{{ $row['invoice_date'] }}</td>
                    <td>{{ $row['due_date'] }}</td>
                    <td class="num">{{ $row['days_past_due'] }}</td>
                    <td>{{ $row['bucket_label'] }}</td>
                    <td class="num">{{ number_format($row['total_amount'], 2) }}</td>
                    <td class="num">{{ number_format($row['balance'], 2) }}</td>
                    <td>{{ ucfirst($row['payment_status']) }}</td>
                    <td>{{ ucfirst($row['three_way_match_status']) }}</td>
                </tr>
            @empty
                <tr><td colspan="10">{{ $data['empty_message'] ?? 'No outstanding payables.' }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
