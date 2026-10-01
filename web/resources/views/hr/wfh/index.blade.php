@extends('layouts.hr')

@section('title', 'Work from home')

@section('hr-content')
    <x-page-toolbar title="Work from home" meta="Staff WFH requests · Independent of leave · 1 day/week with in-month carry" />

    @if (session('status'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('status') }}</div>
    @endif

    <form method="GET" class="tich-card tich-mt-6" style="padding:1rem;">
        <div class="tich-grid tich-grid--3" style="gap:0.75rem; align-items:end;">
            <div class="tich-form-group" style="margin:0;">
                <label class="tich-label">Search</label>
                <input type="search" name="search" class="tich-input" value="{{ $filters['search'] ?? '' }}" placeholder="Name, employee no., code…">
            </div>
            <div class="tich-form-group" style="margin:0;">
                <label class="tich-label">Status</label>
                <select name="status" class="tich-input">
                    <option value="pending_hr" @selected(($filters['status'] ?? '') === 'pending_hr')>Pending HR</option>
                    <option value="approved" @selected(($filters['status'] ?? '') === 'approved')>Approved</option>
                    <option value="rejected" @selected(($filters['status'] ?? '') === 'rejected')>Rejected</option>
                    <option value="returned" @selected(($filters['status'] ?? '') === 'returned')>Returned</option>
                    <option value="cancelled" @selected(($filters['status'] ?? '') === 'cancelled')>Cancelled</option>
                    <option value="all" @selected(($filters['status'] ?? '') === 'all')>All</option>
                </select>
            </div>
            <div class="tich-form-group" style="margin:0;">
                <label class="tich-label">Year</label>
                <input type="number" name="year" class="tich-input" value="{{ $filters['year'] ?? '' }}" min="2020" max="2100">
            </div>
            <div class="tich-form-group" style="margin:0;">
                <label class="tich-label">Month</label>
                <select name="month" class="tich-input">
                    <option value="">Any</option>
                    @for ($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" @selected((int) ($filters['month'] ?? 0) === $m)>{{ \Carbon\Carbon::create(null, $m, 1)->format('F') }}</option>
                    @endfor
                </select>
            </div>
            <div style="display:flex; gap:0.5rem;">
                <button type="submit" class="tich-btn tich-btn-primary">Filter</button>
                <a href="{{ route('hr.wfh.index') }}" class="tich-btn tich-btn-ghost">Reset</a>
            </div>
        </div>
    </form>

    <div class="tich-card tich-table-panel tich-mt-6">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Staff</th>
                        <th>WFH date</th>
                        <th>Supervisor</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->staff?->fullName() }}</strong>
                                <p class="tich-caption">{{ $item->staff?->employee_number }} · {{ $item->request_code }}</p>
                            </td>
                            <td>{{ $item->work_date?->format('D, d M Y') }}</td>
                            <td>{{ $item->supervisor_name ?? '—' }}</td>
                            <td>{{ $item->submitted_at?->format('d M Y') ?? '—' }}</td>
                            <td><span class="uf-badge">{{ $item->statusLabel() }}</span></td>
                            <td><a href="{{ route('hr.wfh.show', $item) }}" class="tich-btn tich-btn-ghost tich-btn--sm">Review</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="tich-text tich-text--muted">No work from home requests for this filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($requests->hasPages())
            <div class="tich-mt-4">{{ $requests->links() }}</div>
        @endif
    </div>
@endsection
