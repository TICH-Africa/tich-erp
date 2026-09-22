@php
    use App\Services\Qa\IqaAssessmentSchema;
    if (! is_array($table ?? null)) {
        return;
    }
    $labels = IqaAssessmentSchema::tableColumnLabels($tableKey);
    $columns = $table['columns'] ?? array_keys($labels);
    $rows = $table['rows'] ?? [];
@endphp

<h3>{{ $title }}</h3>
<table>
    <thead>
        <tr>
            <th style="width:4%;">#</th>
            @foreach ($columns as $col)
                <th>{{ $labels[$col] ?? $col }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $ri => $row)
            <tr>
                <td class="num">{{ $ri + 1 }}</td>
                @foreach ($columns as $col)
                    <td>{{ $row[$col] ?? '' }}</td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>
@if (! empty($table['remarks']))
    <p class="remarks"><strong>Remarks:</strong> {!! nl2br(e($table['remarks'])) !!}</p>
@endif
