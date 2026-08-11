<div class="{{ ($tableClass ?? '') === 'tich-doc-table' ? '' : 'tich-card tich-table-panel' }}">
    @php($tableClass = $tableClass ?? 'tich-admin-table tich-fin-report-table')

    @if (($tableClass ?? '') !== 'tich-doc-table')
        <form method="get" action="{{ route('finance.reports.index') }}" class="tich-flex tich-mb-4" style="gap:0.5rem; flex-wrap:wrap;">
            <input type="hidden" name="report" value="finance_audit">
            <input type="search" name="search" value="{{ $filters['search'] ?? request('search') }}" class="tich-input" placeholder="Search action, entity, reason…">
            <input type="date" name="from" value="{{ $filters['from'] ?? request('from') }}" class="tich-input">
            <input type="date" name="to" value="{{ $filters['to'] ?? request('to') }}" class="tich-input">
            <button type="submit" class="tich-btn tich-btn-secondary">Filter</button>
        </form>
    @endif

    <table class="{{ $tableClass }}">
        <thead>
            <tr>
                <th>Timestamp</th>
                <th>Action</th>
                <th>Entity</th>
                <th>Entity ID</th>
                <th>User</th>
                <th>Status</th>
                <th>Reason</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data['rows'] as $row)
                <tr>
                    <td>{{ $row['created_at'] }}</td>
                    <td><code style="font-size:0.8125rem;">{{ $row['action'] }}</code></td>
                    <td>{{ $row['entity_type'] }}</td>
                    <td>{{ $row['entity_id'] }}</td>
                    <td>{{ $row['user_email'] ?? '—' }}</td>
                    <td>{{ ucfirst($row['status']) }}</td>
                    <td>{{ $row['reason'] ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="7">No finance audit entries yet. Actions such as invoices, payments, and payroll posting will appear here.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
