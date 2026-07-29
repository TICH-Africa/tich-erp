@extends('layouts.print-document')

@section('document-content')
    @if ($sessions->isEmpty())
        <p>No sessions are scheduled on this timetable yet.</p>
    @else
        @include('academics.programs.partials.timetable-grid-print', [
            'sessions' => $sessions,
            'dayLabels' => $dayLabels,
            'segmentTypes' => $segmentTypes,
            'activeDays' => $activeDays,
            'segments' => $segments,
        ])
    @endif

    <section class="tich-doc-section">
        <h2>Session register</h2>
        <table class="tich-doc-table">
            <thead>
                <tr>
                    <th>Day</th>
                    <th>Time</th>
                    <th>Unit / title</th>
                    <th>Lecturer</th>
                    <th>Venue</th>
                    <th>Type</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sessions->sortBy(['day_of_week', 'start_time']) as $session)
                    <tr>
                        <td>{{ $dayLabels[$session->day_of_week] ?? $session->day_of_week }}</td>
                        <td>{{ substr((string) $session->start_time, 0, 5) }}–{{ substr((string) $session->end_time, 0, 5) }}</td>
                        <td>{{ $session->displayTitle() }}</td>
                        <td>{{ $session->staff ? trim($session->staff->first_name.' '.$session->staff->surname) : '—' }}</td>
                        <td>{{ $session->room?->room_code ?? $session->venue ?? '—' }}</td>
                        <td>{{ $segmentTypes[$session->session_type] ?? ucfirst($session->session_type) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
@endsection
