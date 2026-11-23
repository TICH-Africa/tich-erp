@extends('layouts.employee')

@section('title', 'Weekly Time Sheet')

@section('employee-content')
    <x-page-toolbar title="Weekly Time Sheet" meta="Fill weekly, or complete every week at month end · Submitted to HR">
        @unless ($employeeTimeLogHold ?? null)
            <x-slot:actions>
                <a href="{{ route('employee.dashboard') }}" class="tich-btn tich-btn-ghost">Back</a>
            </x-slot:actions>
        @endunless
    </x-page-toolbar>

    @if ($employeeTimeLogHold ?? null)
        <div class="tich-alert tich-alert--warning tich-mt-4">
            <p>Complete and submit your weekly time sheets for <strong>{{ $employeeTimeLogHold['month_label'] }}</strong> before using other employee portal sections.</p>
            @if (! empty($employeeTimeLogHold['incomplete_weeks']))
                <p class="tich-mt-2" style="margin-bottom:0;">Still required:</p>
                <ul class="wtl-hold-weeks tich-mt-2">
                    @foreach ($employeeTimeLogHold['incomplete_weeks'] as $openWeek)
                        <li>
                            <span class="tich-badge tich-badge--warning">{{ $openWeek['label'] }} · {{ $openWeek['week_ref'] }}</span>
                            <span class="tich-caption"> - open below and submit</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif
    @if (session('warning'))
        <div class="tich-alert tich-alert--warning tich-mt-4">{{ session('warning') }}</div>
    @endif

    @if (session('status'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('status') }}</div>
    @endif
    @error('time_log')
        <div class="tich-alert tich-alert--error tich-mt-4">{{ $message }}</div>
    @enderror

    <div class="tich-card tich-mt-6">
        <h2 class="tich-h3">Open / start a week</h2>
        <p class="tich-caption tich-mt-1">Month and week default to today. Change them to fill past or remaining weeks.</p>
        <form method="POST" action="{{ route('employee.time-logs.create') }}" class="tich-form-stack tich-mt-4" id="wtl-start-form">
            @csrf
            <div class="tich-grid tich-grid--3" style="gap:1rem; align-items:end;">
                <div class="tich-form-group" style="margin:0;">
                    <label class="tich-label" for="wtl-year">Year</label>
                    <input type="number" id="wtl-year" name="year" class="tich-input" value="{{ $currentYear }}" min="2020" max="2100" required>
                </div>
                <div class="tich-form-group" style="margin:0;">
                    <label class="tich-label" for="wtl-month">Month</label>
                    <select id="wtl-month" name="month" class="tich-input" required>
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" @selected((int) $currentMonth === $m)>{{ \Carbon\Carbon::create(null, $m, 1)->format('F') }}</option>
                        @endfor
                    </select>
                </div>
                <div class="tich-form-group" style="margin:0;">
                    <label class="tich-label" for="wtl-week">Week</label>
                    <select id="wtl-week" name="week_number" class="tich-input" required>
                        @foreach ($weeks as $week)
                            <option
                                value="{{ $week['week_number'] }}"
                                @selected((int) $currentWeek === (int) $week['week_number'])
                                @if (! empty($week['needs_attention'])) data-needs-attention="1" @endif
                            >
                                {{ $week['label'] }} · {{ $week['week_ref'] }}@if (! empty($week['needs_attention'])) · Submit required @endif
                            </option>
                        @endforeach
                    </select>
                    <p id="wtl-week-hint" class="tich-caption tich-mt-1 @if (! ($employeeTimeLogHold ?? null)) hidden @endif">
                        Weeks marked <strong>Submit required</strong> are still locking the portal.
                    </p>
                </div>
            </div>
            <button type="submit" class="tich-btn tich-btn-primary tich-mt-4">Open week</button>
        </form>
    </div>

    <div class="tich-card tich-table-panel tich-mt-8">
        <h2 class="tich-h3">My time sheets</h2>
        <div class="tich-table-wrap tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Period</th>
                        <th>Hours</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $item)
                        <tr>
                            <td><strong>{{ $item->log_code }}</strong><p class="tich-caption">{{ $item->week_ref }}</p></td>
                            <td>{{ $item->monthLabel() }} · Week {{ $item->week_number }}</td>
                            <td>{{ number_format((float) $item->total_hours, 2) }}</td>
                            <td><x-status-badge :status="$item->status" /></td>
                            <td class="tich-caption">{{ $item->employee_signed_at?->format('d M Y') ?? '-' }}</td>
                            <td><a href="{{ route('employee.time-logs.show', $item) }}" class="tich-btn tich-btn-secondary" style="padding:0.35rem 0.6rem;font-size:0.85rem;">Open</a></td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', ['colspan' => 6, 'title' => 'No time sheets yet', 'icon' => 'inbox'])
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($logs->hasPages())
            <div class="tich-mt-4">{{ $logs->links() }}</div>
        @endif
    </div>

    <style>
        .wtl-hold-weeks {
            margin: 0;
            padding-left: 1.1rem;
            list-style: disc;
        }

        .wtl-hold-weeks li + li {
            margin-top: 0.35rem;
        }

        #wtl-week option[data-needs-attention="1"] {
            font-weight: 700;
            color: #b45309;
        }

        .tich-dropdown__option.wtl-dropdown-option--required {
            background: #fff3e0;
            color: #9a3412;
            font-weight: 600;
        }

        .tich-dropdown__option.wtl-dropdown-option--required:hover,
        .tich-dropdown__option.wtl-dropdown-option--required:focus-visible {
            background: #ffe0b2;
            color: #7c2d12;
        }
    </style>

    <script>
    (function () {
        var yearEl = document.getElementById('wtl-year');
        var monthEl = document.getElementById('wtl-month');
        var weekEl = document.getElementById('wtl-week');
        var hintEl = document.getElementById('wtl-week-hint');
        var hold = @json($employeeTimeLogHold ?? null);
        var url = @json(route('employee.time-logs.weeks'));

        function reloadWeeks() {
            fetch(url + '?year=' + encodeURIComponent(yearEl.value) + '&month=' + encodeURIComponent(monthEl.value), {
                headers: { 'Accept': 'application/json' }
            }).then(function (r) { return r.json(); }).then(function (data) {
                var previous = weekEl.value;
                weekEl.innerHTML = '';
                var firstRequired = null;
                var anyRequired = false;
                (data.weeks || []).forEach(function (w) {
                    var opt = document.createElement('option');
                    opt.value = w.week_number;
                    opt.textContent = w.label + ' · ' + w.week_ref + (w.needs_attention ? ' · Submit required' : '');
                    if (w.needs_attention) {
                        opt.dataset.needsAttention = '1';
                        anyRequired = true;
                        if (firstRequired === null) {
                            firstRequired = w.week_number;
                        }
                    }
                    weekEl.appendChild(opt);
                });

                if (hintEl) {
                    hintEl.classList.toggle('hidden', !anyRequired);
                }

                var holdMatchesMonth = hold
                    && Number(hold.year) === Number(yearEl.value)
                    && Number(hold.month) === Number(monthEl.value);

                if (holdMatchesMonth && firstRequired !== null) {
                    weekEl.value = String(firstRequired);
                } else if (previous && weekEl.querySelector('option[value="' + previous + '"]')) {
                    weekEl.value = previous;
                }

                if (weekEl.tichDropdownWrapper && typeof weekEl.tichDropdownLabel !== 'undefined') {
                    weekEl.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }).catch(function () {});
        }

        yearEl.addEventListener('change', reloadWeeks);
        monthEl.addEventListener('change', reloadWeeks);
    })();
    </script>
@endsection
