@php
    $activeDayList = $activeDays ?? range(1, 5);
    $editable = $editable ?? false;
    $canEditInvigilator = $canEditInvigilator ?? false;
    $moveSessionUrl = $moveSessionUrl ?? null;
    $bellRows = collect($segments ?? [])->reject(fn ($segment) => $segment->segment_type === 'break')->values();
    $rowStart = fn ($row): string => substr((string) $row->start_time, 0, 5);
    $toMinutes = fn ($time): int => (int) substr((string) $time, 0, 2) * 60 + (int) substr((string) $time, 3, 2);

    // A class added by hand seldom starts on the exact minute a bell slot opens, and forcing a
    // second row for the same hour left the timetable full of gaps. A class now sits on the bell
    // slot it overlaps most; only a break keeps its own row.
    $placement = collect($sessions ?? [])->mapWithKeys(function ($session) use ($bellRows, $rowStart, $toMinutes): array {
        $own = $rowStart($session);

        if ($bellRows->contains(fn ($row) => $rowStart($row) === $own)
            || in_array($session->session_type, ['break', 'lunch'], true)) {
            return [(int) $session->id => $own];
        }

        $start = $toMinutes($session->start_time);
        $end = max($start, $toMinutes($session->end_time));
        $best = null;
        $bestOverlap = 0;

        foreach ($bellRows as $row) {
            $rowStartMinutes = $toMinutes($row->start_time);
            $rowEndMinutes = max($rowStartMinutes, $toMinutes($row->end_time));
            $overlap = min($end, $rowEndMinutes) - max($start, $rowStartMinutes);

            if ($overlap > $bestOverlap) {
                $bestOverlap = $overlap;
                $best = $rowStart($row);
            }
        }

        return [(int) $session->id => $best ?? $own];
    });
    // A dated class is read by date, so each row names the dates it carries under the time.
    $datesByRowStart = collect($sessions ?? [])
        ->filter(fn ($session) => ! empty($session->session_date))
        ->groupBy(fn ($session) => $placement[(int) $session->id] ?? $rowStart($session))
        ->map(fn ($group) => $group
            ->map(fn ($session) => $session->session_date->format('d M'))
            ->unique()
            ->values());
    $sessionsByDayAndTime = collect($sessions ?? [])->groupBy(
        fn ($session) => $session->day_of_week.'|'.($placement[(int) $session->id] ?? $rowStart($session))
    );

    $rows = $bellRows;

    // A bell row that carries neither a time nor a class is noise on a hand-built timetable, so it
    // is dropped; rows that hold a class always stay.
    $rows = $rows->filter(function ($row) use ($sessionsByDayAndTime, $activeDayList, $rowStart) {
        $start = $rowStart($row);

        if ($start !== '' && $start !== '00:00') {
            return true;
        }

        foreach ($activeDayList as $dayNum) {
            if ($sessionsByDayAndTime->has($dayNum.'|'.$start)) {
                return true;
            }
        }

        return false;
    })->values();

    if ($rows->isEmpty()) {
        $rows = collect($sessions ?? [])->map(fn ($session) => (object) [
            'label' => $session->timeLabel(),
            'start_time' => $session->start_time,
            'end_time' => $session->end_time,
            'segment_type' => 'slot',
        ])->unique(fn ($row) => $row->start_time.'-'.$row->end_time)->sortBy('start_time')->values();
    } else {
        // A class timed outside the bell slots - an 08:00 start or a lecture that runs to 17:00 - is
        // still a class somebody has to see, so it gets its own row instead of vanishing.
        $rowStarts = $rows->map($rowStart);

        foreach ($sessions ?? [] as $session) {
            $start = $placement[(int) $session->id] ?? $rowStart($session);

            if ($rowStarts->contains($start)) {
                continue;
            }

            $rows->push((object) [
                'label' => $start.' - '.substr((string) $session->end_time, 0, 5),
                'start_time' => $start,
                'end_time' => $session->end_time,
                'segment_type' => 'slot',
                'id' => null,
            ]);
            $rowStarts->push($start);
        }

        $rows = $rows->sortBy(fn ($row) => substr((string) $row->start_time, 0, 5))->values();
    }
    $typeClasses = [
        'lesson' => 'is-lesson',
        'exam' => 'is-exam',
        'supplementary' => 'is-supplementary',
        'special_exam' => 'is-special-exam',
        'break' => 'is-break',
        // Lunch rest belongs to everybody at the same hour, so it reads like a break rather than
        // as an unclassified block.
        'lunch' => 'is-break',
        'other' => 'is-other',
    ];
@endphp

<div
    class="tich-timetable-grid-wrap tich-table-wrap tich-mt-4"
    @if ($editable && $moveSessionUrl)
        data-timetable-editable="1"
        data-move-url="{{ $moveSessionUrl }}"
    @endif
