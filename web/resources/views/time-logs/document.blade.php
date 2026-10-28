@extends('layouts.print-document')

@php
    $deptMap = $departments->keyBy('id');
    $staff = $log->staff;
@endphp

@section('document-content')
    <table class="tich-doc-head">
        <tbody>
            <tr>
                <th>NAMES</th>
                <td colspan="3">{{ $staff?->printedName() ?: '_______________________' }}</td>
            </tr>
            <tr>
                <th>EMPLOYEE NO.</th>
                <td>{{ $staff?->employee_number ?: '____________' }}</td>
                <th>DEPARTMENT</th>
                <td>{{ $staff?->department?->dept_name ?: '________________' }}</td>
            </tr>
            <tr>
                <th>MONTH</th>
                <td>{{ $log->monthLabel() }}</td>
                <th>WEEK NO. G/REF</th>
                <td>{{ $log->week_ref }}</td>
            </tr>
            <tr>
                <th>PERIOD</th>
                <td colspan="3">
                    {{ $log->period_start?->format('d M') ?: '____' }}
                    &ndash;
                    {{ $log->period_end?->format('d M Y') ?: '____' }}
                </td>
            </tr>
            <tr>
                <th>STATUS</th>
                <td colspan="3">{{ ucfirst(str_replace('_', ' ', $log->status)) }}</td>
            </tr>
            <tr>
                <th>TOTAL HOURS</th>
                <td class="num">{{ number_format((float) $log->total_hours, 2) }}</td>
                <th>TOTAL UNITS</th>
                <td class="num">{{ number_format((float) ($log->total_units ?? 0), 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="tich-doc-table">
        <thead>
            <tr>
                <th>DAY/DATE</th>
                <th>TIME IN</th>
                <th>TIME OUT</th>
                <th>TASKS ACCOMPLISHED</th>
                <th>INITIALS</th>
                <th>DEPT</th>
                <th>APPROVAL SIGN</th>
                <th>TOTAL HOURS</th>
                <th>TOTAL UNITS</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($log->days as $day)
                @continue($day->work_date && $day->work_date->isWeekend())
                @continue(! $day->in_month)
                @php
                    $labels = collect(is_array($day->department_ids) ? $day->department_ids : [])
                        ->map(fn ($id) => $deptMap->get($id)?->dept_code ?? $deptMap->get($id)?->dept_name)
                        ->filter()
                        ->implode(', ');
                @endphp
                <tr>
                    <td>
                        <strong>{{ $day->day_label }}</strong>
                        <br>{{ $day->work_date?->format('d/m/Y') }}
                    </td>
                    <td>{{ $day->time_in ? \Illuminate\Support\Str::substr((string) $day->time_in, 0, 5) : '' }}</td>
                    <td>{{ $day->time_out ? \Illuminate\Support\Str::substr((string) $day->time_out, 0, 5) : '' }}</td>
                    <td>{{ $day->tasks_accomplished }}</td>
                    <td>{{ $day->initials }}</td>
                    <td>{{ $labels }}</td>
                    <td>{{ $day->approval_sign }}</td>
                    <td class="num">{{ $day->total_hours !== null ? number_format((float) $day->total_hours, 2) : '' }}</td>
                    <td class="num">{{ $day->total_units !== null ? number_format((float) $day->total_units, 2) : '' }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="7">TOTALS</td>
                <td class="num">{{ number_format((float) $log->total_hours, 2) }}</td>
                <td class="num">{{ number_format((float) ($log->total_units ?? 0), 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <section class="tich-doc-section">
        <p>
            NOTE: To be filled on a daily basis. You may also complete every week at month end.
            TOTAL HOURS is time in to time out less one hour for lunch. TOTAL UNITS is double those hours.
        </p>
    </section>

    <section class="tich-doc-section">
        <h2>Signatures</h2>
        <table class="tich-doc-table">
            <tbody>
                <tr>
                    <th style="width:24%;">Signed (Employee)</th>
                    <td>
                        {{ $log->employee_signed_name ?: '_________________________' }}
                        <br>Date: {{ $log->employee_signed_at?->format('d M Y H:i') ?: '___________________' }}
                    </td>
                </tr>
                <tr>
                    <th>Endorsed by (Line manager / HOD)</th>
                    <td>
                        {{ $log->manager_signed_name ?: '_________________________' }}
                        @if ($log->manager_self_endorsed)
                            (self-endorsed)
                        @endif
                        <br>Sign: {{ $log->manager_signature ?: '_________________________' }}
                        <br>Date: {{ $log->manager_signed_at?->format('d M Y H:i') ?: '___________________' }}
                    </td>
                </tr>
                <tr>
                    <th>Reviewed by HR</th>
                    <td>
                        {{ $log->hrReviewer ? $log->hrReviewer->printedName() : '_________________________' }}
                        <br>Decision: {{ str_replace('_', ' ', $log->status) }}
                        <br>Date: {{ $log->hr_reviewed_at?->format('d M Y H:i') ?: '___________________' }}
                        @if ($log->hr_notes)
                            <br>Notes: {{ $log->hr_notes }}
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </section>
@endsection
