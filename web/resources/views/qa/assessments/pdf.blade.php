@php
    use App\Services\Qa\IqaAssessmentSchema;
    /** @var \App\Models\Qa\IqaAssessment $assessment */
    $tableTitles = IqaAssessmentSchema::tableTitles();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $assessment->title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 8pt; color: #111; }
        h1 { font-size: 14pt; text-align: center; margin: 0 0 6pt; text-transform: uppercase; }
        h2 { font-size: 11pt; margin: 14pt 0 6pt; border-bottom: 1pt solid #333; padding-bottom: 3pt; }
        h3 { font-size: 9pt; margin: 10pt 0 4pt; }
        .meta { text-align: center; margin-bottom: 10pt; font-size: 8pt; }
        table { width: 100%; border-collapse: collapse; margin: 4pt 0 8pt; }
        th, td { border: 1pt solid #000; padding: 3pt 4pt; vertical-align: top; }
        th { background: #e8e8e8; font-size: 7.5pt; }
        .remarks { margin: 2pt 0 8pt; }
        .page-break { page-break-before: always; }
        .small { font-size: 7.5pt; color: #333; }
    </style>
</head>
<body>
    <h1>{{ $assessment->title }}</h1>
    <p class="meta">
        Assessment year: {{ $assessment->assessment_year ?: '—' }}
        &nbsp;|&nbsp; Status: {{ strtoupper($assessment->status) }}
        @if ($assessment->isPublished())
            &nbsp;|&nbsp; Published: {{ $assessment->published_at?->format('d M Y') }}
            &nbsp;|&nbsp; Publisher: {{ $assessment->publisher_name }}
        @endif
    </p>

    @foreach ($meta as $n => $m)
        @if ($n > 7)
            @continue
        @endif
        @php
            $sectionData = $payload['sections'][(string) $n] ?? ['items' => [], 'tables' => [], 'overall_recommendations' => ''];
            $items = $sectionData['items'] ?? [];
            $tables = $sectionData['tables'] ?? [];
        @endphp

        <div class="{{ $n > 1 ? 'page-break' : '' }}">
            <h2>{{ $m['number'] }} {{ $m['title'] }}</h2>

            @if ($n === 2)
                @foreach (['admin_offices', 'theory_rooms', 'workshops_labs', 'tools_equipment'] as $tableKey)
                    @include('qa.assessments.partials.pdf-table', [
                        'tableKey' => $tableKey,
                        'table' => $tables[$tableKey] ?? null,
                        'title' => $tableTitles[$tableKey] ?? $tableKey,
                    ])
                @endforeach
            @endif

            @if (count($items) > 0)
                <table>
                    <thead>
                        <tr>
                            <th style="width:6%;">S/No</th>
                            <th style="width:16%;">Audit Area</th>
                            <th style="width:30%;">Indicator / Question</th>
                            <th style="width:14%;">Target</th>
                            <th style="width:17%;">Observations / Remarks</th>
                            <th style="width:17%;">Recommendations</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $lastArea = null; @endphp
                        @foreach ($items as $item)
                            <tr>
                                <td>{{ $item['sno'] }}</td>
                                <td>{{ $lastArea !== ($item['audit_area'] ?? '') ? $item['audit_area'] : '' }}</td>
                                <td>{{ $item['indicator'] }}</td>
                                <td>{{ $item['target'] ?? '' }}</td>
                                <td>{{ $item['observations'] ?? '' }}</td>
                                <td>{{ $item['recommendations'] ?? '' }}</td>
                            </tr>
                            @php $lastArea = $item['audit_area'] ?? ''; @endphp
                        @endforeach
                    </tbody>
                </table>
            @endif

            @if ($n === 3)
                @include('qa.assessments.partials.pdf-table', [
                    'tableKey' => 'trainers_analysis',
                    'table' => $tables['trainers_analysis'] ?? null,
                    'title' => $tableTitles['trainers_analysis'],
                ])
            @endif

            @if ($n === 5)
                @include('qa.assessments.partials.pdf-table', [
                    'tableKey' => 'programmes_data',
                    'table' => $tables['programmes_data'] ?? null,
                    'title' => $tableTitles['programmes_data'],
                ])
            @endif

            <p class="remarks"><strong>Overall Recommendations:</strong><br>
                {!! nl2br(e($sectionData['overall_recommendations'] ?? '')) !!}
            </p>
        </div>
    @endforeach

    <div class="page-break">
        <h2>8.0 QUALITY AUDITORS</h2>
        <table>
            <thead>
                <tr>
                    <th style="width:8%;">S/No</th>
                    <th style="width:40%;">Name</th>
                    <th style="width:32%;">Signature</th>
                    <th style="width:20%;">Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach (($payload['auditors'] ?? []) as $i => $auditor)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $auditor['name'] ?? '' }}</td>
                        <td style="height:28pt;">{{ $auditor['signature'] ?? '' }}</td>
                        <td>{{ $auditor['date'] ?? '' }}</td>
                    </tr>
                @endforeach
                @if (empty($payload['auditors']))
                    <tr>
                        <td>1</td>
                        <td></td>
                        <td style="height:28pt;"></td>
                        <td></td>
                    </tr>
                @endif
            </tbody>
        </table>
        <p class="small">Signature column left blank for wet-ink signature on the printed copy.</p>
    </div>
</body>
</html>
