@props(['title' => 'No results found', 'description' => 'Try adjusting your search or filter criteria.', 'inline' => false, 'searchTerm' => null])

<div class="tich-state {{ $inline ? 'tich-state--inline' : '' }}">
    <div class="tich-state__icon tich-state__icon--no-results">
        @include('partials.states.icons.search-x')
    </div>
    <h3 class="tich-state__title">{{ $title }}</h3>
    <p class="tich-state__description">
        @if ($searchTerm)
            No results match "{{ $searchTerm }}". Try a different search term or clear filters.
        @else
            {{ $description }}
        @endif
    </p>
</div>
