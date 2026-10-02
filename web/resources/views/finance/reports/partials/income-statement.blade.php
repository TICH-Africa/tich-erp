<div class="{{ ($tableClass ?? '') === 'tich-doc-table' ? '' : 'tich-card tich-table-panel' }}">
    @php($tableClass = $tableClass ?? 'tich-admin-table tich-fin-report-table')
    <table class="{{ $tableClass }}">
        <thead>
            <tr>
                <th>Section</th>
                <th>Account code</th>
                <th>Account name</th>
                <th class="num">Amount (KES)</th>
            </tr>
        </thead>
        <tbody>
            <tr class="tich-fin-report-table__section">
                <td colspan="4"><strong>Revenue / income</strong></td>
            </tr>
            @forelse ($data['revenue']['rows'] as $row)
                <tr>
                    <td></td>
                    <td>{{ $row['account_code'] }}</td>
                    <td>{{ $row['account_name'] }}</td>
                    <td class="num">{{ number_format($row['amount'], 2) }}</td>
                </tr>
            @empty
                <tr><td></td><td colspan="3" class="tich-caption">No revenue recorded in this period.</td></tr>
            @endforelse
            <tr class="tich-fin-report-table__subtotal">
                <td colspan="3"><strong>Total revenue</strong></td>
                <td class="num"><strong>{{ number_format($data['revenue']['total'], 2) }}</strong></td>
            </tr>

            <tr class="tich-fin-report-table__section">
                <td colspan="4"><strong>Expenses</strong></td>
            </tr>
            @forelse ($data['expenses']['rows'] as $row)
                <tr>
                    <td></td>
                    <td>{{ $row['account_code'] }}</td>
                    <td>{{ $row['account_name'] }}</td>
                    <td class="num">{{ number_format($row['amount'], 2) }}</td>
                </tr>
            @empty
                <tr><td></td><td colspan="3" class="tich-caption">No expenses recorded in this period.</td></tr>
            @endforelse
            <tr class="tich-fin-report-table__subtotal">
                <td colspan="3"><strong>Total expenses</strong></td>
                <td class="num"><strong>{{ number_format($data['expenses']['total'], 2) }}</strong></td>
            </tr>

            @if (($data['view_mode'] ?? 'standard') === 'full')
                @foreach (($data['sections'] ?? []) as $section)
                    <tr class="tich-fin-report-table__section">
                        <td colspan="4"><strong>{{ $section['title'] }} (movement in period)</strong></td>
                    </tr>
                    @forelse ($section['rows'] as $row)
                        <tr>
                            <td></td>
                            <td>{{ $row['account_code'] }}</td>
                            <td>{{ $row['account_name'] }}</td>
                            <td class="num">{{ number_format($row['amount'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td></td><td colspan="3" class="tich-caption">No movement in this section.</td></tr>
                    @endforelse
                    <tr class="tich-fin-report-table__subtotal">
                        <td colspan="3"><strong>Total {{ strtolower($section['title']) }}</strong></td>
                        <td class="num"><strong>{{ number_format($section['total'], 2) }}</strong></td>
                    </tr>
                @endforeach
            @endif
        </tbody>
        <tfoot>
            <tr class="tich-fin-report-table__total">
                <th colspan="3">Net income / (loss)</th>
                <th class="num">{{ number_format($data['net_income'], 2) }}</th>
            </tr>
        </tfoot>
    </table>
</div>
