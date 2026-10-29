@extends('layouts.administration')

@section('title', 'Budget aggregation')

@section('administration-content')
    <x-page-toolbar title="Budget aggregation" meta="Cross-departmental consolidation with CBE framework support">
        <x-slot:actions>
            <button type="button" class="tich-btn tich-btn-primary" data-open-modal="budget-request-modal">+ Budget request</button>
        </x-slot:actions>
    </x-page-toolbar>

    <form method="GET" class="tich-flex-wrap tich-mt-6" style="gap: 0.75rem; align-items: end;">
        <div class="tich-form-group" style="margin:0;">
            <label class="tich-label">Fiscal year</label>
            <input type="number" name="fiscal_year" value="{{ $fiscalYear }}" class="tich-input" style="width: 8rem;">
        </div>
        <button type="submit" class="tich-btn tich-btn-secondary">Filter</button>
    </form>

    <div class="tich-stat-row tich-stat-row--4 tich-mt-6">
        <div class="tich-stat">
            <p class="tich-stat__label">Requested</p>
            <p class="tich-stat__value">KES {{ number_format($aggregation['totals']['requested'], 0) }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Verified</p>
            <p class="tich-stat__value">KES {{ number_format($aggregation['totals']['verified'], 0) }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Approved</p>
            <p class="tich-stat__value">KES {{ number_format($aggregation['totals']['approved'], 0) }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">CBE share</p>
            <p class="tich-stat__value">{{ $aggregation['totals']['cbe_share'] }}%</p>
        </div>
    </div>

    <div class="tich-table-panel tich-mt-8">
        <h2 class="tich-h3">By department</h2>
        <div class="tich-table-wrap tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Department</th>
                        <th>Requests</th>
                        <th>Requested</th>
                        <th>Verified</th>
                        <th>Approved</th>
                        <th>CBE</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($aggregation['by_department'] as $row)
                        <tr>
                            <td><strong>{{ $row['department'] }}</strong> <span class="tich-caption">{{ $row['dept_code'] }}</span></td>
                            <td>{{ $row['requests'] }}</td>
                            <td>KES {{ number_format($row['requested'], 0) }}</td>
                            <td>KES {{ number_format($row['verified'], 0) }}</td>
                            <td>KES {{ number_format($row['approved'], 0) }}</td>
                            <td>{{ $row['cbe_count'] }}</td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', ['colspan' => 6, 'title' => 'No budget requests for this year', 'icon' => 'inbox'])
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="tich-table-panel tich-mt-8">
        <h2 class="tich-h3">All requests</h2>
        <div class="tich-table-wrap tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Department</th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $item)
                        @php $annual = $item->annualQuartersPayload(); @endphp
                        <tr>
                            <td><strong>{{ $item->request_code }}</strong></td>
                            <td>{{ $item->department?->dept_name }}</td>
                            <td>{{ $item->title }}</td>
                            <td class="tich-caption">{{ $item->budget_type ? ucfirst($item->budget_type) : strtoupper($item->framework) }}</td>
                            <td>
                                {{ $item->requestedAmountLabel(0) }}
                                @if ($annual)
                                    <p class="tich-caption">{{ $item->annualBreakdownLabel() }}</p>
                                @endif
                            </td>
                            <td><x-status-badge :status="$item->status" /></td>
                            <td>
                                <a href="{{ route('administration.approvals.show', $item) }}" class="tich-btn tich-btn-secondary" style="padding:0.35rem 0.6rem; font-size:0.85rem;">View</a>
                            </td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', ['colspan' => 7, 'title' => 'No requests yet', 'icon' => 'inbox'])
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($requests instanceof \Illuminate\Contracts\Pagination\Paginator && $requests->hasPages())
            <div class="tich-mt-4">{{ $requests->links() }}</div>
        @endif
    </div>

    <div id="budget-request-modal" class="tich-modal" aria-hidden="true" role="dialog" aria-modal="true">
        <div class="tich-modal__backdrop" data-close-modal="budget-request-modal"></div>
        <div class="tich-modal__dialog">
            <header class="tich-modal__header">
                <h2 class="tich-h3" style="margin:0;">Submit budget request</h2>
                <button type="button" class="tich-modal__close" data-close-modal="budget-request-modal">&times;</button>
            </header>
            <form method="POST" action="{{ route('administration.budget-aggregation.store') }}" class="tich-modal__body">
                @csrf
                <div class="tich-form-stack">
                    <div class="tich-form-group">
                        <label class="tich-label">Department <span class="tich-text--danger">*</span></label>
                        <select name="department_id" class="tich-input" required>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}">{{ $department->dept_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="tich-grid tich-grid--2" style="gap:1rem;">
                        <div class="tich-form-group">
                            <label class="tich-label" for="title">Title <span class="tich-text--danger">*</span></label>
                            <input type="text" id="title" name="title" class="tich-input" required maxlength="300" placeholder="e.g. FY2026 operations budget">
                        </div>
                        <div class="tich-form-group">
                            <label class="tich-label" for="budget_type">Budget type</label>
                            <select id="budget_type" name="budget_type" class="tich-input">
                                <option value="">Optional</option>
                                @foreach (['annual', 'quarterly', 'monthly', 'weekly'] as $type)
                                    <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label" for="planning_cycle_id">Planning cycle</label>
                        <select id="planning_cycle_id" name="planning_cycle_id" class="tich-input">
                            <option value="">Optional</option>
                            @foreach ($cycles as $cycle)
                                <option value="{{ $cycle->id }}">{{ $cycle->cycle_code }} - {{ $cycle->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label" for="justification">Justification</label>
                        <textarea id="justification" name="justification" class="tich-input" rows="3" maxlength="3000" placeholder="Why this budget is needed"></textarea>
                    </div>
                </div>

                <div class="tich-table-panel tich-mt-4">
                    <div class="tich-flex-wrap" style="justify-content: space-between; align-items: center; gap: 0.75rem;">
                        <div>
                            <h2 class="tich-h3" style="margin:0;">Line items</h2>
                            <p class="tich-caption tich-mt-1" style="margin:0;">Enter items and quantities. Procurement adds the prices when they review the request.</p>
                        </div>
                        <button type="button" class="tich-btn tich-btn-secondary" id="admin-budget-add-line">+ Add line</button>
                    </div>
                    <div class="tich-table-wrap tich-mt-4">
                        <table class="tich-admin-table" id="admin-budget-lines-table">
                            <thead>
                                <tr>
                                    <th style="min-width:9rem;">Item</th>
                                    <th style="min-width:6rem;">Quantity</th>
                                    <th style="min-width:12rem;">Description</th>
                                    <th style="min-width:8rem;">Price per item</th>
                                    <th style="min-width:7rem;">Unit of measure</th>
                                    <th style="min-width:8rem;">Total</th>
                                    <th style="width:3rem;"></th>
                                </tr>
                            </thead>
                            <tbody id="admin-budget-lines-body">
                                <tr class="dept-budget-line">
                                    <td><input type="text" name="lines[0][item]" class="tich-input" required maxlength="255" placeholder="Item name"></td>
                                    <td><input type="number" name="lines[0][quantity]" class="tich-input js-line-qty" value="1" min="0.0001" step="any" required></td>
                                    <td><input type="text" name="lines[0][description]" class="tich-input" maxlength="2000" placeholder="Optional"></td>
                                    <td><input type="text" class="tich-input" placeholder="Procurement" disabled aria-disabled="true" tabindex="-1"></td>
                                    <td><input type="text" name="lines[0][unit_of_measure]" class="tich-input" maxlength="50" placeholder="e.g. pcs"></td>
                                    <td><span class="tich-caption">Awaiting prices</span></td>
                                    <td><button type="button" class="tich-btn tich-btn-ghost js-remove-line" title="Remove line" aria-label="Remove line">&times;</button></td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" style="text-align:right; font-weight:600;">Grand total (KES)</td>
                                    <td><strong><span class="tich-caption">Procurement</span></strong></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <footer class="tich-modal__footer">
                    <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="budget-request-modal">Cancel</button>
                    <button type="submit" class="tich-btn tich-btn-primary">Submit</button>
                </footer>
            </form>
        </div>
    </div>

    @include('admin.partials.tich-modal-assets')

    <script>
    (function () {
        var body = document.getElementById('admin-budget-lines-body');
        var addBtn = document.getElementById('admin-budget-add-line');
        if (!body || !addBtn) return;

        function reindexRows() {
            body.querySelectorAll('.dept-budget-line').forEach(function (row, index) {
                row.querySelectorAll('input[name^="lines["]').forEach(function (input) {
                    input.name = input.name.replace(/lines\[\d+]/, 'lines[' + index + ']');
                });
            });
        }

        function bindRow(row) {
            var removeBtn = row.querySelector('.js-remove-line');
            if (removeBtn) {
                removeBtn.addEventListener('click', function () {
                    if (body.querySelectorAll('.dept-budget-line').length <= 1) {
                        row.querySelectorAll('input').forEach(function (input) {
                            if (!input.name) return;
                            input.value = input.classList.contains('js-line-qty') ? '1' : '';
                        });
                        return;
                    }
                    row.remove();
                    reindexRows();
                });
            }
        }

        function addRow() {
            var index = body.querySelectorAll('.dept-budget-line').length;
            var row = document.createElement('tr');
            row.className = 'dept-budget-line';
            row.innerHTML =
                '<td><input type="text" name="lines[' + index + '][item]" class="tich-input" required maxlength="255" placeholder="Item name"></td>' +
                '<td><input type="number" name="lines[' + index + '][quantity]" class="tich-input js-line-qty" value="1" min="0.0001" step="any" required></td>' +
                '<td><input type="text" name="lines[' + index + '][description]" class="tich-input" maxlength="2000" placeholder="Optional"></td>' +
                '<td><input type="text" class="tich-input" placeholder="Procurement" disabled aria-disabled="true" tabindex="-1"></td>' +
                '<td><input type="text" name="lines[' + index + '][unit_of_measure]" class="tich-input" maxlength="50" placeholder="e.g. pcs"></td>' +
                '<td><span class="tich-caption">Awaiting prices</span></td>' +
                '<td><button type="button" class="tich-btn tich-btn-ghost js-remove-line" title="Remove line" aria-label="Remove line">&times;</button></td>';
            body.appendChild(row);
            bindRow(row);
            row.querySelector('input[name$="[item]"]')?.focus();
        }

        body.querySelectorAll('.dept-budget-line').forEach(bindRow);
        addBtn.addEventListener('click', addRow);
    })();
    </script>
@endsection
