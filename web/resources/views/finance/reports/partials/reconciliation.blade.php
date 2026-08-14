<div class="tich-card tich-table-panel">
    @php($tableClass = $tableClass ?? 'tich-admin-table tich-fin-report-table')
    <div class="tich-flex tich-mb-4" style="gap:1.5rem; flex-wrap:wrap;">
        <div class="tich-card" style="flex:1; min-width:200px; text-align:center; padding:1.5rem;">
            <p class="tich-caption">Total income</p>
            <p class="tich-h3" style="margin:0; color:#059669;">{{ number_format($data['income']['total'], 2) }}</p>
        </div>
        <div class="tich-card" style="flex:1; min-width:200px; text-align:center; padding:1.5rem;">
            <p class="tich-caption">Total expenses</p>
            <p class="tich-h3" style="margin:0; color:#dc2626;">{{ number_format($data['expenses']['total'], 2) }}</p>
        </div>
        <div class="tich-card" style="flex:1; min-width:200px; text-align:center; padding:1.5rem;">
            <p class="tich-caption">Net position</p>
            <p class="tich-h3" style="margin:0; color:{{ $data['net_position'] >= 0 ? '#059669' : '#dc2626' }};">{{ number_format($data['net_position'], 2) }}</p>
        </div>
        <div class="tich-card" style="flex:1; min-width:200px; text-align:center; padding:1.5rem;">
            <p class="tich-caption">Closing balance</p>
            <p class="tich-h3" style="margin:0;">{{ number_format($data['closing_balance'], 2) }}</p>
        </div>
    </div>
</div>

<div class="tich-card tich-table-panel">
    <h3 class="tich-h4" style="margin-top:0;">Income breakdown</h3>
    <table class="{{ $tableClass }}">
        <thead>
            <tr>
                <th>Category</th>
                <th class="num">Transactions</th>
                <th class="num">Amount (KES)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data['income']['categories'] as $category => $summary)
                <tr>
                    <td>{{ $category }}</td>
                    <td class="num">{{ $summary['count'] }}</td>
                    <td class="num">{{ number_format($summary['total'], 2) }}</td>
                </tr>
            @endforeach
            @if (empty($data['income']['categories']))
                <tr><td colspan="3" class="tich-caption">No income recorded in this period.</td></tr>
            @endif
        </tbody>
        <tfoot>
            <tr class="tich-fin-report-table__total">
                <th>Total income</th>
                <th class="num">{{ collect($data['income']['categories'])->sum('count') }}</th>
                <th class="num">{{ number_format($data['income']['total'], 2) }}</th>
            </tr>
        </tfoot>
    </table>
</div>

<div class="tich-card tich-table-panel">
    <h3 class="tich-h4" style="margin-top:0;">Expense breakdown</h3>
    <table class="{{ $tableClass }}">
        <thead>
            <tr>
                <th>Category</th>
                <th class="num">Transactions</th>
                <th class="num">Amount (KES)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data['expenses']['categories'] as $category => $summary)
                <tr>
                    <td>{{ $category }}</td>
                    <td class="num">{{ $summary['count'] }}</td>
                    <td class="num">{{ number_format($summary['total'], 2) }}</td>
                </tr>
            @endforeach
            @if (empty($data['expenses']['categories']))
                <tr><td colspan="3" class="tich-caption">No expenses recorded in this period.</td></tr>
            @endif
        </tbody>
        <tfoot>
            <tr class="tich-fin-report-table__total">
                <th>Total expenses</th>
                <th class="num">{{ collect($data['expenses']['categories'])->sum('count') }}</th>
                <th class="num">{{ number_format($data['expenses']['total'], 2) }}</th>
            </tr>
        </tfoot>
    </table>
</div>

<div class="tich-card tich-table-panel">
    <h3 class="tich-h4" style="margin-top:0;">Detailed transactions</h3>
    <p class="tich-caption tich-mb-2">{{ $data['period_label'] }} · {{ number_format($data['entry_count']) }} entries</p>
    <table class="{{ $tableClass }}">
        <thead>
            <tr>
                <th>Date</th>
                <th>Category</th>
                <th>Type</th>
                <th>Narration</th>
                <th>Reference</th>
                <th class="num">Income (KES)</th>
                <th class="num">Expense (KES)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data['rows'] as $row)
                <tr>
                    <td>{{ $row['date_display'] }}</td>
                    <td>{{ $row['category'] }}</td>
                    <td>{{ $row['type'] }}</td>
                    <td>{{ $row['narration'] }}</td>
                    <td>{{ $row['reference'] }}</td>
                    <td class="num">{{ $row['income'] > 0 ? number_format($row['income'], 2) : '-' }}</td>
                    <td class="num">{{ $row['expense'] > 0 ? number_format($row['expense'], 2) : '-' }}</td>
                </tr>
            @endforeach
            @if (empty($data['rows']))
                <tr><td colspan="7" class="tich-caption">No transactions recorded in this period.</td></tr>
            @endif
        </tbody>
    </table>
</div>
