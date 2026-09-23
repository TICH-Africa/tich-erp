@php
    use App\Services\Qa\IqaAssessmentSchema;
    $labels = IqaAssessmentSchema::tableColumnLabels($tableKey);
    $columns = $table['columns'] ?? array_keys($labels);
    $rows = $table['rows'] ?? [];
    $fixedLabels = $table['fixed_labels'] ?? null;
@endphp

<div class="tich-card tich-table-panel tich-mt-6">
    <h2 class="tich-h3" style="padding:1rem 1rem 0;">{{ $title }}</h2>
    <div class="tich-table-wrap tich-mt-2">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th style="width:3rem;">#</th>
                    @foreach ($columns as $col)
                        <th>{{ $labels[$col] ?? $col }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $ri => $row)
                    <tr>
                        <td>{{ $ri + 1 }}</td>
                        @foreach ($columns as $ci => $col)
                            @php
                                $isFixed = is_array($fixedLabels) && $ci === 0;
                                $value = $row[$col] ?? '';
                            @endphp
                            <td>
                                @if ($readonly || $isFixed)
                                    {{ $value !== '' ? $value : ($isFixed ? ($fixedLabels[$ri] ?? '—') : '—') }}
                                    @if ($isFixed && ! $readonly)
                                        <input type="hidden" name="tables[{{ $tableKey }}][rows][{{ $ri }}][{{ $col }}]" value="{{ $fixedLabels[$ri] ?? $value }}">
                                    @endif
                                @else
                                    <input
                                        type="text"
                                        class="tich-input"
                                        name="tables[{{ $tableKey }}][rows][{{ $ri }}][{{ $col }}]"
                                        value="{{ old('tables.'.$tableKey.'.rows.'.$ri.'.'.$col, $value) }}"
                                    >
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div style="padding:1rem;">
        <label class="tich-label" for="remarks-{{ $tableKey }}">Remarks</label>
        @if ($readonly)
            <p class="tich-text tich-mt-2" style="white-space:pre-wrap;">{{ $table['remarks'] ?: '—' }}</p>
        @else
            <textarea
                id="remarks-{{ $tableKey }}"
                name="tables[{{ $tableKey }}][remarks]"
                class="tich-input tich-mt-2"
                rows="3"
            >{{ old('tables.'.$tableKey.'.remarks', $table['remarks'] ?? '') }}</textarea>
        @endif
    </div>
</div>
