{{--
  Shared budget request line display.
  Annual (annual_quarters_v1): Q1–Q4 with income | expenditure side by side.
  Legacy / non-annual: flat expenditure table.
  Expects: $budgetRequest (App\Models\Administration\BudgetRequest)
  Optional: $idPrefix (string) to uniquify aria ids when needed
--}}
@php
    /** @var \App\Models\Administration\BudgetRequest $budgetRequest */
    $annual = $budgetRequest->annualQuartersPayload();
    $lines = $budgetRequest->expenditureLines();
    $structured = $lines !== [] && isset($lines[0]) && is_array($lines[0]) && array_key_exists('unit_price', $lines[0]);
    $incomeGrand = (float) ($annual['income_grand_total'] ?? 0);
    $expGrand = (float) ($annual['expenditure_grand_total'] ?? $budgetRequest->requested_amount);
    $balance = $incomeGrand - $expGrand;
    $idPrefix = $idPrefix ?? 'budrev';
    $awaiting = static fn ($value) => $value > 0 ? number_format((float) $value, 2) : 'Awaiting prices';
    $hasPrice = static fn ($row) => is_array($row) && array_key_exists('unit_price', $row) && is_numeric($row['unit_price']);
    $quarterMeta = [
        'q1' => ['label' => 'Q1', 'period' => 'January – March'],
        'q2' => ['label' => 'Q2', 'period' => 'April – June'],
        'q3' => ['label' => 'Q3', 'period' => 'July – September'],
        'q4' => ['label' => 'Q4', 'period' => 'October – December'],
    ];
@endphp

