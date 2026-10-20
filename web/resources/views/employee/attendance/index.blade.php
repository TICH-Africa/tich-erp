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
                                <div id="clock-in-location-panel" class="tich-card" style="padding: 1rem; background: #fef2f2; border: 1px solid #fecaca; border-style: solid;">
                                    <p class="tich-caption" style="margin: 0 0 0.35rem; color: #991b1b; font-weight: 600;">Location check</p>
                                    <p class="tich-text" id="clock-in-location-status" style="margin: 0 0 0.5rem; color: #991b1b;">Location is required to clock in. Click the button below and allow location access when prompted.</p>
                                    <p class="tich-caption tich-mt-2" style="margin-bottom: 0.5rem;">
                                        On-campus clock-ins must be within {{ number_format($campusGeofence['radius_meters']) }} m of {{ $campusGeofence['name'] }}.
                                    </p>
                                    <button type="button" class="tich-btn tich-btn-primary" id="clock-in-refresh-location" style="font-size:0.8125rem; background: #dc2626; border-color: #dc2626;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -2px; margin-right: 0.35rem;">
                                            <polygon points="3 11 22 2 13 21 11 13 3 11"></polygon>
                                        </svg>
                                        Detect My Location
                                    </button>
                                </div>
                            @endif

                            <div class="tich-form-row">
                                <label class="tich-label" style="display: inline-flex; align-items: center; gap: 0.5rem; font-weight: normal; cursor: pointer;">
                                    <input type="checkbox" name="is_off_campus" value="1" id="is_off_campus" style="width: auto; height: auto; margin: 0;">
                                    Off campus (not at {{ $campusGeofence['name'] }})
                                </label>
                                <p class="tich-caption tich-mt-2" style="margin-bottom: 0;">
                                    Leave unchecked when you are at the main campus (GPS check). Turn on when clocking in from another campus, a community college, or another site.
                                </p>
                            </div>
                            <div id="off-campus-site-panel" hidden>
                                <label for="clock_in_campus_id" class="tich-label">Campus / community college <span class="tich-required">*</span></label>
                                <select id="clock_in_campus_id" name="clock_in_campus_id" class="tich-input">
                                    <option value="">Select where you are reporting from</option>
                                    @php
                                        $campusSites = collect($clockInSites ?? [])->where('group', 'campus');
                                        $ccSites = collect($clockInSites ?? [])->where('group', 'community_college');
                                        $otherSite = collect($clockInSites ?? [])->firstWhere('group', 'other');
                                    @endphp
                                    @if ($campusSites->isNotEmpty())
                                        <optgroup label="Campuses">
                                            @foreach ($campusSites as $site)
                                                <option value="{{ $site['id'] }}" @selected(old('clock_in_campus_id') == $site['id'])>{{ $site['label'] }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                    @if ($ccSites->isNotEmpty())
                                        <optgroup label="Community colleges">
                                            @foreach ($ccSites as $site)
                                                <option value="{{ $site['id'] }}" @selected(old('clock_in_campus_id') == $site['id'])>{{ $site['label'] }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                    @if ($otherSite)
                                        <option value="{{ $otherSite['id'] }}" @selected(old('clock_in_campus_id') === $otherSite['id'])>{{ $otherSite['label'] }}</option>
                                    @endif
                                </select>
                                @error('clock_in_campus_id')<p class="tich-field-error">{{ $message }}</p>@enderror
                                <div id="clock-in-campus-other-wrap" class="tich-mt-4" hidden>
                                    <label for="clock_in_campus_other" class="tich-label">Other location <span class="tich-required">*</span></label>
                                    <input type="text" id="clock_in_campus_other" name="clock_in_campus_other" class="tich-input" maxlength="300" value="{{ old('clock_in_campus_other') }}" placeholder="Enter site name">
                                    @error('clock_in_campus_other')<p class="tich-field-error">{{ $message }}</p>@enderror
                                </div>
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
                var offCampusSitePanel = document.getElementById('off-campus-site-panel');
                var campusSelect = document.getElementById('clock_in_campus_id');
                var otherWrap = document.getElementById('clock-in-campus-other-wrap');
                var otherInput = document.getElementById('clock_in_campus_other');
                var submitButton = document.getElementById('clock-in-submit');
                var otherCampusValue = @json(\App\Services\StaffClockInSiteService::OTHER_VALUE);
                var refreshButton = document.getElementById('clock-in-refresh-location');
                var statusEl = document.getElementById('clock-in-location-status');
                var latInput = document.getElementById('clock_in_latitude');
                var lngInput = document.getElementById('clock_in_longitude');
                var accuracyInput = document.getElementById('clock_in_accuracy_m');
                var legacyInput = document.getElementById('location_lat_long');
                var campusLat = @json($campusGeofence['latitude']);
                var campusLng = @json($campusGeofence['longitude']);
                var campusRadius = @json($campusGeofence['radius_meters']);
                var campusName = @json($campusGeofence['name']);
                var maxAccuracy = @json($maxLocationAccuracy ?? 2000);
                var allowRemoteClockIn = {{ ($needsLocationVerification ?? false) ? 'true' : 'false' }};
                var detecting = false;
                var locationReady = false;

                console.log('[ClockIn] Script loaded, form found:', !!form);

                function setLocationReady(ready) {
                    locationReady = ready;
                    console.log('[ClockIn] Location ready:', ready);
                    if (!submitButton) {
                        return;
                    }

                    submitButton.setAttribute('aria-disabled', ready ? 'false' : 'true');
                    submitButton.style.opacity = ready ? '' : '0.55';
                    submitButton.style.cursor = ready ? '' : 'not-allowed';
                }

                function syncSitePanels() {
                    var off = offCampus && offCampus.checked;
                    if (offCampusSitePanel) {
                        offCampusSitePanel.hidden = !off;
                    }
                    if (campusSelect) {
                        campusSelect.disabled = !off;
                        campusSelect.required = off;
                    }
                    if (otherWrap && campusSelect) {
                        var showOther = off && campusSelect.value === otherCampusValue;
                        otherWrap.hidden = !showOther;
                        if (otherInput) {
                            otherInput.required = showOther;
                        }
                    }
                    updateStatus();
                }

                if (offCampus) {
                    offCampus.addEventListener('change', syncSitePanels);
                }
                if (campusSelect) {
                    campusSelect.addEventListener('change', syncSitePanels);
                }
                syncSitePanels();

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

                function updateStatus() {
                    console.log('[ClockIn] updateStatus called, detecting:', detecting, 'lat:', latInput.value, 'lng:', lngInput.value);
                    if (!statusEl || !submitButton) {
                        return;
                    }

                    if (detecting) {
                        statusEl.textContent = 'Detecting your location…';
                        setLocationReady(false);
                        return;
                    }

                    if (!latInput.value || !lngInput.value) {
                        statusEl.textContent = 'Location is required to clock in. Click "Detect My Location" below.';
                        setLocationReady(false);
                        return;
                    }

                    var lat = parseFloat(latInput.value);
                    var lng = parseFloat(lngInput.value);
                    var accuracy = accuracyInput.value ? parseFloat(accuracyInput.value) : null;
                    var distance = Math.round(distanceMeters(lat, lng, campusLat, campusLng));
                    var offCampusChecked = offCampus && offCampus.checked;

                    if (!accuracyIsAcceptable(accuracy)) {
                        statusEl.textContent = 'Location accuracy is ±' + Math.round(accuracy) + ' m. Move to an open area and retry.';
                        setLocationReady(false);
                        return;
                    }

                    if (offCampusChecked || allowRemoteClockIn) {
                        var prefix = allowRemoteClockIn
                            ? 'Location captured for re-verification'
                            : 'Off-campus location captured';
                        statusEl.textContent = prefix + ' (±' + (accuracy ? Math.round(accuracy) : '?') + ' m, ' + distance + ' m from ' + campusName + '). You may clock in.';
                        setLocationReady(true);
                        return;
                    }

                    if (distance <= campusRadius) {
                        statusEl.textContent = 'On campus verified (±' + (accuracy ? Math.round(accuracy) : '?') + ' m, ' + distance + ' m from ' + campusName + '). You may clock in.';
                        setLocationReady(true);
                        return;
                    }

                    statusEl.textContent = 'You appear to be ' + distance + ' m from ' + campusName + '. Turn on "Off campus" and select where you are clocking in from.';
                    setLocationReady(false);
                }

                function setLocation(position) {
                    detecting = false;
                    var lat = position.coords.latitude;
                    var lng = position.coords.longitude;
                    var accuracy = position.coords.accuracy;

                    console.log('[ClockIn] Location captured:', lat, lng, 'accuracy:', accuracy);

                    latInput.value = lat;
                    lngInput.value = lng;
                    accuracyInput.value = accuracy ?? '';
                    legacyInput.value = lat + ',' + lng;

                    var distance = Math.round(distanceMeters(lat, lng, campusLat, campusLng));

                    if (distance > campusRadius && offCampus && !offCampus.checked) {
                        offCampus.checked = true;
                        syncSitePanels();
                    }

                    updateStatus();
                }

                function locationError(message) {
                    detecting = false;
                    if (statusEl) {
                        statusEl.textContent = message;
                    }
                    setLocationReady(false);
                }

                function requestLocation() {
                    console.log('[ClockIn] requestLocation called');
                    if (!navigator.geolocation) {
                        console.log('[ClockIn] Geolocation not supported');
                        locationError('This browser does not support location services. Use a device with GPS to clock in.');
                        return;
                    }

                    if (!window.isSecureContext) {
                        console.log('[ClockIn] Not secure context:', window.location.protocol);
                        locationError('Location services require a secure connection (HTTPS) or localhost.');
                        return;
                    }

                    detecting = true;
                    updateStatus();
                    console.log('[ClockIn] Calling getCurrentPosition...');

                    try {
                        navigator.geolocation.getCurrentPosition(setLocation, function (error) {
                            console.log('[ClockIn] Geolocation error:', error.code, error.message);
                            detecting = false;

                            switch (error.code) {
                                case error.PERMISSION_DENIED:
                                    locationError('Location permission denied. Click the lock icon in your address bar, set Location to Allow, then refresh.');
                                    break;

                                case error.POSITION_UNAVAILABLE:
                                    locationError('Location signal unavailable. Check GPS/location services and try again.');
                                    break;

                                case error.TIMEOUT:
                                    locationError('Location request timed out. Move to an open area and try again.');
                                    break;

                                default:
                                    locationError('Could not detect your location. Check GPS/location services and click Refresh location.');
                            }
                        }, {
                            enableHighAccuracy: true,
                            timeout: 30000,
                            maximumAge: 0,
                        });
                    } catch (e) {
                        locationError('Location services could not be started. Check browser permissions and try again.');
                        detecting = false;
                    }
                }

                if (refreshButton) {
                    refreshButton.addEventListener('click', function () {
                        requestLocation();
                    });
                }

                function siteSelectionValid() {
                    var off = offCampus && offCampus.checked;
                    if (!off) {
                        return '';
                    }
                    if (!campusSelect || !campusSelect.value) {
                        return 'Select the campus or community college you are clocking in from.';
                    }
                    if (campusSelect.value === otherCampusValue) {
                        if (!otherInput || !otherInput.value.trim()) {
                            return 'Enter where you are reporting from when you select Other.';
                        }
                    }
                    return '';
                }

                form.addEventListener('submit', function (event) {
                    console.log('[ClockIn] Form submit, offCampus:', !!offCampus && offCampus.checked, 'lat:', latInput.value, 'lng:', lngInput.value, 'ready:', locationReady);
                    var siteError = siteSelectionValid();
                    if (siteError) {
                        event.preventDefault();
                        if (statusEl) {
                            statusEl.textContent = siteError;
                        }
                        return;
                    }

                    if (!navigator.geolocation) {
                        event.preventDefault();
                        locationError('Location services are required to clock in.');
                        return;
                    }

                    var offCampusChecked = offCampus && offCampus.checked;

                    if (offCampusChecked) {
                        return;
                    }

                    if (latInput.value && lngInput.value && locationReady) {
                        return;
                    }

                    event.preventDefault();

                    if (!latInput.value || !lngInput.value) {
                        locationError('Location is required. Click "Detect My Location" first.');
                        return;
                    }

                    if (!locationReady) {
                        locationError('Your current location does not meet the clock-in rules shown above.');
                    }
                });
            })();
        </script>
    @else
        <script>
            (function () {
                var offCampus = document.getElementById('is_off_campus');
                var offCampusSitePanel = document.getElementById('off-campus-site-panel');
                var campusSelect = document.getElementById('clock_in_campus_id');
                var otherWrap = document.getElementById('clock-in-campus-other-wrap');
                var otherInput = document.getElementById('clock_in_campus_other');
                var otherCampusValue = @json(\App\Services\StaffClockInSiteService::OTHER_VALUE);

                function syncSitePanels() {
                    var off = offCampus && offCampus.checked;
                    if (offCampusSitePanel) {
                        offCampusSitePanel.hidden = !off;
                    }
                    if (campusSelect) {
                        campusSelect.disabled = !off;
                        campusSelect.required = off;
                    }
                    if (otherWrap && campusSelect) {
                        var showOther = off && campusSelect.value === otherCampusValue;
                        otherWrap.hidden = !showOther;
                        if (otherInput) {
                            otherInput.required = showOther;
                        }
                    }
                }

                if (offCampus) {
                    offCampus.addEventListener('change', syncSitePanels);
                }
                if (campusSelect) {
                    campusSelect.addEventListener('change', syncSitePanels);
                }
                syncSitePanels();

                var form = document.getElementById('clock-in-form');
                if (form) {
                    form.addEventListener('submit', function (event) {
                        var off = offCampus && offCampus.checked;
                        if (!off) {
                            return;
                        }
                        if (!campusSelect || !campusSelect.value) {
                            event.preventDefault();
                            alert('Select the campus or community college you are clocking in from.');
                            return;
                        }
                        if (campusSelect.value === otherCampusValue && (!otherInput || !otherInput.value.trim())) {
                            event.preventDefault();
                            alert('Enter where you are reporting from when you select Other.');
                        }
                    });
                }
            })();
        </script>
    @endif
@endsection
