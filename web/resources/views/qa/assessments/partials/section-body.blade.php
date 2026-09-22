@php
    use App\Services\Qa\IqaAssessmentSchema;
    $items = $sectionData['items'] ?? [];
    $tables = $sectionData['tables'] ?? [];
    $tableTitles = IqaAssessmentSchema::tableTitles();
@endphp

{{-- Section 2: sampling tables 2.1–2.4 first, then checklist 2.5–2.8 --}}
@if ($section === 2)
    @foreach (['admin_offices', 'theory_rooms', 'workshops_labs', 'tools_equipment'] as $tableKey)
        @if (! empty($tables[$tableKey]))
            @include('qa.assessments.partials.sampling-table', [
                'tableKey' => $tableKey,
                'table' => $tables[$tableKey],
                'title' => $tableTitles[$tableKey] ?? $tableKey,
                'readonly' => $readonly,
            ])
        @endif
    @endforeach
@endif

@if (count($items) > 0)
    <div class="tich-card tich-table-panel tich-mt-6">
        <h2 class="tich-h3" style="padding:1rem 1rem 0;">Checklist</h2>
        <div class="tich-table-wrap tich-mt-2">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th style="width:3.5rem;">S/No</th>
                        <th style="width:12rem;">Audit Area</th>
                        <th>Indicator / Question</th>
                        <th style="width:10rem;">Target</th>
                        <th style="width:12rem;">Observations / Remarks</th>
                        <th style="width:12rem;">Recommendations</th>
                    </tr>
                </thead>
                <tbody>
                    @php $lastArea = null; @endphp
                    @foreach ($items as $i => $item)
                        <tr>
                            <td>{{ $item['sno'] }}</td>
                            <td>
                                @if ($lastArea !== ($item['audit_area'] ?? ''))
                                    <strong>{{ $item['audit_area'] }}</strong>
                                    @php $lastArea = $item['audit_area'] ?? ''; @endphp
                                @endif
                            </td>
                            <td>{{ $item['indicator'] }}</td>
                            @if ($readonly)
                                <td>{{ $item['target'] ?: '—' }}</td>
                                <td>{{ $item['observations'] ?: '—' }}</td>
                                <td>{{ $item['recommendations'] ?: '—' }}</td>
                            @else
                                <td>
                                    <textarea name="items[{{ $i }}][target]" class="tich-input" rows="2">{{ old('items.'.$i.'.target', $item['target'] ?? '') }}</textarea>
                                </td>
                                <td>
                                    <textarea name="items[{{ $i }}][observations]" class="tich-input" rows="2">{{ old('items.'.$i.'.observations', $item['observations'] ?? '') }}</textarea>
                                </td>
                                <td>
                                    <textarea name="items[{{ $i }}][recommendations]" class="tich-input" rows="2">{{ old('items.'.$i.'.recommendations', $item['recommendations'] ?? '') }}</textarea>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

{{-- Section 3: trainers analysis after 3.1 / 3.2 checklist --}}
@if ($section === 3 && ! empty($tables['trainers_analysis']))
    @include('qa.assessments.partials.sampling-table', [
        'tableKey' => 'trainers_analysis',
        'table' => $tables['trainers_analysis'],
        'title' => $tableTitles['trainers_analysis'],
        'readonly' => $readonly,
    ])
@endif

{{-- Section 5: programmes data after indicators --}}
@if ($section === 5 && ! empty($tables['programmes_data']))
    @include('qa.assessments.partials.sampling-table', [
        'tableKey' => 'programmes_data',
        'table' => $tables['programmes_data'],
        'title' => $tableTitles['programmes_data'],
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
