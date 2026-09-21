@extends('layouts.employee')

@section('title', 'Weekly Time Log')

@section('employee-content')
    <x-page-toolbar title="Weekly Time Log" meta="Fill weekly, or complete every week at month end · Submitted to HR">
        <x-slot:actions>
            <a href="{{ route('employee.dashboard') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

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
                            <option value="{{ $week['week_number'] }}" @selected((int) $currentWeek === (int) $week['week_number'])>{{ $week['label'] }} · {{ $week['week_ref'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <button type="submit" class="tich-btn tich-btn-primary tich-mt-4">Open week</button>
        </form>
    </div>

    <div class="tich-card tich-table-panel tich-mt-8">
        <h2 class="tich-h3">My time logs</h2>
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
                            <td class="tich-caption">{{ $item->employee_signed_at?->format('d M Y') ?? '—' }}</td>
                            <td><a href="{{ route('employee.time-logs.show', $item) }}" class="tich-btn tich-btn-secondary" style="padding:0.35rem 0.6rem;font-size:0.85rem;">Open</a></td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', ['colspan' => 6, 'title' => 'No time logs yet', 'icon' => 'inbox'])
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($logs->hasPages())
            <div class="tich-mt-4">{{ $logs->links() }}</div>
        @endif
    </div>

    <script>
    (function () {
        var yearEl = document.getElementById('wtl-year');
        var monthEl = document.getElementById('wtl-month');
        var weekEl = document.getElementById('wtl-week');
        var url = @json(route('employee.time-logs.weeks'));
        function reloadWeeks() {
            fetch(url + '?year=' + encodeURIComponent(yearEl.value) + '&month=' + encodeURIComponent(monthEl.value), {
                headers: { 'Accept': 'application/json' }
            }).then(function (r) { return r.json(); }).then(function (data) {
                weekEl.innerHTML = '';
                (data.weeks || []).forEach(function (w) {
                    var opt = document.createElement('option');
                    opt.value = w.week_number;
                    opt.textContent = w.label + ' · ' + w.week_ref;
                    weekEl.appendChild(opt);
                });
            }).catch(function () {});
        }
        yearEl.addEventListener('change', reloadWeeks);
        monthEl.addEventListener('change', reloadWeeks);
    })();
    </script>
@endsection
