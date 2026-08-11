<div class="{{ ($tableClass ?? '') === 'tich-doc-table' ? '' : 'tich-card tich-table-panel' }}">
    @php($tableClass = $tableClass ?? 'tich-admin-table tich-fin-report-table')
    <table class="{{ $tableClass }}">
        <thead>
            <tr>
                <th>Activity</th>
                <th>Description</th>
                <th class="num">Amount (KES)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data['sections'] as $section)
                <tr class="tich-fin-report-table__section">
                    <td colspan="3"><strong>{{ $section['title'] }}</strong></td>
                </tr>
                @foreach ($section['rows'] as $row)
                    <tr>
                        <td></td>
                        <td>{{ $row['label'] }}</td>
                        <td class="num">{{ number_format($row['amount'], 2) }}</td>
                    </tr>
                @endforeach
                <tr class="tich-fin-report-table__subtotal">
                    <td colspan="2"><strong>Net cash from {{ strtolower($section['title']) }}</strong></td>
                    <td class="num"><strong>{{ number_format($section['total'], 2) }}</strong></td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="tich-fin-report-table__total">
                <th colspan="2">Net change in cash</th>
                <th class="num">{{ number_format($data['net_change_in_cash'], 2) }}</th>
            </tr>
            <tr class="tich-fin-report-table__total">
                <th colspan="2">Closing cash balance</th>
                <th class="num">{{ number_format($data['closing_cash_balance'], 2) }}</th>
            </tr>
        </tfoot>
    </table>
</div>
