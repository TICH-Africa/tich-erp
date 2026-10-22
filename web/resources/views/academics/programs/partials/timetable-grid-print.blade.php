@php
    $activeDayList = $activeDays ?? range(1, 5);
    $sessionsByDayAndTime = collect($sessions ?? [])->groupBy(
        fn ($session) => $session->day_of_week.'|'.substr((string) $session->start_time, 0, 5)
    );
    $datesByRowStart = collect($sessions ?? [])
        ->filter(fn ($session) => ! empty($session->session_date))
        ->groupBy(fn ($session) => substr((string) $session->start_time, 0, 5))
        ->map(fn ($group) => $group
            ->map(fn ($session) => $session->session_date->format('d M'))
            ->unique()
            ->values());

    // The printed page must show the same classes the screen shows, including any timed outside the
    // bell slots, so those get their own row here too.
    $printRows = collect($segments ?? [])->filter(function ($row) use ($sessionsByDayAndTime, $activeDayList) {
        $start = substr((string) $row->start_time, 0, 5);

        if ($start !== '' && $start !== '00:00') {
            return true;
        }

        foreach ($activeDayList as $dayNum) {
            if ($sessionsByDayAndTime->has($dayNum.'|'.$start)) {
                return true;
            }
        }

        return false;
    });
    $rowStarts = $printRows->map(fn ($row) => substr((string) $row->start_time, 0, 5));

    foreach ($sessions ?? [] as $session) {
        $start = substr((string) $session->start_time, 0, 5);

        if ($rowStarts->contains($start)) {
            continue;
        }

        $printRows->push((object) [
            'label' => $session->timeLabel(),
            'start_time' => $start,
            'end_time' => $session->end_time,
            'segment_type' => $session->session_type,
        ]);
        $rowStarts->push($start);
    }

    $printRows = $printRows->sortBy(fn ($row) => substr((string) $row->start_time, 0, 5))->values();
@endphp

<table class="tich-doc-timetable-grid">
    <thead>
        <tr>
            <th class="tich-doc-timetable-grid__time">Time</th>
            @foreach ($dayLabels as $dayNum => $dayLabel)
                @if (in_array($dayNum, $activeDayList, true))
                    <th>{{ $dayLabel }}</th>
                @endif
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach ($printRows as $segment)
            @if ($segment->segment_type === 'break')
                <tr class="tich-doc-timetable-grid__break">
                    <td>{{ method_exists($segment, 'timeLabel') ? $segment->timeLabel() : $segment->label }}</td>
                    <td colspan="{{ count($activeDayList) }}">{{ $segment->label }}</td>
                </tr>
            @else
                <tr>
                    <td class="tich-doc-timetable-grid__time">
                        {{ $segment->label ?? 'Slot' }}<br>
                        <span style="font-weight:normal;color:#64748b;">{{ method_exists($segment, 'timeLabel') ? $segment->timeLabel() : '' }}</span>
                        @php $printRowDates = $datesByRowStart->get(substr((string) $segment->start_time, 0, 5), collect()); @endphp
                        @if ($printRowDates->isNotEmpty())
                            <br><span style="font-weight:normal;color:#64748b;">{{ $printRowDates->implode(' · ') }}</span>
                        @endif
                    </td>
                    @foreach ($dayLabels as $dayNum => $dayLabel)
                        @if (! in_array($dayNum, $activeDayList, true))
                            @continue
                        @endif
                        @php
                            $key = $dayNum.'|'.substr((string) $segment->start_time, 0, 5);
                            $cellSessions = $sessionsByDayAndTime->get($key, collect());
                        @endphp
                        <td>
                            @forelse ($cellSessions as $session)
                                <div class="tich-doc-session">
                                    <strong>{{ $session->displayTitle() }}</strong>
                                    @if ($session->isOnline())
                                        <span>Online</span>
                                    @endif
                                    @if ($session->room)
                                        <span>{{ $session->room->room_code }}</span>
                                    @elseif ($session->venue)
                                        <span>{{ $session->venue }}</span>
                                    @endif
                                    @if ($session->session_date)
                                        <span>{{ $session->session_date->format('d M Y') }}</span>
                                    @endif
                                    @if ($session->staff)
                                        <span>{{ $session->staff->first_name }} {{ $session->staff->surname }}</span>
                                    @endif
                                    <span>{{ $segmentTypes[$session->session_type] ?? ucfirst($session->session_type) }}</span>
                                    @if (! in_array($session->session_type, ['break', 'lunch'], true))
                                        <span>{{ $session->recurrenceLabel() }}@if ($session->cycleLabel()) · {{ $session->cycleLabel() }} @endif</span>
                                    @endif
                                </div>
                            @empty
                                <span style="color:#94a3b8;">-</span>
                            @endforelse
                        </td>
                    @endforeach
                </tr>
            @endif
        @endforeach
    </tbody>
</table>
