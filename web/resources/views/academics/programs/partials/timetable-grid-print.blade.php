@php
    $activeDayList = $activeDays ?? range(1, 5);
    $sessionsByDayAndTime = collect($sessions ?? [])->groupBy(
        fn ($session) => $session->day_of_week.'|'.substr((string) $session->start_time, 0, 5)
    );
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
        @foreach ($segments as $segment)
            @if ($segment->segment_type === 'break')
                <tr class="tich-doc-timetable-grid__break">
                    <td>{{ $segment->timeLabel() ?? $segment->label }}</td>
                    <td colspan="{{ count($activeDayList) }}">{{ $segment->label }}</td>
                </tr>
            @else
                <tr>
                    <td class="tich-doc-timetable-grid__time">
                        {{ $segment->label ?? 'Slot' }}<br>
                        <span style="font-weight:normal;color:#64748b;">{{ $segment->timeLabel() ?? '' }}</span>
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
                                    @if ($session->room)
                                        <span>{{ $session->room->room_code }}</span>
                                    @elseif ($session->venue)
                                        <span>{{ $session->venue }}</span>
                                    @endif
                                    @if ($session->staff)
                                        <span>{{ $session->staff->first_name }} {{ $session->staff->surname }}</span>
                                    @endif
                                    <span>{{ $segmentTypes[$session->session_type] ?? ucfirst($session->session_type) }}</span>
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
