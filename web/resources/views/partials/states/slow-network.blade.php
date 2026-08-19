@props(['title' => 'Slow connection detected', 'description' => 'Your network connection is slower than expected. Content may take longer to load.', 'inline' => false])

<div class="tich-state {{ $inline ? 'tich-state--inline' : '' }}">
    <div class="tich-state__icon tich-state__icon--slow">
        @include('partials.states.icons.clock')
    </div>
    <h3 class="tich-state__title">{{ $title }}</h3>
    <p class="tich-state__description">{{ $description }}</p>
</div>
