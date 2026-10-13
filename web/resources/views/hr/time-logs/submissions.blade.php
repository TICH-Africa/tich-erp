@extends('layouts.hr')

@section('title', 'Time log submissions by employee')

@section('hr-content')
    @php
        $year = (int) $filters['year'];
        $month = (int) $filters['month'];
        $monthLabel = \Carbon\Carbon::create($year, $month, 1)->format('F Y');
        $weekCount = count($matrix['weeks']);
        $prev = \Carbon\Carbon::create($year, $month, 1)->subMonth();
        $next = \Carbon\Carbon::create($year, $month, 1)->addMonth();
    @endphp

    <x-page-toolbar title="Submissions per employee" meta="Weekly submission status for {{ $monthLabel }}">
        <x-slot:actions>
            <a
                href="{{ route('hr.time-logs.submissions', ['year' => $prev->year, 'month' => $prev->month, 'search' => $filters['search'] ?? null]) }}"
                class="tich-btn tich-btn-ghost"
            >← Prev month</a>
            <a
                href="{{ route('hr.time-logs.submissions', ['year' => $next->year, 'month' => $next->month, 'search' => $filters['search'] ?? null]) }}"
                class="tich-btn tich-btn-ghost"
            >Next month →</a>
            <a href="{{ route('hr.time-logs.index') }}" class="tich-btn tich-btn-ghost">Back to time logs</a>
        </x-slot:actions>
        <x-slot:filters>
            <form method="GET" action="{{ route('hr.time-logs.submissions') }}" class="tich-page-toolbar__filters-form tich-time-log-toolbar-filters" data-live-filter>
                @include('partials.search-field', ['placeholder' => 'Name, employee no.…', 'value' => $filters['search'] ?? ''])
                <input
                    id="sub-year"
                    type="number"
                    name="year"
                    class="tich-input tich-input--compact tich-time-log-toolbar-filters__year"
                    value="{{ $year }}"
                    min="2020"
                    max="2100"
                    required
                    title="Year"
                    aria-label="Year"
                >
                <select id="sub-month" name="month" class="tich-input tich-input--compact" required title="Month" aria-label="Month">
                    @for ($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" @selected($month === $m)>{{ \Carbon\Carbon::create(null, $m, 1)->format('F') }}</option>
                    @endfor
                </select>
            </form>
        </x-slot:filters>
    </x-page-toolbar>

    <div class="tich-time-log-legend">
        <span class="tich-time-log-mark tich-time-log-mark--yes" title="Submitted">✓</span>
        <span class="tich-caption">Submitted</span>
        <span class="tich-time-log-mark tich-time-log-mark--no" title="Not submitted">–</span>
        <span class="tich-caption">Not submitted</span>
    </div>

    <div class="tich-card tich-table-panel tich-mt-3">
        <div class="tich-table-wrap">
            <table class="tich-admin-table tich-time-log-matrix">
                <thead>
                    <tr>
                        <th>Employee</th>
                        @foreach ($matrix['weeks'] as $week)
                            <th class="tich-time-log-matrix__week">
                                Week {{ $week['week_number'] }}
                                <div class="tich-caption">
                                    {{ \Carbon\Carbon::parse($week['period_start'])->format('d M') }}
                                    –
                                    {{ \Carbon\Carbon::parse($week['period_end'])->format('d M') }}
                                </div>
                            </th>
                        @endforeach
                        <th>Progress</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($matrix['rows'] as $row)
                        <tr>
                            <td>
                                <strong>{{ $row['staff']->fullName() }}</strong>
                                <p class="tich-caption">{{ $row['staff']->employee_number }}</p>
                            </td>
                            @foreach ($matrix['weeks'] as $week)
                                @php
                                    $cell = $row['weeks'][$week['week_number']] ?? null;
                                    $submitted = (bool) ($cell['submitted'] ?? false);
                                    $log = $cell['log'] ?? null;
                                @endphp
                                <td class="tich-time-log-matrix__cell">
                                    @if ($submitted && $log)
                                        <a
                                            href="{{ route('hr.time-logs.show', $log) }}"
                                            class="tich-time-log-mark tich-time-log-mark--yes"
                                            title="Submitted · {{ $cell['status'] ?? '' }}"
                                        >✓</a>
                                    @else
                                        <span class="tich-time-log-mark tich-time-log-mark--no" title="Not submitted">–</span>
                                    @endif
                                </td>
                            @endforeach
                            <td>
                                <span class="tich-caption">{{ $row['submitted_count'] }}/{{ $weekCount }}</span>
                            </td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', [
                            'colspan' => $weekCount + 2,
                            'title' => 'No employees found',
                            'icon' => 'inbox',
                        ])
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
