@extends('layouts.employee')

@section('employee-content')
    @php
        $clockedIn = $todayRecord && $todayRecord->clock_in_time && ! $todayRecord->clock_out_time;
        $completed = $todayRecord && $todayRecord->clock_out_time;
    @endphp

    <x-page-toolbar title="Clock in / out" meta="Daily attendance, including off-campus field work" />

    @error('attendance')
        <div class="tich-alert tich-alert--error tich-mt-4">{{ $message }}</div>
    @enderror

    <div class="tich-grid tich-grid--2 tich-mt-8" style="align-items:start; gap:1.5rem;">
        <article class="tich-card">
            <h2 class="tich-h3">Today - {{ now()->format('l, d M Y') }}</h2>

            @if ($todayRecord)
                <dl class="tich-dl tich-mt-4">
                    <dt>Clock in</dt>
                    <dd>{{ $todayRecord->clock_in_time ? substr((string) $todayRecord->clock_in_time, 0, 5) : '-' }}</dd>
                    <dt>Clock out</dt>
                    <dd>{{ $todayRecord->clock_out_time ? substr((string) $todayRecord->clock_out_time, 0, 5) : '-' }}</dd>
                    @if ($todayRecord->work_hours)
                        <dt>Hours worked</dt>
                        <dd>{{ number_format((float) $todayRecord->work_hours, 2) }}</dd>
                    @endif
                    @if ($todayRecord->is_off_campus)
                        <dt>Location</dt>
                        <dd>Off-campus{{ $todayRecord->field_project_name ? ' · '.$todayRecord->field_project_name : '' }}</dd>
                    @endif
                </dl>
            @else
                <p class="tich-text tich-mt-4">You have not clocked in today.</p>
            @endif

            @if (! $completed)
                @if (! $clockedIn)
                    <form method="POST" action="{{ route('employee.attendance.clock-in') }}" class="tich-mt-6" id="clock-in-form">
                        @csrf
                        <div class="tich-form-stack">
                            <label class="tich-checkbox">
                                <input type="checkbox" name="is_off_campus" value="1" id="is_off_campus">
                                Off-campus / field work
                            </label>
                            <div id="field-details" hidden>
                                <label for="field_project_name" class="tich-label">Project / site name</label>
                                <input type="text" id="field_project_name" name="field_project_name" class="tich-input" maxlength="300">
                            </div>
                            <input type="hidden" name="location_lat_long" id="location_lat_long">
                            <button type="submit" class="tich-btn tich-btn-primary">Clock in</button>
                        </div>
                    </form>
                @else
                    <form method="POST" action="{{ route('employee.attendance.clock-out') }}" class="tich-mt-6">
                        @csrf
                        <div class="tich-form-stack">
                            <label for="notes" class="tich-label">Notes (optional)</label>
                            <input type="text" id="notes" name="notes" class="tich-input" maxlength="500">
                            <button type="submit" class="tich-btn tich-btn-primary">Clock out</button>
                        </div>
                    </form>
                @endif
            @endif
        </article>

        <article class="tich-card">
            <h2 class="tich-h3">Recent attendance</h2>
            <div class="tich-table-wrap tich-mt-4">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>In</th>
                            <th>Out</th>
                            <th>Hours</th>
                            <th>Location</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentRecords as $record)
                            <tr>
                                <td>{{ $record->attendance_date->format('d M Y') }}</td>
                                <td>{{ $record->clock_in_time ? substr((string) $record->clock_in_time, 0, 5) : '-' }}</td>
                                <td>{{ $record->clock_out_time ? substr((string) $record->clock_out_time, 0, 5) : '-' }}</td>
                                <td>{{ $record->work_hours ? number_format((float) $record->work_hours, 2) : '-' }}</td>
                                <td>{{ $record->is_off_campus ? 'Field' : 'On campus' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="tich-text tich-text--secondary">No attendance records yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </div>

    </div>

    <script>
        (function () {
            var offCampus = document.getElementById('is_off_campus');
            var fieldDetails = document.getElementById('field-details');
            var locationInput = document.getElementById('location_lat_long');

            if (offCampus && fieldDetails) {
                offCampus.addEventListener('change', function () {
                    fieldDetails.hidden = !offCampus.checked;
                });
            }

            if (navigator.geolocation && locationInput) {
                navigator.geolocation.getCurrentPosition(function (position) {
                    locationInput.value = position.coords.latitude + ',' + position.coords.longitude;
                }, function () {}, { enableHighAccuracy: true, timeout: 8000 });
            }
        })();
    </script>
@endsection
