<div class="{{ ($tableClass ?? '') === 'tich-doc-table' ? '' : 'tich-card tich-table-panel' }}">
    @php($tableClass = $tableClass ?? 'tich-admin-table tich-fin-report-table')
    <table class="{{ $tableClass }}">
        <thead>
            <tr>
                <th>Date</th>
                <th>Transaction</th>
                <th>Debit account</th>
                <th>Credit account</th>
                <th class="num">Amount (KES)</th>
                <th>Narration</th>
                <th>Reference</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data['rows'] as $row)
                <tr>
                    <td>{{ $row['ledger_date_display'] }}</td>
                    <td>{{ ucwords($row['transaction_type']) }}</td>
                    <td><span class="tich-caption">{{ $row['debit_account_code'] }}</span> {{ $row['debit_account_name'] }}</td>
                    <td><span class="tich-caption">{{ $row['credit_account_code'] }}</span> {{ $row['credit_account_name'] }}</td>
                    <td class="num">{{ number_format($row['amount'], 2) }}</td>
                    <td>{{ $row['narration'] }}</td>
                    <td>{{ $row['reference_id'] ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="7">No journal entries posted yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
