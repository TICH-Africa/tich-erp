@extends('layouts.hr')

@section('title', 'Time logs')

@section('hr-content')
    <x-page-toolbar title="Time logs" meta="Staff weekly time logs submitted for HR review" />

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
                    <option value="pending_hr" @selected(($filters['status'] ?? 'pending_hr') === 'pending_hr')>Pending HR</option>
                    <option value="approved" @selected(($filters['status'] ?? '') === 'approved')>Approved</option>
                    <option value="rejected" @selected(($filters['status'] ?? '') === 'rejected')>Rejected</option>
                    <option value="returned" @selected(($filters['status'] ?? '') === 'returned')>Returned</option>
                </select>
            </div>
            <div class="tich-form-group" style="margin:0;">
                <label class="tich-label">Year</label>
                <input type="number" name="year" class="tich-input" value="{{ $filters['year'] ?? '' }}" min="2020" max="2100" placeholder="e.g. {{ now()->year }}">
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
            <div class="tich-form-group" style="margin:0;">
                <label class="tich-label">Week of month</label>
                <input type="number" name="week_number" class="tich-input" value="{{ $filters['week_number'] ?? '' }}" min="1" max="6" placeholder="1–6">
            </div>
            <div class="tich-form-group" style="margin:0;">
                <label class="tich-label">Submitted from</label>
                <input type="date" name="submitted_from" class="tich-input" value="{{ $filters['submitted_from'] ?? '' }}">
            </div>
            <div class="tich-form-group" style="margin:0;">
                <label class="tich-label">Submitted to</label>
                <input type="date" name="submitted_to" class="tich-input" value="{{ $filters['submitted_to'] ?? '' }}">
            </div>
            <div style="display:flex; gap:0.5rem;">
                <button type="submit" class="tich-btn tich-btn-primary">Filter</button>
                <a href="{{ route('hr.time-logs.index') }}" class="tich-btn tich-btn-ghost">Reset</a>
            </div>
        </div>
    </form>

    <div class="tich-card tich-table-panel tich-mt-6">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Staff</th>
                        <th>Period</th>
                        <th>Hours</th>
                        <th>Submitted</th>
                        <th>Manager</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->staff?->fullName() }}</strong>
                                <p class="tich-caption">{{ $item->staff?->employee_number }} · {{ $item->log_code }}</p>
                            </td>
                            <td>
                                {{ $item->monthLabel() }} · Week {{ $item->week_number }}
                                <p class="tich-caption">{{ $item->week_ref }}</p>
                            </td>
                            <td>{{ number_format((float) $item->total_hours, 2) }}</td>
                            <td class="tich-caption">{{ $item->employee_signed_at?->format('d M Y H:i') ?? '—' }}</td>
                            <td class="tich-caption">
                                @if ($item->manager_signed_at)
                                    {{ $item->manager_signed_name }}{{ $item->manager_self_endorsed ? ' (self)' : '' }}
                                @else
                                    Awaiting endorsement
                                @endif
                            </td>
                            <td><x-status-badge :status="$item->status" /></td>
                            <td><a href="{{ route('hr.time-logs.show', $item) }}" class="tich-btn tich-btn-primary" style="padding:0.35rem 0.6rem;font-size:0.85rem;">Open</a></td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', ['colspan' => 7, 'title' => 'No time logs match these filters', 'icon' => 'inbox'])
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($logs->hasPages())
            <div class="tich-mt-4">{{ $logs->links() }}</div>
        @endif
    </div>
@endsection
