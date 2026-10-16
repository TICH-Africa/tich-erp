@php($tableClass = $tableClass ?? 'tich-admin-table tich-fin-report-table')

<div class="tich-card tich-table-panel">
    <div class="tich-flex tich-mb-4" style="gap:1.5rem; flex-wrap:wrap;">
        <div class="tich-card" style="flex:1; min-width:180px; text-align:center; padding:1.5rem;">
            <p class="tich-caption">Money received</p>
            <p class="tich-h3" style="margin:0; color:#059669;">{{ number_format($data['received']['total'], 2) }}</p>
        </div>
        <div class="tich-card" style="flex:1; min-width:180px; text-align:center; padding:1.5rem;">
            <p class="tich-caption">Money paid out</p>
            <p class="tich-h3" style="margin:0; color:#dc2626;">{{ number_format($data['paid_out']['total'], 2) }}</p>
        </div>
        <div class="tich-card" style="flex:1; min-width:180px; text-align:center; padding:1.5rem;">
            <p class="tich-caption">Net movement</p>
            <p class="tich-h3" style="margin:0; color:{{ $data['net_movement'] >= 0 ? '#059669' : '#dc2626' }};">{{ number_format($data['net_movement'], 2) }}</p>
        </div>
        <div class="tich-card" style="flex:1; min-width:180px; text-align:center; padding:1.5rem;">
            <p class="tich-caption">Closing balance</p>
            <p class="tich-h3" style="margin:0;">{{ number_format($data['closing_balance'], 2) }}</p>
        </div>
    </div>
    <p class="tich-caption" style="margin:0;">
        Bank, cash and M-Pesa accounts only. Invoices raised and bills entered are accruals, so they are not counted here
        unless money actually moved. {{ number_format($data['transfers_total'], 2) }} moved between our own accounts as
        transfers and is excluded from the received and paid totals.
    </p>
</div>

<div class="tich-card tich-table-panel">
    <h3 class="tich-h4" style="margin-top:0;">Reconciliation by account</h3>
    <table class="{{ $tableClass }}">
        <thead>
            <tr>
                <th>Account</th>
                <th class="num">Opening (KES)</th>
                <th class="num">Received (KES)</th>
                <th class="num">Paid out (KES)</th>
                <th class="num">Transfers in (KES)</th>
                <th class="num">Transfers out (KES)</th>
                <th class="num">Closing (KES)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data['received']['accounts'] as $account)
                <tr>
                    <td>{{ $account['account_code'] }} · {{ $account['account_name'] }}</td>
                    <td class="num">{{ number_format($account['opening_balance'], 2) }}</td>
                    <td class="num">{{ $account['received'] != 0 ? number_format($account['received'], 2) : '-' }}</td>
                    <td class="num">{{ $account['paid_out'] != 0 ? number_format($account['paid_out'], 2) : '-' }}</td>
                    <td class="num">{{ $account['transfers_in'] != 0 ? number_format($account['transfers_in'], 2) : '-' }}</td>
                    <td class="num">{{ $account['transfers_out'] != 0 ? number_format($account['transfers_out'], 2) : '-' }}</td>
                    <td class="num">{{ number_format($account['closing_balance'], 2) }}</td>
                </tr>
            @endforeach
            @if (empty($data['received']['accounts']))
                <tr><td colspan="7" class="tich-caption">No bank, cash or M-Pesa accounts are set up.</td></tr>
            @endif
        </tbody>
        <tfoot>
            <tr class="tich-fin-report-table__total">
                <th>Total</th>
                <th class="num">{{ number_format($data['opening_balance'], 2) }}</th>
                <th class="num">{{ number_format($data['received']['total'], 2) }}</th>
                <th class="num">{{ number_format($data['paid_out']['total'], 2) }}</th>
                <th class="num">{{ number_format(collect($data['received']['accounts'])->sum('transfers_in'), 2) }}</th>
                <th class="num">{{ number_format(collect($data['received']['accounts'])->sum('transfers_out'), 2) }}</th>
                <th class="num">{{ number_format($data['closing_balance'], 2) }}</th>
            </tr>
        </tfoot>
    </table>
</div>

