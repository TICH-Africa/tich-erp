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
        /* Single type system for the whole document — matches §2 sampling tables */
        body, table, th, td, h1, h2, h3, p, span, strong, em {
            font-family: tichbody, georgia, 'Times New Roman', Times, serif;
            color: #111111;
        }
        body {
            font-size: 9pt;
            line-height: 1.35;
        }
        h1 {
            font-size: 12pt;
            font-weight: bold;
            text-align: center;
            margin: 0 0 6pt;
            text-transform: uppercase;
            line-height: 1.3;
        }
        h2 {
            font-size: 10pt;
            font-weight: bold;
            margin: 12pt 0 5pt;
            line-height: 1.3;
        }
        h3 {
            font-size: 9pt;
            font-weight: bold;
            margin: 10pt 0 4pt;
            line-height: 1.3;
        }
        p, .meta, .remarks, .note, .small {
            font-size: 9pt;
            line-height: 1.35;
            margin: 0 0 6pt;
        }
        .meta {
            text-align: center;
            margin-bottom: 8pt;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 6pt;
            font-size: 9pt;
        }
        th, td {
            border: 0.6pt solid #000000;
            padding: 3pt 4pt;
            vertical-align: top;
            font-size: 9pt;
            line-height: 1.3;
            text-align: left;
        }
        th {
            background-color: #e8e8e8;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
        }
        td.num {
            text-align: center;
            font-weight: bold;
            width: 4%;
        }
        td.area-code {
            text-align: center;
            font-weight: bold;
            vertical-align: top;
        }
        .remarks {
            margin: 2pt 0 10pt;
        }
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <h1>{{ $assessment->title }}</h1>
    <p class="meta">
        Assessment year: {{ $assessment->assessment_year ?: '—' }}
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
            $sectionData = $payload['sections'][(string) $n] ?? ['items' => [], 'tables' => [], 'overall_recommendations' => '', 'layout' => 'areas'];
            $items = $sectionData['items'] ?? [];
            $tables = $sectionData['tables'] ?? [];
            $layout = $sectionData['layout'] ?? ($n === 5 ? 'numbered' : 'areas');
            $groups = IqaAssessmentSchema::groupItemsByArea($items);
        @endphp

        <div class="{{ $n > 1 ? 'page-break' : '' }}">
            <h2>{{ $m['number'] }} {{ $m['title'] }}</h2>

            @if ($n === 2)
                @foreach (['admin_offices', 'theory_rooms', 'workshops_labs', 'tools_equipment'] as $tableKey)
                    @include('qa.assessments.partials.pdf-table', [
                        'tableKey' => $tableKey,
                        'table' => $tables[$tableKey] ?? null,
                        'title' => ($tables[$tableKey]['title'] ?? null) ?: ($tableTitles[$tableKey] ?? $tableKey),
                    ])
                    @if (! empty($tables[$tableKey]['note']))
                        <p class="note">{{ $tables[$tableKey]['note'] }}</p>
                    @endif
                @endforeach
            @endif

            @if (count($items) > 0)
                <table>
                    <thead>
                        <tr>
                            <th style="width:7%;">S/No.</th>
                            @if ($layout !== 'numbered')
                                <th style="width:16%;">Audit Area</th>
                            @endif
                            <th style="width:{{ $layout === 'numbered' ? '48%' : '32%' }};">Indicator/Question Target</th>
                            <th style="width:22%;">Observations/Remarks</th>
                            <th style="width:23%;">Recommendations</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($groups as $group)
                            @php
                                $rowCount = count($group['rows']);
                                $first = $group['rows'][0] ?? [];
                                $obs = (string) ($first['observations'] ?? '');
                                $rec = (string) ($first['recommendations'] ?? '');
                            @endphp
                            @foreach ($group['rows'] as $ri => $item)
                                <tr>
                                    @if ($ri === 0)
                                        <td class="area-code" rowspan="{{ $rowCount }}">{{ $group['area_code'] }}</td>
                                        @if ($layout !== 'numbered')
                                            <td rowspan="{{ $rowCount }}">{{ $group['audit_area'] }}</td>
                                        @endif
                                    @endif
                                    <td>{{ $item['indicator'] }}</td>
                                    @if ($ri === 0)
                                        <td rowspan="{{ $rowCount }}">{{ $obs }}</td>
                                        <td rowspan="{{ $rowCount }}">{{ $rec }}</td>
                                    @endif
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            @endif

            @if ($n === 3)
                @include('qa.assessments.partials.pdf-table', [
                    'tableKey' => 'trainers_analysis',
                    'table' => $tables['trainers_analysis'] ?? null,
                    'title' => ($tables['trainers_analysis']['title'] ?? null) ?: $tableTitles['trainers_analysis'],
                ])
            @endif

            @if ($n === 5)
                @include('qa.assessments.partials.pdf-table', [
                    'tableKey' => 'programmes_data',
                    'table' => $tables['programmes_data'] ?? null,
                    'title' => ($tables['programmes_data']['title'] ?? null) ?: $tableTitles['programmes_data'],
                ])
            @endif

            <p class="remarks"><strong>Overall Recommendations:</strong> {!! nl2br(e($sectionData['overall_recommendations'] ?? '')) !!}</p>
        </div>
    @endforeach

    <div class="page-break">
        <h2>QUALITY AUDITORS</h2>
        <p>PARTICIPANTS IN QUALITY ASSURANCE</p>
        <table>
            <thead>
                <tr>
                    <th style="width:8%;">S/No.</th>
                    <th style="width:40%;">Name</th>
                    <th style="width:32%;">Signature</th>
                    <th style="width:20%;">Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach (($payload['auditors'] ?? [['name'=>'','date'=>'','signature'=>''],['name'=>'','date'=>'','signature'=>''],['name'=>'','date'=>'','signature'=>'']]) as $i => $auditor)
                    <tr>
                        <td class="num">{{ $i + 1 }}.</td>
                        <td>{{ $auditor['name'] ?? '' }}</td>
                        <td style="height:22pt;">{{ $auditor['signature'] ?? '' }}</td>
                        <td>{{ $auditor['date'] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>
