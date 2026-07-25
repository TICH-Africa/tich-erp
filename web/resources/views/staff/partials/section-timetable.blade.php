<header class="tich-dept-header">
    <h1 class="tich-h1 tich-dept-header__title">My timetable</h1>
    <p class="tich-text">Sessions where you are assigned as the lecturer.</p>
</header>

@if ($portalData['timetable_sessions']->isEmpty())
    <article class="tich-card tich-mt-6">
        <p class="tich-text">No timetable sessions assigned to you yet. Sessions appear here once the academic office assigns you on the programme timetable.</p>
    </article>
@else
    <div class="tich-card tich-mt-6" style="overflow-x:auto; padding:0;">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Day</th>
                    <th>Time</th>
                    <th>Unit / session</th>
                    <th>Programme</th>
                    <th>Room</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($portalData['timetable_sessions'] as $session)
                    <tr>
                        <td>{{ $portalData['day_labels'][$session->day_of_week] ?? 'Day '.$session->day_of_week }}</td>
                        <td>{{ substr((string) $session->start_time, 0, 5) }} - {{ substr((string) $session->end_time, 0, 5) }}</td>
                        <td>{{ $session->displayTitle() }}</td>
                        <td>{{ $session->timetable?->program?->program_code }}</td>
                        <td>{{ $session->room?->room_code ?? $session->venue ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
