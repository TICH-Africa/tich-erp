<div class="{{ ($tableClass ?? '') === 'tich-doc-table' ? '' : 'tich-card tich-table-panel' }}">
    @php($tableClass = $tableClass ?? 'tich-admin-table tich-fin-report-table')
    <table class="{{ $tableClass }}">
        <thead>
            <tr>
                <th>Account code</th>
                <th>Account name</th>
                <th>Type</th>
                <th class="num">Debit (KES)</th>
                <th class="num">Credit (KES)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data['rows'] as $row)
                <tr>
                    <td>{{ $row['account_code'] }}</td>
                    <td>{{ $row['account_name'] }}</td>
                    <td>{{ ucfirst($row['account_type']) }}</td>
                    <td class="num">{{ $row['debit'] > 0 ? number_format($row['debit'], 2) : '-' }}</td>
                    <td class="num">{{ $row['credit'] > 0 ? number_format($row['credit'], 2) : '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No ledger balances to report yet.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="tich-fin-report-table__total">
                <th colspan="3">Totals</th>
                <th class="num">{{ number_format($data['total_debit'], 2) }}</th>
                <th class="num">{{ number_format($data['total_credit'], 2) }}</th>
            </tr>
            @if (isset($data['is_balanced']))
                <tr>
                    <td colspan="5" class="tich-caption">
                        {{ $data['is_balanced'] ? 'Trial balance is balanced.' : 'Warning: debits and credits do not match.' }}
                    </td>
                </tr>
            @endif
        </tfoot>
    </table>
</div>
