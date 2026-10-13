@extends('layouts.hr')

@section('title', 'Time logs')

@section('hr-content')
    <x-page-toolbar title="Time logs" meta="Staff weekly time logs submitted for HR review">
        <x-slot:actions>
            <a href="{{ route('hr.time-logs.submissions') }}" class="tich-btn tich-btn-primary">View submissions per employee</a>
            <a href="{{ route('hr.time-logs.index') }}" class="tich-btn tich-btn-ghost">Reset</a>
        </x-slot:actions>
        <x-slot:filters>
            <form method="GET" action="{{ route('hr.time-logs.index') }}" class="tich-page-toolbar__filters-form tich-time-log-toolbar-filters" data-live-filter>
                @include('partials.search-field', ['placeholder' => 'Name, employee no., code…', 'value' => $filters['search'] ?? ''])
                <select id="tl-status" name="status" class="tich-input tich-input--compact" title="Status" aria-label="Status">
                    <option value="all" @selected(($filters['status'] ?? '') === 'all')>All statuses</option>
                    <option value="pending_hr" @selected(($filters['status'] ?? 'pending_hr') === 'pending_hr')>Pending HR</option>
                    <option value="approved" @selected(($filters['status'] ?? '') === 'approved')>Approved</option>
                    <option value="rejected" @selected(($filters['status'] ?? '') === 'rejected')>Rejected</option>
                    <option value="returned" @selected(($filters['status'] ?? '') === 'returned')>Returned</option>
                </select>
                <input
                    id="tl-year"
                    type="number"
                    name="year"
                    class="tich-input tich-input--compact tich-time-log-toolbar-filters__year"
                    value="{{ $filters['year'] ?? '' }}"
                    min="2020"
                    max="2100"
                    placeholder="Year"
                    title="Year"
                    aria-label="Year"
                >
                <select id="tl-month" name="month" class="tich-input tich-input--compact" title="Month" aria-label="Month">
                    <option value="">Any month</option>
                    @for ($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" @selected((int) ($filters['month'] ?? 0) === $m)>{{ \Carbon\Carbon::create(null, $m, 1)->format('F') }}</option>
                    @endfor
                </select>
                <input
                    id="tl-week"
                    type="number"
                    name="week_number"
                    class="tich-input tich-input--compact tich-time-log-toolbar-filters__week"
                    value="{{ $filters['week_number'] ?? '' }}"
                    min="1"
                    max="6"
                    placeholder="Week 1–6"
                    title="Week of month"
                    aria-label="Week of month"
                >
                <input
                    id="tl-from"
                    type="date"
                    name="submitted_from"
                    class="tich-input tich-input--compact"
                    value="{{ $filters['submitted_from'] ?? '' }}"
                    title="Submitted from"
                    aria-label="Submitted from"
                >
                <input
                    id="tl-to"
                    type="date"
                    name="submitted_to"
                    class="tich-input tich-input--compact"
                    value="{{ $filters['submitted_to'] ?? '' }}"
                    title="Submitted to"
                    aria-label="Submitted to"
                >
            </form>
        </x-slot:filters>
    </x-page-toolbar>

    @if (session('status'))
        <div class="tich-alert tich-alert--success">{{ session('status') }}</div>
    @endif

    <div class="tich-card tich-table-panel">
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
                            <td class="tich-caption">{{ $item->employee_signed_at?->format('d M Y H:i') ?? '-' }}</td>
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
