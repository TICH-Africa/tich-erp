{{--
  Paginated list footer: page links + rows-per-page control.
  Required: $paginator (LengthAwarePaginator|Paginator)
  Optional: $perPageOptions (list of ints)
--}}
@php
    use App\Support\Pagination;
    $paginator = $paginator ?? null;
    $perPageOptions = $perPageOptions ?? Pagination::OPTIONS;
@endphp
@if ($paginator)
    <div class="tich-pagination-footer">
        <div class="tich-pagination-footer__meta">
            @if (method_exists($paginator, 'total'))
                Showing
                <strong>{{ $paginator->firstItem() ?? 0 }}</strong>
                –
                <strong>{{ $paginator->lastItem() ?? 0 }}</strong>
                of
                <strong>{{ number_format($paginator->total()) }}</strong>
            @else
                Page {{ $paginator->currentPage() }}
            @endif
        </div>

        <div class="tich-pagination-footer__links">
            {{ $paginator->links() }}
        </div>

        <form method="get" class="tich-pagination-footer__per-page" aria-label="Rows per page">
            @foreach(request()->except(['per_page', 'page']) as $key => $value)
                @if (is_array($value))
                    @foreach($value as $nested)
                        <input type="hidden" name="{{ $key }}[]" value="{{ $nested }}">
                    @endforeach
                @else
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <label for="per_page" class="tich-caption">Rows</label>
            <select id="per_page" name="per_page" class="tich-input tich-input--compact" onchange="this.form.submit()">
                @foreach($perPageOptions as $option)
                    <option value="{{ $option }}" @selected((int) $paginator->perPage() === (int) $option)>{{ $option }}</option>
                @endforeach
            </select>
        </form>
    </div>
@endif
