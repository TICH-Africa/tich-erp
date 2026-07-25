<header class="tich-dept-header">
    <h1 class="tich-h1 tich-dept-header__title">My units</h1>
    <p class="tich-text">Units assigned to you for the current semester(s).</p>
</header>

@if ($portalData['allocations']->isEmpty())
    <article class="tich-card tich-mt-6">
        <p class="tich-text">No units assigned yet. Your HOD will allocate units via the workload matrix in Academics.</p>
    </article>
@else
    <div class="tich-card tich-mt-6" style="overflow-x:auto; padding:0;">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Unit</th>
                    <th>Semester</th>
                    <th>Campus</th>
                    <th>Hours</th>
                    <th>Coordinator</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($portalData['allocations'] as $allocation)
                    <tr>
                        <td>{{ $allocation->unit?->unit_code }} - {{ $allocation->unit?->unit_name }}</td>
                        <td>{{ $allocation->semester?->semester_label }}</td>
                        <td>{{ $allocation->campus?->campus_name }}</td>
                        <td>{{ $allocation->contact_hours_assigned }}</td>
                        <td>{{ $allocation->is_coordinator ? 'Yes' : 'No' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
