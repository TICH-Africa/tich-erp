@extends('layouts.finance')

@section('title', 'Budgeting')

@section('finance-content')
    <x-page-toolbar title="{{ $budget->budget_name }}" meta="{{ $budget->budget_code }} · FY {{ $budget->fiscal_year }}">
        <x-slot:actions>
            <a href="{{ route('finance.budgeting.index', $department) }}" class="tich-btn tich-btn-ghost">Back to budgets</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-grid tich-grid--4 tich-mt-6">
        <article class="tich-card tich-stat">
            <p class="tich-caption">Allocated</p>
            <p class="tich-stat__value">KES {{ number_format((float) $budget->allocated_amount, 2) }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Spent</p>
            <p class="tich-stat__value">KES {{ number_format((float) $budget->spent_amount, 2) }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Committed</p>
            <p class="tich-stat__value">KES {{ number_format((float) $budget->committed_amount, 2) }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Available</p>
            <p class="tich-stat__value">KES {{ number_format($budget->availableAmount(), 2) }}</p>
        </article>
    </div>

    <article class="tich-card tich-mt-6">
        <dl style="display:grid; gap:0.85rem; grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr));">
            <div><dt class="tich-caption">Type</dt><dd>{{ ucfirst($budget->budget_type) }}</dd></div>
            <div><dt class="tich-caption">Department</dt><dd>{{ $budget->department?->dept_name ?? 'Institution-wide' }}</dd></div>
            <div><dt class="tich-caption">Period</dt><dd>{{ $budget->period_start?->format('d M Y') }} - {{ $budget->period_end?->format('d M Y') }}</dd></div>
            <div><dt class="tich-caption">Status</dt><dd>{{ ucfirst($budget->status) }}</dd></div>
            <div><dt class="tich-caption">Approved by</dt><dd>{{ $budget->approver?->fullName() ?? '-' }}</dd></div>
        </dl>
        @if ($budget->notes)
            <p class="tich-mt-6 tich-text">{{ $budget->notes }}</p>
        @endif
    </article>

    <article class="tich-card tich-mt-6">
        <div class="tich-flex" style="justify-content:space-between; align-items:center;">
            <h2 class="tich-h3">Budget cycles</h2>
            <button type="button" class="tich-btn tich-btn-primary" data-open-modal="cycle-create-modal">+ Add cycle</button>
        </div>
        <p class="tich-caption tich-mt-2">Divide this budget into annual, quarterly, monthly, or weekly allocations.</p>

        <div class="tich-table-wrap tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Cycle</th>
                        <th>Label</th>
                        <th>Period</th>
                        <th>Allocated</th>
                        <th>Spent</th>
                        <th>Committed</th>
                        <th>Available</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($budget->cycles as $cycle)
                        <tr>
                            <td><span class="tich-badge">{{ ucfirst($cycle->cycle_type) }}</span></td>
                            <td>{{ $cycle->label }}</td>
                            <td>{{ $cycle->period_start?->format('d M Y') }} - {{ $cycle->period_end?->format('d M Y') }}</td>
                            <td>KES {{ number_format((float) $cycle->allocated_amount, 2) }}</td>
                            <td>KES {{ number_format((float) $cycle->spent_amount, 2) }}</td>
                            <td>KES {{ number_format((float) $cycle->committed_amount, 2) }}</td>
                            <td>KES {{ number_format($cycle->availableAmount(), 2) }}</td>
                            <td>{{ ucfirst($cycle->status) }}</td>
                            <td>
                                <button type="button" class="tich-btn tich-btn-secondary" style="padding:0.35rem 0.6rem; font-size:0.85rem;" data-open-modal="cycle-edit-modal-{{ $cycle->id }}" data-cycle-label="{{ $cycle->label }}" data-cycle-type="{{ $cycle->cycle_type }}" data-period-start="{{ $cycle->period_start?->format('Y-m-d') }}" data-period-end="{{ $cycle->period_end?->format('Y-m-d') }}" data-allocated="{{ $cycle->allocated_amount }}" data-notes="{{ $cycle->notes }}">Edit</button>
                            </td>
                        </tr>
                    @empty
                        <tr>@include('partials.states.table-empty', ['colspan' => 9, 'title' => 'No cycles added yet.', 'icon' => 'inbox'])</tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </article>

    <div id="cycle-create-modal" class="tich-modal" aria-hidden="true" role="dialog" aria-modal="true">
        <div class="tich-modal__backdrop" data-close-modal="cycle-create-modal"></div>
        <div class="tich-modal__dialog">
            <header class="tich-modal__header">
                <h2 class="tich-h3" style="margin:0;">Add budget cycle</h2>
                <button type="button" class="tich-modal__close" data-close-modal="cycle-create-modal">&times;</button>
            </header>
            <form method="POST" action="{{ route('finance.budgeting.cycles.store', [$department, $budget]) }}" class="tich-modal__body">
                @csrf
                <div class="tich-form-stack">
                    <div class="tich-form-group">
                        <label class="tich-label">Cycle type</label>
                        <select name="cycle_type" class="tich-input" required>
                            <option value="annual">Annual</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="monthly">Monthly</option>
                            <option value="weekly">Weekly</option>
                        </select>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Label</label>
                        <input type="text" name="label" class="tich-input" placeholder="e.g. Q1 2026, January 2026" required>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Period start</label>
                        <input type="date" name="period_start" class="tich-input" required>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Period end</label>
                        <input type="date" name="period_end" class="tich-input" required>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Allocated amount (KES)</label>
                        <input type="number" step="0.01" min="0" name="allocated_amount" class="tich-input" required>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Notes</label>
                        <textarea name="notes" class="tich-input" rows="2"></textarea>
                    </div>
                    <button type="submit" class="tich-btn tich-btn-primary">Add cycle</button>
                </div>
            </form>
        </div>
    </div>

    @foreach ($budget->cycles as $cycle)
        <div id="cycle-edit-modal-{{ $cycle->id }}" class="tich-modal" aria-hidden="true" role="dialog" aria-modal="true">
            <div class="tich-modal__backdrop" data-close-modal="cycle-edit-modal-{{ $cycle->id }}"></div>
            <div class="tich-modal__dialog">
                <header class="tich-modal__header">
                    <h2 class="tich-h3" style="margin:0;">Edit budget cycle</h2>
                    <button type="button" class="tich-modal__close" data-close-modal="cycle-edit-modal-{{ $cycle->id }}">&times;</button>
                </header>
                <form method="POST" action="{{ route('finance.budgeting.cycles.update', [$department, $budget, $cycle]) }}" class="tich-modal__body">
                    @csrf
                    @method('PUT')
                    <div class="tich-form-stack">
                        <div class="tich-form-group">
                            <label class="tich-label">Cycle type</label>
                            <select name="cycle_type" class="tich-input" required>
                                <option value="annual" {{ $cycle->cycle_type === 'annual' ? 'selected' : '' }}>Annual</option>
                                <option value="quarterly" {{ $cycle->cycle_type === 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                                <option value="monthly" {{ $cycle->cycle_type === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                <option value="weekly" {{ $cycle->cycle_type === 'weekly' ? 'selected' : '' }}>Weekly</option>
                            </select>
                        </div>
                        <div class="tich-form-group">
                            <label class="tich-label">Label</label>
                            <input type="text" name="label" class="tich-input" value="{{ $cycle->label }}" required>
                        </div>
                        <div class="tich-form-group">
                            <label class="tich-label">Period start</label>
                            <input type="date" name="period_start" class="tich-input" value="{{ $cycle->period_start?->format('Y-m-d') }}" required>
                        </div>
                        <div class="tich-form-group">
                            <label class="tich-label">Period end</label>
                            <input type="date" name="period_end" class="tich-input" value="{{ $cycle->period_end?->format('Y-m-d') }}" required>
                        </div>
                        <div class="tich-form-group">
                            <label class="tich-label">Allocated amount (KES)</label>
                            <input type="number" step="0.01" min="0" name="allocated_amount" class="tich-input" value="{{ $cycle->allocated_amount }}" required>
                        </div>
                        <div class="tich-form-group">
                            <label class="tich-label">Notes</label>
                            <textarea name="notes" class="tich-input" rows="2">{{ $cycle->notes }}</textarea>
                        </div>
                        <button type="submit" class="tich-btn tich-btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach

    @include('admin.partials.tich-modal-assets')
@endsection
