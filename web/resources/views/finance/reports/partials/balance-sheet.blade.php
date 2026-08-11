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
            @foreach ($data['sections'] as $section)
                <tr class="tich-fin-report-table__section">
                    <td colspan="4"><strong>{{ $section['title'] }}</strong></td>
                </tr>
                @forelse ($section['rows'] as $row)
                    <tr>
                        <td></td>
                        <td>{{ $row['account_code'] }}</td>
                        <td>{{ $row['account_name'] }}</td>
                        <td class="num">{{ number_format($row['amount'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td></td>
                        <td colspan="3" class="tich-caption">No balances in this section.</td>
                    </tr>
                @endforelse
                <tr class="tich-fin-report-table__subtotal">
                    <td colspan="3"><strong>Total {{ strtolower($section['title']) }}</strong></td>
                    <td class="num"><strong>{{ number_format($section['total'], 2) }}</strong></td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="tich-fin-report-table__total">
                <th colspan="3">Total assets</th>
                <th class="num">{{ number_format($data['total_assets'], 2) }}</th>
            </tr>
            <tr class="tich-fin-report-table__total">
                <th colspan="3">Total liabilities + equity</th>
                <th class="num">{{ number_format($data['total_liabilities_equity'], 2) }}</th>
            </tr>
            @if (isset($data['is_balanced']))
                <tr>
                    <td colspan="4" class="tich-caption">
                        {{ $data['is_balanced'] ? 'Balance sheet balances correctly.' : 'Warning: assets do not equal liabilities plus equity.' }}
                    </td>
                </tr>
            @endif
        </tfoot>
    </table>
</div>