<div class="tich-flex" style="gap:1rem; flex-wrap:wrap; align-items:flex-start;">
    <div class="tich-card tich-table-panel" style="flex:1; min-width:300px;">
        <h3 class="tich-h4" style="margin-top:0;">Money received by type</h3>
        <table class="{{ $tableClass }}">
            <thead>
                <tr>
                    <th>Type</th>
                    <th class="num">Entries</th>
                    <th class="num">Amount (KES)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data['received']['sources'] as $type => $summary)
                    <tr>
                        <td>{{ ucfirst($type) }}</td>
                        <td class="num">{{ $summary['count'] }}</td>
                        <td class="num">{{ number_format($summary['total'], 2) }}</td>
                    </tr>
                @endforeach
                @if (empty($data['received']['sources']))
                    <tr><td colspan="3" class="tich-caption">Nothing was received into these accounts in this period.</td></tr>
                @endif
            </tbody>
            <tfoot>
                <tr class="tich-fin-report-table__total">
                    <th>Total received</th>
                    <th class="num">{{ collect($data['received']['sources'])->sum('count') }}</th>
                    <th class="num">{{ number_format($data['received']['total'], 2) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="tich-card tich-table-panel" style="flex:1; min-width:300px;">
        <h3 class="tich-h4" style="margin-top:0;">Money paid out by type</h3>
        <table class="{{ $tableClass }}">
            <thead>
                <tr>
                    <th>Type</th>
                    <th class="num">Entries</th>
                    <th class="num">Amount (KES)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data['paid_out']['sources'] as $type => $summary)
                    <tr>
                        <td>{{ ucfirst($type) }}</td>
                        <td class="num">{{ $summary['count'] }}</td>
                        <td class="num">{{ number_format($summary['total'], 2) }}</td>
                    </tr>
                @endforeach
                @if (empty($data['paid_out']['sources']))
                    <tr><td colspan="3" class="tich-caption">Nothing was paid out of these accounts in this period.</td></tr>
                @endif
            </tbody>
            <tfoot>
                <tr class="tich-fin-report-table__total">
                    <th>Total paid out</th>
                    <th class="num">{{ collect($data['paid_out']['sources'])->sum('count') }}</th>
                    <th class="num">{{ number_format($data['paid_out']['total'], 2) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@if (! empty($data['differences']))
    <div class="tich-card tich-table-panel">
        <h3 class="tich-h4" style="margin-top:0;">Differences to check against the bank statement</h3>
        <p class="tich-caption tich-mb-2">
            What the operational modules recorded against what actually reached the ledger. A gap here is money a bank
            statement would show and these figures would not.
        </p>
        <table class="{{ $tableClass }}">
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="num">Recorded in module (KES)</th>
                    <th class="num">Posted to bank (KES)</th>
                    <th class="num">Difference (KES)</th>
                    <th>Note</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data['differences'] as $difference)
                    <tr>
                        <td>{{ $difference['label'] }}</td>
                        <td class="num">{{ number_format($difference['recorded'], 2) }}</td>
                        <td class="num">{{ number_format($difference['posted'], 2) }}</td>
                        <td class="num" style="color:{{ abs($difference['difference']) >= 0.01 ? '#dc2626' : '#059669' }};">
                            {{ number_format($difference['difference'], 2) }}
                        </td>
                        <td class="tich-caption">{{ $difference['note'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

<div class="tich-card tich-table-panel">
    <h3 class="tich-h4" style="margin-top:0;">Bank and cash movements</h3>
    <p class="tich-caption tich-mb-2">{{ $data['period_label'] }} · {{ number_format($data['entry_count']) }} entries</p>
    <table class="{{ $tableClass }}">
        <thead>
            <tr>
                <th>Date</th>
                <th>Account</th>
                <th>Movement</th>
                <th>Type</th>
                <th>Narration</th>
                <th>Reference</th>
                <th class="num">Received (KES)</th>
                <th class="num">Paid out (KES)</th>
                <th class="num">Transfer (KES)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data['rows'] as $row)
                <tr>
                    <td>{{ $row['date_display'] }}</td>
                    <td>{{ $row['account_code'] }}</td>
                    <td>{{ ['in' => 'Money in', 'out' => 'Money out', 'transfer' => 'Transfer'][$row['movement']] }}</td>
                    <td>{{ ucfirst($row['type']) }}</td>
                    <td>{{ $row['narration'] }}</td>
                    <td>{{ $row['reference'] }}</td>
                    <td class="num">{{ $row['received'] != 0 ? number_format($row['received'], 2) : '-' }}</td>
                    <td class="num">{{ $row['paid_out'] != 0 ? number_format($row['paid_out'], 2) : '-' }}</td>
                    <td class="num">{{ $row['transfer'] != 0 ? number_format($row['transfer'], 2) : '-' }}</td>
                </tr>
            @endforeach
            @if (empty($data['rows']))
                <tr><td colspan="9" class="tich-caption">No money moved through the bank, cash or M-Pesa accounts in this period.</td></tr>
            @endif
        </tbody>
    </table>
</div>
