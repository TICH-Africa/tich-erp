<ol class="tich-apply-progress" aria-label="Application progress">
    @foreach ($steps as $number => $meta)
        <li class="tich-apply-progress__item {{ $number === $step ? 'is-current' : '' }} {{ $number < $step ? 'is-complete' : '' }}">
            <span class="tich-apply-progress__index">{{ $number }}</span>
            <span class="tich-apply-progress__label">{{ $meta['label'] }}</span>
        </li>
    @endforeach
</ol>
