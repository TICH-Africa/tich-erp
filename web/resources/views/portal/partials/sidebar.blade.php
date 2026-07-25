<aside class="tich-admin-sidebar">
    <p class="tich-admin-sidebar__title">Student portal</p>
    <p class="tich-caption" style="margin: -0.5rem 0 1rem;">
        {{ $student->registration_number }}<br>
        {{ $biodata['academic']['program'] ?? '-' }}
    </p>

    <nav class="tich-admin-sidebar__nav">
        @php $currentSection = request()->query('section', 'overview'); @endphp

        @foreach ($sidebarNavigation as $item)
            @if ($item['type'] === 'heading')
                <p class="tich-admin-sidebar__section">{{ $item['label'] }}</p>
            @elseif (! empty($item['coming_soon']))
                <span class="tich-admin-sidebar__disabled">{{ $item['label'] }} <small>(soon)</small></span>
            @elseif (! empty($item['section']))
                <a href="{{ route('portal.dashboard', ['section' => $item['section']]) }}"
                   @class(['is-active' => $currentSection === $item['section']])>{{ $item['label'] }}</a>
            @elseif (! empty($item['route']))
                <a href="{{ route($item['route'], $item['params'] ?? []) }}">{{ $item['label'] }}</a>
            @endif
        @endforeach
    </nav>
</aside>