>
    @if ($editable)
        <p class="tich-caption tich-mb-3">
            Drag sessions to another day or time slot. Drop onto another session to swap.
            For anything else - the unit, lecturer, venue, mode, week or month - use <strong>Edit timetable</strong> below the grid.
        </p>
        <div class="tich-timetable-drag-status" hidden role="status"></div>
    @endif
    @if ($canEditInvigilator)
        <p class="tich-caption tich-mb-3">Use <strong>Invigilator</strong> on a slot to assign or change who supervises that exam.</p>
    @endif

    <table class="tich-timetable-grid">
        <thead>
            <tr>
                <th>Time</th>
                @foreach ($dayLabels as $dayNum => $dayLabel)
                    @if (in_array($dayNum, $activeDayList, true))
                        <th>{{ $dayLabel }}</th>
                    @endif
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $segment)
                @if ($segment->segment_type === 'break')
                    <tr class="tich-timetable-grid__break">
                        <td>{{ method_exists($segment, 'timeLabel') ? $segment->timeLabel() : ($segment->label ?? '') }}</td>
                        <td colspan="{{ count($activeDayList) }}">{{ $segment->label }}</td>
                    </tr>
                @else
                    <tr>
                        <td class="tich-timetable-grid__time">{{ $segment->label }}@if (($segment->segment_type ?? '') !== 'slot')<br><span class="tich-caption">{{ method_exists($segment, 'timeLabel') ? $segment->timeLabel() : (substr((string) $segment->start_time, 0, 5).' - '.substr((string) $segment->end_time, 0, 5)) }}</span>@endif
                            @php $rowDates = $datesByRowStart->get(substr((string) $segment->start_time, 0, 5), collect()); @endphp
                            @if ($rowDates->isNotEmpty())
                                <br><span class="tich-caption tich-timetable-grid__dates">{{ $rowDates->implode(' · ') }}</span>
                            @endif
                        </td>
                        @foreach ($dayLabels as $dayNum => $dayLabel)
                            @if (! in_array($dayNum, $activeDayList, true))
                                @continue
                            @endif
                            @php
                                $key = $dayNum.'|'.substr((string) $segment->start_time, 0, 5);
                                $cellSessions = $sessionsByDayAndTime->get($key, collect());
                                // Only a bell slot has a template segment to snap a dropped class onto, so
                                // a row for a class timed outside the bell schedule shows but does not receive.
                                $slotSegmentId = $segment->id ?? null;
                                $isDropzone = $editable && $slotSegmentId;
                            @endphp
                            <td
                                class="tich-timetable-grid__cell @if ($isDropzone) tich-timetable-grid__dropzone @endif"
                                @if ($isDropzone)
                                    data-drop-day="{{ $dayNum }}"
                                    data-segment-id="{{ $slotSegmentId }}"
                                @endif
                            >
                                @forelse ($cellSessions as $session)
                                    <div
                                        class="tich-timetable-session {{ $typeClasses[$session->session_type] ?? 'is-other' }} @if ($editable) is-draggable @endif"
                                        @if ($editable)
                                            draggable="true"
                                            data-session-id="{{ $session->id }}"
                                        @endif
                                    >
                                        @if ($editable)
                                            <span class="tich-timetable-session__handle" aria-hidden="true">⋮⋮</span>
                                        @endif
                                        <strong>{{ $session->displayTitle() }}</strong>
                                        @if (($placement[(int) $session->id] ?? $rowStart($session)) !== $rowStart($session))
                                            <span class="tich-caption">{{ $session->timeLabel() }}</span>
                                        @endif
                                        @if ($session->isOnline())
                                            <span class="tich-caption tich-timetable-session__mode"><strong>Online</strong></span>
                                        @endif
                                        @if ($session->room)
                                            <span class="tich-caption">{{ $session->room->room_code }}</span>
                                        @elseif ($session->venue)
                                            <span class="tich-caption">{{ $session->venue }}</span>
                                        @endif
                                        @if ($session->session_date)
                                            <span class="tich-caption">{{ $session->session_date->format('d M Y') }}</span>
                                        @endif
                                        @if ($session->staff)
                                            <span class="tich-caption">{{ $canEditInvigilator ? 'Invigilator: ' : '' }}{{ $session->staff->first_name }} {{ $session->staff->surname }}</span>
                                        @elseif ($canEditInvigilator)
                                            <span class="tich-caption">Invigilator: unassigned</span>
                                        @endif
                                        <span class="tich-caption">{{ ($sessionTypes ?? $segmentTypes)[$session->session_type] ?? ucfirst($session->session_type) }}</span>
                                        @if (in_array($session->session_type, ['break', 'lunch'], true) === false)
                                            <span class="tich-caption">{{ $session->recurrenceLabel() }}@if ($session->cycleLabel()) · {{ $session->cycleLabel() }} @endif</span>
                                        @endif
                                        @if ($canEditInvigilator)
                                            <button
                                                type="button"
                                                class="tich-link tich-timetable-session__invigilator-btn"
                                                data-open-invigilator-modal
                                                data-session-id="{{ $session->id }}"
                                                data-staff-id="{{ $session->staff_id ?? '' }}"
                                                data-session-label="{{ $session->displayTitle() }} · {{ $dayLabel }} · {{ method_exists($segment, 'timeLabel') ? $segment->timeLabel() : ($segment->label ?? '') }}"
                                            >{{ $session->staff_id ? 'Change invigilator' : 'Assign invigilator' }}</button>
                                        @endif
                                    </div>
                                @empty
                                    <span class="tich-timetable-grid__empty">-</span>
                                @endforelse
                            </td>
                        @endforeach
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>
</div>
