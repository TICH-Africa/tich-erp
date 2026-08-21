@extends('layouts.hr')

@section('title', 'Staff attendance reviews')

@section('hr-content')
    <x-page-toolbar
        title="Staff attendance reviews"
        :meta="$pendingCount > 0 ? $pendingCount . ' clock-in(s) awaiting HR review' : 'All reviewed clock-ins'"
    >
        <x-slot:filters>
            <form method="GET" action="{{ route('hr.attendance.index') }}" class="tich-page-toolbar__filters-form">
                <select name="period" class="tich-input tich-input--compact" onchange="toggleCustomDates(this.value)">
                    <option value="this_month" @selected($period === 'this_month')>This month</option>
                    <option value="today" @selected($period === 'today')>Today</option>
                    <option value="this_week" @selected($period === 'this_week')>This week</option>
                    <option value="this_year" @selected($period === 'this_year')>This year</option>
                    <option value="last_month" @selected($period === 'last_month')>Last month</option>
                    <option value="last_quarter" @selected($period === 'last_quarter')>Last quarter</option>
                    <option value="custom" @selected($period === 'custom')>Custom range…</option>
                </select>

                <div id="custom-dates" style="display: none;">
                    <input type="date" name="start_date" value="{{ request('start_date') }}" class="tich-input tich-input--compact">
                    <input type="date" name="end_date" value="{{ request('end_date') }}" class="tich-input tich-input--compact">
                </div>

                <select name="hr_status" class="tich-input tich-input--compact" onchange="this.form.submit()">
                    <option value="">All HR statuses</option>
                    <option value="pending" @selected(request('hr_status') === 'pending')>Pending review</option>
                    <option value="approved" @selected(request('hr_status') === 'approved')>Approved</option>
                    <option value="rejected" @selected(request('hr_status') === 'rejected')>Rejected</option>
                </select>
            </form>
        </x-slot:filters>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Employee</th>
                        <th>Clock in</th>
                        <th>Clock out</th>
                        <th>Hours</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($attendance as $record)
                        <tr>
                            <td>{{ $record->attendance_date->format('D d M Y') }}</td>
                            <td>
                                <strong>{{ $record->staff->fullName() }}</strong>
                                <p class="tich-caption">{{ $record->staff->employee_number }} · {{ $record->staff->department?->dept_name ?? '-' }}</p>
                            </td>
                            <td>{{ $record->clock_in_time ? substr((string) $record->clock_in_time, 0, 5) : '-' }}</td>
                            <td>{{ $record->clock_out_time ? substr((string) $record->clock_out_time, 0, 5) : '-' }}</td>
                            <td>{{ $record->work_hours ? number_format((float) $record->work_hours, 2) : '-' }}</td>
                            <td>
                                @if ($record->location_verification_status)
                                    @if ($record->clockInMapsUrl())
                                        <a href="{{ $record->clockInMapsUrl() }}" class="tich-link" target="_blank" rel="noopener">{{ app(\App\Services\StaffClockInLocationService::class)->statusLabel($record->location_verification_status) }}</a>
                                    @else
                                        {{ app(\App\Services\StaffClockInLocationService::class)->statusLabel($record->location_verification_status) }}
                                    @endif
                                @else
                                    <span class="tich-text tich-text--secondary">-</span>
                                @endif
                            </td>
                            <td>{!! $record->hrReviewBadge() !!}</td>
                            <td>
                                <a href="{{ route('hr.attendance.show', $record) }}" class="tich-btn tich-btn-ghost tich-btn--sm">Review</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="tich-text tich-text--secondary">No attendance records found for this period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($attendance->hasPages())
            <div class="tich-mt-4">{{ $attendance->links() }}</div>
        @endif
    </div>

    <script>
        function toggleCustomDates(value) {
            document.getElementById('custom-dates').style.display = value === 'custom' ? 'flex' : 'none';
            if (value === 'custom') return;
            document.querySelector('[name="hr_status"]').form.submit();
        }
    </script>
@endsection
