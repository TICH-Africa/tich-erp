<header class="tich-dept-header">
    <h1 class="tich-h1 tich-dept-header__title">My units</h1>
    <p class="tich-text">Units assigned to you for the current semester(s).</p>
</header>

@if ($portalData['allocations']->isEmpty())
    <article class="tich-card tich-mt-6">
        <p class="tich-text">No units assigned yet. Your HOD will allocate units from the programme unit catalog.</p>
    </article>
@else
    <div class="tich-card tich-table-panel tich-mt-6">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Unit</th>
                    <th>Intake</th>
                    <th>Semester</th>
                    <th>Campus</th>
                    <th>Hours</th>
                    <th>Coordinator</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($portalData['allocations'] as $allocation)
                    <tr>
                        <td>{{ $allocation->unit?->unit_code }} - {{ $allocation->unit?->unit_name }}</td>
                        <td>{{ $allocation->intake_label ?? '-' }}</td>
                        <td>{{ $allocation->semester?->semester_label }}</td>
                        <td>{{ $allocation->campus?->campus_name }}</td>
                        <td>{{ $allocation->contact_hours_assigned }}</td>
                        <td>{{ $allocation->is_coordinator ? 'Yes' : 'No' }}</td>
                        <td><a href="{{ route('staff.dashboard', ['section' => 'grading', 'allocation' => $allocation->id]) }}" class="tich-link">Enter marks</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
