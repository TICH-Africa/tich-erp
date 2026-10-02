<div class="{{ ($tableClass ?? '') === 'tich-doc-table' ? '' : 'tich-card tich-table-panel' }}">
    @php($tableClass = $tableClass ?? 'tich-admin-table tich-fin-report-table')
    <table class="{{ $tableClass }}">
        <thead>
            <tr>
                <th>Section</th>
                <th>Account code</th>
                <th>Account name</th>
                <th class="num">Account only</th>
                <th class="num">Amount (KES)</th>
            </tr>
        </thead>
        <tbody>
            <tr class="tich-fin-report-table__section">
                <td colspan="5"><strong>Revenue</strong></td>
            </tr>
            @forelse ($data['revenue']['rows'] as $row)
                <tr class="{{ ($row['level'] ?? 0) > 0 ? 'tich-fin-report-table__child' : '' }}">
                    <td></td>
                    <td>{{ $row['account_code'] }}</td>
                    <td style="padding-left: {{ 8 + 18 * (int) ($row['level'] ?? 0) }}px">{{ $row['account_name'] }}</td>
                    <td class="num">{{ ($row['level'] ?? 0) > 0 || ($row['is_group'] ?? false) ? number_format($row['own_amount'], 2) : '' }}</td>
                    <td class="num">{{ number_format($row['amount'], 2) }}</td>
                </tr>
            @empty
                <tr><td></td><td colspan="4" class="tich-caption">No revenue recorded.</td></tr>
            @endforelse
            <tr class="tich-fin-report-table__subtotal">
                <td colspan="4"><strong>Total revenue</strong></td>
                <td class="num"><strong>{{ number_format($data['revenue']['total'], 2) }}</strong></td>
            </tr>

            <tr class="tich-fin-report-table__section">
                <td colspan="5"><strong>Expenses</strong></td>
            </tr>
            @forelse ($data['expenses']['rows'] as $row)
                <tr class="{{ ($row['level'] ?? 0) > 0 ? 'tich-fin-report-table__child' : '' }}">
                    <td></td>
                    <td>{{ $row['account_code'] }}</td>
                    <td style="padding-left: {{ 8 + 18 * (int) ($row['level'] ?? 0) }}px">{{ $row['account_name'] }}</td>
                    <td class="num">{{ ($row['level'] ?? 0) > 0 || ($row['is_group'] ?? false) ? number_format($row['own_amount'], 2) : '' }}</td>
                    <td class="num">{{ number_format($row['amount'], 2) }}</td>
                </tr>
            @empty
                <tr><td></td><td colspan="4" class="tich-caption">No expenses recorded.</td></tr>
            @endforelse
            <tr class="tich-fin-report-table__subtotal">
                <td colspan="4"><strong>Total expenses</strong></td>
                <td class="num"><strong>{{ number_format($data['expenses']['total'], 2) }}</strong></td>
            </tr>
        </tbody>
        <tfoot>
            <tr class="tich-fin-report-table__total">
                <th colspan="4">Net income / (loss)</th>
                <th class="num">{{ number_format($data['net_income'], 2) }}</th>
            </tr>
            @if (array_key_exists('ties_to_ledger', $data))
                <tr>
                    <td colspan="5" class="tich-caption">
                        {{ $data['ties_to_ledger'] ? 'Cross-checked against the general ledger: revenue and expenditure agree with the journal entries for this period.' : 'Warning: this statement does not agree with the general ledger for the period (revenue difference '.number_format($data['revenue_difference'], 2).', expenditure difference '.number_format($data['expense_difference'], 2).').' }}
                    </td>
                </tr>
            @endif
        </tfoot>
    </table>
</div>
