@extends('layouts.administration')

@section('title', 'Fund distribution')

@section('administration-content')
    <x-page-toolbar title="Fund distribution" meta="Approved budgets from Finance and released monthly allocations to departments">
        <x-slot:actions>
            <button type="button" class="tich-btn tich-btn-primary" data-open-modal="fund-release-modal">+ Release allocation</button>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel tich-mt-8">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Department</th>
                        <th>Period</th>
                        <th>Amount</th>
                        <th>Linked request</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($approvedRequests as $approved)
                        <tr>
                            <td><strong>{{ $approved->request_code }}</strong></td>
                            <td>{{ $approved->department?->dept_name }}</td>
                            <td class="tich-caption">
                                {{ $approved->planningCycle?->fiscal_year ?? now()->year }}
                            </td>
                            <td>KES {{ number_format((float) ($approved->approved_amount ?? $approved->requested_amount ?? 0), 0) }}</td>
                            <td class="tich-caption">{{ $approved->request_code }}</td>
                            <td class="tich-caption">{{ $approved->disbursed_at?->format('d M Y H:i') ?? ($approved->executive_approved_at?->format('d M Y H:i') ?? ($approved->finance_verified_at?->format('d M Y H:i') ?? ($approved->submitted_at?->format('d M Y H:i') ?? '-'))) }}</td>
                            <td>
                                @if ($approved->status === 'disbursed')
                                    <span class="tich-badge tich-badge--success">Disbursed</span>
                                @elseif ($approved->status === 'approved')
                                    <span class="tich-badge tich-badge--warning">Waiting disbursement</span>
                                    <form method="POST" action="{{ route('administration.fund-distribution.budget.disburse', $approved->id) }}" onsubmit="return confirm('Mark this budget request as disbursed?')" style="display:inline; margin-left: 0.5rem;">
                                        @csrf
                                        <button type="submit" class="tich-btn tich-btn-primary" style="padding:0.35rem 0.6rem; font-size:0.85rem;">Mark as disbursed</button>
                                    </form>
                                @elseif ($approved->status === 'executive_review')
                                    <span class="tich-badge tich-badge--warning">Awaiting CEO Approval</span>
                                @elseif ($approved->status === 'finance_review')
                                    <span class="tich-badge tich-badge--info">In Finance Review</span>
                                @else
                                    <span class="tich-badge tich-badge--info">Awaiting Finance Review</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', ['colspan' => 7, 'title' => 'No budgets in the approval pipeline', 'icon' => 'inbox'])
                    @endforelse

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
                            <td class="tich-caption">{{ $allocation->budgetRequest?->request_code ?? '-' }}</td>
                            <td class="tich-caption">{{ $allocation->released_at?->format('d M Y H:i') ?? '-' }}</td>
                            <td>
                                @if ($allocation->status === 'released')
                                    <form method="POST" action="{{ route('administration.fund-distribution.disburse', $allocation) }}" onsubmit="return confirm('Mark this allocation as disbursed?')" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="tich-btn tich-btn-primary" style="padding:0.35rem 0.6rem; font-size:0.85rem;">Mark as disbursed</button>
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
                                    {{ $approved->request_code }} - {{ $approved->department?->dept_name }} (KES {{ number_format($approved->approved_amount ?? 0, 0) }})
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
