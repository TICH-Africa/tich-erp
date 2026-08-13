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
            <div><dt class="tich-caption">Period</dt><dd>{{ $budget->period_start?->format('d M Y') }} – {{ $budget->period_end?->format('d M Y') }}</dd></div>
            <div><dt class="tich-caption">Status</dt><dd>{{ ucfirst($budget->status) }}</dd></div>
            <div><dt class="tich-caption">Approved by</dt><dd>{{ $budget->approver?->fullName() ?? '-' }}</dd></div>
        </dl>
        @if ($budget->notes)
            <p class="tich-mt-6 tich-text">{{ $budget->notes }}</p>
        @endif
    </article>
@endsection
