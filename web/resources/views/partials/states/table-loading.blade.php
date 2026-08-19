@props(['colspan' => 5, 'rows' => 3])

@for ($i = 0; $i < $rows; $i++)
<tr class="tich-skeleton-row">
    @for ($j = 0; $j < $colspan; $j++)
        <td>
            <div class="tich-skeleton-bar {{ $j === 0 ? 'tich-skeleton-bar--medium' : ($j % 3 === 0 ? 'tich-skeleton-bar--short' : '') }}"></div>
        </td>
    @endfor
</tr>
@endfor
