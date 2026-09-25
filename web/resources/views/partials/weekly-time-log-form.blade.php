{{-- Shared weekly time log grid. Expects: $log, $departments, $editable (bool) --}}
@php
    $deptMap = $departments->keyBy('id');
@endphp

<div class="tich-card tich-mt-4">
    <div class="tich-grid tich-grid--3" style="gap:1rem;">
        <div>
            <p class="tich-caption">Names</p>
            <p><strong>{{ $log->staff?->fullName() }}</strong></p>
            <p class="tich-caption">{{ $log->staff?->employee_number }}</p>
        </div>
        <div>
            <p class="tich-caption">Month</p>
            <p><strong>{{ $log->monthLabel() }}</strong></p>
        </div>
        <div>
            <p class="tich-caption">Week No. G/REF</p>
            <p><strong>{{ $log->week_ref }}</strong></p>
            <p class="tich-caption">{{ $log->period_start?->format('d M') }} – {{ $log->period_end?->format('d M Y') }}</p>
        </div>
    </div>
</div>

<div class="tich-card tich-table-panel tich-mt-4">
    <div class="tich-table-wrap">
        <table class="tich-admin-table wtl-table">
            <thead>
                <tr>
                    <th>DAY/DATE</th>
                    <th>TIME IN</th>
                    <th>TIME OUT</th>
                    <th style="min-width:14rem;">TASKS ACCOMPLISHED</th>
                    <th>INITIALS</th>
                    <th style="min-width:10rem;">DEPT</th>
                    <th>APPROVAL SIGN</th>
                    <th>TOTAL HOURS</th>
                    <th>TOTAL UNITS</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($log->days as $day)
                    @continue($day->work_date && $day->work_date->isWeekend())
                    @php
                        $dateKey = $day->work_date->toDateString();
                        $disabled = ! $editable || ! $day->in_month;
                        $selectedDepts = is_array($day->department_ids) ? $day->department_ids : [];
                    @endphp
                    <tr class="{{ $day->in_month ? '' : 'wtl-row--outside' }}">
                        <td>
                            <strong>{{ $day->day_label }}</strong>
                            <div class="tich-caption">{{ $day->work_date->format('d/m/Y') }}</div>
                            @unless ($day->in_month)
                                <span class="tich-caption">Outside period</span>
                            @endunless
                        </td>
                        <td>
                            @if ($disabled)
                                {{ $day->time_in ? \Illuminate\Support\Str::substr((string) $day->time_in, 0, 5) : '—' }}
                            @else
                                <input type="time" class="tich-input js-wtl-in" name="days[{{ $dateKey }}][time_in]" value="{{ $day->time_in ? \Illuminate\Support\Str::substr((string) $day->time_in, 0, 5) : '' }}">
                            @endif
                        </td>
                        <td>
                            @if ($disabled)
                                {{ $day->time_out ? \Illuminate\Support\Str::substr((string) $day->time_out, 0, 5) : '—' }}
                            @else
                                <input type="time" class="tich-input js-wtl-out" name="days[{{ $dateKey }}][time_out]" value="{{ $day->time_out ? \Illuminate\Support\Str::substr((string) $day->time_out, 0, 5) : '' }}">
                            @endif
                        </td>
                        <td>
                            @if ($disabled)
                                <span class="tich-caption" style="white-space:pre-wrap;">{{ $day->tasks_accomplished ?: '—' }}</span>
                            @else
                                <textarea class="tich-input" name="days[{{ $dateKey }}][tasks_accomplished]" rows="2" maxlength="5000">{{ $day->tasks_accomplished }}</textarea>
                            @endif
                        </td>
                        <td>
                            @if ($disabled)
                                {{ $day->initials ?: '—' }}
                            @else
                                <input type="text" class="tich-input" name="days[{{ $dateKey }}][initials]" value="{{ $day->initials }}" maxlength="20">
                            @endif
                        </td>
                        <td>
                            @if ($disabled)
                                @php
                                    $labels = collect($selectedDepts)->map(fn ($id) => $deptMap->get($id)?->dept_code ?? $deptMap->get($id)?->dept_name)->filter()->implode(', ');
                                @endphp
                                {{ $labels !== '' ? $labels : '—' }}
                            @else
                                <select class="tich-input" name="days[{{ $dateKey }}][department_ids][]" multiple size="{{ min(4, max(2, $departments->count())) }}">
                                    @foreach ($departments as $dept)
                                        <option value="{{ $dept->id }}" @selected(in_array($dept->id, $selectedDepts, false))>{{ $dept->dept_code }} — {{ $dept->dept_name }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </td>
                        <td>{{ $day->approval_sign ?: '—' }}</td>
                        <td>
                            @if ($disabled)
                                {{ $day->total_hours !== null ? number_format((float) $day->total_hours, 2) : '—' }}
                            @else
                                <input type="number" step="0.01" min="0" max="24" class="tich-input js-wtl-hours" name="days[{{ $dateKey }}][total_hours]" value="{{ $day->total_hours }}">
                            @endif
                        </td>
                        <td>
                            @if ($disabled)
                                {{ $day->total_units !== null ? number_format((float) $day->total_units, 2) : '—' }}
                            @else
                                <input type="number" step="0.01" min="0" class="tich-input" name="days[{{ $dateKey }}][total_units]" value="{{ $day->total_units }}">
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="7" style="text-align:right; font-weight:700;">TOTALS</td>
                    <td><strong>{{ number_format((float) $log->total_hours, 2) }}</strong></td>
                    <td><strong>{{ number_format((float) ($log->total_units ?? 0), 2) }}</strong></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <p class="tich-caption tich-mt-3">NOTE: To be filled on a daily basis. You may also complete every week at month end.</p>
</div>

<div class="tich-card tich-mt-4">
    <div class="tich-grid tich-grid--2" style="gap:1.25rem;">
        <div>
            <p class="tich-caption">Signed (Employee)</p>
            <p><strong>{{ $log->employee_signed_name ?: '—' }}</strong></p>
            <p class="tich-caption">Date: {{ $log->employee_signed_at?->format('d M Y H:i') ?? '—' }}</p>
        </div>
        <div>
            <p class="tich-caption">Endorsed by (Line manager / HOD)</p>
            <p><strong>{{ $log->manager_signed_name ?: '—' }}</strong>
                @if ($log->manager_self_endorsed)
                    <span class="tich-caption">(self-endorsed)</span>
                @endif
            </p>
            <p class="tich-caption">Sign: {{ $log->manager_signature ?: '—' }}</p>
            <p class="tich-caption">Date: {{ $log->manager_signed_at?->format('d M Y H:i') ?? '—' }}</p>
        </div>
    </div>
</div>

@if ($editable)
<script>
(function () {
    function hoursBetween(a, b) {
        if (!a || !b) return '';
        var p = function (t) { var x = t.split(':'); return (+x[0]) * 60 + (+x[1]); };
        var d = p(b) - p(a);
        if (d < 0) d += 24 * 60;
        return (Math.round((d / 60) * 100) / 100).toFixed(2);
    }
    document.querySelectorAll('.wtl-table tbody tr').forEach(function (row) {
        var inn = row.querySelector('.js-wtl-in');
        var out = row.querySelector('.js-wtl-out');
        var hrs = row.querySelector('.js-wtl-hours');
        if (!inn || !out || !hrs) return;
        function sync() {
            if (inn.value && out.value) hrs.value = hoursBetween(inn.value, out.value);
        }
        inn.addEventListener('change', sync);
        out.addEventListener('change', sync);
    });
})();
</script>
@endif
