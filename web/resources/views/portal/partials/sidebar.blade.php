<aside class="tich-admin-sidebar">
    <p class="tich-admin-sidebar__title">Student portal</p>
    <p class="tich-caption">
        {{ $student->registration_number }}<br>
        {{ $biodata['academic']['program'] ?? '-' }}
    </p>

    <nav class="tich-admin-sidebar__nav">
        @php $currentSection = request()->query('section', 'overview'); @endphp

        @foreach ($sidebarNavigation as $item)
            @if ($item['type'] === 'heading')
                <p class="tich-admin-sidebar__section">{{ $item['label'] }}</p>
            @elseif (! empty($item['coming_soon']))
                <span class="tich-admin-sidebar__disabled">
                    <span class="tich-admin-sidebar__icon">
                        @include('partials.navigation.sidebar-icon', ['name' => \App\Support\SidebarIcon::forSection($item['section'] ?? null)])
                    </span>
                    <span>{{ $item['label'] }} <small>(soon)</small></span>
                </span>
            @elseif (! empty($item['section']))
                @include('partials.navigation.sidebar-link', [
                    'href' => route('portal.dashboard', ['section' => $item['section']]),
                    'label' => $item['label'],
                    'icon' => \App\Support\SidebarIcon::forSection($item['section']),
                    'active' => $currentSection === $item['section'],
                ])
            @elseif (! empty($item['route']))
                @include('partials.navigation.sidebar-link', [
                    'href' => route($item['route'], $item['params'] ?? []),
                    'label' => $item['label'],
                    'icon' => \App\Support\SidebarIcon::forRoute($item['route']),
                ])
            @endif
        @endforeach
    </nav>
</aside>
