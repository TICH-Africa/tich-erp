@extends('layouts.employee')

@section('employee-content')
    @php
        $needsLocationVerification = $todayRecord && $todayRecord->needsClockInLocationVerification();
        $clockedIn = $todayRecord && $todayRecord->clock_in_time && ! $todayRecord->clock_out_time && ! $needsLocationVerification;
        $completed = $todayRecord && $todayRecord->clock_out_time;
    @endphp

    <x-page-toolbar title="Clock in / out" meta="GPS location is required when clocking in to confirm where you are reporting from" />

    @error('attendance')
        @unless ($message === \App\Services\StaffClockInLocationService::LOCATION_REQUIRED_MESSAGE)
            <div class="tich-alert tich-alert--error tich-mt-4">{{ $message }}</div>
        @endunless
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
                    <dt>Reporting location</dt>
                    <dd>
                        {{ $todayRecord->clockInLocationLabel() }}
                        @if ($todayRecord->field_project_name)
                            · {{ $todayRecord->field_project_name }}
                        @endif
                        @if ($todayRecord->clockInMapsUrl())
                            · <a href="{{ $todayRecord->clockInMapsUrl() }}" class="tich-link" target="_blank" rel="noopener">View on map</a>
                        @endif
                    </dd>
                    @if ($todayRecord->clock_in_accuracy_m)
                        <dt>GPS accuracy</dt>
                        <dd>±{{ number_format((float) $todayRecord->clock_in_accuracy_m, 0) }} m</dd>
                    @endif
                </dl>

                @if ($needsLocationVerification)
                    <div class="tich-card tich-mt-4" style="padding: 0.875rem 1rem; background: #fffbeb; border-color: #f59e0b;">
                        <p class="tich-text" style="margin: 0;">
                            Your clock-in at {{ substr((string) $todayRecord->clock_in_time, 0, 5) }} was not verified with GPS.
                            Please clock in again below with location enabled.
                        </p>
                    </div>
                @endif
            @else
                <p class="tich-text tich-mt-4">You have not clocked in today.</p>
            @endif

            @if (! $completed)
                @if (! $clockedIn)
                    <form method="POST" action="{{ route('employee.attendance.clock-in') }}" class="tich-mt-6" id="clock-in-form">
                        @csrf
                        <div class="tich-form-stack">
                            @if ($requireLocation)
                                <div id="clock-in-location-panel" class="tich-card" style="padding: 0.875rem 1rem; background: var(--tich-surface-muted, #f8fafc); border-style: dashed;">
                                    <p class="tich-caption" style="margin: 0 0 0.35rem;">Location check</p>
                                    <p class="tich-text" id="clock-in-location-status" style="margin: 0;">Waiting for your device location…</p>
                                    <p class="tich-caption tich-mt-2" style="margin-bottom: 0;">
                                        On-campus clock-ins must be within {{ number_format($campusGeofence['radius_meters']) }} m of {{ $campusGeofence['name'] }}.
                                    </p>
                                    <button type="button" class="tich-btn tich-btn-ghost tich-mt-2" id="clock-in-refresh-location" style="font-size:0.8125rem;">
                                        Refresh location
                                    </button>
                                </div>
                            @endif

                            <label class="tich-checkbox">
                                <input type="checkbox" name="is_off_campus" value="1" id="is_off_campus">
                                Off-campus / field work
                            </label>
                            <div id="field-details" hidden>
                                <label for="field_project_name" class="tich-label">Project / site name</label>
                                <input type="text" id="field_project_name" name="field_project_name" class="tich-input" maxlength="300" placeholder="e.g. Community outreach - Muhoroni">
                            </div>

                            <input type="hidden" name="clock_in_latitude" id="clock_in_latitude">
                            <input type="hidden" name="clock_in_longitude" id="clock_in_longitude">
                            <input type="hidden" name="clock_in_accuracy_m" id="clock_in_accuracy_m">
                            <input type="hidden" name="location_lat_long" id="location_lat_long">

                            <button type="submit" class="tich-btn tich-btn-primary" id="clock-in-submit" @if($requireLocation) aria-disabled="true" @endif>
                                {{ ($needsLocationVerification ?? false) ? 'Clock in again' : 'Clock in' }}
                            </button>
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
                                <td>
                                    {{ $record->clockInLocationLabel() }}
                                    @if ($record->clockInMapsUrl())
                                        · <a href="{{ $record->clockInMapsUrl() }}" class="tich-link" target="_blank" rel="noopener">Map</a>
                                    @endif
                                </td>
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

    @if (! $completed && ! $clockedIn && $requireLocation)
        <script>
            (function () {
                var form = document.getElementById('clock-in-form');
                if (!form) {
                    return;
                }

                var offCampus = document.getElementById('is_off_campus');
                var fieldDetails = document.getElementById('field-details');
                var submitButton = document.getElementById('clock-in-submit');
                var refreshButton = document.getElementById('clock-in-refresh-location');
                var statusEl = document.getElementById('clock-in-location-status');
                var latInput = document.getElementById('clock_in_latitude');
                var lngInput = document.getElementById('clock_in_longitude');
                var accuracyInput = document.getElementById('clock_in_accuracy_m');
                var legacyInput = document.getElementById('location_lat_long');
                var campusLat = {{ json_encode($campusGeofence['latitude']) }};
                var campusLng = {{ json_encode($campusGeofence['longitude']) }};
                var campusRadius = {{ json_encode($campusGeofence['radius_meters']) }};
                var campusName = {{ json_encode($campusGeofence['name']) }};
                var maxAccuracy = {{ json_encode($maxLocationAccuracy ?? 2000) }};
                var allowRemoteClockIn = {{ ($needsLocationVerification ?? false) ? 'true' : 'false' }};
                var shouldPromptLocation = {{ ($promptLocation ?? false) ? 'true' : 'false' }};
                var latestPosition = null;
                var locating = false;
                var submitAllowed = false;
                var pendingSubmit = false;

                function setSubmitAllowed(allowed) {
                    submitAllowed = allowed;
                    if (!submitButton) {
                        return;
                    }

                    submitButton.setAttribute('aria-disabled', allowed ? 'false' : 'true');
                    submitButton.style.opacity = allowed ? '' : '0.55';
                    submitButton.style.cursor = allowed ? '' : 'not-allowed';
                }

                if (offCampus && fieldDetails) {
                    offCampus.addEventListener('change', function () {
                        fieldDetails.hidden = !offCampus.checked;
                        updateStatusMessage();
                    });
                }

                function toRadians(value) {
                    return value * Math.PI / 180;
                }

                function distanceMeters(lat1, lng1, lat2, lng2) {
                    var earthRadius = 6371000;
                    var latDelta = toRadians(lat2 - lat1);
                    var lngDelta = toRadians(lng2 - lng1);
                    var a = Math.sin(latDelta / 2) * Math.sin(latDelta / 2)
                        + Math.cos(toRadians(lat1)) * Math.cos(toRadians(lat2))
                        * Math.sin(lngDelta / 2) * Math.sin(lngDelta / 2);
                    var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

                    return earthRadius * c;
                }

                function accuracyIsAcceptable(accuracy) {
                    if (accuracy === null || accuracy === undefined || isNaN(accuracy)) {
                        return true;
                    }

                    return accuracy <= maxAccuracy;
                }

                function setLocation(position) {
                    locating = false;
                    latestPosition = position;
                    var lat = position.coords.latitude;
                    var lng = position.coords.longitude;
                    var accuracy = position.coords.accuracy;

                    latInput.value = lat;
                    lngInput.value = lng;
                    accuracyInput.value = accuracy ?? '';
                    legacyInput.value = lat + ',' + lng;

                    updateStatusMessage();

                    if (pendingSubmit) {
                        if (submitAllowed) {
                            form.requestSubmit();
                        }
                        pendingSubmit = false;
                    }
                }

                function updateStatusMessage() {
                    if (!statusEl || !submitButton) {
                        return;
                    }

                    if (locating) {
                        statusEl.textContent = 'Detecting your location…';
                        setSubmitAllowed(false);
                        return;
                    }

                    if (!latestPosition) {
                        statusEl.textContent = 'Waiting for your device location. Click "Refresh location" if this takes too long.';
                        setSubmitAllowed(false);
                        return;
                    }

                    var lat = latestPosition.coords.latitude;
                    var lng = latestPosition.coords.longitude;
                    var accuracy = latestPosition.coords.accuracy;
                    var distance = Math.round(distanceMeters(lat, lng, campusLat, campusLng));
                    var offCampusChecked = offCampus && offCampus.checked;

                    if (!accuracyIsAcceptable(accuracy)) {
                        statusEl.textContent = 'Location accuracy is ±' + Math.round(accuracy) + ' m. Move to an open area for a clearer GPS reading, then refresh.';
                        setSubmitAllowed(false);
                        return;
                    }

                    if (offCampusChecked || allowRemoteClockIn) {
                        var prefix = allowRemoteClockIn
                            ? 'Location captured for re-verification'
                            : 'Off-campus location captured';
                        statusEl.textContent = prefix + ' (±' + (accuracy ? Math.round(accuracy) : '?') + ' m, ' + distance + ' m from ' + campusName + '). You may clock in.';
                        setSubmitAllowed(true);
                        return;
                    }

                    if (distance <= campusRadius) {
                        statusEl.textContent = 'On campus verified (±' + (accuracy ? Math.round(accuracy) : '?') + ' m, ' + distance + ' m from ' + campusName + '). You may clock in.';
                        setSubmitAllowed(true);
                        return;
                    }

                    statusEl.textContent = 'You appear to be ' + distance + ' m from ' + campusName + '. Tick "Off-campus / field work" if you are working away from campus, then clock in again.';
                    setSubmitAllowed(false);
                }

                function locationError(message) {
                    locating = false;
                    if (statusEl) {
                        statusEl.textContent = message;
                    }
                    setSubmitAllowed(false);
                }

                function requestLocation(fromUserAction) {
                    if (!navigator.geolocation) {
                        locationError('This browser does not support location services. Use a device with GPS to clock in.');
                        pendingSubmit = false;
                        return;
                    }

                    locating = true;
                    updateStatusMessage();

                    navigator.geolocation.getCurrentPosition(setLocation, function (error) {
                        pendingSubmit = false;

                        if (error.code === error.PERMISSION_DENIED) {
                            if (fromUserAction) {
                                locationError('Location access was denied. Enable location for this site in your browser settings, then try again.');
                            } else {
                                statusEl.textContent = 'Location permission needed. Click Clock in to allow access.';
                            }
                            setSubmitAllowed(false);
                            return;
                        }

                        locationError('Could not detect your location. Check GPS/location services and click Refresh location.');
                    }, {
                        enableHighAccuracy: true,
                        timeout: 20000,
                        maximumAge: 0,
                    });
                }

                if (refreshButton) {
                    refreshButton.addEventListener('click', function () {
                        requestLocation(true);
                    });
                }

                if (!shouldPromptLocation) {
                    requestLocation(false);
                } else {
                    statusEl.textContent = 'Location permission needed. Click Clock in to allow access.';
                }

                form.addEventListener('submit', function (event) {
                    if (!navigator.geolocation) {
                        event.preventDefault();
                        locationError('Location services are required to clock in.');
                        return;
                    }

                    if (latInput.value && lngInput.value && submitAllowed) {
                        return;
                    }

                    event.preventDefault();

                    if (!latInput.value || !lngInput.value || shouldPromptLocation) {
                        shouldPromptLocation = false;
                        pendingSubmit = true;
                        requestLocation(true);
                        return;
                    }

                    if (!submitAllowed) {
                        locationError('Your current location does not meet the clock-in rules shown above.');
                    }
                });
            })();
        </script>
    @else
        <script>
            (function () {
                var offCampus = document.getElementById('is_off_campus');
                var fieldDetails = document.getElementById('field-details');

                if (offCampus && fieldDetails) {
                    offCampus.addEventListener('change', function () {
                        fieldDetails.hidden = !offCampus.checked;
                    });
                }
            })();
        </script>
    @endif
@endsection
