@extends('layouts.administration')

@section('title', 'Fund distribution')

@section('administration-content')
    <x-page-toolbar title="Fund distribution" meta="Approved budgets and monthly allocations released to departments">
        <x-slot:actions>
            <button type="button" class="tich-btn tich-btn-primary" data-open-modal="fund-release-modal">+ Release allocation</button>
        </x-slot:actions>
    </x-page-toolbar>

    @if (session('status'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('status') }}</div>
    @endif
    @error('allocation')
        <div class="tich-alert tich-alert--error tich-mt-4">{{ $message }}</div>
    @enderror

    <div class="tich-card tich-table-panel tich-mt-8">
        <h2 class="tich-h3">Budget pipeline</h2>
        <p class="tich-caption tich-mt-1">Department budgets in Finance / CEO / disbursement stages. Open a request to see the full quarterly income and expenditure layout.</p>
        <div class="tich-table-wrap tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Request</th>
                        <th>Department</th>
                        <th>Type</th>
                        <th>Cycle</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($approvedRequests as $approved)
                        @php
                            $annual = $approved->annualQuartersPayload();
                            $amount = (float) ($approved->approved_amount ?? $approved->verified_amount ?? $approved->requested_amount ?? 0);
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $approved->request_code }}</strong>
                                <p class="tich-caption">{{ $approved->title }}</p>
                            </td>
                            <td>{{ $approved->department?->dept_name }}</td>
                            <td>
                                <span class="tich-badge">{{ $approved->budget_type ? ucfirst($approved->budget_type) : '—' }}</span>
                            </td>
                            <td class="tich-caption">
                                {{ $approved->planningCycle?->title ?? ($approved->planningCycle?->fiscal_year ?? '—') }}
                            </td>
                            <td>
                                <strong>KES {{ number_format($amount, 0) }}</strong>
                                @if ($annual)
                                    <p class="tich-caption">Income {{ number_format((float) ($annual['income_grand_total'] ?? 0), 0) }}</p>
                                    <p class="tich-caption">Expenditure {{ number_format((float) ($annual['expenditure_grand_total'] ?? 0), 0) }}</p>
                                @endif
                            </td>
                            <td>
                                @if ($approved->status === 'disbursed')
                                    <span class="tich-badge tich-badge--success">Disbursed</span>
                                @elseif ($approved->status === 'approved')
                                    <span class="tich-badge tich-badge--warning">Waiting disbursement</span>
                                @elseif ($approved->status === 'executive_review')
                                    <span class="tich-badge tich-badge--warning">Awaiting CEO</span>
                                @elseif ($approved->status === 'finance_review')
                                    <span class="tich-badge tich-badge--info">Finance review</span>
                                @else
                                    <span class="tich-badge tich-badge--info">{{ str_replace('_', ' ', ucfirst($approved->status)) }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="tich-flex-wrap" style="gap:0.5rem; align-items:center;">
                                    <a href="{{ route('administration.fund-distribution.budget.show', $approved->id) }}" class="tich-btn tich-btn-secondary" style="padding:0.35rem 0.6rem; font-size:0.85rem;">View</a>
                                    @if ($approved->status === 'approved')
                                        <form method="POST" action="{{ route('administration.fund-distribution.budget.disburse', $approved->id) }}" onsubmit="return confirm('Mark this budget request as disbursed?')" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="tich-btn tich-btn-primary" style="padding:0.35rem 0.6rem; font-size:0.85rem;">Mark disbursed</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', ['colspan' => 7, 'title' => 'No budgets in the approval pipeline', 'icon' => 'inbox'])
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="tich-card tich-table-panel tich-mt-8">
        <h2 class="tich-h3">Monthly allocations</h2>
        <p class="tich-caption tich-mt-1">Digital releases to departments against approved budgets.</p>
        <div class="tich-table-wrap tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Department</th>
                        <th>Period</th>
                        <th>Amount</th>
                        <th>Linked request</th>
                        <th>Released</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($allocations as $allocation)
                        <tr>
                            <td><strong>{{ $allocation->allocation_code }}</strong></td>
                            <td>{{ $allocation->department?->dept_name }}</td>
                            <td class="tich-caption">
                                {{ $allocation->fiscal_year }}
                                @if ($allocation->month)
                                    / {{ str_pad((string) $allocation->month, 2, '0', STR_PAD_LEFT) }}
                                @endif
                            </td>
                            <td>KES {{ number_format((float) $allocation->amount, 0) }}</td>
                            <td class="tich-caption">
                                @if ($allocation->budgetRequest)
                                    <a href="{{ route('administration.fund-distribution.budget.show', $allocation->budgetRequest->id) }}" class="tich-link">{{ $allocation->budgetRequest->request_code }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="tich-caption">{{ $allocation->released_at?->format('d M Y H:i') ?? '—' }}</td>
                            <td>
                                @if ($allocation->status === 'released')
                                    <form method="POST" action="{{ route('administration.fund-distribution.disburse', $allocation) }}" onsubmit="return confirm('Mark this allocation as disbursed?')" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="tich-btn tich-btn-primary" style="padding:0.35rem 0.6rem; font-size:0.85rem;">Mark disbursed</button>
                                    </form>
                                @else
                                    <span class="tich-badge tich-badge--success">{{ ucfirst($allocation->status) }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', ['colspan' => 7, 'title' => 'No allocations released yet', 'icon' => 'inbox'])
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($allocations instanceof \Illuminate\Contracts\Pagination\Paginator && $allocations->hasPages())
            <div class="tich-mt-4">{{ $allocations->links() }}</div>
        @endif
    </div>

    <div id="fund-release-modal" class="tich-modal" aria-hidden="true" role="dialog" aria-modal="true">
        <div class="tich-modal__backdrop" data-close-modal="fund-release-modal"></div>
        <div class="tich-modal__dialog">
            <header class="tich-modal__header">
                <h2 class="tich-h3" style="margin:0;">Release monthly allocation</h2>
                <button type="button" class="tich-modal__close" data-close-modal="fund-release-modal">&times;</button>
            </header>
            <form method="POST" action="{{ route('administration.fund-distribution.store') }}" class="tich-modal__body">
                @csrf
                <div class="tich-form-stack">
                    <div class="tich-form-group">
                        <label class="tich-label">Approved budget request</label>
                        <select name="budget_request_id" class="tich-input">
                            <option value="">Optional</option>
                            @foreach ($approvedRequests->where('status', 'approved') as $approved)
                                <option value="{{ $approved->id }}">
                                    {{ $approved->request_code }} — {{ $approved->title }} · {{ $approved->department?->dept_name }}
                                    (KES {{ number_format((float) ($approved->approved_amount ?? $approved->requested_amount ?? 0), 0) }})
                                </option>
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
                        <label class="tich-label">Fiscal year</label>
                        <input type="number" name="fiscal_year" class="tich-input" value="{{ now()->year }}" required>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Month</label>
                        <input type="number" name="month" min="1" max="12" class="tich-input" value="{{ now()->month }}">
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Amount (KES)</label>
                        <input type="number" step="0.01" min="0.01" name="amount" class="tich-input" required>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Notes</label>
                        <textarea name="notes" class="tich-input" rows="2"></textarea>
                    </div>
                </div>
                <footer class="tich-modal__footer">
                    <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="fund-release-modal">Cancel</button>
                    <button type="submit" class="tich-btn tich-btn-primary">Release</button>
                </footer>
            </form>
        </div>
    </div>

    @include('admin.partials.tich-modal-assets')
@endsection
