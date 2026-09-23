@php
    use App\Services\Qa\IqaAssessmentSchema;
    $items = $sectionData['items'] ?? [];
    $tables = $sectionData['tables'] ?? [];
    $tableTitles = IqaAssessmentSchema::tableTitles();
    $layout = $sectionData['layout'] ?? (($section ?? 0) === 5 ? 'numbered' : 'areas');
    $groups = IqaAssessmentSchema::groupItemsByArea($items);
    $flatIndex = 0;
@endphp

@if ($section === 2)
    @foreach (['admin_offices', 'theory_rooms', 'workshops_labs', 'tools_equipment'] as $tableKey)
        @if (! empty($tables[$tableKey]))
            @include('qa.assessments.partials.sampling-table', [
                'tableKey' => $tableKey,
                'table' => $tables[$tableKey],
                'title' => $tables[$tableKey]['title'] ?? ($tableTitles[$tableKey] ?? $tableKey),
                'readonly' => $readonly,
            ])
            @if (! empty($tables[$tableKey]['note']))
                <p class="tich-caption tich-mt-2">{{ $tables[$tableKey]['note'] }}</p>
            @endif
        @endif
    @endforeach
@endif

@if (count($items) > 0)
    <div class="tich-card tich-table-panel tich-mt-6">
        <div class="tich-table-wrap">
            <table class="tich-admin-table" style="white-space:normal;">
                <thead>
                    <tr>
                        <th style="width:4.5rem;">S/No.</th>
                        @if ($layout !== 'numbered')
                            <th style="width:11rem;">Audit Area</th>
                        @endif
                        <th>Indicator/Question Target</th>
                        <th style="width:16rem;">Observations/Remarks</th>
                        <th style="width:16rem;">Recommendations</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($groups as $group)
                        @php
                            $rowCount = count($group['rows']);
                            $firstIndex = $flatIndex;
                            $first = $group['rows'][0] ?? [];
                            // Area-level answers live on the first indicator row (original form layout).
                            $obs = (string) ($first['observations'] ?? '');
                            $rec = (string) ($first['recommendations'] ?? '');
                        @endphp
                        @foreach ($group['rows'] as $ri => $item)
                            <tr>
                                @if ($ri === 0)
                                    <td rowspan="{{ $rowCount }}" style="vertical-align:top;font-weight:700;">{{ $group['area_code'] }}</td>
                                    @if ($layout !== 'numbered')
                                        <td rowspan="{{ $rowCount }}" style="vertical-align:top;">{{ $group['audit_area'] }}</td>
                                    @endif
                                @endif
                                <td style="white-space:normal;">{{ $item['indicator'] }}</td>
                                @if ($ri === 0)
                                    @if ($readonly)
                                        <td rowspan="{{ $rowCount }}" style="white-space:normal;vertical-align:top;">{{ $obs !== '' ? $obs : '—' }}</td>
                                        <td rowspan="{{ $rowCount }}" style="white-space:normal;vertical-align:top;">{{ $rec !== '' ? $rec : '—' }}</td>
                                    @else
                                        <td rowspan="{{ $rowCount }}" style="vertical-align:top;">
                                            <textarea name="items[{{ $firstIndex }}][observations]" class="tich-input" rows="{{ max(3, $rowCount) }}">{{ old('items.'.$firstIndex.'.observations', $obs) }}</textarea>
                                        </td>
                                        <td rowspan="{{ $rowCount }}" style="vertical-align:top;">
                                            <textarea name="items[{{ $firstIndex }}][recommendations]" class="tich-input" rows="{{ max(3, $rowCount) }}">{{ old('items.'.$firstIndex.'.recommendations', $rec) }}</textarea>
                                        </td>
                                    @endif
                                @endif
                            </tr>
                            @php $flatIndex++; @endphp
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@if ($section === 3 && ! empty($tables['trainers_analysis']))
    @include('qa.assessments.partials.sampling-table', [
        'tableKey' => 'trainers_analysis',
        'table' => $tables['trainers_analysis'],
        'title' => $tables['trainers_analysis']['title'] ?? $tableTitles['trainers_analysis'],
        'readonly' => $readonly,
    ])
@endif

@if ($section === 5 && ! empty($tables['programmes_data']))
    @include('qa.assessments.partials.sampling-table', [
        'tableKey' => 'programmes_data',
        'table' => $tables['programmes_data'],
        'title' => $tables['programmes_data']['title'] ?? $tableTitles['programmes_data'],
        'readonly' => $readonly,
    ])
@endif

<div class="tich-card tich-mt-6">
    <label class="tich-label" for="overall_recommendations">Overall Recommendations</label>
    @if ($readonly)
        <p class="tich-text tich-mt-2" style="white-space:pre-wrap;">{{ $sectionData['overall_recommendations'] ?: '—' }}</p>
    @else
        <textarea
            id="overall_recommendations"
            name="overall_recommendations"
            class="tich-input tich-mt-2"
            rows="5"
        >{{ old('overall_recommendations', $sectionData['overall_recommendations'] ?? '') }}</textarea>
    @endif
</div>
