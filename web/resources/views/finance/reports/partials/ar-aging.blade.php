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
        · {{ $data['invoice_count'] }} open invoice(s)
    </p>

    <table class="{{ $tableClass }}">
        <thead>
            <tr>
                <th>Invoice</th>
                <th>Student</th>
                <th>Due</th>
                <th class="num">Days</th>
                <th>Bucket</th>
                <th class="num">Balance (KES)</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data['rows'] as $row)
                <tr>
                    <td>{{ $row['invoice_number'] }}</td>
                    <td>{{ $row['student_name'] }}<br><span class="tich-caption">{{ $row['registration_number'] }}</span></td>
                    <td>{{ $row['due_date'] }}</td>
                    <td class="num">{{ $row['days_past_due'] }}</td>
                    <td>{{ $row['bucket_label'] }}</td>
                    <td class="num">{{ number_format($row['balance'], 2) }}</td>
                    <td>{{ ucfirst($row['status']) }}</td>
                </tr>
            @empty
                <tr><td colspan="7">No open receivables.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
