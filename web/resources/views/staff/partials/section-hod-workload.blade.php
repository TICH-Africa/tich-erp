<x-page-toolbar title="Workload matrix" :meta="($staff->department?->dept_name ?? 'Department').' · Teaching load'" />

@php
    $hodWorkload = $hodWorkload ?? ['rows' => collect(), 'semesters' => collect(), 'max_hours' => 18, 'max_units' => 4, 'semester_id' => null];
    $rows = $hodWorkload['rows'] ?? collect();
@endphp

<div class="tich-mt-6">
    <form method="GET" action="{{ route('staff.dashboard') }}" class="tich-flex-wrap" style="gap:0.75rem; align-items:end;">
        <input type="hidden" name="section" value="hod-workload">
        <div class="tich-form-group" style="margin:0;">
            <label class="tich-label" for="semester">Semester filter</label>
            <select id="semester" name="semester" class="tich-select">
                <option value="">All active allocations</option>
                @foreach ($hodWorkload['semesters'] as $semester)
                    <option value="{{ $semester->id }}" @selected((int) ($hodWorkload['semester_id'] ?? 0) === (int) $semester->id)>
                        {{ $semester->semester_label }}
                    </option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="tich-btn tich-btn-secondary">Filter</button>
    </form>

    <p class="tich-caption tich-mt-4">
        Overload warning when total hours &gt; {{ $hodWorkload['max_hours'] }}
        or unit count &gt; {{ $hodWorkload['max_units'] }}.
        Reassign tutors in
        <a href="{{ route('departments.academics.programs.index', ['learning_department' => $staff->department_id]) }}" class="tich-link">Programme curriculum</a>.
    </p>

    @if ($rows->isEmpty())
        <p class="tich-text tich-mt-4">No active unit allocations for this department.</p>
    @else
        <div class="tich-table-panel tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Lecturer</th>
                        <th>Units</th>
                        <th>Hours</th>
                        <th>Assigned units</th>
                        <th>Load</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td>
                                <strong>{{ trim(($row->first_name ?? '').' '.($row->surname ?? '')) }}</strong>
                                <div class="tich-caption">{{ $row->employee_number }}</div>
                            </td>
                            <td>{{ $row->unit_count }}</td>
                            <td>{{ number_format((float) $row->total_hours, 0) }}</td>
                            <td>
                                <ul class="tich-caption" style="margin:0; padding-left:1rem;">
                                    @foreach ($row->units as $unit)
                                        <li>{{ $unit->unit_code }} — {{ $unit->unit_name }} ({{ $unit->contact_hours_assigned }}h)</li>
                                    @endforeach
                                </ul>
                            </td>
                            <td>
                                @if ($row->is_overloaded)
                                    <span class="tich-badge" style="background:#fef3c7; color:#92400e;">Overloaded</span>
                                    <div class="tich-caption tich-mt-1">
                                        @if ($row->overload_hours) Hours @endif
                                        @if ($row->overload_hours && $row->overload_units) · @endif
                                        @if ($row->overload_units) Units @endif
                                    </div>
                                @else
                                    <span class="tich-badge">OK</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
