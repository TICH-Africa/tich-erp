<x-page-toolbar title="My units" meta="Units assigned for current semester(s), grouped by year of study" />

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
                    <th>Students</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($portalData['allocations']->groupBy(fn ($row) => $row->year_of_study ? 'Year '.$row->year_of_study : 'Year not set') as $yearLabel => $yearAllocations)
                    <tr>
                        <td colspan="8" style="background: var(--tich-surface-hover, #f1f5f9);">
                            <strong>{{ $yearLabel }}</strong>
                            <span class="tich-caption">
                                · {{ $yearAllocations->count() }} {{ str('unit')->plural($yearAllocations->count()) }}
                            </span>
                        </td>
                    </tr>
                    @foreach ($yearAllocations as $allocation)
                        @php $roster = $portalData['rosters'][(int) $allocation->id] ?? collect(); @endphp
                        <tr>
                            <td>{{ $allocation->unit?->unit_code }} - {{ $allocation->unit?->unit_name }}</td>
                            <td>{{ $allocation->intake_label ?? '-' }}</td>
                            <td>{{ $allocation->period_label }}</td>
                            <td>{{ $allocation->campus?->campus_name }}</td>
                            <td>{{ $allocation->contact_hours_assigned }}</td>
                            <td>{{ $allocation->is_coordinator ? 'Yes' : 'No' }}</td>
                            <td>
                                @if ($roster->isEmpty())
                                    <span class="tich-caption">No registrations yet</span>
                                @else
                                    <details>
                                        <summary class="tich-link">{{ $roster->count() }} on the unit</summary>
                                        <ul class="tich-caption" style="margin:0.5rem 0 0; padding-left:1rem;">
                                            @foreach ($roster as $student)
                                                <li>{{ $student->registration_number }} - {{ $student->student_name }}</li>
                                            @endforeach
                                        </ul>
                                    </details>
                                @endif
                            </td>
                            <td><a href="{{ route('staff.dashboard', ['section' => 'grading', 'allocation' => $allocation->id]) }}" class="tich-link">Enter marks</a></td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
@endif
