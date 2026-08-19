@if ($academics['attendance']->isNotEmpty())
    <section class="tich-dept-panel tich-mt-8">
        <div class="tich-dept-panel__head">
            <h2 class="tich-h2 tich-dept-panel__title">Attendance summary</h2>
        </div>
        <div class="tich-card tich-table-panel tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Unit</th>
                        <th>Semester</th>
                        <th>Present</th>
                        <th>Percentage</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($academics['attendance'] as $row)
                        <tr>
                            <td>{{ $row->unit_code }} - {{ $row->unit_name }}</td>
                            <td>{{ $row->semester_label }}</td>
                            <td>{{ $row->total_present }}/{{ $row->total_sessions }}</td>
                            <td>{{ number_format((float) $row->attendance_percentage, 1) }}%</td>
                            <td>@include('partials.attendance-flag', ['flag' => $row->status_flag ?? 'neutral'])</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@else
    @include('partials.states.empty', [
        'title' => 'No attendance records yet',
        'description' => 'Attendance summaries will appear here once sessions are recorded for your registered units.',
        'icon' => 'inbox',
    ])
@endif