@if ($annual)
    <div class="budrev-year-head tich-mt-6">
        <h2 class="tich-h3" style="margin:0;">Quarterly breakdown</h2>
        <p class="tich-caption" style="margin:0.35rem 0 0;">Income and expenditure for each quarter · prices filled by Procurement</p>
    </div>

    @foreach ($quarterMeta as $qKey => $meta)
        @php
            $block = is_array($annual['quarters'][$qKey] ?? null) ? $annual['quarters'][$qKey] : [];
            $incomeRows = array_values(array_filter(
                is_array($block['income'] ?? null) ? $block['income'] : [],
                static fn ($r) => is_array($r) && (trim((string) ($r['source'] ?? '')) !== '' || (float) ($r['amount'] ?? 0) > 0)
            ));
            $expRows = array_values(array_filter(
                is_array($block['expenditure'] ?? null) ? $block['expenditure'] : [],
                static fn ($r) => is_array($r) && trim((string) ($r['item'] ?? '')) !== ''
            ));
            $qIncome = (float) ($block['income_total'] ?? 0);
            $qExp = (float) ($block['expenditure_total'] ?? 0);
            $qNet = $qIncome - $qExp;
            $titleId = $idPrefix.'-'.$qKey.'-title';
        @endphp
        <section class="budrev-quarter tich-mt-6" aria-labelledby="{{ $titleId }}">
            <header class="budrev-quarter__head">
                <div>
                    <h3 id="{{ $titleId }}" class="budrev-quarter__title">{{ $meta['label'] }}</h3>
                    <p class="budrev-quarter__period">{{ $meta['period'] }}</p>
                </div>
                <div class="budrev-quarter__totals">
                    <span class="budrev-pill budrev-pill--income">Income {{ number_format($qIncome, 2) }}</span>
                    <span class="budrev-pill budrev-pill--exp">Expenditure {{ $awaiting($qExp) }}</span>
                    @if ($expRows === [] || $qExp > 0)
                        <span class="budrev-pill {{ $qNet >= 0 ? 'budrev-pill--ok' : 'budrev-pill--warn' }}">
                            {{ $qNet >= 0 ? 'Net +' : 'Net −' }}{{ number_format(abs($qNet), 2) }}
                        </span>
                    @endif
                </div>
            </header>

            <div class="budrev-quarter__grid">
                <div class="budrev-panel budrev-panel--income">
                    <div class="budrev-panel__head">
                        <h4 class="budrev-panel__title">Income</h4>
                        <span class="budrev-panel__sum">{{ number_format($qIncome, 2) }}</span>
                    </div>
                    <div class="tich-table-wrap">
                        <table class="tich-admin-table budrev-table">
                            <thead>
                                <tr>
                                    <th>Source</th>
                                    <th class="budrev-num">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($incomeRows as $row)
                                    <tr>
                                        <td>{{ $row['source'] ?: '-' }}</td>
                                        <td class="budrev-num">{{ number_format((float) ($row['amount'] ?? 0), 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="tich-caption">No income lines for this quarter.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td>Quarter income</td>
                                    <td class="budrev-num"><strong>{{ number_format($qIncome, 2) }}</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="budrev-panel budrev-panel--exp">
                    <div class="budrev-panel__head">
                        <h4 class="budrev-panel__title">Expenditure</h4>
                        <span class="budrev-panel__sum">{{ $awaiting($qExp) }}</span>
                    </div>
                    <div class="tich-table-wrap">
                        <table class="tich-admin-table budrev-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th class="budrev-num">Qty</th>
                                    <th>Description</th>
                                    <th class="budrev-num">Unit price</th>
                                    <th class="budrev-num">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($expRows as $row)
                                    @php
                                        $qty = (float) ($row['quantity'] ?? 0);
                                        $priced = $hasPrice($row);
                                        $price = $priced ? (float) $row['unit_price'] : null;
                                        $total = $priced ? (float) ($row['total'] ?? ($qty * $price)) : null;
                                    @endphp
                                    <tr>
                                        <td>
                                            <strong>{{ $row['item'] ?? '-' }}</strong>
                                            @if (! empty($row['unit_of_measure']))
                                                <span class="tich-caption"> · {{ $row['unit_of_measure'] }}</span>
                                            @endif
                                        </td>
                                        <td class="budrev-num">{{ rtrim(rtrim(number_format($qty, 4, '.', ','), '0'), '.') }}</td>
                                        <td class="tich-caption">{{ $row['description'] ?: '-' }}</td>
                                        <td class="budrev-num">{{ $priced ? number_format($price, 2) : '—' }}</td>
                                        <td class="budrev-num"><strong>{{ $total !== null ? number_format($total, 2) : '—' }}</strong></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="tich-caption">No expenditure lines for this quarter.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4">Quarter expenditure</td>
                                    <td class="budrev-num"><strong>{{ $awaiting($qExp) }}</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    @endforeach

    <div class="budrev-year-foot tich-mt-6">
        <div>
            <span class="tich-caption">Annual income</span>
            <strong>KES {{ number_format($incomeGrand, 2) }}</strong>
        </div>
        <div>
            <span class="tich-caption">Annual expenditure</span>
            <strong>{{ $expGrand > 0 ? 'KES '.number_format($expGrand, 2) : 'Awaiting prices' }}</strong>
        </div>
        @if ($expGrand > 0)
            <div>
                <span class="tich-caption">{{ $balance >= 0 ? 'Annual surplus' : 'Annual shortfall' }}</span>
                <strong>KES {{ number_format(abs($balance), 2) }}</strong>
            </div>
        @endif
    </div>
@else
    <div class="tich-card tich-table-panel tich-mt-6">
        <h2 class="tich-h3">Line items</h2>
        <div class="tich-table-wrap tich-mt-4">
            @if ($structured)
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Description</th>
                            <th>Price per item</th>
                            <th>UoM</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lines as $line)
                            @php $linePriced = $hasPrice($line); @endphp
                            <tr>
                                <td>{{ $line['item'] ?? '-' }}</td>
                                <td>{{ $line['quantity'] ?? '-' }}</td>
                                <td class="tich-caption">{{ $line['description'] ?? '-' }}</td>
                                <td>{{ $linePriced ? 'KES ' . number_format((float) $line['unit_price'], 2) : 'Awaiting prices' }}</td>
                                <td class="tich-caption">{{ $line['unit_of_measure'] ?? '-' }}</td>
                                <td><strong>{{ $linePriced ? 'KES ' . number_format((float) ($line['total'] ?? ((float) ($line['quantity'] ?? 0) * (float) $line['unit_price'])), 2) : '—' }}</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5" style="text-align:right; font-weight:600;">Grand total</td>
                            <td><strong>{{ $budgetRequest->requestedAmountLabel() }}</strong></td>
                        </tr>
                    </tfoot>
                </table>
            @elseif ($lines !== [])
                <pre class="tich-pre budrev-notes">{{ json_encode($lines, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            @else
                <p class="tich-caption">No line items were provided with this request.</p>
            @endif
        </div>
    </div>
@endif
