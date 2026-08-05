<aside class="tich-admin-sidebar">
    <p class="tich-admin-sidebar__title">{{ $department->dept_name }}</p>
    <p class="tich-caption">{{ $categoryLabel($department) }} · {{ $department->dept_code }}</p>

    <nav class="tich-admin-sidebar__nav">
        @foreach ($sidebarNavigation as $item)
            @if ($item['type'] === 'heading')
                <p class="tich-admin-sidebar__section">{{ $item['label'] }}</p>
            @else
                @php
                    $isActive = false;

                    if (($item['route'] ?? '') === 'departments.show') {
                        $currentSection = request()->query('section', 'overview');
                        $itemSection = $item['section'] ?? 'overview';
                        $targetId = $item['target_id'] ?? ($item['params']['department'] ?? null);
                        $isActive = request()->routeIs('departments.show')
                            && (int) request()->route('department')?->id === (int) $targetId
                            && $currentSection === $itemSection;
                    } elseif (($item['route'] ?? '') === 'dashboard') {
                        $isActive = request()->routeIs('dashboard');
                    } elseif (! empty($item['route'])) {
                        $routeBase = preg_replace('/\.(index|dashboard)$/', '', $item['route']) ?? $item['route'];
                        $isActive = request()->routeIs($item['route']) || request()->routeIs($routeBase.'.*');
                    }

                    $icon = $item['icon'] ?? \App\Support\SidebarIcon::forRoute($item['route'] ?? null);
                @endphp

                @if (! empty($item['coming_soon']))
                    <span class="tich-admin-sidebar__disabled">
                        <span class="tich-admin-sidebar__icon">
                            @include('partials.navigation.sidebar-icon', ['name' => $icon])
                        </span>
                        <span>{{ $item['label'] }} <small>(soon)</small></span>
                    </span>
                @else
                    @include('partials.navigation.sidebar-link', [
                        'href' => route($item['route'], $item['params'] ?? []),
                        'label' => $item['label'],
                        'icon' => $icon,
                        'active' => $isActive,
                        'badge' => $item['badge'] ?? null,
                    ])
                @endif
            @endif
        @endforeach
    </nav>
</aside>
