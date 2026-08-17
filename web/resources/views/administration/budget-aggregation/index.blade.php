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

    <div class="tich-card tich-table-panel tich-mt-8">
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
                        <tr><td colspan="6" class="tich-table-empty">No budget requests for this year.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="tich-card tich-table-panel tich-mt-8">
        <h2 class="tich-h3">All requests</h2>
        <div class="tich-table-wrap tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Department</th>
                        <th>Title</th>
                        <th>Framework</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $item)
                        <tr>
                            <td><strong>{{ $item->request_code }}</strong></td>
                            <td>{{ $item->department?->dept_name }}</td>
                            <td>{{ $item->title }}</td>
                            <td class="tich-caption">{{ strtoupper($item->framework) }}</td>
                            <td>KES {{ number_format($item->requested_amount, 0) }}</td>
                            <td><span class="tich-badge">{{ str_replace('_', ' ', ucfirst($item->status)) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="tich-table-empty">No requests yet.</td></tr>
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
                        <label class="tich-label">Planning cycle</label>
                        <select name="planning_cycle_id" class="tich-input">
                            <option value="">Optional</option>
                            @foreach ($cycles as $cycle)
                                <option value="{{ $cycle->id }}">{{ $cycle->cycle_code }} - {{ $cycle->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Department</label>
                        <select name="department_id" class="tich-input" required>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}">{{ $department->dept_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Title</label>
                        <input type="text" name="title" class="tich-input" required>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Framework</label>
                        <select name="framework" class="tich-input" required>
                            <option value="standard">Standard</option>
                            <option value="cbe">CBE</option>
                        </select>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Requested amount (KES)</label>
                        <input type="number" step="0.01" min="0" name="requested_amount" class="tich-input" required>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Justification</label>
                        <textarea name="justification" class="tich-input" rows="3"></textarea>
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
@endsection
