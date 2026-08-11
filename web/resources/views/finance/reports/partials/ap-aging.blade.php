<div class="{{ ($tableClass ?? '') === 'tich-doc-table' ? '' : 'tich-card tich-table-panel' }}">
    @php($tableClass = $tableClass ?? 'tich-admin-table tich-fin-report-table')

    <article class="tich-card tich-mb-4">
        <p>{{ $data['empty_message'] ?? 'No accounts payable data is available yet.' }}</p>
        <p class="tich-caption tich-mt-2">AP ageing will populate when vendor invoices and the accounts payable module are implemented.</p>
    </article>

    <table class="{{ $tableClass }}">
        <thead>
            <tr>
                <th>Bucket</th>
                <th class="num">Vendors</th>
                <th class="num">Outstanding (KES)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data['buckets'] as $bucket)
                <tr>
                    <td>{{ $bucket['label'] }}</td>
                    <td class="num">{{ $bucket['count'] }}</td>
                    <td class="num">{{ number_format($bucket['total'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
