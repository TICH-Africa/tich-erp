<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Attendance sheet {{ $tracking_id }}</title>
    <link rel="stylesheet" href="{{ asset('css/tich-print-documents.css') }}">
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #222; margin: 24px; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .meta { margin-bottom: 16px; }
        .meta p { margin: 2px 0; }
        .tracking { font-size: 14px; font-weight: bold; color: #1669a6; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #f5f6f6; }
        .signature { width: 120px; }
        .footer { margin-top: 24px; font-size: 11px; color: #666; }
        @media print {
            .no-print { display: none; }
            body { margin: 12mm; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 16px;">
        <button onclick="window.print()">Print sheet</button>
        <button onclick="window.close()">Close</button>
    </div>

    @include('partials.print.document-letterhead', ['institution' => $institution ?? []])

    <h1>Class attendance sheet</h1>
    <div class="meta">
        <p class="tracking">Tracking ID: {{ $tracking_id }}</p>
        <p><strong>Unit:</strong> {{ $unit?->unit_code }} - {{ $unit?->unit_name }}</p>
        <p><strong>Date:</strong> {{ $session->session_date?->format('d M Y') }}</p>
        <p><strong>Time:</strong> {{ substr((string) $session->start_time, 0, 5) }} – {{ substr((string) $session->end_time, 0, 5) }}</p>
        <p><strong>Venue:</strong> {{ $session->venue ?? 'TBC' }}</p>
        <p><strong>Tutor:</strong> {{ $tutor?->fullName() }}</p>
        <p><strong>Intake:</strong> {{ $intake_label ?? '-' }}</p>
        <p><strong>Semester:</strong> {{ $allocation?->semester?->semester_label }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Registration no.</th>
                <th>Student name</th>
                <th class="signature">Student signature</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($records as $index => $record)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $record->student?->registration_number }}</td>
                    <td>{{ $record->student?->applicant?->fullName() ?? '-' }}</td>
                    <td></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Tutor signature: ___________________________ &nbsp; Date: ______________</p>
        <p>This sheet must be photographed and uploaded to the digital register after class. Tracking ID {{ $tracking_id }} links the physical sheet to the electronic roster.</p>
    </div>
</body>
</html>
