@extends('layouts.hr')

@section('title', 'Calendar')

@section('hr-content')
    <x-page-toolbar title="Calendar" meta="Kenyan national public holidays by month">
        <x-slot:filters>
            <form method="GET" action="{{ route('hr.calendar.index') }}" class="tich-page-toolbar__filters-form">
                <label for="calendar-year" class="tich-caption">Year</label>
                <select id="calendar-year" name="year" class="tich-input" onchange="this.form.submit()">
                    @foreach ($yearOptions as $optionYear)
                        <option value="{{ $optionYear }}" @selected((int) $optionYear === (int) $year)>{{ $optionYear }}</option>
                    @endforeach
                </select>
            </form>
        </x-slot:filters>
    </x-page-toolbar>

    <p class="tich-caption tich-mt-4">
        Public holidays for {{ $year }} (synced from Nager.Date and Aladhan for Kenya, including Good Friday, Easter Monday, and Idd dates).
        Used for leave working-day calculations. Official Islamic dates may follow moon-sighting announcements.
    </p>

    <div class="hr-year-calendar tich-mt-8">
        @for ($month = 1; $month <= 12; $month++)
            @php
                $monthStart = \Carbon\Carbon::createFromDate($year, $month, 1);
                $daysInMonth = $monthStart->daysInMonth;
                $monthHolidays = $months[$month] ?? collect();
                $holidayByDate = $monthHolidays->keyBy(fn ($h) => (string) $h->holiday_date);
                $leadingBlanks = ($monthStart->dayOfWeekIso - 1);
            @endphp
            <article class="tich-card hr-year-calendar__month">
                <h2 class="tich-h3">{{ $monthStart->format('F') }}</h2>

                <div class="hr-year-calendar__weekdays" aria-hidden="true">
                    @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $weekday)
                        <span>{{ $weekday }}</span>
                    @endforeach
                </div>

                <div class="hr-year-calendar__grid">
                    @for ($i = 0; $i < $leadingBlanks; $i++)
                        <span class="hr-year-calendar__cell hr-year-calendar__cell--empty"></span>
                    @endfor
                    @for ($day = 1; $day <= $daysInMonth; $day++)
                        @php
                            $dateStr = $monthStart->copy()->day($day)->toDateString();
                            $holiday = $holidayByDate->get($dateStr);
                        @endphp
                        <div @class([
                            'hr-year-calendar__cell',
                            'hr-year-calendar__cell--holiday' => $holiday !== null,
                        ])>
                            <span class="hr-year-calendar__day">{{ $day }}</span>
                            @if ($holiday)
                                <span class="hr-year-calendar__holiday" title="{{ $holiday->holiday_name }}">{{ $holiday->holiday_name }}</span>
                            @endif
                        </div>
                    @endfor
                </div>

                @if ($monthHolidays->isNotEmpty())
                    <ul class="hr-year-calendar__list tich-mt-4">
                        @foreach ($monthHolidays as $holiday)
                            <li>
                                <strong>{{ \Carbon\Carbon::parse($holiday->holiday_date)->format('d M') }}</strong>
                                — {{ $holiday->holiday_name }}
                            </li>
                        @endforeach
                    </ul>
                @endif
            </article>
        @endfor
    </div>

    <style>
        .hr-year-calendar {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.25rem;
        }
        .hr-year-calendar__month { padding: 1rem 1.25rem; }
        .hr-year-calendar__weekdays,
        .hr-year-calendar__grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.25rem;
        }
        .hr-year-calendar__weekdays {
            margin-top: 0.75rem;
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--tich-muted, #64748b);
            text-align: center;
        }
        .hr-year-calendar__cell {
            min-height: 3.25rem;
            border: 1px solid var(--tich-border, #e2e8f0);
            border-radius: 6px;
            padding: 0.2rem 0.25rem;
            font-size: 0.75rem;
            background: var(--tich-white, #fff);
        }
        .hr-year-calendar__cell--empty {
            border: none;
            background: transparent;
            min-height: 0;
        }
        .hr-year-calendar__cell--holiday {
            background: #fef3c7;
            border-color: #f59e0b;
        }
        .hr-year-calendar__day {
            display: block;
            font-weight: 600;
            font-size: 0.8rem;
        }
        .hr-year-calendar__holiday {
            display: block;
            margin-top: 0.15rem;
            font-size: 0.62rem;
            line-height: 1.2;
            color: #92400e;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .hr-year-calendar__list {
            margin: 0;
            padding-left: 1.1rem;
            font-size: 0.875rem;
        }
    </style>
@endsection
