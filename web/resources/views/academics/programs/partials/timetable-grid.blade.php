@php
    $activeDayList = $activeDays ?? range(1, 5);
    $editable = $editable ?? false;
    $moveSessionUrl = $moveSessionUrl ?? null;
    $rows = collect($segments ?? [])->filter(fn ($segment) => $segment->segment_type !== 'break');
    if ($rows->isEmpty()) {
        $rows = collect($sessions ?? [])->map(fn ($session) => (object) [
            'label' => $session->timeLabel(),
            'start_time' => $session->start_time,
            'end_time' => $session->end_time,
            'segment_type' => 'slot',
        ])->unique(fn ($row) => $row->start_time.'-'.$row->end_time)->sortBy('start_time')->values();
    }
    $sessionsByDayAndTime = collect($sessions ?? [])->groupBy(fn ($session) => $session->day_of_week.'|'.substr((string) $session->start_time, 0, 5));
    $typeClasses = [
        'lesson' => 'is-lesson',
        'exam' => 'is-exam',
        'supplementary' => 'is-supplementary',
        'special_exam' => 'is-special-exam',
        'break' => 'is-break',
        'other' => 'is-other',
    ];
@endphp

<div
    class="tich-timetable-grid-wrap tich-mt-4"
    style="overflow-x:auto;"
    @if ($editable && $moveSessionUrl)
        data-timetable-editable="1"
        data-move-url="{{ $moveSessionUrl }}"
    @endif
>
    @if ($editable)
        <p class="tich-caption tich-mb-3">Drag sessions to another day or time slot. Drop onto another session to swap.</p>
        <div class="tich-timetable-drag-status" hidden role="status"></div>
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
            @foreach ($segments as $segment)
                @if ($segment->segment_type === 'break')
                    <tr class="tich-timetable-grid__break">
                        <td>{{ $segment->timeLabel() }}</td>
                        <td colspan="{{ count($activeDayList) }}">{{ $segment->label }}</td>
                    </tr>
                @else
                    <tr>
                        <td class="tich-timetable-grid__time">{{ $segment->label }}<br><span class="tich-caption">{{ $segment->timeLabel() }}</span></td>
                        @foreach ($dayLabels as $dayNum => $dayLabel)
                            @if (! in_array($dayNum, $activeDayList, true))
                                @continue
                            @endif
                            @php
                                $key = $dayNum.'|'.substr((string) $segment->start_time, 0, 5);
                                $cellSessions = $sessionsByDayAndTime->get($key, collect());
                            @endphp
                            <td
                                class="tich-timetable-grid__cell @if ($editable) tich-timetable-grid__dropzone @endif"
                                @if ($editable)
                                    data-drop-day="{{ $dayNum }}"
                                    data-segment-id="{{ $segment->id }}"
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
                                        @if ($session->room)
                                            <span class="tich-caption">{{ $session->room->room_code }}</span>
                                        @elseif ($session->venue)
                                            <span class="tich-caption">{{ $session->venue }}</span>
                                        @endif
                                        @if ($session->staff)
                                            <span class="tich-caption">{{ $session->staff->first_name }} {{ $session->staff->surname }}</span>
                                        @endif
                                        <span class="tich-caption">{{ $segmentTypes[$session->session_type] ?? ucfirst($session->session_type) }}</span>
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
