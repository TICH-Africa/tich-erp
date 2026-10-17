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
        @if ($canManageGazetteHolidays)
            <strong class="hr-year-calendar__hint">Hover a date and click to add a gazetted holiday announced on short notice.</strong>
        @endif
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
                        <span class="hr-year-calendar__cell hr-year-calendar__cell--empty" aria-hidden="true"></span>
                    @endfor
                    @for ($day = 1; $day <= $daysInMonth; $day++)
                        @php
                            $dateStr = $monthStart->copy()->day($day)->toDateString();
                            $holiday = $holidayByDate->get($dateStr);
                            $source = $holiday->source ?? null;
                            $isGazette = $source === 'gazette';
                        @endphp
                        @if ($canManageGazetteHolidays)
                            <button
                                type="button"
                                class="hr-year-calendar__cell hr-year-calendar__cell--interactive @if ($holiday) hr-year-calendar__cell--holiday @endif @if ($isGazette) hr-year-calendar__cell--gazette @endif"
                                data-calendar-day
                                data-date="{{ $dateStr }}"
                                data-holiday-name="{{ $holiday?->holiday_name ?? '' }}"
                                data-holiday-source="{{ $source ?? '' }}"
                                title="Add or manage gazetted holiday on {{ \Carbon\Carbon::parse($dateStr)->format('j M Y') }}"
                            >
                                <span class="hr-year-calendar__day">{{ $day }}</span>
                                <span class="hr-year-calendar__holiday" @if ($holiday) title="{{ $holiday->holiday_name }}" @endif>@if ($holiday){{ $holiday->holiday_name }}@endif</span>
                            </button>
                        @else
                            <div @class([
                                'hr-year-calendar__cell',
                                'hr-year-calendar__cell--holiday' => $holiday !== null,
                                'hr-year-calendar__cell--gazette' => $isGazette,
                            ])>
                                <span class="hr-year-calendar__day">{{ $day }}</span>
                                <span class="hr-year-calendar__holiday" @if ($holiday) title="{{ $holiday->holiday_name }}" @endif>@if ($holiday){{ $holiday->holiday_name }}@endif</span>
                            </div>
                        @endif
                    @endfor
                </div>

                @if ($monthHolidays->isNotEmpty())
                    <ul class="hr-year-calendar__list tich-mt-4">
                        @foreach ($monthHolidays as $holiday)
                            <li>
                                <strong>{{ \Carbon\Carbon::parse($holiday->holiday_date)->format('d M') }}</strong>
                                - {{ $holiday->holiday_name }}
                                @if (($holiday->source ?? '') === 'gazette')
                                    <span class="hr-year-calendar__badge">Gazette</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </article>
        @endfor
    </div>

    @if ($canManageGazetteHolidays)
        <div id="hr-gazette-holiday-modal" class="tich-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="hr-gazette-holiday-modal-title">
            <div class="tich-modal__backdrop" data-close-modal="hr-gazette-holiday-modal"></div>
            <div class="tich-modal__dialog">
                <header class="tich-modal__header">
                    <h2 id="hr-gazette-holiday-modal-title" class="tich-h3">Gazetted public holiday</h2>
                    <button type="button" class="tich-modal__close" data-close-modal="hr-gazette-holiday-modal" aria-label="Close">&times;</button>
                </header>
                <div class="tich-modal__body">
                    <p class="tich-caption tich-mb-4" id="hr-gazette-holiday-modal-date"></p>
                    <p class="tich-caption tich-mb-4" id="hr-gazette-holiday-modal-synced-note" hidden></p>

                    <form method="POST" action="{{ route('hr.calendar.gazette-holidays.store') }}" id="hr-gazette-holiday-form">
                        @csrf
                        <input type="hidden" name="year" value="{{ $year }}">
                        <input type="hidden" name="holiday_date" id="hr-gazette-holiday-date-input" value="{{ old('holiday_date') }}">

                        <div class="tich-form-group tich-mb-4">
                            <label for="hr-gazette-holiday-name" class="tich-label">Holiday name</label>
                            <input
                                type="text"
                                id="hr-gazette-holiday-name"
                                name="holiday_name"
                                class="tich-input"
                                maxlength="200"
                                required
                                value="{{ old('holiday_name') }}"
                                placeholder="e.g. Public Holiday (Election Day)"
                            >
                            @error('holiday_name')
                                <p class="tich-form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="tich-form-group tich-mb-4">
                            <label for="hr-gazette-holiday-description" class="tich-label">Notes <span class="tich-caption">(optional)</span></label>
                            <textarea
                                id="hr-gazette-holiday-description"
                                name="description"
                                class="tich-input"
                                rows="2"
                                maxlength="1000"
                                placeholder="Gazette reference or announcement note"
                            >{{ old('description') }}</textarea>
                            @error('description')
                                <p class="tich-form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        @error('holiday_date')
                            <p class="tich-form-error tich-mb-4">{{ $message }}</p>
                        @enderror

                        <footer class="tich-modal__footer hr-year-calendar__modal-footer">
                            <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="hr-gazette-holiday-modal">Cancel</button>
                            <button type="submit" class="tich-btn tich-btn-primary">Save gazetted holiday</button>
                        </footer>
                    </form>

                    <form method="POST" action="{{ route('hr.calendar.gazette-holidays.destroy') }}" id="hr-gazette-holiday-delete-form" class="tich-mt-4" hidden onsubmit="return confirm('Remove this gazetted holiday? Leave calculations will no longer treat this date as a public holiday.');">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="year" value="{{ $year }}">
                        <input type="hidden" name="holiday_date" id="hr-gazette-holiday-delete-date">
                        <button type="submit" class="tich-btn tich-btn-ghost tich-btn--danger">Remove gazetted holiday</button>
                    </form>
                </div>
            </div>
        </div>

        @include('admin.partials.tich-modal-assets')

        <script>
            (function () {
                var modalId = 'hr-gazette-holiday-modal';
                var dateLabel = document.getElementById('hr-gazette-holiday-modal-date');
                var syncedNote = document.getElementById('hr-gazette-holiday-modal-synced-note');
                var dateInput = document.getElementById('hr-gazette-holiday-date-input');
                var nameInput = document.getElementById('hr-gazette-holiday-name');
                var deleteForm = document.getElementById('hr-gazette-holiday-delete-form');
                var deleteDateInput = document.getElementById('hr-gazette-holiday-delete-date');

                function formatDateLabel(isoDate) {
                    var parts = isoDate.split('-');
                    if (parts.length !== 3) {
                        return isoDate;
                    }
                    var d = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
                    return d.toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
                }

                document.querySelectorAll('[data-calendar-day]').forEach(function (cell) {
                    cell.addEventListener('click', function () {
                        var isoDate = cell.getAttribute('data-date') || '';
                        var holidayName = cell.getAttribute('data-holiday-name') || '';
                        var source = cell.getAttribute('data-holiday-source') || '';

                        dateInput.value = isoDate;
                        deleteDateInput.value = isoDate;
                        dateLabel.textContent = formatDateLabel(isoDate);

                        if (source && source !== 'gazette' && holidayName) {
                            syncedNote.hidden = false;
                            syncedNote.textContent = 'This date already has a synced holiday ("' + holidayName + '"). Saving will replace it with your gazetted entry.';
                            nameInput.value = holidayName;
                        } else {
                            syncedNote.hidden = true;
                            syncedNote.textContent = '';
                            nameInput.value = source === 'gazette' ? holidayName : '';
                        }

                        deleteForm.hidden = source !== 'gazette';

                        if (window.tichOpenModal) {
                            window.tichOpenModal(modalId);
                        }
                        nameInput.focus();
                    });
                });

                @if ($errors->any() && old('holiday_date'))
                    if (window.tichOpenModal) {
                        window.tichOpenModal(modalId);
                    }
                @endif
            })();
        </script>
    @endif

    <style>
        .hr-year-calendar {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1.25rem;
        }
        @media (max-width: 1280px) {
            .hr-year-calendar {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (max-width: 640px) {
            .hr-year-calendar {
                grid-template-columns: 1fr;
            }
        }
        .hr-year-calendar__month {
            padding: 1rem 1.25rem;
        }
        .hr-year-calendar__weekdays,
        .hr-year-calendar__grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 0.25rem;
        }
        .hr-year-calendar__grid {
            grid-auto-rows: 4.25rem;
        }
        .hr-year-calendar__weekdays {
            margin-top: 0.75rem;
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--tich-muted, #64748b);
            text-align: center;
        }
        .hr-year-calendar__cell {
            box-sizing: border-box;
            height: 100%;
            min-height: 4.25rem;
            border: 1px solid var(--tich-border, #e2e8f0);
            border-radius: 6px;
            padding: 0.2rem 0.25rem;
            font-size: 0.75rem;
            background: var(--tich-white, #fff);
            text-align: left;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .hr-year-calendar__cell--interactive {
            cursor: pointer;
            font: inherit;
            color: inherit;
            width: 100%;
            transition: background-color 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .hr-year-calendar__cell--interactive:hover,
        .hr-year-calendar__cell--interactive:focus-visible {
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25);
            outline: none;
        }
        .hr-year-calendar__cell--empty {
            border: none;
            background: transparent;
            min-height: 4.25rem;
            visibility: hidden;
        }
        .hr-year-calendar__cell--holiday {
            background: #fef3c7;
            border-color: #f59e0b;
        }
        .hr-year-calendar__cell--gazette {
            background: #ecfdf5;
            border-color: #10b981;
        }
        .hr-year-calendar__cell--gazette.hr-year-calendar__cell--holiday {
            background: #ecfdf5;
            border-color: #059669;
        }
        .hr-year-calendar__day {
            flex-shrink: 0;
            font-weight: 600;
            font-size: 0.8rem;
            line-height: 1.2;
        }
        .hr-year-calendar__holiday {
            flex: 1 1 auto;
            margin-top: 0.15rem;
            font-size: 0.62rem;
            line-height: 1.25;
            color: #92400e;
            overflow: hidden;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 3;
            word-break: break-word;
        }
        .hr-year-calendar__cell--gazette .hr-year-calendar__holiday {
            color: #047857;
        }
        .hr-year-calendar__cell:not(.hr-year-calendar__cell--holiday):not(.hr-year-calendar__cell--gazette) .hr-year-calendar__holiday {
            color: transparent;
            user-select: none;
        }
        .hr-year-calendar__list {
            margin: 0;
            padding-left: 1.1rem;
            font-size: 0.875rem;
        }
        .hr-year-calendar__badge {
            display: inline-block;
            margin-left: 0.35rem;
            padding: 0.05rem 0.4rem;
            font-size: 0.65rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            color: #047857;
            background: #d1fae5;
            border-radius: 4px;
        }
        .hr-year-calendar__hint {
            display: block;
            margin-top: 0.35rem;
            color: var(--tich-text, #0f172a);
        }
        .hr-year-calendar__modal-footer {
            margin-top: 0.5rem;
            padding: 0;
            border: none;
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
        }
    </style>
@endsection
