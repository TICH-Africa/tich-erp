@extends('layouts.print-document')

@section('document-content')
    @if (empty($units))
        <p class="tich-doc-note">No eligible examination sessions are listed on this card yet.</p>
    @else
        <section class="tich-doc-section">
            <h2>Registered examinations</h2>
            <table class="tich-doc-table">
                <thead>
                    <tr>
                        <th>Unit</th>
                        <th>Title</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Venue</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($units as $unit)
                        <tr>
                            <td>{{ $unit->unit_code }}</td>
                            <td>{{ $unit->unit_name }}</td>
                            <td>{{ $unit->exam_date ? \Illuminate\Support\Carbon::parse($unit->exam_date)->format('d M Y') : '-' }}</td>
                            <td>
                                @if ($unit->start_time && $unit->end_time)
                                    {{ substr((string) $unit->start_time, 0, 5) }} – {{ substr((string) $unit->end_time, 0, 5) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $unit->venue ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif

    <p class="tich-doc-note tich-mt-4">
        Present this card together with your student ID at each examination session.
        Issued {{ optional($exam_card->issued_at)->format('d F Y') ?? now()->format('d F Y') }}.
    </p>
@endsection
