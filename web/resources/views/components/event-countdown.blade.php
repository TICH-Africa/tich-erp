@props([
    'event',
    'caption' => 'Countdown',
])

@php
    // The timer only ever speaks for an event that has not started yet. A past date means no markup
    // at all, so the banner goes back to plain words on the next page load and the script removes
    // the block live for a visitor already watching.
    // The officer types Nairobi wall-clock time, so the stored digits are read as East Africa Time
    // whatever the server's own zone is; the ISO target then carries the +03:00 offset and every
    // visitor, in any country, counts down to that same instant.
    $typed = isset($event->start_datetime) && $event->start_datetime ? $event->start_datetime->format('Y-m-d H:i:s') : null;
    $start = $typed ? \Illuminate\Support\Carbon::parse($typed, 'Africa/Nairobi') : null;
@endphp

@if ($start && $start->isFuture())
    @php
        $secondsLeft = max(0, now()->diffInSeconds($start, false));
        $days = intdiv($secondsLeft, 86400);
        $hours = intdiv($secondsLeft % 86400, 3600);
        $minutes = intdiv($secondsLeft % 3600, 60);
        $seconds = $secondsLeft % 60;
    @endphp

    <div class="tich-countdown" data-countdown data-countdown-target="{{ $start->toIso8601String() }}">
        <p class="tich-countdown__label">{{ $caption }}</p>

        <div class="tich-countdown__units" role="timer">
            @foreach ([
                ['days', $days, min(1, $days / 30), 'Days'],
                ['hours', $hours, $hours / 24, 'Hours'],
                ['minutes', $minutes, $minutes / 60, 'Minutes'],
                ['seconds', $seconds, $seconds / 60, 'Seconds'],
            ] as [$key, $value, $fraction, $name])
                <div class="tich-countdown__unit">
                    <span class="tich-countdown__ring" style="--tich-countdown-frac: {{ number_format($fraction, 4, '.', '') }}">
                        <span class="tich-countdown__arc" aria-hidden="true"></span>
                        <span class="tich-countdown__readout">
                            <span class="tich-countdown__value" data-countdown-value="{{ $key }}">{{ str_pad((string) $value, 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="tich-countdown__name">{{ $name }}</span>
                        </span>
                    </span>
                </div>
            @endforeach
        </div>
    </div>

    <script>
        (function () {
            var rings = {
                days: 86400,
                hours: 3600,
                minutes: 60,
                seconds: 1
            };

            document.querySelectorAll('[data-countdown]').forEach(function (panel) {
                var target = Date.parse(panel.getAttribute('data-countdown-target') || '');
                if (isNaN(target)) {
                    panel.remove();
                    return;
                }

                var values = {};
                var frames = {};

                Object.keys(rings).forEach(function (key) {
                    values[key] = panel.querySelector('[data-countdown-value="' + key + '"]');
                    frames[key] = values[key] ? values[key].closest('.tich-countdown__ring') : null;
                });

                function paint(key, amount, fraction) {
                    if (values[key]) {
                        values[key].textContent = (amount < 10 ? '0' : '') + amount;
                    }
                    if (frames[key]) {
                        frames[key].style.setProperty('--tich-countdown-frac', Math.min(1, fraction).toFixed(4));
                    }
                }

                function tick() {
                    var left = Math.floor((target - Date.now()) / 1000);

                    if (left <= 0) {
                        panel.remove();
                        if (timer) {
                            clearInterval(timer);
                        }
                        return;
                    }

                    var days = Math.floor(left / 86400);
                    paint('days', days, days / 30);
                    paint('hours', Math.floor(left % 86400 / 3600), (left % 86400 / 3600) / 24);
                    paint('minutes', Math.floor(left % 3600 / 60), (left % 3600 / 60) / 60);
                    paint('seconds', left % 60, (left % 60) / 60);
                }

                tick();
                var timer = setInterval(tick, 1000);
            });
        })();
    </script>
@endif
