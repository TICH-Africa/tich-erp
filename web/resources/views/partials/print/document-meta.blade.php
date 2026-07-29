@if (! empty($metaRows))
    <dl class="tich-doc-meta">
        @foreach ($metaRows as $row)
            <div @class(['tich-doc-meta__full' => ! empty($row['full'])])>
                <dt>{{ $row['label'] }}</dt>
                <dd>{!! $row['value'] !!}</dd>
            </div>
        @endforeach
    </dl>
@endif
