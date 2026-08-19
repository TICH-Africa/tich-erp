<tr>
    <td colspan="{{ $colspan ?? 1 }}" class="tich-table-empty">
        @include('partials.states.empty', ['title' => $title ?? 'No data yet', 'icon' => $icon ?? 'inbox', 'inline' => true])
    </td>
</tr>
