<x-page-toolbar title="HOD Unit allocations" :meta="$staff->department?->dept_name . ' · Management'" />

<div class="tich-mt-6">
    <p class="tich-text tich-mt-2">Lecturers assigned to units in your department.</p>
    @if ($hodManagement['allocations']->isEmpty())
        <p class="tich-text tich-mt-4">No unit allocations found.</p>
    @else
        <div class="tich-table-wrap tich-mt-4">
            <table class="tich-admin-table">
                <thead><tr><th>Unit</th><th>Lecturer</th><th>Semester</th><th>Campus</th></tr></thead>
                <tbody>
                    @foreach ($hodManagement['allocations'] as $allocation)
                        <tr>
                            <td>{{ $allocation->unit?->unit_code }} - {{ $allocation->unit?->unit_name }}</td>
                            <td>{{ $allocation->staff?->fullName() }}</td>
                            <td>{{ $allocation->semester?->semester_label ?? '-' }}</td>
                            <td>{{ $allocation->campus?->campus_name ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="tich-caption tich-mt-2">Open <a href="{{ route('departments.academics.programs.index', ['learning_department' => $staff->department_id]) }}" class="tich-link">Programme curriculum</a> in Academics to manage allocations.</p>
    @endif
</div>
