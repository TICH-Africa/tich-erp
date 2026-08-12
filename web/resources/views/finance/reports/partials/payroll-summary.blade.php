<div class="{{ ($tableClass ?? '') === 'tich-doc-table' ? '' : 'tich-card tich-table-panel' }}">
    @php($tableClass = $tableClass ?? 'tich-admin-table tich-fin-report-table')

    @if (! empty($data['totals']))
        <div class="tich-grid tich-grid--4 tich-dept-stats tich-mb-4">
            <article class="tich-card tich-stat">
                <p class="tich-caption">Runs</p>
                <p class="tich-stat__value">{{ $data['totals']['runs'] }}</p>
            </article>
            <article class="tich-card tich-stat">
                <p class="tich-caption">Staff paid</p>
                <p class="tich-stat__value">{{ $data['totals']['staff'] }}</p>
            </article>
            <article class="tich-card tich-stat">
                <p class="tich-caption">Total gross</p>
                <p class="tich-stat__value" style="font-size:1rem;">KES {{ number_format($data['totals']['gross'], 2) }}</p>
            </article>
            <article class="tich-card tich-stat">
                <p class="tich-caption">Total net</p>
                <p class="tich-stat__value" style="font-size:1rem;">KES {{ number_format($data['totals']['net'], 2) }}</p>
            </article>
        </div>
    @endif

    <table class="{{ $tableClass }}">
        <thead>
            <tr>
                <th>Run</th>
                <th>Period</th>
                <th>Status</th>
                <th class="num">Staff</th>
                <th class="num">Gross (KES)</th>
                <th class="num">Net (KES)</th>
                <th class="num">PAYE (KES)</th>
                <th>Posted</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data['rows'] as $row)
                <tr>
                    <td>{{ $row['run_number'] }}</td>
                    <td>{{ $row['period'] }}</td>
                    <td>{{ ucfirst($row['status']) }}</td>
                    <td class="num">{{ $row['staff_count'] }}</td>
                    <td class="num">{{ number_format($row['total_gross'], 2) }}</td>
                    <td class="num">{{ number_format($row['total_net'], 2) }}</td>
                    <td class="num">{{ number_format($row['total_paye'], 2) }}</td>
                    <td>{{ $row['posted_at'] ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="8">No approved payroll runs yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
